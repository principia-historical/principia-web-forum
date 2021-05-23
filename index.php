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
		$sql->query("DELETE r FROM threadsread r LEFT JOIN threads t ON t.id = r.tid WHERE t.forum = ? AND r.uid = ?", [$fid, $userdata['id']]);
		//add new forumsread entry
		$sql->query("REPLACE INTO forumsread VALUES (?,?,?)", [$userdata['id'], $fid, time()]);
	} else {
		//mark all read
		$sql->query("DELETE FROM threadsread WHERE uid=" . $userdata['id']);
		$sql->query("REPLACE INTO forumsread (uid,fid,time) SELECT " . $userdata['id'] . ",f.id," . time() . " FROM forums f");
	}
	redirect('index.php');
}

$categs = $sql->query("SELECT * FROM categories ORDER BY ord,id");
while ($c = $categs->fetch()) {
	$categ[$c['id']] = $c;
}

$forums = $sql->query("SELECT f.*, ".($log ? "r.time rtime, " : '').userfields('u', 'u')." "
		. "FROM forums f "
		. "LEFT JOIN principia.users u ON u.id=f.lastuser "
		. "LEFT JOIN categories c ON c.id=f.cat "
		. ($log ? "LEFT JOIN forumsread r ON r.fid = f.id AND r.uid = ".$userdata['id'] : '')
		. " ORDER BY c.ord,c.id,f.ord,f.id", []);

$twig = _twigloader();
echo $twig->render('index.twig', [
	'forums' => $forums,
	'categories' => $categs
]);