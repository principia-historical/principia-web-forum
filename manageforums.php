<?php
require('lib/common.php');

if (!has_perm('edit-forums')) noticemsg("Error", "You have no permissions to do this!", true);

$error = '';

if (isset($_POST['savecat'])) {
	// save new/existing category
	$cid = $_GET['cid'];
	$title = $_POST['title'];
	$ord = (int)$_POST['ord'];
	if (!trim($title))
		$error = 'Please enter a title for the category.';
	else {
		if ($cid == 'new') {
			$cid = $sql->result("SELECT MAX(id) FROM categories");
			if (!$cid) $cid = 0;
			$cid++;
			$sql->query("INSERT INTO categories (id,title,ord) VALUES (?,?,?)", [$cid, $title, $ord]);
		} else {
			$cid = (int)$cid;
			if (!$sql->result("SELECT COUNT(*) FROM categories WHERE id=?",[$cid])) redirect('manageforums.php');
			$sql->query("UPDATE categories SET title = ?, ord = ? WHERE id = ?", [$title, $ord, $cid]);
		}
		redirect('manageforums.php?cid='.$cid);
	}
} else if (isset($_POST['delcat'])) {
	// delete category
	$cid = (int)$_GET['cid'];
	$sql->query("DELETE FROM categories WHERE id = ?",[$cid]);

	redirect('manageforums.php');
} else if (isset($_POST['saveforum'])) {
	// save new/existing forum
	$fid = $_GET['fid'];
	$cat = (int)$_POST['cat'];
	$title = $_POST['title'];
	$descr = $_POST['descr'];
	$ord = (int)$_POST['ord'];
	$private = isset($_POST['private']) ? 1 : 0;
	$readonly = isset($_POST['readonly']) ? 1 : 0;

	if (!trim($title))
		$error = 'Please enter a title for the forum.';
	else {
		if ($fid == 'new') {
			$fid = $sql->result("SELECT MAX(id) FROM forums");
			if (!$fid) $fid = 0;
			$fid++;
			$sql->query("INSERT INTO forums (id,cat,title,descr,ord,private,readonly) VALUES (?,?,?,?,?,?,?)",
				[$fid, $cat, $title, $descr, $ord, $private, $readonly]);
		} else {
			$fid = (int)$fid;
			if (!$sql->result("SELECT COUNT(*) FROM forums WHERE id=?",[$fid]))
				redirect('manageforums.php');
			$sql->query("UPDATE forums SET cat=?, title=?, descr=?, ord=?, private=?, readonly=? WHERE id=?",
				[$cat, $title, $descr, $ord, $private, $readonly, $fid]);
		}
		saveperms('forums', $fid);
		redirect('manageforums.php?fid='.$fid);
	}
} else if (isset($_POST['delforum'])) {
	// delete forum
	$fid = (int)$_GET['fid'];
	$sql->query("DELETE FROM forums WHERE id=?",[$fid]);
	deleteperms('forums', $fid);
	redirect('manageforums.php');
}

pageheader('Forum management');

?>
<script>function toggleAll(cls, enable) {
	var elems = document.getElementsByClassName(cls);
	for (var i = 0; i < elems.length; i++) elems[i].disabled = !enable;
}</script>
<style type="text/css">label { white-space: nowrap; } input:disabled { opacity: 0.5; }</style>
<?php

if ($error) noticemsg("Error", $error);

