<?php
require('lib/common.php');

needs_login();

$action = (isset($_POST['action']) ? $_POST['action'] : null);
$fid = (isset($_GET['id']) ? $_GET['id'] : (isset($_POST['fid']) ? $_POST['fid'] : null));

$forum = $sql->fetch("SELECT * FROM forums WHERE id = ? AND id IN ".forums_with_view_perm(), [$fid]);

if (!$forum)
	error("Error", "Forum does not exist.");
if (!can_create_forum_thread($forum))
	error("Error", "You have no permissions to create threads in this forum!");

if ($action == 'Submit') {
	if (strlen(trim($_POST['title'])) < 4)
		error("Error", "You need to enter a longer title.");
	if ($userdata['lastpost'] > time() - 30 && $action == 'Submit' && !has_perm('ignore-thread-time-limit'))
		error("Error", "Don't post threads so fast, wait a little longer.");
	if ($userdata['lastpost'] > time() - 2 && $action == 'Submit' && has_perm('ignore-thread-time-limit'))
		error("Error", "You must wait 2 seconds before posting a thread.");

	$sql->query("UPDATE principia.users SET posts = posts + 1, threads = threads + 1, lastpost = ? WHERE id = ?", [time(), $userdata['id']]);

	$sql->query("INSERT INTO threads (title, forum, user, lastdate, lastuser) VALUES (?,?,?,?,?)",
		[$_POST['title'], $fid, $userdata['id'], time(), $userdata['id']]);

	$tid = $sql->insertid();
	$sql->query("INSERT INTO posts (user, thread, date, num) VALUES (?,?,?,?)",
		[$userdata['id'], $tid, time(), $userdata['posts']++]);

	$pid = $sql->insertid();
	$sql->query("INSERT INTO poststext (id, text) VALUES (?,?)",
		[$pid, $_POST['message']]);

	$sql->query("UPDATE forums SET threads = threads + 1, posts = posts + 1, lastdate = ?,lastuser = ?,lastid = ? WHERE id = ?", [time(), $userdata['id'], $pid, $fid]);

	$sql->query("UPDATE threads SET lastid = ? WHERE id = ?", [$pid, $tid]);

	redirect("thread.php?id=$tid");
}

$topbot = [
	'breadcrumb' => [['href' => './', 'title' => 'Main'], ['href' => "forum.php?id=$fid", 'title' => $forum['title']]],
	'title' => "New thread"
];

ob_start();

RenderPageBar($topbot);

$title = '';
$message = '';

if ($action == 'Preview') {
	$post['date'] = time();
	$post['num'] = $userdata['posts']++;
	$post['text'] = $_POST['message'];
	foreach ($userdata as $field => $val)
		$post['u' . $field] = $val;
	$post['ulastpost'] = time();

	$topbot['title'] .= ' (Preview)';
	echo '<br><table class="c1"><tr class="h"><td class="b h" colspan="2">Post preview</table>'.threadpost($post);

	$title = $_POST['title'];
	$message = $_POST['message'];
}

?><br><form action="newthread.php" method="post">
	<table class="c1">
		<tr class="h"><td class="b h" colspan="2">Thread</td></tr>
		<tr>
			<td class="b n1 center" width="120">Thread title:</td>
			<td class="b n2"><input type="text" name="title" size="100" maxlength="100" value="<?=esc($title) ?>"></td>
		</tr><tr>
			<td class="b n1 center">Format:</td>
			<td class="b n2"><?=posttoolbar() ?></td>
		</tr><tr>
			<td class="b n1 center">Post:</td>
			<td class="b n2"><textarea name="message" id="message" rows="20" cols="80"><?=esc($message) ?></textarea></td>
		</tr><tr>
			<td class="b n1"></td>
			<td class="b n1">
				<input type="hidden" name="fid" value="<?=$fid ?>">
				<input type="submit" name="action" value="Submit">
				<input type="submit" name="action" value="Preview">
			</td>
		</tr>
	</table>
</form><br><?php

RenderPageBar($topbot);

$content = ob_get_contents();
ob_end_clean();

$twig = _twigloader();
echo $twig->render('_legacy.twig', [
	'page_title' => 'New Thread',
	'content' => $content
]);
