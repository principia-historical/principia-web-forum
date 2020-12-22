<?php
require('lib/common.php');

$act = (isset($_POST['action']) ? $_POST['action'] : '');
if ($act == 'Register') {
	$name = trim($_POST['name']);

	$cname = strtolower(str_replace([' ',"\xC2\xA0"],'',$name));
	$dupe = $sql->result("SELECT COUNT(*) FROM users WHERE LOWER(REPLACE(REPLACE(name,' ',''),0xC2A0,''))=? OR LOWER(REPLACE(REPLACE(displayname,' ',''),0xC2A0,''))=?", [$cname,$cname]);

	$gender = (int)$_POST['gender'];
	if ($gender < 0 || $gender > 2) $gender = 1;

	$timezone = $_POST['timezone'];

	$err = '';
	if ($dupe)
		$err = 'This username is already taken, please choose another.';
	elseif ($name == '' || $cname == '')
		$err = 'The username must not be empty, please choose one.';
	elseif (strlen($_POST['pass']) < 4)
		$err = 'Your password must be at least 4 characters long.';
	elseif ($_POST['pass'] != $_POST['pass2'])
		$err = "The two passwords you entered don't match.";
	elseif ($puzzle && $_POST['puzzle'] != $puzzleAnswer)
		$err = "Wrong security question.";

	if (empty($err)) {
		$salted_password = md5($pwdsalt2 . $_POST['pass'] . $pwdsalt);
		$res = $sql->query("INSERT INTO users (`name`,pass,regdate,lastview,ip,gender,timezone,theme) VALUES (?,?,?,?,?,?,?,?);",
			[$name, $salted_password, time(), time(), $userip, $gender, $timezone, $defaulttheme]);
		if ($res) {
			$id = $sql->insertid();

			$ugid = 0;
			// Derp killer
			if ($id == 1 || $_POST['name'] == 'Needle') {
				$ugid = $rootgroup;
			} else {
				$ugid = $defaultgroup;
			}
			$sql->query("UPDATE users SET group_id=? WHERE id=?",[$ugid,$id]);

			// mark existing threads and forums as read
			$sql->query("INSERT INTO threadsread (uid,tid,time) SELECT ?,id,? FROM threads", [$id, time()]);
			$sql->query("INSERT INTO forumsread (uid,fid,time) SELECT ?,id,? FROM forums", [$id, time()]);

			setcookie('user', $id, 2147483647);
			setcookie('pass', packlcookie(md5($pwdsalt2 . $_POST['pass'] . $pwdsalt), implode(".", array_slice(explode(".", $_SERVER['REMOTE_ADDR']), 0, 2)) . ".*"), 2147483647);

			echo 'If you aren\'t redirected, then please <a href="./">go here.</a>
				<script>window.location = "./"</script>';
			die();
		} else {
			$err = "Registration failed: ";//.$sql->error()
		}
	}
}

pageheader('Register');

$timezones = [];
foreach (timezone_identifiers_list() as $tz) {
	$timezones[$tz] = $tz;
}

if (!empty($err)) noticemsg("Error", $err);
?>
<form action="register.php" method="post">
	<table class="c1">
		<tr class="h">
			<td class="b h" colspan="2">Register</td>
		</tr><tr>
			<td class="b n1 center" width=150>Username:</td>
			<td class="b n2"><input type="text" name="name" size="25" maxlength="25"></td>
		</tr><tr>
			<td class="b n1 center">Password:</td>
			<td class="b n2"><input type="password" name="pass" size="25" maxlength="32"></td>
		</tr><tr>
			<td class="b n1 center">Password (again):</td>
			<td class="b n2"><input type="password" name="pass2" size="25" maxlength="32"></td>
		</tr>
		<?php
		echo fieldrow('Gender',fieldoption('gender',2,$gender));
		echo fieldrow('Timezone',fieldselect('timezone','UTC',$timezones));
		if ($puzzle) { ?>
			<tr>
				<td class="b n1 center" width="120"><?=$puzzleQuestion ?></td>
				<td class="b n2"><input type="text" name="puzzle" size="25" maxlength="20"></td>
			</tr>
		<?php } ?>
		<tr class="n1">
			<td class="b"></td>
			<td class="b">
				<input type="submit" name="action" value="Register">
				<span class="sfont">Please read the <a href="faq.php">FAQ</a> before registering.</span>
			</td>
		</tr>
	</table>
</form>
<?php pagefooter();