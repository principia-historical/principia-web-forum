<?php
require('lib/common.php');
needs_login();

$fieldlist = userfields('u', 'u').','.userfields_post();

$pid = (isset($_GET['id']) ? $_GET['id'] : null);

if (!$pid) error("Error", "Private message does not exist.");

$pmsgs = $sql->fetch("SELECT $fieldlist p.* FROM pmsgs p LEFT JOIN principia.users u ON u.id = p.userfrom WHERE p.id = ?", [$pid]);
if ($pmsgs == null) error("Error", "Private message does not exist.");
$tologuser = ($pmsgs['userto'] == $userdata['id']);

if ((!$tologuser && $pmsgs['userfrom'] != $userdata['id']) && !has_perm('view-user-pms'))
	error("Error", "Private message does not exist.");
elseif ($tologuser && $pmsgs['unread'])
	$sql->query("UPDATE pmsgs SET unread = 0 WHERE id = ?", [$pid]);

$pagebar = [
	'breadcrumb' => [
		['href' => './', 'title' => 'Main'],
		['href' => "private.php".(!$tologuser ? '?id='.$pmsgs['userto'] : ''), 'title' => 'Private messages']
	],
	'title' => esc($pmsgs['title']),
	'actions' => [['href' => "sendprivate.php?pid=$pid", 'title' => 'Reply']]
];

$pmsgs['id'] = 0;

$twig = _twigloader();
echo $twig->render('showprivate.twig', [
	'pagebar' => $pagebar,
	'pmsgs' => $pmsgs
]);
