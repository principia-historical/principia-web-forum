<?php
require('lib/common.php');

$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
if ($page < 0) error("Error", "Invalid page number");

$fieldlist = userfields('u', 'u') . ',' . userfields_post();

$ppp = isset($_REQUEST['ppp']) ? (int)$_REQUEST['ppp'] : $userdata['ppp'];
if ($ppp < 0) error("Error", "Invalid posts per page number");

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
	$numpid = $sql->fetch("SELECT t.id tid FROM posts p LEFT JOIN threads t ON p.thread = t.id WHERE p.id = ?", [$pid]);
	if (!$numpid) error("Error", "Thread post does not exist.");

	$tid = $sql->result("SELECT thread FROM posts WHERE id = ?", [$pid]);
	$page = floor($sql->result("SELECT COUNT(*) FROM posts WHERE thread = ? AND id < ?", [$tid, $pid]) / $ppp) + 1;
	$viewmode = "thread";
} else {
	error("Error", "Thread does not exist.");
}

if ($viewmode == "thread")
	$threadcreator = $sql->result("SELECT user FROM threads WHERE id = ?", [$tid]);
else
	$threadcreator = 0;

$action = '';

$post_c = isset($_POST['c']) ? $_POST['c'] : '';
$act = isset($_POST['action']) ? $_POST['action'] : '';

if (isset($tid) && $log && $act && (can_edit_forum_threads(getforumbythread($tid)) ||
		($userdata['id'] == $threadcreator && $act == "rename" && has_perm('rename-own-thread')))) {

	if ($act == 'stick') {
		$action = ',sticky=1';
	} elseif ($act == 'unstick') {
		$action = ',sticky=0';
	} elseif ($act == 'close') {
		$action = ',closed=1';
	} elseif ($act == 'open') {
		$action = ',closed=0';
	} elseif ($act == 'trash') {
		editthread($tid, '', $trashid, 1);
	} elseif ($act == 'rename' && $_POST['title']) {
		$action = ",title=?";
	} elseif ($act == 'move') {
		editthread($tid, '', $_POST['arg']);
	} else {
		error("Error", "Unknown action.");
	}
}

//determine string for revision pinning
if (isset($_GET['pin']) && isset($_GET['rev']) && is_numeric($_GET['pin']) && is_numeric($_GET['rev']) && has_perm('view-post-history')) {
	$pinstr = "AND (pt2.id<>$_GET[pin] OR pt2.revision<>($_GET[rev]+1)) ";
} else
	$pinstr = '';

