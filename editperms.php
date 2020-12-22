<?php
require('lib/common.php');

$permlist = null;

if (!has_perm('edit-permissions')) noticemsg("Error", "You have no permissions to do this!", true);

if (isset($_GET['gid'])) {
	$id = (int)$_GET['gid'];
	if ((is_root_gid($id) || (!can_edit_group_assets($id) && $id!=$loguser['group_id'])) && !has_perm('no-restrictions')) {
		noticemsg("Error", "You have no permissions to do this!", true);
	}
	if ($loguser['group_id'] == $id && !has_perm('edit-own-permissions')) {
		noticemsg("Error", "You have no permissions to do this!", true);
	}
	$permowner = $sql->fetch("SELECT id,title,inherit_group_id FROM groups WHERE id=?", [$id]);
	$type = 'group';
} else if (isset($_GET['uid'])) {
	$id = (int)$_GET['uid'];

	$tuser = $sql->result("SELECT group_id FROM users WHERE id = ?",[$id]);
	if ((is_root_gid($tuser) || (!can_edit_user_assets($tuser) && $id != $loguser['id'])) && !has_perm('no-restrictions')) {
		noticemsg("Error", "You have no permissions to do this!", true);
	}

	if ($id == $loguser['id'] && !has_perm('edit-own-permissions')) {
		noticemsg("Error", "You have no permissions to do this!", true);
	}
	$permowner = $sql->fetch("SELECT u.id,u.name title,u.group_id,g.title group_title FROM users u LEFT JOIN groups g ON g.id=u.group_id WHERE u.id=?", [$id]);
	$type = 'user';
} else if (isset($_GET['fid'])) {
	$id = (int)$_GET['fid'];
	$permowner = $sql->fetch("SELECT id,title FROM forums WHERE id=?", [$id]);
	$type = 'forum';
} else {
	$id = 0;
	$permowner = null;
	$type = '';
}

if (!$permowner) noticemsg("Error", "Invalid {$type} ID.", true);

$errmsg = '';

if (isset($_POST['addnew'])) {
	$revoke = (int)$_POST['revoke_new'];
	$permid = $_POST['permid_new'];
	$bindval = (int)$_POST['bindval_new'];

	if (has_perm('no-restrictions') || $permid != 'no-restrictions') {
		$sql->query("INSERT INTO `x_perm` (`x_id`,`x_type`,`perm_id`,`permbind_id`,`bindvalue`,`revoke`) VALUES (?,?,?,'',?,?)",
			[$id, $type, $permid, $bindval, $revoke]);
		$msg = "The %s permission has been successfully assigned!";
	} else {
		$msg = "You do not have the permissions to assign the %s permission!";
	}
} else if (isset($_POST['apply'])) {
	$keys = array_keys($_POST['apply']);
	$pid = $keys[0];

	$revoke = (int)$_POST['revoke'][$pid];
	$permid = $_POST['permid'][$pid];
	$bindval = (int)$_POST['bindval'][$pid];

	if (has_perm('no-restrictions') || $permid != 'no-restrictions') {
		$sql->query("UPDATE `x_perm` SET `perm_id`=?, `bindvalue`=?, `revoke`=? WHERE `id`=?",
			[$permid, $bindval, $revoke, $pid]);
		$msg = "The %s permission has been successfully edited!";
	} else {
		$msg = "You do not have the permissions to edit the %s permission!";
	}
} else if (isset($_POST['del'])) {
	$keys = array_keys($_POST['del']);
	$pid = $keys[0];
	$permid = $_POST['permid'][$pid];
	if (has_perm('no-restrictions') || $permid != 'no-restrictions') {
		$sql->query("DELETE FROM `x_perm`WHERE `id`=?", [$pid]); $msg="The %s permission has been successfully deleted!";
	} else {
		$msg = "You do not have the permissions to delete the %s permission!";
	}
}

pageheader('Edit permissions');

$pagebar = [
	'breadcrumb' => [['href'=>'./', 'title'=>'Main']],
	'title' => 'Edit permissions',
	'actions' => [],
	'message' => (isset($msg) ? sprintf($msg, title_for_perm($permid)) : '')
];

RenderPageBar($pagebar);

echo '<br><form action="" method="POST">';

$header = ['c0' => ['name' => '&nbsp;'], 'c1' => ['name' => '&nbsp;']];
$data = [];

$permset = PermSet($type, $id);
$row = []; $i = 0;
while ($perm = $permset->fetch()) {
	$pid = $perm['id'];

	$field = RevokeSelect("revoke[{$pid}]", $perm['revoke'])
			.PermSelect("permid[{$pid}]", $perm['perm_id'])
			.sprintf(
				 ' for ID <input type="text" name="bindval[%s]" value="%s" size="3" maxlength="8">'
				.' <input type="submit" name="apply[%s]" value="Apply">'
				.' <input type="submit" name="del[%s]" value="Remove">',
			$pid, $perm['bindvalue'], $pid, $pid);
	$row['c'.$i] = $field;

	$i++;
	if ($i == 2) {
		$data[] = $row;
		$row = [];
		$i = 0;
	}
}
if (($i % 2) != 0) {
	$row['c1'] = '&nbsp;';
	$data[] = $row;
}

