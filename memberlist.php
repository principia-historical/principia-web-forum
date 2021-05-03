<?php
require('lib/common.php');
pageheader('Memberlist');

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'posts';
$page = isset($_GET['page']) ? $_GET['page'] : '';
$orderby = isset($_GET['orderby']) ? $_GET['orderby'] : '';

$ppp = 50;
if ($page < 1) $page = 1;

$sortby = ($orderby == 'a' ? " ASC" : " DESC");

$order = 'posts' . $sortby;
if ($sort == 'name') $order = 'name' . $sortby;
if ($sort == 'reg') $order = 'joined' . $sortby;

$users = $sql->query("SELECT * FROM principia.users ORDER BY $order LIMIT " . ($page - 1) * $ppp . ",$ppp");
$num = $sql->result("SELECT COUNT(*) FROM principia.users");

$pagelist = '';
if ($num >= $ppp) {
	$pagelist = 'Pages:';
	for ($p = 1; $p <= 1 + floor(($num - 1) / $ppp); $p++)
		$pagelist .= ($p == $page ? " $p" : ' ' . mlink($p, $sort, $p, $orderby) . "</a>");
}

?>
<table class="c1">
	<tr class="h"><td class="b h" colspan="2">Memberlist</td></tr>
	<tr>
		<td class="b n1" width="80">Sort by:</td>
		<td class="b n2 center">
			<?=mlink('Posts', '', $page, $orderby) ?> |
			<?=mlink('Username', 'name', $page, $orderby) ?> |
			<?=mlink('Registration date', 'reg', $page, $orderby) ?> |
			<?=mlink('[ &#x25BC; ]', $sort, $page, 'd') ?>
			<?=mlink('[ &#x25B2; ]', $sort, $page, 'a') ?>
		</td>
	</tr>
</table><br>
<table class="c1">
	<tr class="h">
		<td class="b h" width="32">#</td>
		<td class="b h" width="62">Picture</td>
		<td class="b h">Name</td>
		<td class="b h" width="130">Registered on</td>
		<td class="b h" width="50">Posts</td>
	</tr>
<?php

for ($i = 1; $user = $users->fetch(); $i++) {
	$tr = ($i % 2 ? 1 : 2);
	$picture = ($user['avatar'] ? '<img src="userpic/'.$user['id'].'" width="60" height="60">' : '');
	?><tr class="n<?=$tr ?>" style="height:69px">
		<td class="b center"><?=$user['id'] ?>.</td>
		<td class="b center"><?=$picture ?></td>
		<td class="b"><?=userlink($user) ?></td>
		<td class="b center"><?=date($dateformat,$user['joined']) ?></td>
		<td class="b center"><?=$user['posts'] ?></td>
	</tr><?php
}
if_empty_query($i, "No users found.", 5);
echo '</table>';

if ($pagelist)
	echo '<br>'.$pagelist.'<br>';
pagefooter();

function mlink($name, $sort, $page, $orderby) {
	return '<a href="memberlist.php?'.
		($sort ? "sort=$sort" : '').($page != 1 ? "&page=$page" : '').
		($orderby != '' ? "&orderby=$orderby" : '').'">'
		.$name.'</a>';
}