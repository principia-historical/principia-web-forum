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

$token = md5($pwdsalt2 . $loguser['pass'] . $pwdsalt);
if ($act == 'Edit profile') {
	if ($_POST['token'] !== $token) die('No.');

	if ($_POST['pass'] != '' && $_POST['pass'] == $_POST['pass2'] && $targetuserid == $loguser['id'])
		setcookie('pass', packlcookie(md5($pwdsalt2 . $_POST['pass'] . $pwdsalt)), 2147483647);
}

$user = $sql->fetch("SELECT * FROM users WHERE id = ?", [$targetuserid]);

if (!$user) noticemsg("Error", "This user doesn't exist!", true);

if ($act == 'Edit profile') {
	$error = '';

	if ($_POST['pass'] && $_POST['pass2'] && $_POST['pass'] != $_POST['pass2'])
		$error = "- The passwords you entered don't match.<br>";

	$usepic = $user['usepic'];
	$fname = $_FILES['picture'];
	if ($fname['size'] > 0) {
		$ftypes = ['png','jpeg','jpg','gif'];
		$img_data = getimagesize($fname['tmp_name']);
		$err = '';
		if ($img_data[0] > 180 || $img_data[1] > 180)
			$err .= "<br>The image is too big.";
		if ($fname['size'] > 81920)
			$err .= "<br>The image filesize too big.";
		if (!in_array(str_replace('image/','',$img_data['mime']),$ftypes))
			$err = "Invalid file type.";

		if ($err != '')
			$ava_out = $err;
		else {
			if (move_uploaded_file($fname['tmp_name'], "userpic/$user[id]")) {
				$ava_out = "OK!";
			} else {
				$ava_out = "<br>Error creating file.";
			}
		}

		if ($ava_out != "OK!") {
			$error .= $ava_out;
		} else
			$usepic = 1;
	}
	if (isset($_POST['picturedel']))
		$usepic = 0;

	if ($_POST['gender'] < 0 || $_POST['gender'] > 2) $_POST['gender'] = 2;

	$pass = (strlen($_POST['pass2']) ? $_POST['pass'] : '');

	//Validate birthday values.
	if (!$_POST['birthM'] || !$_POST['birthD']) //Reject if any are missing.
		$birthday = -1;
	else {
		if (!is_numeric($_POST['birthM']) || !is_numeric($_POST['birthD'])) //Reject if not numeric.
			$birthday = -1;
	}
	if ($_POST['birthM'] > 12 || $_POST['birthD'] > 31) // fixes a small bug where if the fields are above a certain value, the profile fails to load
		$birthday = -1;
	$year = $_POST['birthY'];
	if (!$_POST['birthY'] || !is_numeric($_POST['birthY']))
		$year = -1;
	if ($birthday != -1 && $_POST['birthM'] != '' && $_POST['birthD'] != '')
		$birthday = str_pad($_POST['birthM'], 2, "0", STR_PAD_LEFT) . '-' . str_pad($_POST['birthD'], 2, "0", STR_PAD_LEFT) . '-' . $year;
	else
		$birthday = -1;

	$dateformat = $_POST['dateformat'];
	$timeformat = $_POST['timeformat'];

	if (has_perm("edit-users")) {
		$targetgroup = $_POST['group_id'];

		if (!isset($listgroup[$targetgroup]))
			$targetgroup = 0;

		if (!can_edit_group_assets($targetgroup) && $targetgroup != $loguser['group_id']) {
			$error .= "- You do not have the permissions to assign this group.<br>";
		}
		$targetname = $_POST['name'];

		if ($sql->result("SELECT COUNT(name) FROM users WHERE (name = ? OR displayname = ?) AND id != ?", [$targetname, $targetname, $user['id']])) {
			$error .= "- Name already in use.<br>";
		}
	}
	if (checkcdisplayname($targetuserid)) {
		//Checks Displayname to name and other displaynames
		$targetdname = $_POST['displayname'];

		if (checkcdisplayname($targetuserid) && $targetdname != '') {
			if ($sql->result("SELECT COUNT(name) FROM users WHERE (name = ? OR displayname = ?) AND id != ?", [$targetdname, $targetdname, $user['id']])) {
				$error .= "- Displayname already in use.<br>";
			}
		}
	}

	if (checkcusercolor($targetuserid)) {
		//Validate Custom username color is a 6 digit hex RGB color
		$_POST['nick_color'] = ltrim($_POST['nick_color'], '#');

		if ($_POST['nick_color'] != '') {
			if (!preg_match('/^([A-Fa-f0-9]{6})$/', $_POST['nick_color'])) {
				$error .= "- Custom usercolor is not a valid RGB hex color.<br>";
			}
		}
	}

	if (!$error) {
		$showemail = ($_POST['showemail'] ? 1 : 0);
		$enablecolor = ($_POST['enablecolor'] ? 1 : 0);

		$sql->query("UPDATE users SET gender = ?, ppp = ?, tpp = ?, signsep = ?, rankset = ?, location = ?, email = ?, head = ?, sign = ?, bio = ?,
			theme = ?, blocklayouts = ?, showemail = ?, timezone = ?, birth = ?, usepic = ?, dateformat = ?, timeformat = ? WHERE id = ?",
			[$_POST['gender'], $_POST['ppp'], $_POST['tpp'], $_POST['signsep'], $_POST['rankset'], $_POST['location'], $_POST['email'], $_POST['head'], $_POST['sign'],
			$_POST['bio'], $_POST['theme'], $_POST['blocklayouts'], $showemail, $_POST['timezone'], $birthday, $usepic, $dateformat, $timeformat, $user['id']]
		);

		if ($pass)
			$sql->query("UPDATE users SET pass = ? WHERE id = ?", [md5($pwdsalt2 . $pass . $pwdsalt), $user['id']]);
		if (checkcdisplayname($targetuserid))
			$sql->query("UPDATE users SET displayname = ? WHERE id = ?", [$_POST['displayname'], $user['id']]);
		if (checkcusercolor($targetuserid))
			$sql->query("UPDATE users SET nick_color = ?, enablecolor = ? WHERE id = ?", [$_POST['nick_color'], $enablecolor, $user['id']]);
		if (checkctitle($targetuserid))
			$sql->query("UPDATE users SET title = ? WHERE id = ?", [$_POST['title'], $user['id']]);

		if (has_perm("edit-users") && $targetgroup != 0)
			$sql->query("UPDATE users SET group_id = ? WHERE id = ?", [$targetgroup, $user['id']]);

		redirect("profile.php?id=$user[id]");
	} else {
		noticemsg("Error", "Couldn't save the profile changes. The following errors occured:<br><br>" . $error);

		$act = '';
		foreach ($_POST as $k => $v)
			$user[$k] = $v;
		$user['birth'] = $birthday;
	}
}

