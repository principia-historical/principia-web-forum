<?php
require('lib/common.php');

$sort = $_GET['sort'] ?? 'posts';
$page = $_GET['page'] ?? '';
$orderby = $_GET['orderby'] ?? '';

$upp = 30;
if ($page < 1) $page = 1;

$sortby = ($orderby == 'a' ? " ASC" : " DESC");

$order = 'posts' . $sortby;
if ($sort == 'name') $order = 'name' . $sortby;
if ($sort == 'reg') $order = 'joined' . $sortby;

$users = query("SELECT * FROM users ORDER BY $order LIMIT ?,?", [($page - 1) * $upp, $upp]);
$num = result("SELECT COUNT(*) FROM users");

$pagelist = '';
if ($num > $upp) {
	$pagelist = 'Pages:';
	for ($p = 1; $p <= 1 + floor(($num - 1) / $upp); $p++)
		$pagelist .= ($p == $page ? " $p" : ' ' . mlink($p, $sort, $p, $orderby) . "</a>");
}

$twig = _twigloader();
echo $twig->render('memberlist.twig', [
	'sort' => $sort,
	'page' => $page,
	'orderby' => $orderby,
	'users' => $users,
	'pagelist' => $pagelist
]);