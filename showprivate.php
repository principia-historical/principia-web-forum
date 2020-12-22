<?php
require('lib/common.php');
needs_login();

if (!has_perm('view-own-pms')) noticemsg("Error", "You have no permissions to do this!", true);

$fieldlist = userfields('u', 'u').','.userfields_post();

$pid = (isset($_GET['id']) ? $_GET['id'] : null);

if (!$pid) noticemsg("Error", "Private message does not exist.", true);

$pmsgs = $sql->fetch("SELECT $fieldlist p.* FROM pmsgs p LEFT JOIN users u ON u.id = p.userfrom WHERE p.id = ?", [$pid]);
if ($pmsgs == null) noticemsg("Error", "Private message does not exist.", true);
$tologuser = ($pmsgs['userto'] == $loguser['id']);

if ((!$tologuser && $pmsgs['userfrom'] != $loguser['id']) && !has_perm('view-user-pms'))
	noticemsg("Error", "Private message does not exist.", true);
elseif ($tologuser && $pmsgs['unread'])
	$sql->query("UPDATE pmsgs SET unread = 0 WHERE id = ?", [$pid]);

pageheader($pmsgs['title']);

$pagebar = [
	'breadcrumb' => [
		['href' => './', 'title' => 'Main'],
		['href' => "private.php".(!$tologuser ? '?id='.$pmsgs['userto'] : ''), 'title' => 'Private messages']
	],
	'title' => esc($pmsgs['title']),
	'actions' => [['href' => "sendprivate.php?pid=$pid", 'title' => 'Reply']]
];

$pmsgs['id'] = $pmsgs['num'] = 0;

RenderPageBar($pagebar);
echo '<br>' . threadpost($pmsgs) . '<br>';
RenderPageBar($pagebar);

pagefooter();