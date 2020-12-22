<?php
require('lib/common.php');

$page = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$fid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$uid = isset($_GET['user']) ? (int)$_GET['user'] : 0;

if (isset($_GET['id']) && $fid = $_GET['id']) {
	if ($log) {
		$forum = $sql->fetch("SELECT f.*, r.time rtime FROM forums f LEFT JOIN forumsread r ON (r.fid = f.id AND r.uid = ?) "
			. "WHERE f.id = ? AND f.id IN " . forums_with_view_perm(), [$loguser['id'], $fid]);
		if (!$forum['rtime']) $forum['rtime'] = 0;
	} else
		$forum = $sql->fetch("SELECT * FROM forums WHERE id = ? AND id IN " . forums_with_view_perm(), [$fid]);

	if (!isset($forum['id'])) noticemsg("Error", "Forum does not exist.", true);

	//append the forum's title to the site title
	pageheader($forum['title'], $fid);

	$threads = $sql->query("SELECT " . userfields('u1', 'u1') . "," . userfields('u2', 'u2') . ", t.*"
		. ($log ? ", (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<'$forum[rtime]') isread" : '') . ' '
		. "FROM threads t "
		. "LEFT JOIN users u1 ON u1.id=t.user "
		. "LEFT JOIN users u2 ON u2.id=t.lastuser "
		. ($log ? "LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$loguser[id])" : '')
		. "WHERE t.forum = ? AND t.announce = 0 "
		. "ORDER BY t.sticky DESC, t.lastdate DESC "
		. "LIMIT " . (($page - 1) * $loguser['tpp']) . "," . $loguser['tpp'],
		[$fid]);

	$topbot = [
		'breadcrumb' => [['href' => './', 'title' => 'Main']],
		'title' => $forum['title']
	];
	if (can_create_forum_thread($forum))
		$topbot['actions'] = [['href' => "newthread.php?id=$fid", 'title' => 'New thread']];
} elseif (isset($_GET['user']) && $uid = $_GET['user']) {
	$user = $sql->fetch("SELECT displayname, name FROM users WHERE id = ?", [$uid]);

	if (!isset($user)) noticemsg("Error", "User does not exist.", true);

	pageheader("Threads by " . ($user['displayname'] ? $user['displayname'] : $user['name']));

	$threads = $sql->query("SELECT " . userfields('u1', 'u1') . "," . userfields('u2', 'u2') . ", t.*, f.id fid, "
		. ($log ? " (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<fr.time) isread, " : ' ')
		. "f.title ftitle FROM threads t "
		. "LEFT JOIN users u1 ON u1.id=t.user "
		. "LEFT JOIN users u2 ON u2.id=t.lastuser "
		. "LEFT JOIN forums f ON f.id=t.forum "
		. ($log ? "LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$loguser[id]) "
			. "LEFT JOIN forumsread fr ON (fr.fid=f.id AND fr.uid=$loguser[id]) " : '')
		. "WHERE t.user = ? "
		. "AND f.id IN " . forums_with_view_perm() . " "
		. "ORDER BY t.sticky DESC, t.lastdate DESC "
		. "LIMIT " . (($page - 1) * $loguser['tpp']) . "," . $loguser['tpp'],
		[$uid]);

	$forum['threads'] = $sql->result("SELECT count(*) FROM threads t "
		. "LEFT JOIN forums f ON f.id = t.forum "
		. "WHERE t.user = ? AND f.id IN " . forums_with_view_perm(), [$uid]);

	$topbot = [
		'breadcrumb' => [['href' => './', 'title' => 'Main'], ['href' => "profile.php?id=$uid", 'title' => ($user['displayname'] ? $user['displayname'] : $user['name'])]],
		'title' => 'Threads'
	];
} elseif ($time = $_GET['time']) {
	if (is_numeric($time))
		$mintime = time() - $time;
	else
		$mintime = 86400;

	pageheader('Latest posts');

	$threads = $sql->query("SELECT " . userfields('u1', 'u1') . "," . userfields('u2', 'u2') . ", t.*, f.id fid,
		f.title ftitle" . ($log ? ', (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<fr.time) isread ' : ' ')
		. "FROM threads t "
		. "LEFT JOIN users u1 ON u1.id=t.user "
		. "LEFT JOIN users u2 ON u2.id=t.lastuser "
		. "LEFT JOIN forums f ON f.id=t.forum "
		. ($log ? "LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$loguser[id]) "
			. "LEFT JOIN forumsread fr ON (fr.fid=f.id AND fr.uid=$loguser[id]) " : '')
		. "WHERE t.lastdate>$mintime "
		. " AND f.id IN " . forums_with_view_perm() . " "
		. "ORDER BY t.lastdate DESC "
		. "LIMIT " . (($page - 1) * $loguser['tpp']) . "," . $loguser['tpp']);
	$forum['threads'] = $sql->result("SELECT count(*) "
		. "FROM threads t "
		. "LEFT JOIN forums f ON f.id=t.forum "
		. "WHERE t.lastdate > $mintime "
		. "AND f.id IN " . forums_with_view_perm() . " ");

	$topbot = [];
} else {
	noticemsg("Error", "Forum does not exist.", true);
}

