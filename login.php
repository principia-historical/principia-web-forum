<?php
require('lib/common.php');

$act = (isset($_POST['action']) ? $_POST['action'] : 'needle');
if ($act == 'Login') {
	if ($userid = checkuser($_POST['name'], md5($pwdsalt2 . $_POST['pass'] . $pwdsalt))) {
		setcookie('user', $userid, 2147483647);
		setcookie('pass', packlcookie(md5($pwdsalt2 . $_POST['pass'] . $pwdsalt),
				implode(".", array_slice(explode(".", $_SERVER['REMOTE_ADDR']), 0, 2)) . ".*"), 2147483647);
		redirect('./');
	} else {
		$err = "Invalid username or password, cannot log in.";
	}
} elseif ($act == 'logout') {
	setcookie('user', 0);
	setcookie('pass', '');
	redirect('./');
}

pageheader('Login');
if (isset($err))
	noticemsg("Error", $err);
?>
<form action="login.php" method="post"><table class="c1">
	<tr class="h"><td class="b h" colspan="2">Login</td></tr>
	<tr>
		<td class="b n1 center" width="120">Username:</td>
		<td class="b n2"><input type="text" name="name" size="25" maxlength="25"></td>
	</tr><tr>
		<td class="b n1 center">Password:</td>
		<td class="b n2"><input type="password" name="pass" size="25" maxlength="32"></td>
	</tr><tr>
		<td class="b n1"></td>
		<td class="b n1"><input type="submit" name="action" value="Login"></td>
	</tr>
</table></form>
<?php pagefooter();