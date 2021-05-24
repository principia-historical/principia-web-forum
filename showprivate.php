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

$pmsgs['id'] = $pmsgs['num'] = 0;

ob_start();

RenderPageBar($pagebar);
echo '<br>' . threadpost($pmsgs) . '<br>';
RenderPageBar($pagebar);

$content = ob_get_contents();
ob_end_clean();

$twig = _twigloader();
echo $twig->render('_legacy.twig', [
	'page_title' => $pmsgs['title'],
	'content' => $content
]);
