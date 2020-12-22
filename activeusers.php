<?php
require('lib/common.php');
pageheader('Active users');

$time = (isset($_GET['time']) && is_numeric($_GET['time']) ? $_GET['time'] : 86400);

$users = $sql->query("SELECT ".userfields('u').",u.posts,u.regdate,COUNT(*) num FROM users u LEFT JOIN posts p ON p.user = u.id WHERE p.date > ? GROUP BY u.id ORDER BY num DESC",
	[(time() - $time)]);
?>
<table class="c1" style="width:auto">
	<tr class="h"><td class="b">Active users during the last <?=timeunits2($time) ?></td></tr>
	<tr class="n1"><td class="b n1 center"><?=timelink(3600,'activeusers').' | '.timelink(86400,'activeusers').' | '.timelink(604800,'activeusers').' | '.timelink(2592000,'activeusers') ?></td></tr>
</table><br>
<table class="c1">
	<tr class="h">
		<td class="b h" width="30">#</td>
		<td class="b h">Username</td>
		<td class="b h" width="200">Registered on</td>
		<td class="b h" width="50">Posts</td>
		<td class="b h" width="50">Total</td>
	</tr>
<?php
for ($i = 1; $user = $users->fetch(); $i++) {
	$tr = ($i % 2 ? 1 : 2);
	?><tr class="n<?=$tr ?> center">
		<td class="b"><?=$i ?>.</td>
		<td class="b left"><?=userlink($user) ?></td>
		<td class="b"><?=date($dateformat,$user['regdate']) ?></td>
		<td class="b"><b><?=$user['num'] ?></b></td>
		<td class="b"><b><?=$user['posts'] ?></b></td>
	</tr><?php
}
if_empty_query($i, "There are no active users in the given timespan.", 5);

echo '</table>';

pagefooter();