if ($viewmode == "thread") {
	if (!$tid) $tid = 0;

	if ($act == 'rename')
		$params = [$_POST['title'], $tid];
	else
		$params = [$tid];
	$sql->query("UPDATE threads SET views = views + 1 $action WHERE id = ?", $params);

	$thread = $sql->fetch("SELECT t.*, f.title ftitle, t.forum fid".($log ? ', r.time frtime' : '').' '
			. "FROM threads t LEFT JOIN forums f ON f.id=t.forum "
			. ($log ? "LEFT JOIN forumsread r ON (r.fid=f.id AND r.uid=$userdata[id]) " : '')
			. "WHERE t.id = ? AND t.forum IN ".forums_with_view_perm(),
			[$tid]);

	if (!isset($thread['id'])) error("Error", "Thread does not exist.");

	//append thread's title to page title
	$title = $thread['title'];

	//mark thread as read
	if ($log && $thread['lastdate'] > $thread['frtime'])
		$sql->query("REPLACE INTO threadsread VALUES (?,?,?)", [$userdata['id'], $thread['id'], time()]);

	//check for having to mark the forum as read too
	if ($log) {
		$readstate = $sql->fetch("SELECT ((NOT ISNULL(r.time)) OR t.lastdate < ?) n FROM threads t LEFT JOIN threadsread r ON (r.tid = t.id AND r.uid = ?) "
			. "WHERE t.forum = ? GROUP BY ((NOT ISNULL(r.time)) OR t.lastdate < ?) ORDER BY n ASC",
			[$thread['frtime'], $userdata['id'], $thread['fid'], $thread['frtime']]);
		//if $readstate[n] is 1, MySQL did not create a group for threads where ((NOT ISNULL(r.time)) OR t.lastdate<'$thread[frtime]') is 0;
		//thus, all threads in the forum are read. Mark it as such.
		if ($readstate['n'] == 1)
			$sql->query("REPLACE INTO forumsread VALUES (?,?,?)", [$userdata['id'], $thread['fid'], time()]);
	}

	//select top revision
	$posts = $sql->query("SELECT $fieldlist p.*, pt.text, pt.date ptdate, pt.user ptuser, pt.revision, t.forum tforum "
		. "FROM posts p "
		. "LEFT JOIN threads t ON t.id = p.thread "
		. "LEFT JOIN poststext pt ON p.id = pt.id "
		. "LEFT JOIN poststext pt2 ON pt2.id = pt.id AND pt2.revision = (pt.revision + 1) $pinstr " //SQL barrel roll
		. "LEFT JOIN principia.users u ON p.user = u.id "
		. "WHERE p.thread = ? AND ISNULL(pt2.id) "
		. "GROUP BY p.id ORDER BY p.id "
		. "LIMIT ".(($page - 1) * $ppp).",$ppp",
		[$tid]);
} elseif ($viewmode == "user") {
	$user = $sql->fetch("SELECT * FROM principia.users WHERE id = ?", [$uid]);

	if ($user == null) error("Error", "User doesn't exist.");

	$title = "Posts by " . $user['name'];
	$posts = $sql->query("SELECT $fieldlist p.*, pt.text, pt.date ptdate, pt.user ptuser, pt.revision, t.id tid, f.id fid, f.private fprivate, t.title ttitle, t.forum tforum "
		. "FROM posts p "
		. "LEFT JOIN poststext pt ON p.id=pt.id "
		. "LEFT JOIN poststext pt2 ON pt2.id=pt.id AND pt2.revision=(pt.revision+1) $pinstr "
		. "LEFT JOIN principia.users u ON p.user=u.id "
		. "LEFT JOIN threads t ON p.thread=t.id "
		. "LEFT JOIN forums f ON f.id=t.forum "
		. "WHERE p.user=$uid AND ISNULL(pt2.id) "
		. "ORDER BY p.id "
		. "LIMIT " . (($page - 1) * $ppp) . "," . $ppp);

	$thread['replies'] = $sql->result("SELECT count(*) FROM posts p WHERE user = ?", [$uid]) - 1;
} elseif ($viewmode == "time") {
	if (is_numeric($time))
		$mintime = time() - $time;
	else
		$mintime = 86400;

	$title = 'Latest posts';

	$posts = $sql->query("SELECT $fieldlist p.*, pt.text, pt.date ptdate, pt.user ptuser, pt.revision, t.id tid, f.id fid, f.private fprivate, t.title ttitle, t.forum tforum "
		. "FROM posts p "
		. "LEFT JOIN poststext pt ON p.id=pt.id "
		. "LEFT JOIN poststext pt2 ON pt2.id=pt.id AND pt2.revision=(pt.revision+1) $pinstr "
		. "LEFT JOIN principia.users u ON p.user=u.id "
		. "LEFT JOIN threads t ON p.thread=t.id "
		. "LEFT JOIN forums f ON f.id=t.forum "
		. "WHERE p.date > ? AND ISNULL(pt2.id) "
		. "ORDER BY p.date DESC "
		. "LIMIT " . (($page - 1) * $ppp) . "," . $ppp, [$mintime]);

	$thread['replies'] = $sql->result("SELECT count(*) FROM posts WHERE date > ?", [$mintime]) - 1;
} else
	$title = '';

if ($thread['replies'] <= $ppp) {
	$pagelist = '';
} else {
	if ($viewmode == "thread")
		$furl = "thread.php?id=$tid";
	elseif ($viewmode == "user")
		$furl = "thread.php?user=$uid";
	elseif ($viewmode == "time")
		$furl = "thread.php?time=$time";
	$pagelist = '<br>'.pagelist($thread['replies'], $ppp, $furl, $page, true);
}

