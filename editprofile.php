<?php
require("lib/common.php");

needs_login();

$targetuserid = $loguser['id'];
$act = isset($_POST['action']) ? $_POST['action'] : '';

if (isset($_GET['id'])) {
	$targetuserid = $_GET['id'];
}

if (!can_edit_user($targetuserid)) noticemsg("Error", "You have no permissions to do this!", true);

$blockroot = (!has_perm('no-restrictions') ? "AND id != $rootgroup" : '');

$allgroups = $sql->query("SELECT * FROM groups WHERE visible = '1' $blockroot ORDER BY sortorder ASC");
$listgroup = [];

while ($group = $allgroups->fetch()) {
	$listgroup[$group['id']] = $group['title'];
}

$user = $sql->fetch("SELECT * FROM principia.users WHERE id = ?", [$targetuserid]);

if (!$user) noticemsg("Error", "This user doesn't exist!", true);

if ($act == 'Edit profile') {
	$error = '';

	if (has_perm("edit-users")) {
		$targetgroup = $_POST['group_id'];

		if (!isset($listgroup[$targetgroup]))
			$targetgroup = 0;

		if (!can_edit_group_assets($targetgroup) && $targetgroup != $loguser['group_id']) {
			$error .= "- You do not have the permissions to assign this group.<br>";
		}
		$targetname = $_POST['name'];
	}

	if (!$error) {
		$sql->query("UPDATE principia.users SET signature_separator = ?, signature_header = ?, signature = ? WHERE id = ?",
			[$_POST['signature_separator'], $_POST['signature_header'], $_POST['signature'], $user['id']]
		);

		if (checkctitle($targetuserid))
			$sql->query("UPDATE principia.users SET title = ? WHERE id = ?", [$_POST['title'], $user['id']]);

		if (has_perm("edit-users") && $targetgroup != 0)
			$sql->query("UPDATE principia.users SET group_id = ? WHERE id = ?", [$targetgroup, $user['id']]);

		redirect("profile.php?id=$user[id]");
	} else {
		noticemsg("Error", "Couldn't save the profile changes. The following errors occured:<br><br>" . $error);

		$act = '';
		foreach ($_POST as $k => $v)
			$user[$k] = $v;
	}
}

pageheader('Edit profile');

echo '<form action="editprofile.php?id='.$targetuserid.'" method="post" enctype="multipart/form-data"><table class="c1">';

if (has_perm("edit-users"))
	echo
	catheader('Administrative bells and whistles')
.fieldrow('Group', fieldselect('group_id', $user['group_id'], $listgroup));

echo

catheader('Post layout')
.fieldrow('Header', fieldtext(5, 80, 'signature_header'))
.fieldrow('Signature', fieldtext(5, 80, 'signature'))
.fieldrow('Signature line', fieldoption('signature_separator', $user['signature_separator'], ['Display', 'Hide']))
.	catheader('&nbsp;'); ?>
<tr class="n1"><td class="b"></td><td class="b"><input type="submit" name="action" value="Edit profile"></td>
</table><input type="hidden" name="token" value="<?=$token?>"></form><?php

pagefooter();