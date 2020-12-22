<?php
if (isset($_GET['p'])) redirect("thread.php?pid={$_GET['p']}#{$_GET['p']}");
if (isset($_GET['t'])) redirect("thread.php?id={$_GET['t']}");
if (isset($_GET['u'])) redirect("profile.php?id={$_GET['u']}");

require('lib/common.php');

$action = isset($_GET['action']) ? $_GET['action'] : '';

//mark forum read
if ($log && $action == 'markread') {
	$fid = $_GET['fid'];
	if ($fid != 'all') {
		//delete obsolete threadsread entries
		$sql->query("DELETE r FROM threadsread r LEFT JOIN threads t ON t.id = r.tid WHERE t.forum = ? AND r.uid = ?", [$fid, $loguser['id']]);
		//add new forumsread entry
		$sql->query("REPLACE INTO forumsread VALUES (?,?,?)", [$loguser['id'], $fid, time()]);
	} else {
		//mark all read
		$sql->query("DELETE FROM threadsread WHERE uid=" . $loguser['id']);
		$sql->query("REPLACE INTO forumsread (uid,fid,time) SELECT " . $loguser['id'] . ",f.id," . time() . " FROM forums f");
	}
	redirect('index.php');
}

pageheader(null,0);

$categs = $sql->query("SELECT * FROM categories ORDER BY ord,id");
while ($c = $categs->fetch()) {
	$categ[$c['id']] = $c;
}

$forums = $sql->query("SELECT f.*, ".($log ? "r.time rtime, " : '').userfields('u', 'u')." "
		. "FROM forums f "
		. "LEFT JOIN users u ON u.id=f.lastuser "
		. "LEFT JOIN categories c ON c.id=f.cat "
		. ($log ? "LEFT JOIN forumsread r ON r.fid = f.id AND r.uid = ".$loguser['id'] : '')
		. " ORDER BY c.ord,c.id,f.ord,f.id", []);
$cat = -1;

?>
<table class="c1">
	<?=announcement_row(5) ?>
	<tr class="h">
		<td class="b h" width="17">&nbsp;</td>
		<td class="b h">Forum</td>
		<td class="b h" width="50">Threads</td>
		<td class="b h" width="50">Posts</td>
		<td class="b h" width="150">Last post</td>
	</tr>
<?php

while ($forum = $forums->fetch()) {
	if (!can_view_forum($forum)) continue;

	if ($forum['cat'] != $cat) {
		$cat = $forum['cat'];
		?><tr class="c"><td class="b" colspan="5"><?=$categ[$cat]['title'] ?></td></tr><?php
	}

	if ($forum['posts'] > 0 && $forum['lastdate'] > 0)
		$lastpost = sprintf(
			'<nobr>%s</nobr><br><span class=sfont>by %s<a href="thread.php?pid=%s#%s">&raquo;</a></span>',
		date($dateformat, $forum['lastdate']), userlink($forum, 'u'), $forum['lastid'], $forum['lastid']);
	else
		$lastpost = 'None';

	$status = ($forum['lastdate'] > ($log ? $forum['rtime'] : time() - 3600) ? rendernewstatus("n") : '');

	?><tr class="center">
		<td class="b n1"><?=$status ?></td>
		<td class="b n2 left">
			<?=($forum['private'] ? '(' : '') ?><a href="forum.php?id=<?=$forum['id'] ?>"><?=$forum['title'] ?></a><?=($forum['private'] ? ')' : '') ?>
			<br><span class="sfont"><?=str_replace("%%%RANDOM%%%", $randdesc[array_rand($randdesc)], $forum['descr']) ?></span>
		</td>
		<td class="b n1"><?=$forum['threads'] ?></td>
		<td class="b n1"><?=$forum['posts'] ?></td>
		<td class="b n2"><?=$lastpost ?></td>
	</tr><?php
}
?></table><?php
pagefooter();