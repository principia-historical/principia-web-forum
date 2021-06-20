<?php
require('lib/common.php');

needs_login();

$action = (isset($_POST['action']) ? $_POST['action'] : null);
$fid = (isset($_GET['id']) ? $_GET['id'] : (isset($_POST['fid']) ? $_POST['fid'] : null));

$forum = $sql->fetch("SELECT * FROM forums WHERE id = ? AND id IN ".forums_with_view_perm(), [$fid]);

if (!$forum)
	error("404", "Forum does not exist.");
if (!can_create_forum_thread($forum))
	error("403", "You have no permissions to create threads in this forum!");

$error = '';

if ($action == 'Submit') {
	if (strlen(trim($_POST['title'])) < 4)
		$error = "You need to enter a longer title.";
	if ($userdata['lastpost'] > time() - 30 && $action == 'Submit' && !has_perm('ignore-thread-time-limit'))
		$error = "Don't post threads so fast, wait a little longer.";
	if ($userdata['lastpost'] > time() - 2 && $action == 'Submit' && has_perm('ignore-thread-time-limit'))
		$error = "You must wait 2 seconds before posting a thread.";

	if (!$error) {
		$sql->query("UPDATE principia.users SET posts = posts + 1, threads = threads + 1, lastpost = ? WHERE id = ?",
			[time(), $userdata['id']]);

		$sql->query("INSERT INTO threads (title, forum, user, lastdate, lastuser) VALUES (?,?,?,?,?)",
			[$_POST['title'], $fid, $userdata['id'], time(), $userdata['id']]);

		$tid = $sql->insertid();
		$sql->query("INSERT INTO posts (user, thread, date) VALUES (?,?,?)",
			[$userdata['id'], $tid, time()]);

		$pid = $sql->insertid();
		$sql->query("INSERT INTO poststext (id, text) VALUES (?,?)", [$pid, $_POST['message']]);

		$sql->query("UPDATE forums SET threads = threads + 1, posts = posts + 1, lastdate = ?,lastuser = ?,lastid = ? WHERE id = ?",
			[time(), $userdata['id'], $pid, $fid]);

		$sql->query("UPDATE threads SET lastid = ? WHERE id = ?", [$pid, $tid]);

		redirect("thread.php?id=$tid");
	}
}

$topbot = [
	'breadcrumb' => [['href' => './', 'title' => 'Main'], ['href' => "forum.php?id=$fid", 'title' => $forum['title']]],
	'title' => "New thread"
];

$title = isset($_POST['title']) ? $_POST['title'] : '';
$message = isset($_POST['message']) ? $_POST['message'] : '';

if ($action == 'Preview') {
	$post['date'] = time();
	$post['text'] = $_POST['message'];
	foreach ($userdata as $field => $val)
		$post['u' . $field] = $val;
	$post['ulastpost'] = time();

	$topbot['title'] .= ' (Preview)';

	$title = $_POST['title'];
	$message = $_POST['message'];
}

$twig = _twigloader();
echo $twig->render('newthread.twig', [
	'post' => (isset($post) ? $post : null),
	'title' => $title,
	'message' => $message,
	'topbot' => $topbot,
	'action' => $action,
	'fid' => $fid,
	'error' => $error
]);