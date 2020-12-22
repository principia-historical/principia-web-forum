<?php
require('lib/common.php');

if (!has_perm('ban-users')) noticemsg("Error", "You have no permissions to do this!", true);

$id = (int)$_GET['id'];

$tuser = $sql->result("SELECT group_id FROM users WHERE id = ?",[$id]);
if ((is_root_gid($tuser) || (!can_edit_user_assets($tuser) && $id != $loguser['id'])) && !has_perm('no-restrictions')) {
	noticemsg("Error", "You have no permissions to do this!", true);
}

if ($uid = $_GET['id']) {
	$numid = $sql->fetch("SELECT id FROM users WHERE id = ?",[$uid]);
	if (!$numid) noticemsg("Error", "Invalid user ID.", true);
}

$user = $sql->fetch("SELECT * FROM users WHERE id = ?",[$uid]);

if (isset($_POST['banuser']) && $_POST['banuser'] == "Ban User") {
	if ($_POST['tempbanned'] > 0) {
		$banreason = "Banned until ".date("m-d-y h:i A",time() + ($_POST['tempbanned']));
	} else {
		$banreason = "Banned permanently";
	}
	if ($_POST['title']) {
		$banreason .= ': '.esc($_POST['title']);
	}

	$sql->query("UPDATE users SET group_id = ?, title = ?, tempbanned = ? WHERE id = ?",
		[$bannedgroup, $banreason, ($_POST['tempbanned'] > 0 ? ($_POST['tempbanned'] + time()) : 0), $user['id']]);

	redirect("profile.php?id=$user[id]");
} elseif (isset($_POST['unbanuser']) && $_POST['unbanuser'] == "Unban User") {
	if ($user['group_id'] != $bannedgroup) noticemsg("Error", "This user is not a banned user.", true);

	$sql->query("UPDATE users SET group_id = ?, title = '', tempbanned = 0 WHERE id = ?", [$defaultgroup,$user['id']]);

	redirect("profile.php?id=$user[id]");
}

pageheader(isset($_GET['unban']) ? 'Unban User' : 'Ban User');

$pagebar = [
	'breadcrumb' => [['href' => './', 'title' => 'Main'], ['href' => "profile.php?id=$uid", 'title' => ($user['displayname'] ? $user['displayname'] : $user['name'])]]
];

$pagebar['title'] = (isset($_GET['unban']) ? 'Unban User' : 'Ban User');

RenderPageBar($pagebar);

if (isset($_GET['unban'])) {
	?><br><form action="banmanager.php?id=<?=$uid ?>" method="post" enctype="multipart/form-data"><table class="c1">
		<tr class="h"><td class="b">Unban User</td></tr>
		<tr class="n1"><td class="b n1 center"><input type="submit" name="unbanuser" value="Unban User"></td></tr>
	</table></form><br><?php
} else {
	?><br><form action="banmanager.php?id=<?=$uid ?>" method="post" enctype="multipart/form-data">
	<table class="c1">
		<?=catheader('Ban User') ?>
		<tr>
			<td class="b n1 center">Reason:</td>
			<td class="b n2"><input type="text" name="title"></td>
		</tr><tr>
			<td class="b n1 center">Expires?</td>
			<td class="b n2"><?=bantimeselect("tempbanned") ?></td>
		</tr><tr class="n1">
			<td class="b"></td>
			<td class="b"><input type="submit" name="banuser" value="Ban User"></td>
		</tr>
	</table></form><br><?php
}

RenderPageBar($pagebar);

pagefooter();