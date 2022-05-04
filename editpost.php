<?php
require('lib/common.php');

$_GET['act'] = $_GET['act'] ?? null;
$_POST['action'] = $_POST['action'] ?? '';

if ($action = $_POST['action']) {
	$pid = $_POST['pid'];
} else {
	$pid = $_GET['pid'];
}

if ($_GET['act'] == 'delete' || $_GET['act'] == 'undelete') {
	$action = $_GET['act'];
	$pid = $pid;
}

needsLogin();

$thread = fetch("SELECT p.user puser, t.*, f.title ftitle FROM z_posts p LEFT JOIN z_threads t ON t.id = p.thread "
	."LEFT JOIN z_forums f ON f.id=t.forum WHERE p.id = ? AND ? >= f.minread", [$pid, $userdata['powerlevel']]);

if (!$thread) $pid = 0;

if ($thread['closed'] && $userdata['powerlevel'] <= 1) {
	error("403", "You can't edit a post in closed threads!");
} else if ($userdata['powerlevel'] < 3 && $userdata['id'] != $thread['puser']) {
	error("403", "You do not have permission to edit this post.");
} else if ($pid == -1) {
	error("404", "Invalid post ID.");
}

$post = fetch("SELECT u.id, p.user, pt.text FROM z_posts p LEFT JOIN z_poststext pt ON p.id=pt.id "
		."JOIN (SELECT id,MAX(revision) toprev FROM z_poststext GROUP BY id) as pt2 ON pt2.id = pt.id AND pt2.toprev = pt.revision "
		."LEFT JOIN users u ON p.user = u.id WHERE p.id = ?", [$pid]);

if (!isset($post)) $err = "Post doesn't exist.";

if ($action == 'Submit') {
	if ($post['text'] == $_POST['message']) {
		error("400", "No changes detected.");
	}

	$newrev = $sql->result("SELECT revision FROM z_posts WHERE id = ?", [$pid]) + 1;

	query("UPDATE z_posts SET revision = ? WHERE id = ?", [$newrev, $id]);

	query("INSERT INTO z_poststext (id,text,revision,date) VALUES (?,?,?,?)",
		[$pid, $_POST['message'], $newrev, time()]);

	redirect("thread.php?pid=$pid#edit");
} else if ($action == 'delete' || $action == 'undelete') {

	if ($userdata['powerlevel'] <= 1) {
		error("403", "You do not have the permission to do this.");
	} else {
		query("UPDATE z_posts SET deleted = ? WHERE id = ?", [($action == 'delete' ? 1 : 0), $pid]);
		redirect("thread.php?pid=$pid#edit");
	}
}

$topbot = [
	'breadcrumb' => [
		['href' => './', 'title' => 'Main'],
		['href' => "forum.php?id={$thread['forum']}", 'title' => $thread['ftitle']],
		['href' => "thread.php?id={$thread['id']}", 'title' => esc($thread['title'])]
	],
	'title' => 'Edit post'
];

$euser = fetch("SELECT * FROM users WHERE id = ?", [$post['id']]);
$post['date'] = time();
$post['text'] = ($action == 'Preview' ? $_POST['message'] : $post['text']);
foreach ($euser as $field => $val)
	$post['u'.$field] = $val;
$post['ulastpost'] = time();

if ($action == 'Preview') {
	$topbot['title'] .= ' (Preview)';
}

$twig = _twigloader();
echo $twig->render('editpost.twig', [
	'post' => $post,
	'topbot' => $topbot,
	'action' => $action,
	'pid' => $pid
]);
