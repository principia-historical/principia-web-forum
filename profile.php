<?php
require("lib/common.php");

$uid = isset($_GET['id']) ? (int)$_GET['id'] : -1;
if ($uid < 0) noticemsg("Error", "You must specify a user ID!", true);

$user = $sql->fetch("SELECT * FROM users WHERE id = ?", [$uid]);
if (!$user) noticemsg("Error", "This user does not exist!", true);

$group = $sql->fetch("SELECT * FROM groups WHERE id = ?", [$user['group_id']]);

pageheader("Profile for ".($user['displayname'] ? $user['displayname'] : $user['name']));

$days = (time() - $user['regdate']) / 86400;

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

$themes = themelist();
foreach ($themes as $k => $v) {
	if ((string)$k == $user['theme']) {
		$themename = $v;
		break;
	}
}

if ($user['birth'] != -1) {
	//Crudely done code.
	$monthnames = [1 => 'January', 'February', 'March', 'April',
		'May', 'June', 'July', 'August',
		'September', 'October', 'November', 'December'];
	$bdec = explode("-", $user['birth']);
	$bstr = $bdec[2] . "-" . $bdec[0] . "-" . $bdec[1];
	$mn = intval($bdec[0]);
	if ($bdec['2'] <= 0 && $bdec['2'] > -2)
		$birthday = $monthnames[$mn] . " " . $bdec[1];
	else
		$birthday = date("F j Y", strtotime($bstr));

	$bd1 = new DateTime($bstr);
	$bd2 = new DateTime(date("Y-m-d"));
	if (($bd2 < $bd1 && !$bdec['2'] <= 0) || ($bdec['2'] <= 0 && $bdec['2'] > -2))
		$age = '';
	else
		$age = '('.intval($bd1->diff($bd2)->format("%Y")).' years old)';
} else {
	$birthday = $age = '';
}

$email = ($user['email'] && $user['showemail'] ? str_replace(".", "<b> (dot) </b>", str_replace("@", "<b> (at) </b>", $user['email'])) : '');

$post['date'] = time();
$post['ip'] = $user['ip'];
$post['num'] = $post['id'] = $post['thread'] = 0;

$post['text'] = <<<HTML
[b]This[/b] is a [i]sample message.[/i] It shows how [u]your posts[/u] will look on the board.
[quote=Anonymous][spoiler]Hello![/spoiler][/quote]
[code]if (true) {\r\n
	print "The world isn't broken.";\r\n
} else {\r\n
	print "Something is very wrong.";\r\n
}[/code]
[irc]This is like code tags but without formatting.
<Anonymous> I said something![/irc]
[url=]Test Link. Ooh![/url]
HTML;

foreach ($user as $field => $val) {
	$post['u'.$field] = $val;
}

$links = [];
$links[] = ['url' => "forum.php?user=$uid", 'title' => 'View threads'];
$links[] = ['url' => "thread.php?user=$uid", 'title' => 'Show posts'];

$rblock = $sql->query("SELECT * FROM blockedlayouts WHERE user = ? AND blockee = ?", [$uid, $loguser['id']]);
$isblocked = $rblock;
if ($log) {
	if (isset($_GET['block'])) {
		$block = (int)$_GET['block'];

		if ($block && !$isblocked) {
			$rblock = $sql->query("INSERT INTO blockedlayouts (user, blockee) values (?,?)", [$uid, $loguser['id']]);
			$isblocked = true;
		} elseif (!$block && $isblocked) {
			$rblock = $sql->query("DELETE FROM blockedlayouts WHERE user = ? AND blockee = ?", [$uid, $loguser['id']]);
			$isblocked = false;
		}
	}

	$links[] = ['url' => "profile.php?id=$uid&block=".($isblocked ? 0 : 1), 'title' => ($isblocked ? 'Unblock' : 'Block').' layout'];

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

//timezone calculations
$now = new DateTime("now");
$usertz = new DateTimeZone($user['timezone']);
$userdate = new DateTime("now", $usertz);
$userct = date_format($userdate, $dateformat);
$logtz = new DateTimeZone($loguser['timezone']);
$usertzoff = $usertz->getOffset($now);
$logtzoff = $logtz->getOffset($now);

$profilefields = [
	"General information" => [
		['title' => 'Real handle', 'value' => '<span style="color:#'.$group['nc'].';"><b>'.esc($user['name']).'</b></span>'],
		['title' => 'Group', 'value' => $group['title']],
		['title' => 'Total posts', 'value' => sprintf('%s (%1.02f per day)', $user['posts'], $user['posts'] / $days)],
		['title' => 'Total threads', 'value' => $user['threads'].' ('.sprintf('%1.02f', $user['threads'] / $days).' per day)'],
		['title' => 'Registered on', 'value' => date($dateformat, $user['regdate']).' ('.timeunits($days * 86400).' ago)'],
		['title' => 'Last post', 'value'=>($user['lastpost'] ? date($dateformat, $user['lastpost'])." (".timeunits(time()-$user['lastpost'])." ago)" : "None").$lastpostlink],
		['title' => 'Last view', 'value' => sprintf(
				'%s (%s ago) %s %s',
			date($dateformat, $user['lastview']), timeunits(time() - $user['lastview']),
			($user['url'] ? sprintf('<br>at <a href="%s">%s</a>', esc($user['url']), esc($user['url'])) : ''),
			(has_perm("view-post-ips") ? '<br>from IP: '.$user['ip'] : ''))]
	],
	"User information" => [
		['title' => 'Gender', 'value' => $gender[$user['gender']]],
		['title' => 'Location', 'value' => ($user['location'] ? esc($user['location']) : '')],
		['title' => 'Birthday', 'value' => "$birthday $age"],
		['title' => 'Bio', 'value' => ($user['bio'] ? postfilter($user['bio']) : '')],
		['title' => 'Email', 'value' => $email]
	],
	"User settings" => [
		['title' => 'Theme', 'value' => esc($themename)],
		['title' => 'Time offset', 'value' => sprintf("%d:%02d", ($usertzoff - $logtzoff) / 3600, abs(($usertzoff - $logtzoff) / 60) % 60)." from you (Current time: $userct)"],
		['title' => 'Items per page', 'value' => $user['ppp']." posts, ".$user['tpp']." threads"]
	]
];

$topbot = [
	'breadcrumb' => [['href' => './', 'title' => 'Main']],
	'title' => ($user['displayname'] ? $user['displayname'] : $user['name'])
];

RenderPageBar($topbot);

foreach ($profilefields as $k => $v) {
	echo '<br><table class="c1"><tr class="h"><td class="b h" colspan="2">'.$k.'</td></tr>';
	foreach ($v as $pf) {
		if ($pf['title'] == 'Real handle' && !$user['displayname']) continue;
		echo '<tr><td class="b n1" width="130"><b>'.$pf['title'].'</b></td><td class="b n2">'.$pf['value'].'</td>';
	}
	echo '</table>';
}

?><br>
<table class="c1"><tr class="h"><td class="b h">Sample post</td><tr></table>
<?=threadpost($post)?>
<br>
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