RenderTable($data, $header);

$header = ['c0' => ['name' => 'Add permission']];

$field = RevokeSelect("revoke_new", 0)
		.PermSelect("permid_new", null)
		.'for ID <input type="text" name="bindval_new" value="" size=3 maxlength=8> <input type="submit" name="addnew" value="Add">';
$data = [['c0' => $field]];
RenderTable($data, $header);

echo "</form><br>";

$permset = PermSet($type, $id);
$permsassigned = [];

$permoverview = '<strong>'.ucfirst($type).' permissions:</strong><br>'.PermTable($permset);

if ($type == 'group' && $permowner['inherit_group_id'] > 0) {
	$permoverview .= '<br><hr><strong>Permissions inherited from parent groups:</strong><br>';
	$parentid = $permowner['inherit_group_id'];
} else if ($type == 'user') {
	$permoverview .= '<hr><strong>Permissions inherited from the group "'.esc($permowner['group_title']).'":</strong><br>';
	$parentid = $permowner['group_id'];
}

while (isset($parentid) && $parentid > 0) {
	$parent = $sql->fetch("SELECT title,inherit_group_id FROM groups WHERE id=?", [$parentid]);
	$permoverview .= '<br>'.esc($parent['title']).':<br>' . PermTable(PermSet('group', $parentid));
	$parentid = $parent['inherit_group_id'];
}

$header = ['cell' => ['name'=>"Permissions overview for {$type} '".esc($permowner['title'])."'"]];
$data = [['cell' => $permoverview]];
RenderTable($data, $header);

echo '<br>';
$pagebar['message'] = '';
RenderPageBar($pagebar);

pagefooter();

function PermSelect($name, $sel) {
	global $sql, $permlist;

	if (!$permlist) {
		$perms = $sql->query("SELECT p.id permid, p.title permtitle FROM perm p ORDER BY p.title ASC");

		$permlist = [];
		while ($perm = $perms->fetch()) $permlist[] = $perm;
	}

	$out = '<select name="'.$name.'">';
	$firstletter = '';
	foreach ($permlist as $perm) {
		if (substr($perm['permtitle'], 0, 1) !== $firstletter) {
			if (!empty($firstletter)) $out .= '</optgroup>';
			$firstletter = substr($perm['permtitle'], 0, 1);
			$out .= '<optgroup label="'.$firstletter.'">';
		}
		$chk = ($perm['permid'] == $sel) ? ' selected="selected"' : '';
		$out .= sprintf('<option value="%s"%s>%s</option>', esc($perm['permid']), $chk, esc($perm['permtitle']));
	}
	$out .= '</select>';

	return $out;
}

function RevokeSelect($name, $sel) {
	$out = sprintf('<select name="%s"><option value="0"%s>Grant</option><option value="1"%s>Revoke</option></select> ',
		$name, ($sel == 0 ? ' selected="selected"' : ''), ($sel == 1 ? ' selected="selected"' : ''));
	return $out;
}

function PermSet($type, $id) {
	global $sql;
	return $sql->query("SELECT x.*, p.title permtitle
		FROM x_perm x LEFT JOIN perm p ON p.id=x.perm_id
		WHERE x.x_type=? AND x.x_id=?", [$type,$id]);
}

function PermTable($permset) {
	global $permsassigned;
	$ret = '';

	$i = 0;
	while ($perm = $permset->fetch()) {
		$key = $perm['perm_id'];
		if ($perm['bindvalue']) $key .= '['.$perm['bindvalue'].']';

		$discarded = false;
		if (isset($permsassigned[$key])) $discarded = true;
		else $permsassigned[$key] = true;

		$permtitle = $perm['permtitle'];
		if (!$permtitle) $permtitle = $perm['perm_id'];

		$ret .= '<td style="width:25%">&bull; ';
		if ($discarded) $ret .= '<s>';
		if ($perm['revoke']) $ret .= '<span style="color:#f88;">Revoke</span>: ';
		else $ret .= '<span style="color:#8f8;">Grant</span>: ';
		$ret .= "'".esc($permtitle)."'";

		if ($perm['bindvalue']) {
			$bindtitle = strtolower($perm['permbind_id']);
			if (!$bindtitle) $bindtitle = $perm['permbind_id'];
			if (!$bindtitle) $bindtitle = 'ID';
			$ret .= ' for '.esc($bindtitle).' #'.$perm['bindvalue'];
		}

		if ($discarded) $ret .= '</s>';

		$ret .= '</td>';

		$i++;
		if (($i % 4) == 0) $ret .= '</tr><tr>';
	}

	if (($i % 4) != 0)
		$ret .= '<td colspan="'.(4-($i%4)).'">&nbsp;</td>';

	if (!$ret) $ret = '<td>&bull; None</td>';

	return '<table style="width:100%"><tr>'.$ret.'</tr></table>';
}