<?php
require('lib/common.php');

$time = (isset($_GET['time']) && is_numeric($_GET['time']) ? $_GET['time'] : 86400);

$users = $sql->query("SELECT ".userfields('u').",u.posts,u.joined,COUNT(*) num FROM principia.users u LEFT JOIN posts p ON p.user = u.id WHERE p.date > ? GROUP BY u.id ORDER BY num DESC",
	[(time() - $time)]);

$twig = _twigloader();
echo $twig->render('activeusers.twig', [
	'time' => $time,
	'users' => $users
]);