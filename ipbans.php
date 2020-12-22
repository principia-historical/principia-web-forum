<?php
require('lib/common.php');

if (!has_perm('edit-ip-bans')) noticemsg("Error", "You have no permissions to do this!", true);

$action = (isset($_GET['action']) ? $_GET['action'] : 'needle');
$what = (isset($_GET['what']) ? $_GET['what'] : 'needle');

function ipfmt($a) {
	$expl = explode(".",$a);
	for ($i = 0; $i < 4; $i++) {
		if (!isset($expl[$i])) {
			$expl[$i] = '*';
		}
	}
	return str_replace(" ","&nbsp;",sprintf("%3s%s%3s%s%3s%s%3s",$expl[0],'.',$expl[1],'.',$expl[2],'.',$expl[3]));
}

pageheader('IP bans');

if ($action == "del") {
	$data = explode(",",decryptpwd($what));
	$sql->query("DELETE FROM ipbans WHERE ipmask = ? AND expires = ?", [$data[0], $data[1]]);
} else if ($action == "add") {
	if ($_POST['ipmask']) {
		$hard = (isset($_POST['hard']) ? $_POST['hard'] : null);
		$expires = ($_POST['expires'] > 0 ? ($_POST['expires'] + time()) : 0);
		$sql->query("INSERT INTO ipbans (ipmask,hard,expires,banner,reason) VALUES (?,?,?,?,?)",
			[$_POST['ipmask'], $hard, $expires, addslashes($loguser['name']), $_POST['reason']]);
	} else {
		$err = "You must enter an IP mask";
	}
}
$ipbans = $sql->query("SELECT * FROM ipbans");
if (isset($err)) noticemsg("Error", $err);
?><form action="ipbans.php?action=add" method="post">
	<table class="c1">
		<tr class="h"><td class="b h" colspan="2">New IP ban</td></tr>
		<tr>
			<td class="b n1" width=150>IP mask</td>
			<td class="b n2"><input type="text" name="ipmask"></td>
		</tr><tr>
			<td class="b n1">Hard?</td>
			<td class="b n2"><input type="checkbox" name="hard" value="1"></td>
		</tr><tr>
			<td class="b n1">Expires?</td>
			<td class="b n2"><?=bantimeselect("expires") ?></td>
		</tr></tr>
			<td class="b n1">Comment</td>
			<td class="b n2"><input type="text" name="reason" size="64">
		</tr><tr>
			<td class="b n1"></td>
			<td class="b n1"><input type="submit" value="Add IP ban">
		</tr>
	</table>
</form><br>
<table class="c1">
	<tr class="h"><td class="b h" colspan="6">IP bans</td></tr>
	<tr class="c">
		<td class="b">IP mask</td>
		<td class="b">Hard</td>
		<td class="b">Expires</td>
		<td class="b">Banner</td>
		<td class="b" width="100%">Comment</td>
		<td class="b" width="20"></td>
	</tr>
<?php while ($i = $ipbans->fetch()) { ?>
	<tr>
		<td class="b n1"><span style="font-family:monospace"><?=ipfmt($i['ipmask']) ?></span></td>
		<td class="b n2 center"><?=($i['hard'] ? "Yes" : "No") ?></td>
		<td class="b n2 center">
			<?=($i['expires'] ? date($loguser['dateformat'],$i['expires'])."&nbsp;".date($loguser['timeformat'],$i['expires']) : "never") ?>
		</td>
		<td class="b n2 center"><?=$i['banner'] ?></td>
		<td class="b n2"><?=$i['reason'] ?></td>
		<td class="b n2 center">
			<a href="ipbans.php?action=del&what=<?=urlencode(encryptpwd($i['ipmask'].",".$i['expires'])) ?>"><img src="img/smilies/no.png" align=absmiddle></a>
		</td>
	</tr>
<?php } ?></table><?php
pagefooter();