if (isset($_GET['cid']) && $cid = $_GET['cid']) {
	// category editor
	if ($cid == 'new') {
		$cat = ['id' => 0, 'title' => '', 'ord' => 0];
	} else {
		$cid = (int)$cid;
		$cat = $sql->fetch("SELECT * FROM categories WHERE id=?",[$cid]);
	}
	?><form action="" method="POST">
		<table class="c1">
			<tr class="h"><td class="b h" colspan="2"><?=($cid == 'new' ? 'Create' : 'Edit') ?> category</td></tr>
			<tr>
				<td class="b n1 center">Title:</td>
				<td class="b n2"><input type="text" name="title" value="<?=esc($cat['title']) ?>" size="50" maxlength="500"></td>
			</tr><tr>
				<td class="b n1 center">Display order:</td>
				<td class="b n2"><input type="text" name="ord" value="<?=$cat['ord'] ?>" size="4" maxlength="10"></td>
			</tr>
			<tr class="h"><td class="b h" colspan="2">&nbsp;</td></tr>
			<tr>
				<td class="b n1 center"></td>
				<td class="b n2">
					<input type="submit" name="savecat" value="Save category">
						<?=($cid == 'new' ? '' : '<input type="submit" name="delcat" value="Delete category" onclick="if (!confirm("Really delete this category?")) return false;"> ') ?>
					<button type="button" id="back" onclick="window.location='manageforums.php';">Back</button>
				</td>
			</tr>
		</table>
	</form><?php
} else if (isset($_GET['fid']) && $fid = $_GET['fid']) {
	// forum editor
	if ($fid == 'new') {
		$forum = ['id' => 0, 'cat' => 1, 'title' => '', 'descr' => '', 'ord' => 0, 'private' => 0, 'readonly' => 0];
	} else {
		$fid = (int)$fid;
		$forum = $sql->fetch("SELECT * FROM forums WHERE id=?",[$fid]);
	}
	$qcats = $sql->query("SELECT id,title FROM categories ORDER BY ord, id");
	$cats = [];
	while ($cat = $qcats->fetch())
		$cats[$cat['id']] = $cat['title'];
	$catlist = fieldselect('cat', $forum['cat'], $cats);

	?><form action="" method="POST">
		<table class="c1">
			<tr class="h"><td class="b h" colspan="2"><?=($fid == 'new' ? 'Create' : 'Edit') ?> forum</td></tr>
			<tr>
				<td class="b n1 center">Title:</td>
				<td class="b n2"><input type="text" name="title" value="<?=esc($forum['title']) ?>" size="50" maxlength="500"></td>
			</tr><tr>
				<td class="b n1 center">Description:<br><small>HTML allowed.</small></td>
				<td class="b n2"><textarea wrap="virtual" name="descr" rows="3" cols="50"><?=esc($forum['descr']) ?></textarea></td>
			</tr><tr>
				<td class="b n1 center">Category:</td>
				<td class="b n2"><?=$catlist ?></td>
			</tr><tr>
				<td class="b n1 center">Display order:</td>
				<td class="b n2"><input type="text" name="ord" value="<?=$forum['ord'] ?>" size="4" maxlength="10"></td>
			</tr><tr>
				<td class="b n1 center"></td>
				<td class="b n2">
					<label><input type="checkbox" name="private" value="1" <?=($forum['private'] ? ' checked':'') ?>> Private forum</label>
					<label><input type="checkbox" name="readonly" value="1" <?=($forum['readonly'] ? ' checked' : '')?>> Read-only</label>
				</td>
			</tr>
			<tr class="h"><td class="b h" colspan="2">&nbsp;</td></tr>
			<tr>
				<td class="b n1 center"></td>
				<td class="b n2">
					<input type="submit" name="saveforum" value="Save forum">
					<?php ($fid == 'new' ? '' : '<input type="submit" name="delforum" value="Delete forum" onclick="if (!confirm("Really delete this forum?")) return false;">') ?>
					<button type="button" id="back" onclick="window.location='manageforums.php'">Back</button>
				</td>
			</tr>
		</table><br>
		<?php permtable('forums', $fid) ?>
	</form><?php
} else {
	// main page -- category/forum listing

	$qcats = $sql->query("SELECT id,title FROM categories ORDER BY ord, id");
	$cats = [];
	while ($cat = $qcats->fetch())
		$cats[$cat['id']] = $cat;

	$qforums = $sql->query("SELECT f.id,f.title,f.cat FROM forums f LEFT JOIN categories c ON c.id=f.cat ORDER BY c.ord, c.id, f.ord, f.id");
	$forums = [];
	while ($forum = $qforums->fetch())
		$forums[$forum['id']] = $forum;

	$catlist = ''; $c = 1;
	foreach ($cats as $cat) {
		$catlist .= sprintf('<tr><td class="b n%s"><a href="?cid=%s">%s</a></td></tr>', $c, $cat['id'], $cat['title']);
		$c = ($c == 1) ? 2 : 1;
	}

	$forumlist = ''; $c = 1; $lc = -1;
	foreach ($forums as $forum) {
		if ($forum['cat'] != $lc) {
			$lc = $forum['cat'];
			$forumlist .= sprintf('<tr class="c"><td class="b c">%s</td></tr>', $cats[$forum['cat']]['title']);
		}
		$forumlist .= sprintf('<tr><td class="b n%s"><a href="?fid=%s">%s</a></td></tr>', $c, $forum['id'], $forum['title']);
		$c = ($c == 1) ? 2 : 1;
	}

	?><table style="width:100%;">
		<tr>
			<td class="nb" style="width:50%; vertical-align:top;">
				<table class="c1">
					<tr class="h"><td class="b">Categories</td></tr>
					<?=$catlist ?>
					<tr class="h"><td class="b">&nbsp;</td></tr>
					<tr><td class="b n1"><a href="?cid=new">New category</a></td></tr>
				</table>
			</td>
			<td class="nb" style="width:50%; vertical-align:top;">
				<table class="c1">
					<tr class="h"><td class="b">Forums</td></tr>
					<?=$forumlist ?>
					<tr class="h"><td class="b">&nbsp;</td></tr>
					<tr><td class="b n1"><a href="?fid=new">New forum</a></td></tr>
				</table>
			</td>
		</tr>
	</table><?php
}