if ($viewmode == "thread") {
	$topbot = [
		'breadcrumb' => [['href' => './', 'title' => 'Main'],['href' => 'forum.php?id='.$thread['forum'], 'title' => $thread['ftitle']]],
		'title' => esc($thread['title'])
	];

	$faccess = $sql->fetch("SELECT id,private,readonly FROM forums WHERE id = ?",[$thread['forum']]);
	if (can_create_forum_post($faccess)) {
		if (has_perm('override-closed') && $thread['closed'])
			$topbot['actions'] = [['title' => 'Thread closed'],['href' => "newreply.php?id=$tid", 'title' => 'New reply']];
		else if ($thread['closed'])
			$topbot['actions'] = [['title' => 'Thread closed']];
		else
			$topbot['actions'] = [['href' => "newreply.php?id=$tid", 'title' => 'New reply']];
	}
} elseif ($viewmode == "user") {
	$topbot = [
		'breadcrumb' => [['href' => './', 'title' => 'Main'], ['href' => "profile.php?id=$uid", 'title' => $user['name']]],
		'title' => 'Posts'
	];
} elseif ($viewmode == "time") {
	$topbot = [];
	$time = $_GET['time'];
} else {
	noticemsg("Error", "Thread does not exist.<br><a href=./>Back to main</a>");
	pagefooter();
	die();
}

ob_start();

$modlinks = '';
if (isset($tid) && (can_edit_forum_threads($thread['forum']) || ($userdata['id'] == $thread['user'] && !$thread['closed'] && has_perm('rename-own-thread')))) {
	$link = "<a href=javascript:submitmod";
	if (can_edit_forum_threads($thread['forum'])) {
		$stick = ($thread['sticky'] ? "$link('unstick')>Unstick</a>" : "$link('stick')>Stick</a>");
		$stick2 = addcslashes($stick, "'");

		$close = '| ' . ($thread['closed'] ? "$link('open')>Open</a>" : "$link('close')>Close</a>");
		$close2 = addcslashes($close, "'");

		$trash = '| ' . ($thread['forum'] != $trashid ? '<a href=javascript:submitmod(\'trash\') onclick="trashConfirm(event)">Trash</a>' : '');
		$trash2 = addcslashes($trash, "'");

		$edit = '| <a href="javascript:showrbox()">Rename</a> | <a href="javascript:showmove()">Move</a>';

		$r = $sql->query("SELECT c.title ctitle,f.id,f.title,f.cat,f.private FROM forums f LEFT JOIN categories c ON c.id=f.cat ORDER BY c.ord,c.id,f.ord,f.id");
		$fmovelinks = '<select id="forumselect">';
		$c = -1;
		while ($d = $r->fetch()) {
			if (!can_view_forum($d))
				continue;

			if ($d['cat'] != $c) {
				if ($c != -1)
					$fmovelinks .= '</optgroup>';
				$c = $d['cat'];
				$fmovelinks .= '<optgroup label="' . $d['ctitle'] . '">';
			}
			$fmovelinks .= sprintf(
				'<option value="%s"%s>%s</option>',
			$d['id'], ($d['id'] == $thread['forum'] ? ' selected="selected"' : ''), $d['title']);
		}
		$fmovelinks.="</optgroup></select>";
		$fmovelinks = addslashes($fmovelinks);
		$fmovelinks.='<input type="submit" id="move" value="Submit" name="movethread" onclick="submitmove(movetid())">';
		$fmovelinks.='<input type="button" value="Cancel" onclick="hidethreadedit(); return false;">';
	} else {
		$fmovelinks = $close = $stick = $trash = '';
		$edit = '<a href=javascript:showrbox()>Rename</a>';
	}

	$renamefield = '<input type="text" name="title" id="title" size=60 maxlength=255 value="'.esc($thread['title']).'">';
	$renamefield.= '<input type="submit" name="submit" value="Rename" onclick="submitmod(\'rename\')">';
	$renamefield.= '<input type="button" value="Cancel" onclick="hidethreadedit(); return false">';
	$renamefield = addcslashes($renamefield, "'"); //because of javascript, single quotes will gum up the works

	$threadtitle = addcslashes(htmlentities($thread['title'], ENT_COMPAT | ENT_HTML401, 'UTF-8'), "'");

	$modlinks = <<<HTML
<form action="thread.php" method="post" name="mod" id="mod">
<table class="c1"><tr class="n2">
	<td class="b n2">
		<span id="moptions">Thread options: $stick $close $trash $edit </span>
		<span id="mappend"></span>
		<span id="canceledit"></span>
		<script>
function submitmod(act){
	document.getElementById('action').value=act;
	document.getElementById('mod').submit();
}
function submitrename(name){
	document.mod.arg.value=name;
	submitmod('rename')
}
function submitmove(fid){
	document.mod.arg.value=fid;
	submitmod('move')
}
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
function submit_on_return(event,act){
	a=event.keyCode?event.keyCode:event.which?event.which:event.charCode;
	document.mod.action.value=act;
	document.mod.arg.value=document.mod.tmp.value;
	if (a==13) document.mod.submit();
}
function hidethreadedit() {
	document.getElementById('moptions').innerHTML = 'Thread options: $stick2 $close2 $trash2 $edit';
	document.getElementById('mappend').innerHTML = '<input type=hidden name=tmp style="width:80%!important;border-width:0px!important;padding:0px!important" onkeypress="submit_on_return(event,\'rename\')" value="$threadtitle" maxlength="100">';
	document.getElementById('canceledit').style.display = 'none';
}
function movetid() {
	var x = document.getElementById('forumselect').selectedIndex;
	document.getElementById('move').innerHTML = document.getElementsByTagName('option')[x].value;
	return document.getElementsByTagName('option')[x].value;
}
function renametitle() {
	var x = document.getElementById('title').value;
	document.getElementById('rename').innerHTML = document.getElementsByTagName('input')[x].value;
	return document.getElementsByTagName('input')[x].value;
}
function trashConfirm(e) {
	if (confirm('Are you sure you want to trash this thread?'));
	else {
		e.preventDefault();
	}
}
		</script>
		<input type=hidden id="arg" name="arg" value="">
		<input type=hidden id="id" name="id" value="$tid">
		<input type=hidden id="action" name="action" value="">
	</td>
</table>
</form>
HTML;
}

