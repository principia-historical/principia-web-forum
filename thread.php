<?php
require('lib/common.php');

$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
if ($page < 0) error("404", "Invalid page number");

$fieldlist = userfields('u', 'u') . ',' . userfields_post();

$ppp = $userdata['ppp'];

if (isset($_REQUEST['id'])) {
	$tid = (int)$_REQUEST['id'];
	$viewmode = "thread";
} elseif (isset($_GET['user'])) {
	$uid = (int)$_GET['user'];
	$viewmode = "user";
} elseif (isset($_GET['time'])) {
	$time = (int)$_GET['time'];
	$viewmode = "time";
}
// "link" support (i.e., thread.php?pid=999whatever)
elseif (isset($_GET['pid'])) {
	$pid = (int)$_GET['pid'];
	$numpid = fetch("SELECT t.id tid FROM z_posts p LEFT JOIN z_threads t ON p.thread = t.id WHERE p.id = ?", [$pid]);
	if (!$numpid) error("404", "Thread post does not exist.");

	$tid = result("SELECT thread FROM z_posts WHERE id = ?", [$pid]);
	$page = floor(result("SELECT COUNT(*) FROM z_posts WHERE thread = ? AND id < ?", [$tid, $pid]) / $ppp) + 1;
	$viewmode = "thread";
} else {
	error("404", "Thread does not exist.");
}

if ($viewmode == "thread")
	$threadcreator = result("SELECT user FROM z_threads WHERE id = ?", [$tid]);
else
	$threadcreator = 0;

$action = '';

$post_c = $_POST['c'] ?? '';
$act = $_POST['action'] ?? '';

if (isset($tid) && $log && $act && ($userdata['powerlevel'] > 2 ||
		($userdata['id'] == $threadcreator && $act == "rename" && $userdata['powerlevel'] > 0 && isset($_POST['title'])))) {

	if ($act == 'stick')		$action = ',sticky=1';
	elseif ($act == 'unstick')	$action = ',sticky=0';
	elseif ($act == 'close')	$action = ',closed=1';
	elseif ($act == 'open')		$action = ',closed=0';
	elseif ($act == 'trash')	editThread($tid, '', $trashid, 1);
	elseif ($act == 'rename')	$action = ",title=?";
	elseif ($act == 'move')		editThread($tid, '', $_POST['arg']);
	else						error("400", "Unknown action.");
}

$offset = (($page - 1) * $ppp);