$showforum = (isset($time) ? $time : $uid);

if ($forum['threads'] <= $loguser['tpp']) {
	$fpagelist = '';
} else {
	if ($fid)
		$furl = "forum.php?id=$fid";
	elseif ($uid)
		$furl = "forum.php?user=$uid";
	elseif ($time)
		$furl = "forum.php?time=$time";
	$fpagelist = '<br>'.pagelist($forum['threads'], $loguser['tpp'], $furl, $page);
}

RenderPageBar($topbot);

if (isset($time)) {
	?><table class="c1" style="width:auto">
		<tr class="h"><td class="b">Latest Threads</td></tr>
		<tr><td class="b n1 center">
			By Threads | <a href="thread.php?time=<?=$time ?>">By Posts</a></a><br><br>
			<?=timelink(900,'forum').' | '.timelink(3600,'forum').' | '.timelink(86400,'forum').' | '.timelink(604800,'forum') ?>
		</td></tr>
	</table><?php
}

?><br>
<table class="c1">
	<?=($fid ? announcement_row(6) : '')?>
	<tr class="h">
		<td class="b h" width=17>&nbsp;</td>
		<?=($showforum ? '<td class="b h">Forum</td>' : '') ?>
		<td class="b h">Title</td>
		<td class="b h" width=130>Started by</td>
		<td class="b h" width=50>Replies</td>
		<td class="b h" width=50>Views</td>
		<td class="b h" width=130>Last post</td>
	</tr><?php
$lsticky = 0;

for ($i = 1; $thread = $threads->fetch(); $i++) {
	$pagelist = ' '.pagelist($thread['replies'], $loguser['ppp'], 'thread.php?id='.$thread['id'], 0, false, true);

	$status = '';
	if ($thread['closed']) $status .= 'o';

	if ($log) {
		if (!$thread['isread']) $status .= 'n';
	} else {
		if ($thread['lastdate'] > (time() - 3600)) $status .= 'n';
	}

	if ($status)
		$status = rendernewstatus($status);
	else
		$status = '';

	if (!$thread['title'])
		$thread['title'] = '';

	if ($thread['sticky'])
		$tr = 'n1';
	else
		$tr = ($i % 2 ? 'n2' : 'n3');

	if (!$thread['sticky'] && $lsticky)
		echo '<tr class="c"><td class="b" colspan="'.($showforum ? 8 : 7).'" style="font-size:1px">&nbsp;</td>';
	$lsticky = $thread['sticky'];

	?><tr class="<?=$tr ?> center">
		<td class="b n1"><?=$status ?></td>
		<?=($showforum ? sprintf('<td class="b"><a href="forum.php?id=%s">%s</a></td>', $thread['fid'], $thread['ftitle']) : '')?>
		<td class="b left" style="word-break:break-word"><a href="thread.php?id=<?=$thread['id'] ?>"><?=esc($thread['title']) ?></a><?=$pagelist ?></td>
		<td class="b"><?=userlink($thread, 'u1') ?></td>
		<td class="b"><?=$thread['replies'] ?></td>
		<td class="b"><?=$thread['views'] ?></td>
		<td class="b">
			<nobr><?=date($dateformat, $thread['lastdate']) ?></nobr><br>
			<span class="sfont">by <?=userlink($thread, 'u2') ?> <a href="thread.php?pid=<?=$thread['lastid'] ?>#<?=$thread['lastid'] ?>">&raquo;</a></span>
		</td>
	</tr><?php
}
if_empty_query($i, "No threads found.", ($showforum ? 7 : 6));

echo "</table>$fpagelist".(!isset($time) ? '<br>' : '');

RenderPageBar($topbot);

pagefooter();