RenderPageBar($topbot);

if (isset($time)) {
	?><table class="c1" style="width:auto">
		<tr class="h"><td class="b">Latest Posts</td></tr>
		<tr><td class="b n1 center">
			<a href="forum.php?time=<?=$time ?>">By Threads</a> | By Posts</a><br><br>
			<?=timelink(900,'thread').' | '.timelink(3600,'thread').' | '.timelink(86400,'thread').' | '.timelink(604800,'thread') ?>
		</td></tr>
	</table><?php
}

echo "$modlinks $pagelist";

if ($posts) echo '<br>';

for ($i = 1; $post = $posts->fetch(); $i++) {
	if (isset($post['fid'])) {
		if (!can_view_forum(['id' => $post['fid'], 'private' => $post['fprivate']]))
			continue;
	}
	if (isset($uid) || isset($time)) {
		$pthread['id'] = $post['tid'];
		$pthread['title'] = $post['ttitle'];
	}
	if (!isset($_GET['pin']) || $post['id'] != $_GET['pin']) {
		$post['maxrevision'] = $post['revision']; // not pinned, hence the max. revision equals the revision we selected
	} else {
		$post['maxrevision'] = $sql->result("SELECT MAX(revision) FROM poststext WHERE id = ?", [$_GET['pin']]);
	}
	if (isset($thread['forum']) && can_edit_forum_posts($thread['forum']) && isset($_GET['pin']) && $post['id'] == $_GET['pin'])
		$post['deleted'] = false;

	echo "<br>".threadpost($post);
}

if_empty_query($i, "No posts were found.", 0, true);

echo "$pagelist" . (!isset($time) ? '<br>' : '');

if (isset($thread['id']) && can_create_forum_post($faccess) && !$thread['closed']) {
	?><table class="c1">
<form action="newreply.php" method="post">
	<tr class="h"><td class="b h" colspan=2>Quick Reply</a></td>
	<tr>
		<td class="b n1 center" width=120>Format:</td>
		<td class="b n2"><?=posttoolbar() ?></td>
	</tr><tr>
		<td class="b n1 center" width=120>Reply:</td>
		<td class="b n2"><textarea wrap="virtual" name="message" id="message" rows=8 cols=80></textarea></td>
	</tr><tr class="n1">
		<td class="b"></td>
		<td class="b">
			<input type="hidden" name="tid" value="<?=$tid ?>">
			<input type="submit" name="action" value="Submit">
			<input type="submit" name="action" value="Preview">
		</td>
	</tr>
</form></table><br><?php
}

RenderPageBar($topbot);

$content = ob_get_contents();
ob_end_clean();

$twig = _twigloader();
echo $twig->render('_legacy.twig', [
	'page_title' => $title,
	'content' => $content
]);
