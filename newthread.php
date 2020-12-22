<?php
require('lib/common.php');

needs_login();

$announce = (isset($_REQUEST['announce']) ? $_REQUEST['announce'] : null);

if (!isset($_POST['action'])) $_POST['action'] = '';
if ($act = $_POST['action']) {
	$fid = $_POST['fid'];
} else {
	$fid = (isset($_GET['id']) ? $_GET['id'] : 0);
}

$type = ($announce ? "announcement" : "thread");

if ($announce)
	$forum = ['id' => 0, 'readonly' => 1];
else
	$forum = $sql->fetch("SELECT * FROM forums WHERE id = ? AND id IN ".forums_with_view_perm(), [$fid]);

if (!$forum)
	noticemsg("Error", "Forum does not exist.", true);
else if ($announce && !has_perm('create-forum-announcements'))
	$err = "You have no permissions to create announcements!";
else if (!can_create_forum_thread($forum))
	$err = "You have no permissions to create threads in this forum!";
else if ($loguser['lastpost'] > time() - 30 && $act == 'Submit' && !has_perm('ignore-thread-time-limit'))
	$err = "Don't post threads so fast, wait a little longer.";
else if ($loguser['lastpost'] > time() - 2 && $act == 'Submit' && has_perm('ignore-thread-time-limit'))
	$err = "You must wait 2 seconds before posting a thread.";

if ($act == 'Submit') {
	if (strlen(trim(str_replace(' ', '', $_POST['title']))) < 4)
		$err = "You need to enter a longer title.";
}

$topbot = [
	'breadcrumb' => [['href' => './', 'title' => 'Main']],
	'title' => "New $type"
];

if (!$announce) {
	$topbot['breadcrumb'][] = ['href' => "forum.php?id=$fid", 'title' => $forum['title']];
} else {
	$topbot['breadcrumb'][] = ['href' => "forum.php?announce=1", 'title' => 'Announcements'];
}

if (isset($err)) {
	pageheader("New $type", $forum['id']);
	$topbot['title'] .= ' (Error)';
	RenderPageBar($topbot);
	echo '<br>';
	noticemsg("Error", $err."<a href=\"forum.php?id=$fid\">Back to forum</a>");
} elseif (!$act) {
	pageheader("New $type", $forum['id']);
	RenderPageBar($topbot);
	?><br>
	<form action="newthread.php" method="post"><table class="c1">
		<tr class="h"><td class="b h" colspan="2"><?=ucfirst($type) ?></td></tr>
		<tr>
			<td class="b n1 center" width="120"><?=ucfirst($type) ?> title:</td>
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
				<input type="hidden" name="announce" value="<?=$announce ?>">
				<input type="submit" name="action" value="Submit">
				<input type="submit" name="action" value="Preview">
			</td>
		</tr>
	</table></form>
	<?php
} elseif ($act == 'Preview') {
	$post['date'] = time();
	$post['ip'] = $userip;
	$post['num'] = $loguser['posts']++;
	$post['text'] = $_POST['message'];
	foreach ($loguser as $field => $val)
		$post['u' . $field] = $val;
	$post['ulastpost'] = time();

	pageheader("New $type", $forum['id']);
	$topbot['title'] .= ' (Preview)';
	RenderPageBar($topbot);
	?><br>
	<table class="c1"><tr class="h"><td class="b h" colspan="2">Post preview</td></tr>
	<?=threadpost($post) ?>
	<br>
	<form action="newthread.php" method="post"><table class="c1">
		<tr class="h"><td class="b h" colspan="2"><?=ucfirst($type) ?></td></tr>
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
				<input type="hidden" name="announce" value="<?=$announce ?>">
				<input type="submit" name="action" value="Submit">
				<input type="submit" name="action" value="Preview">
			</td>
		</tr>
	</table></form><?php
} elseif ($act == 'Submit') {
	$modclose = ($announce ? '1' : '0');
	$announce = ($announce ? '1' : '0');

	$sql->query("UPDATE users SET posts = posts + 1,threads = threads + 1,lastpost = ? WHERE id = ?", [time(), $loguser['id']]);
	$sql->query("INSERT INTO threads (title,forum,user,lastdate,lastuser,announce,closed) VALUES (?,?,?,?,?,?,?)",
		[$_POST['title'],$fid,$loguser['id'],time(),$loguser['id'],$announce,$modclose]);
	$tid = $sql->insertid();
	$sql->query("INSERT INTO posts (user,thread,date,ip,num,announce) VALUES (?,?,?,?,?,?)",
		[$loguser['id'],$tid,time(),$userip,$loguser['posts']++,$announce]);
	$pid = $sql->insertid();
	$sql->query("INSERT INTO poststext (id,text) VALUES (?,?)",
		[$pid,$_POST['message']]);
	if (!$announce) {
		$sql->query("UPDATE forums SET threads = threads + 1, posts = posts + 1, lastdate = ?,lastuser = ?,lastid = ? WHERE id = ?", [time(), $loguser['id'], $pid, $fid]);
	}
	$sql->query("UPDATE threads SET lastid = ? WHERE id = ?", [$pid, $tid]);

	if ($announce) {
		$viewlink = "thread.php?announce=1";
	} else {
		$viewlink = "thread.php?id=$tid";
	}

	redirect($viewlink);
}

echo '<br>';
RenderPageBar($topbot);

pagefooter();