if ($viewmode == "thread") {
	if (!$tid) $tid = 0;

	$params = ($act == 'rename' ? [$_POST['title'], $tid] : [$tid]);

	query("UPDATE z_threads SET views = views + 1 $action WHERE id = ?", $params);

	$thread = fetch("SELECT t.*, f.title ftitle, t.forum fid".($log ? ', r.time frtime' : '').' '
			. "FROM z_threads t LEFT JOIN z_forums f ON f.id=t.forum "
			. ($log ? "LEFT JOIN z_forumsread r ON (r.fid=f.id AND r.uid=$userdata[id]) " : '')
			. "WHERE t.id = ? AND ? >= f.minread",
			[$tid, $userdata['powerlevel']]);

	if (!isset($thread['id'])) error("404", "Thread does not exist.");

	//append thread's title to page title
	$title = $thread['title'];

	//mark thread as read
	if ($log && $thread['lastdate'] > $thread['frtime'])
		query("REPLACE INTO z_threadsread VALUES (?,?,?)", [$userdata['id'], $thread['id'], time()]);

	//check for having to mark the forum as read too
	if ($log) {
		$readstate = fetch("SELECT ((NOT ISNULL(r.time)) OR t.lastdate < ?) n FROM z_threads t LEFT JOIN z_threadsread r ON (r.tid = t.id AND r.uid = ?) "
			. "WHERE t.forum = ? GROUP BY ((NOT ISNULL(r.time)) OR t.lastdate < ?) ORDER BY n ASC",
			[$thread['frtime'], $userdata['id'], $thread['fid'], $thread['frtime']]);
		//if $readstate[n] is 1, MySQL did not create a group for threads where ((NOT ISNULL(r.time)) OR t.lastdate<'$thread[frtime]') is 0;
		//thus, all threads in the forum are read. Mark it as such.
		if ($readstate['n'] == 1)
			query("REPLACE INTO z_forumsread VALUES (?,?,?)", [$userdata['id'], $thread['fid'], time()]);
	}

	$posts = query("SELECT $fieldlist p.*, pt.text, pt.date ptdate, pt.revision cur_revision, t.forum tforum
			FROM z_posts p
			LEFT JOIN z_threads t ON t.id = p.thread
			LEFT JOIN z_poststext pt ON p.id = pt.id AND p.revision = pt.revision
			LEFT JOIN users u ON p.user = u.id
			WHERE p.thread = ?
			GROUP BY p.id ORDER BY p.id
			LIMIT ?,?",
		[$tid, $offset, $ppp]);

} elseif ($viewmode == "user") {
	$user = fetch("SELECT * FROM users WHERE id = ?", [$uid]);

	if ($user == null) error("404", "User doesn't exist.");

	$title = "Posts by " . $user['name'];
	$posts = query("SELECT $fieldlist p.*, pt.text, pt.date ptdate, pt.revision cur_revision, t.id tid, f.id fid, t.title ttitle, t.forum tforum
			FROM z_posts p
			LEFT JOIN z_poststext pt ON p.id = pt.id AND p.revision = pt.revision
			LEFT JOIN users u ON p.user = u.id
			LEFT JOIN z_threads t ON p.thread = t.id
			LEFT JOIN z_forums f ON f.id = t.forum
			WHERE p.user = ? AND ? >= f.minread
			ORDER BY p.id LIMIT ?,?",
		[$uid, $userdata['powerlevel'], $offset, $ppp]);

	$thread['replies'] = result("SELECT count(*) FROM z_posts p WHERE user = ?", [$uid]) - 1;
} elseif ($viewmode == "time") {
	$mintime = ($time > 0 && $time <= 2592000 ? time() - $time : 86400);

	$title = 'Latest posts';

	$posts = query("SELECT $fieldlist p.*, pt.text, pt.date ptdate, pt.revision cur_revision, t.id tid, f.id fid, t.title ttitle, t.forum tforum
			FROM z_posts p
			LEFT JOIN z_poststext pt ON p.id = pt.id AND p.revision = pt.revision
			LEFT JOIN users u ON p.user=u.id
			LEFT JOIN z_threads t ON p.thread=t.id
			LEFT JOIN z_forums f ON f.id=t.forum
			WHERE p.date > ? AND ? >= f.minread
			ORDER BY p.date DESC
			LIMIT ?,?",
		[$mintime, $userdata['powerlevel'], $offset, $ppp]);

	$thread['replies'] = result("SELECT count(*) FROM z_posts WHERE date > ?", [$mintime]) - 1;
} else
	$title = '';

if ($thread['replies'] <= $ppp) {
	$pagelist = '';
} else {
	$furl = "thread.php?";
	if ($viewmode == "thread")	$furl .= "id=$tid";
	if ($viewmode == "user")	$furl .= "user=$uid";
	if ($viewmode == "time")	$furl .= "time=$time";
	$pagelist = '<br>'.pagelist($thread['replies'], $ppp, $furl, $page, true);
}

if ($viewmode == "thread") {
	$topbot = [
		'breadcrumb' => [['href' => './', 'title' => 'Main'],['href' => 'forum.php?id='.$thread['forum'], 'title' => $thread['ftitle']]],
		'title' => esc($thread['title'])
	];

	$faccess = fetch("SELECT id,minreply FROM z_forums WHERE id = ?",[$thread['forum']]);
	if ($faccess['minreply'] <= $userdata['powerlevel']) {
		if ($userdata['powerlevel'] > 1 && $thread['closed'])
			$topbot['actions'] = [['title' => 'Thread closed'],['href' => "newreply.php?id=$tid", 'title' => 'New reply']];
		else if ($thread['closed'])
			$topbot['actions'] = [['title' => 'Thread closed']];
		else
			$topbot['actions'] = [['href' => "newreply.php?id=$tid", 'title' => 'New reply']];
	}
} elseif ($viewmode == "user") {
	$topbot = [
		'breadcrumb' => [['href' => './', 'title' => 'Main'], ['href' => "/user.php?id=$uid", 'title' => $user['name']]],
		'title' => 'Posts'
	];
} elseif ($viewmode == "time") {
	$topbot = [];
	$time = $_GET['time'];
} else {
	error("404", "Thread does not exist.<br><a href=./>Back to main</a>");
}

