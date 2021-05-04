<?php
require('lib/common.php');

needs_login();

if (!isset($_POST['action'])) $_POST['action'] = '';
if ($act = $_POST['action']) {
	$fid = $_POST['fid'];
} else {
	$fid = (isset($_GET['id']) ? $_GET['id'] : 0);
}

$forum = $sql->fetch("SELECT * FROM forums WHERE id = ? AND id IN ".forums_with_view_perm(), [$fid]);

if (!$forum)
	noticemsg("Error", "Forum does not exist.", true);
else if (!can_create_forum_thread($forum))
	$err = "You have no permissions to create threads in this forum!";
else if ($userdata['lastpost'] > time() - 30 && $act == 'Submit' && !has_perm('ignore-thread-time-limit'))
	$err = "Don't post threads so fast, wait a little longer.";
else if ($userdata['lastpost'] > time() - 2 && $act == 'Submit' && has_perm('ignore-thread-time-limit'))
	$err = "You must wait 2 seconds before posting a thread.";

if ($act == 'Submit') {
	if (strlen(trim(str_replace(' ', '', $_POST['title']))) < 4)
		$err = "You need to enter a longer title.";
}

$topbot = [
	'breadcrumb' => [['href' => './', 'title' => 'Main'], ['href' => "forum.php?id=$fid", 'title' => $forum['title']]],
	'title' => "New thread"
];

if (isset($err)) {
	pageheader("New thread", $forum['id']);
	$topbot['title'] .= ' (Error)';
	RenderPageBar($topbot);
	echo '<br>';
	noticemsg("Error", $err."<a href=\"forum.php?id=$fid\">Back to forum</a>");
} elseif (!$act) {
	pageheader("New thread", $forum['id']);
	RenderPageBar($topbot);
	?><br>
	<form action="newthread.php" method="post"><table class="c1">
		<tr class="h"><td class="b h" colspan="2">Thread</td></tr>
		<tr>
			<td class="b n1 center" width="120">Thread title:</td>
			<td class="b n2"><input type="text" name="title" size="100" maxlength="100"></td>
		</tr><tr>
			<td class="b n1 center">Format:</td>
			<td class="b n2"><?=posttoolbar() ?></td>
		</tr><tr>
			<td class="b n1 center">Post:</td>
			<td class="b n2"><textarea name="message" id="message" rows="20" cols="80"></textarea></td>
		</tr><tr>
			<td class="b n1"></td>
			<td class="b n1">
				<input type="hidden" name="fid" value="<?=$fid ?>">
				<input type="submit" name="action" value="Submit">
				<input type="submit" name="action" value="Preview">
			</td>
		</tr>
	</table></form>
	<?php
} elseif ($act == 'Preview') {
	$post['date'] = time();
	$post['num'] = $userdata['posts']++;
	$post['text'] = $_POST['message'];
	foreach ($userdata as $field => $val)
		$post['u' . $field] = $val;
	$post['ulastpost'] = time();

	pageheader("New thread", $forum['id']);
	$topbot['title'] .= ' (Preview)';
	RenderPageBar($topbot);
	?><br>
	<table class="c1"><tr class="h"><td class="b h" colspan="2">Post preview</td></tr>
	<?=threadpost($post) ?>
	<br>
	<form action="newthread.php" method="post"><table class="c1">
		<tr class="h"><td class="b h" colspan="2">Thread</td></tr>
		<tr>
			<td class="b n1 center">Title:</td>
			<td class="b n2"><input type="text" name="title" size="100" maxlength="100" value="<?=esc($_POST['title']) ?>"></td>
		</tr><tr>
			<td class="b n1 center" width="120">Format:</td>
			<td class="b n2"><?=posttoolbar() ?></td>
		</tr><tr>
			<td class="b n1 center" width="120">Post:</td>
			<td class="b n2"><textarea name="message" id="message" rows="20" cols="80"><?=esc($_POST['message']) ?></textarea></td>
		</tr><tr>
			<td class="b n1"></td>
			<td class="b n1">
				<input type="hidden" name="fid" value="<?=$fid ?>">
				<input type="submit" name="action" value="Submit">
				<input type="submit" name="action" value="Preview">
			</td>
		</tr>
	</table></form><?php
} elseif ($act == 'Submit') {
	$sql->query("UPDATE principia.users SET posts = posts + 1,threads = threads + 1,lastpost = ? WHERE id = ?", [time(), $userdata['id']]);
	$sql->query("INSERT INTO threads (title,forum,user,lastdate,lastuser) VALUES (?,?,?,?,?)",
		[$_POST['title'],$fid,$userdata['id'],time(),$userdata['id']]);
	$tid = $sql->insertid();
	$sql->query("INSERT INTO posts (user,thread,date,num) VALUES (?,?,?,?)",
		[$userdata['id'],$tid,time(),$userdata['posts']++]);
	$pid = $sql->insertid();
	$sql->query("INSERT INTO poststext (id,text) VALUES (?,?)",
		[$pid,$_POST['message']]);

	$sql->query("UPDATE forums SET threads = threads + 1, posts = posts + 1, lastdate = ?,lastuser = ?,lastid = ? WHERE id = ?", [time(), $userdata['id'], $pid, $fid]);

	$sql->query("UPDATE threads SET lastid = ? WHERE id = ?", [$pid, $tid]);

	redirect("thread.php?id=$tid");
}

echo '<br>';
RenderPageBar($topbot);

pagefooter();