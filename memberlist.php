<?php
require('lib/common.php');
pageheader('Memberlist');

$sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'posts';
$pow = isset($_REQUEST['pow']) ? $_REQUEST['pow'] : '';
$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
$orderby = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : '';

$ppp = 50;
if ($page < 1) $page = 1;

$sortby = ($orderby == 'a' ? " ASC" : " DESC");

$order = 'posts' . $sortby;
if ($sort == 'name') $order = 'name' . $sortby;
if ($sort == 'reg') $order = 'regdate' . $sortby;

$where = (is_numeric($pow) ? "WHERE group_id = $pow" : '');

$users = $sql->query("SELECT * FROM users $where ORDER BY $order LIMIT " . ($page - 1) * $ppp . ",$ppp");
$num = $sql->result("SELECT COUNT(*) FROM users $where");

$pagelist = '';
if ($num >= $ppp) {
	$pagelist = 'Pages:';
	for ($p = 1; $p <= 1 + floor(($num - 1) / $ppp); $p++)
		$pagelist .= ($p == $page ? " $p" : ' ' . mlink($p, $sort, $pow, $p, $orderby) . "</a>");
}

$activegroups = $sql->query("SELECT * FROM groups WHERE id IN (SELECT `group_id` FROM users GROUP BY `group_id`) ORDER BY `sortorder` ASC ");

$groups = [];
$gc = 0;
while ($group = $activegroups->fetch()) {
	$grouptitle = '<span style="color:#' . $group['nc'] . '">' . $group['title'] . '</span>';
	$groups[$gc++] = mlink($grouptitle, $sort, $group['id'], $page, $orderby);
}

?>
<table class="c1">
	<tr class="h"><td class="b h" colspan="2">Memberlist</td></tr>
	<tr>
		<td class="b n1" width="80">Sort by:</td>
		<td class="b n2 center">
			<?=mlink('Posts', '', $pow, $page, $orderby) ?> |
			<?=mlink('Username', 'name', $pow, $page, $orderby) ?> |
			<?=mlink('Registration date', 'reg', $pow, $page, $orderby) ?> |
			<?=mlink('[ &#x25BC; ]', $sort, $pow, $page, 'd') ?>
			<?=mlink('[ &#x25B2; ]', $sort, $pow, $page, 'a') ?>
		</td>
	</tr><tr>
		<td class="b n1">Group:</td>
		<td class="b n2 center">
			<?php foreach ($groups as $group) echo $group.' | ' ?>
			<?=mlink('All', $sort, '', $page, $orderby) ?>
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
	$picture = ($user['usepic'] ? '<img src="userpic/'.$user['id'].'" width="60" height="60">' : '');
	?><tr class="n<?=$tr ?>" style="height:69px">
		<td class="b center"><?=$user['id'] ?>.</td>
		<td class="b center"><?=$picture ?></td>
		<td class="b"><?=userlink($user) ?></td>
		<td class="b center"><?=date($dateformat,$user['regdate']) ?></td>
		<td class="b center"><?=$user['posts'] ?></td>
	</tr><?php
}
if_empty_query($i, "No users found.", 5);
echo '</table>';

if ($pagelist)
	echo '<br>'.$pagelist.'<br>';
pagefooter();

function mlink($name, $sort, $pow, $page, $orderby) {
	return '<a href="memberlist.php?'.
		($sort ? "sort=$sort" : '').($pow != '' ? "&pow=$pow" : '').($page != 1 ? "&page=$page" : '').
		($orderby != '' ? "&orderby=$orderby" : '').'">'
		.$name.'</a>';
}