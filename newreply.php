<?php
require('lib/common.php');

needs_login();

$action = (isset($_POST['action']) ? $_POST['action'] : null);
$tid = (isset($_GET['id']) ? $_GET['id'] : (isset($_POST['tid']) ? $_POST['tid'] : null));

$thread = $sql->fetch("SELECT t.*, f.title ftitle, f.private fprivate, f.readonly freadonly
	FROM threads t LEFT JOIN forums f ON f.id=t.forum
	WHERE t.id = ? AND t.forum IN " . forums_with_view_perm(), [$tid]);

if (!$thread) {
	error("404", "Thread does not exist.");
} else if (!can_create_forum_post(['id' => $thread['forum'], 'private' => $thread['fprivate'], 'readonly' => $thread['freadonly']])) {
	error("403", "You have no permissions to create posts in this forum!");
} elseif ($thread['closed'] && !has_perm('override-closed')) {
	error("400", "You can't post in closed threads.");
}

$error = '';

if ($action == 'Submit') {
	$lastpost = $sql->fetch("SELECT id,user,date FROM posts WHERE thread = ? ORDER BY id DESC LIMIT 1", [$thread['id']]);
	if ($lastpost['user'] == $userdata['id'] && $lastpost['date'] >= (time() - 86400) && !has_perm('consecutive-posts'))
		$error = "You can't double post until it's been at least one day!";
	if ($lastpost['user'] == $userdata['id'] && $lastpost['date'] >= (time() - 2) && !has_perm('consecutive-posts'))
		$error = "You must wait 2 seconds before posting consecutively.";
	if (strlen(trim($_POST['message'])) == 0)
		$error = "Your post is empty! Enter a message and try again.";

	if (!$error) {
		$sql->query("UPDATE principia.users SET posts = posts + 1, lastpost = ? WHERE id = ?", [time(), $userdata['id']]);
		$sql->query("INSERT INTO posts (user,thread,date) VALUES (?,?,?)",
			[$userdata['id'],$tid,time()]);
		$pid = $sql->insertid();
		$sql->query("INSERT INTO poststext (id,text) VALUES (?,?)",
			[$pid,$_POST['message']]);
		$sql->query("UPDATE threads SET replies = replies + 1,lastdate = ?, lastuser = ?, lastid = ? WHERE id = ?",
			[time(), $userdata['id'], $pid, $tid]);
		$sql->query("UPDATE forums SET posts = posts + 1,lastdate = ?, lastuser = ?, lastid = ? WHERE id = ?",
			[time(), $userdata['id'], $pid, $thread['forum']]);

		// nuke entries of this thread in the "threadsread" table
		$sql->query("DELETE FROM threadsread WHERE tid = ? AND NOT (uid = ?)", [$thread['id'], $userdata['id']]);

		redirect("thread.php?pid=$pid#$pid");
	}
}

$topbot = [
	'breadcrumb' => [
		['href' => './', 'title' => 'Main'], ['href' => "forum.php?id={$thread['forum']}", 'title' => $thread['ftitle']],
		['href' => "thread.php?id={$thread['id']}", 'title' => esc($thread['title'])]
	],
	'title' => "New reply"
];

$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$quotetext = isset($_POST['message']) ? $_POST['message'] : '';
if ($pid) {
	$post = $sql->fetch("SELECT u.name name, p.user, pt.text, f.id fid, f.private fprivate, p.thread "
			. "FROM posts p "
			. "LEFT JOIN poststext pt ON p.id=pt.id "
			. "LEFT JOIN poststext pt2 ON pt2.id=pt.id AND pt2.revision=(pt.revision+1) "
			. "LEFT JOIN principia.users u ON p.user=u.id "
			. "LEFT JOIN threads t ON t.id=p.thread "
			. "LEFT JOIN forums f ON f.id=t.forum "
			. "WHERE p.id = ? AND ISNULL(pt2.id)", [$pid]);

	//does the user have reading access to the quoted post?
	if (!can_view_forum(['id' => $post['fid'], 'private' => $post['fprivate']])) {
		$post['name'] = 'ROllerozxa';
		$post['text'] = 'uwu';
	}

	$quotetext = sprintf('[quote="%s" id="%s"]%s[/quote]', $post['name'], $pid, str_replace("&", "&amp;", $post['text']));
}

$post['date'] = time();
$post['text'] = ($action == 'Preview' ? $_POST['message'] : $quotetext);
foreach ($userdata as $field => $val)
	$post['u' . $field] = $val;
$post['ulastpost'] = time();

if ($action == 'Preview') {
	$topbot['title'] .= ' (Preview)';
}

$twig = _twigloader();
echo $twig->render('newreply.twig', [
	'post' => $post,
	'topbot' => $topbot,
	'action' => $action,
	'tid' => $tid,
	'error' => $error
]);