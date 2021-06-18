<?php
require('lib/common.php');

$page = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$fid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$uid = isset($_GET['user']) ? (int)$_GET['user'] : 0;

if (isset($_GET['id']) && $fid = $_GET['id']) {
	if ($log) {
		$forum = $sql->fetch("SELECT f.*, r.time rtime FROM forums f LEFT JOIN forumsread r ON (r.fid = f.id AND r.uid = ?) "
			. "WHERE f.id = ? AND f.id IN " . forums_with_view_perm(), [$userdata['id'], $fid]);
		if (!$forum['rtime']) $forum['rtime'] = 0;
	} else
		$forum = $sql->fetch("SELECT * FROM forums WHERE id = ? AND id IN " . forums_with_view_perm(), [$fid]);

	if (!isset($forum['id'])) error("404", "Forum does not exist.");

	$title = $forum['title'];

	$threads = $sql->query("SELECT " . userfields('u1', 'u1') . "," . userfields('u2', 'u2') . ", t.*"
		. ($log ? ", (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<'$forum[rtime]') isread" : '') . ' '
		. "FROM threads t "
		. "LEFT JOIN principia.users u1 ON u1.id=t.user "
		. "LEFT JOIN principia.users u2 ON u2.id=t.lastuser "
		. ($log ? "LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$userdata[id])" : '')
		. "WHERE t.forum = ? "
		. "ORDER BY t.sticky DESC, t.lastdate DESC "
		. "LIMIT " . (($page - 1) * $userdata['tpp']) . "," . $userdata['tpp'],
		[$fid]);

	$topbot = [
		'breadcrumb' => [['href' => './', 'title' => 'Main']],
		'title' => $forum['title']
	];
	if (can_create_forum_thread($forum))
		$topbot['actions'] = [['href' => "newthread.php?id=$fid", 'title' => 'New thread']];
} elseif (isset($_GET['user']) && $uid = $_GET['user']) {
	$user = $sql->fetch("SELECT name FROM principia.users WHERE id = ?", [$uid]);

	if (!isset($user)) error("404", "User does not exist.");

	$title = "Threads by " . $user['name'];

	$threads = $sql->query("SELECT " . userfields('u1', 'u1') . "," . userfields('u2', 'u2') . ", t.*, f.id fid, "
		. ($log ? " (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<fr.time) isread, " : ' ')
		. "f.title ftitle FROM threads t "
		. "LEFT JOIN principia.users u1 ON u1.id=t.user "
		. "LEFT JOIN principia.users u2 ON u2.id=t.lastuser "
		. "LEFT JOIN forums f ON f.id=t.forum "
		. ($log ? "LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$userdata[id]) "
			. "LEFT JOIN forumsread fr ON (fr.fid=f.id AND fr.uid=$userdata[id]) " : '')
		. "WHERE t.user = ? "
		. "AND f.id IN " . forums_with_view_perm() . " "
		. "ORDER BY t.sticky DESC, t.lastdate DESC "
		. "LIMIT " . (($page - 1) * $userdata['tpp']) . "," . $userdata['tpp'],
		[$uid]);

	$forum['threads'] = $sql->result("SELECT count(*) FROM threads t "
		. "LEFT JOIN forums f ON f.id = t.forum "
		. "WHERE t.user = ? AND f.id IN " . forums_with_view_perm(), [$uid]);

	$topbot = [
		'breadcrumb' => [['href' => './', 'title' => 'Main'], ['href' => "../user.php?id=$uid", 'title' => $user['name']]],
		'title' => 'Threads'
	];
} elseif ($time = $_GET['time']) {
	$mintime = ($time > 0 && $time <= 2592000 ? time() - $time : 86400);

	$title = 'Latest threats';

	$threads = $sql->query("SELECT " . userfields('u1', 'u1') . "," . userfields('u2', 'u2') . ", t.*, f.id fid,
		f.title ftitle" . ($log ? ', (NOT (r.time<t.lastdate OR isnull(r.time)) OR t.lastdate<fr.time) isread ' : ' ')
		. "FROM threads t "
		. "LEFT JOIN principia.users u1 ON u1.id=t.user "
		. "LEFT JOIN principia.users u2 ON u2.id=t.lastuser "
		. "LEFT JOIN forums f ON f.id=t.forum "
		. ($log ? "LEFT JOIN threadsread r ON (r.tid=t.id AND r.uid=$userdata[id]) "
			. "LEFT JOIN forumsread fr ON (fr.fid=f.id AND fr.uid=$userdata[id]) " : '')
		. "WHERE t.lastdate > ? "
		. " AND f.id IN " . forums_with_view_perm()
		. "ORDER BY t.lastdate DESC "
		. "LIMIT " . (($page - 1) * $userdata['tpp']) . "," . $userdata['tpp'],
	[$mintime]);

	$forum['threads'] = $sql->result("SELECT count(*) "
		. "FROM threads t "
		. "LEFT JOIN forums f ON f.id=t.forum "
		. "WHERE t.lastdate > ? "
		. "AND f.id IN " . forums_with_view_perm(),
	[$mintime]);

	$topbot = [];
} else {
	error("404", "Forum does not exist.");
}

$showforum = (isset($time) ? $time : $uid);

if ($forum['threads'] <= $userdata['tpp']) {
	$fpagelist = '';
} else {
	if ($fid)
		$furl = "forum.php?id=$fid";
	elseif ($uid)
		$furl = "forum.php?user=$uid";
	elseif ($time)
		$furl = "forum.php?time=$time";
	$fpagelist = '<br>'.pagelist($forum['threads'], $userdata['tpp'], $furl, $page, true);
}

$twig = _twigloader();
echo $twig->render('forum.twig', [
	'title' => $title,
	'threads' => $threads,
	'showforum' => $showforum,
	'topbot' => $topbot,
	'fpagelist' => $fpagelist,
	'time' => (isset($time) ? $time : null)
]);