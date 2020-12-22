<?php
require('lib/common.php');

needs_login();

$_POST['action'] = (isset($_POST['action']) ? $_POST['action'] : null);

if ($act = $_POST['action']) {
	$tid = $_POST['tid'];
} else {
	$tid = $_GET['id'];
}
$act = (isset($act) ? $act : null);

$thread = $sql->fetch("SELECT t.*, f.title ftitle, f.private fprivate, f.readonly freadonly
	FROM threads t LEFT JOIN forums f ON f.id=t.forum
	WHERE t.id = ? AND t.forum IN " . forums_with_view_perm(), [$tid]);

$err = '';
if (!$thread) {
	noticemsg("Error", "Thread does not exist.", true);
} else if (!can_create_forum_post(['id' => $thread['forum'], 'private' => $thread['fprivate'], 'readonly' => $thread['freadonly']])) {
	$err = "You have no permissions to create posts in this forum!";
} elseif ($thread['closed'] && !has_perm('override-closed')) {
	$err = "You can't post in closed threads!";
}
if ($act == 'Submit') {
	$lastpost = $sql->fetch("SELECT id,user,date FROM posts WHERE thread = ? ORDER BY id DESC LIMIT 1", [$thread['id']]);
	if ($lastpost['user'] == $loguser['id'] && $lastpost['date'] >= (time() - 86400) && !has_perm('consecutive-posts'))
		$err = "You can't double post until it's been at least one day!";
	if ($lastpost['user'] == $loguser['id'] && $lastpost['date'] >= (time() - 2) && !has_perm('consecutive-posts'))
		$err = "You must wait 2 seconds before posting consecutively.";
	if (strlen(trim($_POST['message'])) == 0)
		$err = "Your post is empty! Enter a message and try again.";
	if ($loguser['regdate'] > (time() - 2))
		$err = "You must wait 2 seconds before posting on a freshly registered account.";
}

$topbot = [
	'breadcrumb' => [
		['href' => './', 'title' => 'Main'], ['href' => "forum.php?id={$thread['forum']}", 'title' => $thread['ftitle']],
		['href' => "thread.php?id={$thread['id']}", 'title' => esc($thread['title'])]
	],
	'title' => "New reply"
];

$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$quotetext = '';
if ($pid) {
	$post = $sql->fetch("SELECT IF(u.displayname='',u.name,u.displayname) name, p.user, pt.text, f.id fid, f.private fprivate, p.thread "
			. "FROM posts p "
			. "LEFT JOIN poststext pt ON p.id=pt.id "
			. "LEFT JOIN poststext pt2 ON pt2.id=pt.id AND pt2.revision=(pt.revision+1) "
			. "LEFT JOIN users u ON p.user=u.id "
			. "LEFT JOIN threads t ON t.id=p.thread "
			. "LEFT JOIN forums f ON f.id=t.forum "
			. "WHERE p.id = ? AND ISNULL(pt2.id)", [$pid]);

	//does the user have reading access to the quoted post?
	if (!can_view_forum(['id' => $post['fid'], 'private' => $post['fprivate']])) {
		$post['name'] = 'your overlord';
		$post['text'] = '';
	}

	$quotetext = sprintf('[quote="%s" id="%s"]%s[/quote]', $post['name'], $pid, str_replace("&", "&amp;", $post['text']));
}

if ($err) {
	pageheader('New reply', $thread['forum']);
	$topbot['title'] .= ' (Error)';
	RenderPageBar($topbot);
	echo '<br>';
	noticemsg("Error", $err."<br><a href=\"thread.php?id=$tid\">Back to thread</a>");
} elseif ($act == 'Preview' || !$act) {
	$post['date'] = time();
	$post['ip'] = $userip;
	$post['num'] = $loguser['posts']++;
	$post['text'] = ($act == 'Preview' ? $_POST['message'] : $quotetext);
	foreach ($loguser as $field => $val)
		$post['u' . $field] = $val;
	$post['ulastpost'] = time();

	if ($act == 'Preview') {
		pageheader('New reply', $thread['forum']);
		$topbot['title'] .= ' (Preview)';
		RenderPageBar($topbot);
		echo '<br><table class="c1"><tr class="h"><td class="b h" colspan="2">Post preview</table>'.threadpost($post);
	} else {
		pageheader('New reply', $thread['forum']);
		RenderPageBar($topbot);
	}
	?><br>
	<form action="newreply.php" method="post"><table class="c1">
		<tr class="h"><td class="b h" colspan="2">Reply</td></tr>
		<tr>
			<td class="b n1 center" width="120">Format:</td>
			<td class="b n2"><?=posttoolbar() ?></td>
		</tr><tr>
			<td class="b n1 center">Post:</td>
			<td class="b n2"><textarea name="message" id="message" rows="20" cols="80"><?=esc($post['text']) ?></textarea></td>
		</tr><tr>
			<td class="b n1"></td>
			<td class="b n1">
				<input type="hidden" name="tid" value="<?=$tid ?>">
				<input type="submit" name="action" value="Submit">
				<input type="submit" name="action" value="Preview">
			</td>
		</tr>
	</table></form><?php
} elseif ($act == 'Submit') {
	$sql->query("UPDATE users SET posts = posts + 1, lastpost = ? WHERE id = ?", [time(), $loguser['id']]);
	$sql->query("INSERT INTO posts (user,thread,date,ip,num) VALUES (?,?,?,?,?)",
		[$loguser['id'],$tid,time(),$userip,$loguser['posts']++]);
	$pid = $sql->insertid();
	$sql->query("INSERT INTO poststext (id,text) VALUES (?,?)",
		[$pid,$_POST['message']]);
	$sql->query("UPDATE threads SET replies = replies + 1,lastdate = ?, lastuser = ?, lastid = ? WHERE id = ?",
		[time(), $loguser['id'], $pid, $tid]);
	$sql->query("UPDATE forums SET posts = posts + 1,lastdate = ?, lastuser = ?, lastid = ? WHERE id = ?",
		[[time(), $loguser['id'], $pid, $thread['forum']]]);

	// nuke entries of this thread in the "threadsread" table
	$sql->query("DELETE FROM threadsread WHERE tid = ? AND NOT (uid = ?)", [$thread['id'], $loguser['id']]);

	redirect("thread.php?pid=$pid#$pid");
}

echo '<br>';
RenderPageBar($topbot);

pagefooter();