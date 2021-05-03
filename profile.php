<?php
require("lib/common.php");

$uid = isset($_GET['id']) ? (int)$_GET['id'] : -1;
if ($uid < 0) noticemsg("Error", "You must specify a user ID!", true);

$user = $sql->fetch("SELECT * FROM principia.users WHERE id = ?", [$uid]);
if (!$user) noticemsg("Error", "This user does not exist!", true);

$group = $sql->fetch("SELECT * FROM groups WHERE id = ?", [$user['group_id']]);

pageheader("Profile for ".$user['name']);

$days = (time() - $user['joined']) / 86400;

$thread = $sql->fetch("SELECT p.id, t.title ttitle, f.title ftitle, t.forum, f.private FROM forums f
	LEFT JOIN threads t ON t.forum = f.id LEFT JOIN posts p ON p.thread = t.id
	WHERE p.date = ? AND p.user = ? AND f.id IN " . forums_with_view_perm(), [$user['lastpost'], $uid]);

if ($thread) {
	$lastpostlink = sprintf(
		'<br>in <a href="thread.php?pid=%s#%s">%s</a> (<a href="forum.php?id=%s">%s</a>)',
	$thread['id'], $thread['id'], esc($thread['ttitle']), $thread['forum'], esc($thread['ftitle']));
} else if ($user['posts'] == 0) {
	$lastpostlink = '';
} else {
	$lastpostlink = "<br>in <i>a private forum</i>";
}

$links = [];
$links[] = ['url' => "forum.php?user=$uid", 'title' => 'View threads'];
$links[] = ['url' => "thread.php?user=$uid", 'title' => 'Show posts'];

if ($log) {
	if (has_perm('create-pms'))
		$links[] = ['url' => "sendprivate.php?uid=$uid", 'title' => 'Send private message'];
}

if (has_perm('view-user-pms'))
	$links[] = ['url' => "private.php?id=$uid", 'title' => 'View private messages'];
if (has_perm('edit-users'))
	$links[] = ['url' => "editprofile.php?id=$uid", 'title' => 'Edit user'];

if (has_perm('edit-permissions') && has_perm('ban-users')) {
	if ($user['group_id'] != $bannedgroup)
		$links[] = ['url' => "banmanager.php?id=$uid", 'title' => 'Ban user'];
	else
		$links[] = ['url' => "banmanager.php?unban&id=$uid", 'title' => 'Unban user'];
}

//More indepth test to not show the link if you can't edit your own perms
if (has_perm('edit-permissions') && (has_perm('edit-own-permissions') || $loguser['id'] != $uid)) {
	$links[] = ['url' => "editperms.php?uid=$uid", 'title' => 'Edit user permissions'];
}

$profilefields = [
	"General information" => [
		['title' => 'Total posts', 'value' => sprintf('%s (%1.02f per day)', $user['posts'], $user['posts'] / $days)],
		['title' => 'Total threads', 'value' => $user['threads'].' ('.sprintf('%1.02f', $user['threads'] / $days).' per day)'],
		['title' => 'Registered on', 'value' => date($dateformat, $user['joined']).' ('.timeunits($days * 86400).' ago)'],
		['title' => 'Last post', 'value'=>($user['lastpost'] ? date($dateformat, $user['lastpost'])." (".timeunits(time()-$user['lastpost'])." ago)" : "None").$lastpostlink],
		['title' => 'Last view', 'value' => sprintf(
				'%s (%s ago)',
			date($dateformat, $user['lastview']), timeunits(time() - $user['lastview'])
		)]
	],
];

$topbot = [
	'breadcrumb' => [['href' => './', 'title' => 'Main']],
	'title' => $user['name']
];

RenderPageBar($topbot);

foreach ($profilefields as $k => $v) {
	echo '<br><table class="c1"><tr class="h"><td class="b h" colspan="2">'.$k.'</td></tr>';
	foreach ($v as $pf) {
		echo '<tr><td class="b n1" width="130"><b>'.$pf['title'].'</b></td><td class="b n2">'.$pf['value'].'</td>';
	}
	echo '</table>';
}

?><br>
<table class="c1">
	<tr class="h"><td class="b n3">
		<?php
		foreach ($links as $link) {
			printf(' | <a href="%s">%s</a>', $link['url'], $link['title']);
		}
		?>
	</td></tr>
</table><br>
<?php
RenderPageBar($topbot);
pagefooter();