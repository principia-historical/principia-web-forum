<?php
require('lib/common.php');
needs_login();

$action = isset($_POST['action']) ? $_POST['action'] : '';
if ($action == "update") {
	if (!preg_match("/^([0-9|.|,|\*]*)$/", $_POST['ranges']))
		$err = 'Range string contains illegal characters.<br><a href="">Go back</a>.';

	if (isset($err)) {
		noticemsg("Error", $err, true);
	} else {
		$_COOKIE['pass'] = packlcookie(unpacklcookie($_COOKIE['pass']), $_POST['ranges']);
		setcookie('pass', $_COOKIE['pass'], 2147483647);
	}
}

pageheader('Advanced login cookie setup');

$dsegments = explode(",", decryptpwd($_COOKIE['pass']));
?>
<table class="c1" style="width:200px!important">
	<tr class="h">
		<td class="b h" colspan="2">Current data</td>
	</tr><tr class="h">
		<td class="b h">Field</td>
		<td class="b h">Value</td>
	</tr><tr>
		<td class="b n1 center">generating IP</td>
		<td class="b n2 center"><?=$dsegments[0] ?></td>
	</tr><?php for ($i = 2; $i < count($dsegments); $i++) { ?>
	<tr>
		<td class="b n1 center">allowed range</td>
		<td class="b n2 center"><?=$dsegments[$i] ?></td>
	</tr><?php } ?>
</table><br>
<form action="lcookie.php" method="post">
	<input type="hidden" name="action" value="update">
	<table class="c1">
		<tr class="h"><td class="b h">Modify allowed ranges</td></tr>
		<tr>
			<td class="b n2">
				<input type="text" name="ranges" value="<?=implode(",", array_slice($dsegments, 2)) ?>" style="width:80%">
				<input type="submit" value="Update">
				<br><span class="sfont">Data must be provided as comma-separated IPs without spaces,
				each potentially ending in a single * wildcard. (e.g. <span style="color:#C0C020">127.*,10.0.*,1.2.3.4</span>)
				</span>
			</td>
		</tr>
	</table>
</form>
<?php pagefooter();