pageheader('Edit profile');

$listtimezones = [];
foreach (timezone_identifiers_list() as $tz) {
	$listtimezones[$tz] = $tz;
}

$birthM = $birthD = $birthY = '';
if ($user['birth'] != -1) {
	$birthday = explode('-', $user['birth']);
	$birthM = $birthday[0]; $birthD = $birthday[1]; $birthY = $birthday[2];
}

$passinput = '<input type="password" name="pass" size="13" maxlength="32"> Retype: <input type="password" name="pass2" size="13" maxlength="32">';
$birthinput = sprintf(
	'Day: <input type="text" name="birthD" size="2" maxlength="2" value="%s">
	Month: <input type="text" name="birthM" size="2" maxlength="2" value="%s">
	Year: <input type="text" name="birthY" size="4" maxlength="4" value="%s">',
$birthD, $birthM, $birthY);

$colorinput = sprintf(
	'<input type="color" name="nick_color" value="#%s">
	<input type="checkbox" name="enablecolor" value="1" id="enablecolor" %s><label for="enablecolor">Enable Color</label>',
$user['nick_color'], ($user['enablecolor'] ? 'checked' : ''));

echo '<form action="editprofile.php?id='.$targetuserid.'" method="post" enctype="multipart/form-data"><table class="c1">' .
	catheader('Login information')
.(has_perm("edit-users") ? fieldrow('Username', fieldinput(40, 255, 'name')) : fieldrow('Username', $user['name']))
.(checkcdisplayname($targetuserid) ? fieldrow('Display name', fieldinput(40, 255, 'displayname')) : '')
.fieldrow('Password', $passinput);

if (has_perm("edit-users"))
	echo
	catheader('Administrative bells and whistles')
.fieldrow('Group', fieldselect('group_id', $user['group_id'], $listgroup))
.(($user['tempbanned'] > 0) ? fieldrow('Ban Information', '<input type=checkbox name=permaban value=1 id=permaban><label for=permaban>Make ban permanent</label>') : '');

echo
	catheader('Appearance')
.fieldrow('Rankset', fieldselect('rankset', $user['rankset'], ranklist()))
.((checkctitle($targetuserid)) ? fieldrow('Title', fieldinput(40, 255, 'title')) : '')
.fieldrow('Picture', '<input type=file name=picture size=40> <input type=checkbox name=picturedel value=1 id=picturedel><label for=picturedel>Erase</label>
	<br><span class=sfont>Must be PNG, JPG or GIF, within 80KB, within 180x180.</span>')
.(checkcusercolor($targetuserid) ? fieldrow('Custom username color', $colorinput) : '')
.	catheader('User information')
.fieldrow('Gender', fieldoption('gender', $user['gender'], ['Male', 'Female', 'N/A']))
.fieldrow('Location', fieldinput(40, 60, 'location'))
.fieldrow('Birthday', $birthinput)
.fieldrow('Bio', fieldtext(5, 80, 'bio'))
.fieldrow('Email address', fieldinput(40, 60, 'email')
				.'<br>'.fieldcheckbox('showemail', $user['showemail'], 'Show email on profile page'))
.	catheader('Post layout')
.fieldrow('Header', fieldtext(5, 80, 'head'))
.fieldrow('Signature', fieldtext(5, 80, 'sign'))
.fieldrow('Signature line', fieldoption('signsep', $user['signsep'], ['Display', 'Hide']))
.	catheader('Options')
.fieldrow('Theme', fieldselect('theme', $user['theme'], themelist(), "themePreview(this.value)"))
.fieldrow('Timezone', fieldselect('timezone', $user['timezone'], $listtimezones))
.fieldrow('Posts per page', fieldinput(3, 3, 'ppp'))
.fieldrow('Threads per page', fieldinput(3, 3, 'tpp'))
.fieldrow('Date format', fieldinput(15, 15, 'dateformat'))
.fieldrow('Time format', fieldinput(15, 15, 'timeformat'))
.fieldrow('Post layouts', fieldoption('blocklayouts', $user['blocklayouts'], ['Show everything in general', 'Block everything']))
.	catheader('&nbsp;'); ?>
<tr class="n1"><td class="b"></td><td class="b"><input type="submit" name="action" value="Edit profile"></td>
</table><input type="hidden" name="token" value="<?=$token?>"></form>
<script>
function themePreview(id) {
	document.head.getElementsByTagName('link')[2].href = 'theme/'+id+'/'+id+'.css';
}
</script><?php

pagefooter();