pagefooter();

function rec_grouplist($parent, $level, $tgroups, $groups) {
	foreach ($tgroups as $g) {
		if ($g['inherit_group_id'] != $parent)
			continue;

		$g['indent'] = $level;
		$groups[] = $g;

		$groups = rec_grouplist($g['id'], $level+1, $tgroups, $groups);
	}
	return $groups;
}
function grouplist() {
	global $usergroups;

	$groups = [];
	$groups = rec_grouplist(0, 0, $usergroups, $groups);

	return $groups;
}
function permtable($bind, $id) {
	global $sql, $rootgroup;

	$qperms = $sql->query("SELECT id,title FROM perm WHERE permbind_id=?",[$bind]);
	$perms = [];
	while ($perm = $qperms->fetch())
		$perms[$perm['id']] = $perm['title'];

	$groups = grouplist();

	$qpermdata = $sql->query("SELECT x.x_id,x.perm_id,x.revoke FROM x_perm x LEFT JOIN perm p ON p.id=x.perm_id WHERE x.x_type=? AND p.permbind_id=? AND x.bindvalue=?",
		['group',$bind,$id]);
	$permdata = [];
	while ($perm = $qpermdata->fetch())
		$permdata[$perm['x_id']][$perm['perm_id']] = !$perm['revoke'];

	echo '<table class="c1"><tr class="h"><td class="b">Group</td><td class="b" colspan="2">Permissions</td></tr>';

	$c = 1;
	foreach ($groups as $group) {
		if ($group['id'] == $rootgroup) break;

		$gid = $group['id'];
		$gtitle = esc($group['title']);

		$pf = $group['visible'] ? '<strong' : '<span';
		if ($group['nc']) $pf .= ' style="color:#'.esc($group['nc']).'"';
		$pf .= '>';
		$sf = $group['visible'] ? '</strong>' : '</span>';
		$gtitle = "{$pf}{$gtitle}{$sf}";

		$doinherit = false;
		$inherit = '';
		if ($group['inherit_group_id']) {
			$doinherit = !isset($permdata[$gid]) || empty($permdata[$gid]);

			$check = $doinherit ? ' checked="checked"' : '';
			$inherit = sprintf(
				'<label><input type="checkbox" name="inherit[%s]" value="1" onclick="toggleAll(\'perm_%s\',!this.checked);"%s> Inherit from parent</label>&nbsp;',
			$gid, $gid, $check);
		}

		$permlist = '';
		foreach ($perms as $pid => $ptitle) {
			$check = ($doinherit ? ' disabled="disabled"' : ($permdata[$gid][$pid] ? ' checked="checked"' : ''));

			$permlist .= sprintf(
				'<label><input type="checkbox" name="perm[%s][%s]" value="1" class="perm_%s"%s> %s</label> ',
			$gid, $pid, $gid, $check, $ptitle);
		}

		?><tr class="n<?=$c ?>">
			<td class="b" style="width:200px;"><span style="white-space:nowrap;"><?=str_repeat('&nbsp; &nbsp; ', $group['indent']) . $gtitle ?></span></td>
			<td class="b" style="width:100px;"><?=$inherit ?></td>
			<td class="b"><?=$permlist ?></td>
		</tr><?php

		$c = ($c == 1) ? 2 : 1;
	}

	?><tr class="n<?=$c ?>">
		<td class="b"></td>
		<td class="b" colspan="2">
			<input type="submit" name="saveforum" value="Save forum">
		</td>
	</tr></table><?php
}

function deleteperms($bind, $id) {
	global $sql;

	$sql->query("DELETE x FROM x_perm x LEFT JOIN perm p ON p.id=x.perm_id WHERE x.x_type=? AND p.permbind_id=? AND x.bindvalue=?",
		['group', $bind, $id]);
}

function saveperms($bind, $id) {
	global $sql, $usergroups;

	$qperms = $sql->query("SELECT id FROM perm WHERE permbind_id=?",[$bind]);
	$perms = [];
	while ($perm = $qperms->fetch())
		$perms[] = $perm['id'];

	// delete the old perms
	deleteperms($bind, $id);

	// apply the new perms
	foreach ($usergroups as $gid => $group) {
		if (is_root_gid($gid)) continue;

		if ($_POST['inherit'][$gid])
			continue;

		$myperms = $_POST['perm'][$gid];
		foreach ($perms as $perm)
			$sql->query("INSERT INTO `x_perm` (`x_id`,`x_type`,`perm_id`,`permbind_id`,`bindvalue`,`revoke`)
				VALUES (?,?,?,?,?,?)", [$gid, 'group', $perm, $bind, $id, $myperms[$perm]?0:1]);
	}
}