$modlinks = '';
if ($log && isset($tid) && ($userdata['powerlevel'] > 2 || ($userdata['id'] == $thread['user'] && !$thread['closed'] && $userdata['powerlevel'] > 0))) {
	$link = "<a href=javascript:submitmod";
	if ($userdata['powerlevel'] > 2) {
		$stick = ($thread['sticky'] ? "$link('unstick')>Unstick</a>" : "$link('stick')>Stick</a>");
		$stick2 = addcslashes($stick, "'");

		$close = '| ' . ($thread['closed'] ? "$link('open')>Open</a>" : "$link('close')>Close</a>");
		$close2 = addcslashes($close, "'");

		$trash = '| ' . ($thread['forum'] != $trashid ? '<a href=javascript:submitmod(\'trash\') onclick="trashConfirm(event)">Trash</a>' : '');
		$trash2 = addcslashes($trash, "'");

		$edit = '| <a href="javascript:showrbox()">Rename</a> | <a href="javascript:showmove()">Move</a>';

		$fmovelinks = addslashes(forumlist($thread['forum']))
		.	'<input type="submit" id="move" value="Submit" name="movethread" onclick="submitmove(movetid())">'
		.	'<input type="button" value="Cancel" onclick="hidethreadedit(); return false;">';
	} else {
		$fmovelinks = $stick = $stick2 = $close = $close2 = $trash = $trash2 = '';
		$edit = '<a href=javascript:showrbox()>Rename</a>';
	}

	$renamefield = '<input type="text" name="title" id="title" size=60 maxlength=255 value="'.esc($thread['title']).'">';
	$renamefield.= '<input type="submit" name="submit" value="Rename" onclick="submitmod(\'rename\')">';
	$renamefield.= '<input type="button" value="Cancel" onclick="hidethreadedit(); return false">';
	$renamefield = addcslashes($renamefield, "'"); //because of javascript, single quotes will gum up the works

	$threadtitle = addcslashes(htmlentities($thread['title'], ENT_COMPAT | ENT_HTML401, 'UTF-8'), "'");

	$modlinks = <<<HTML
<br><form action="thread.php" method="post" name="mod" id="mod">
<table class="c1"><tr class="n2">
	<td class="b n2">
		<span id="moptions">Thread options: $stick $close $trash $edit </span>
		<span id="mappend"></span>
		<span id="canceledit"></span>
		<script>
function showrbox(){
	document.getElementById('moptions').innerHTML='Rename thread:';
	document.getElementById('mappend').innerHTML='$renamefield';
	document.getElementById('mappend').style.display = '';
}
function showmove(){
	document.getElementById('moptions').innerHTML='Move to: ';
	document.getElementById('mappend').innerHTML='$fmovelinks';
	document.getElementById('mappend').style.display = '';
}
function hidethreadedit() {
	document.getElementById('moptions').innerHTML = 'Thread options: $stick2 $close2 $trash2 $edit';
	document.getElementById('mappend').innerHTML = '<input type=hidden name=tmp style="width:80%!important;border-width:0px!important;padding:0px!important" onkeypress="submit_on_return(event,\'rename\')" value="$threadtitle" maxlength="100">';
	document.getElementById('canceledit').style.display = 'none';
}		</script>
		<input type=hidden id="arg" name="arg" value="">
		<input type=hidden id="id" name="id" value="$tid">
		<input type=hidden id="action" name="action" value="">
	</td>
</table>
</form>
HTML;
}

$twig = _twigloader();
echo $twig->render('thread.twig', [
	'thread' => $thread,
	'posts' => $posts,
	'topbot' => $topbot,
	'uid' => $uid ?? null,
	'time' => $time ?? null,
	'modlinks' => $modlinks,
	'pagelist' => $pagelist,
	'faccess' => $faccess ?? null,
	'pin' => $_GET['pin'] ?? null,
	'tid' => $tid ?? null,
	'title' => $title
]);
