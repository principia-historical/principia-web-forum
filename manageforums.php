<?php
require('lib/common.php');

if ($userdata['powerlevel'] < 3) error('403', 'You have no permissions to do this!');

$error = '';

if (isset($_POST['savecat'])) {
	// save new/existing category
	$cid = $_GET['cid'];
	$title = $_POST['title'];
	$ord = (int)$_POST['ord'];
	if (!trim($title))
		$error = 'Please enter a title for the category.';
	else {
		if ($cid == 'new') {
			$cid = result("SELECT MAX(id) FROM z_categories");
			if (!$cid) $cid = 0;
			$cid++;
			query("INSERT INTO z_categories (id,title,ord) VALUES (?,?,?)", [$cid, $title, $ord]);
		} else {
			$cid = (int)$cid;
			if (!result("SELECT COUNT(*) FROM z_categories WHERE id=?",[$cid])) redirect('manageforums.php');
			query("UPDATE z_categories SET title = ?, ord = ? WHERE id = ?", [$title, $ord, $cid]);
		}
		redirect('manageforums.php?cid='.$cid);
	}
} else if (isset($_POST['delcat'])) {
	// delete category
	$cid = (int)$_GET['cid'];
	query("DELETE FROM z_categories WHERE id = ?",[$cid]);

	redirect('manageforums.php');
} else if (isset($_POST['saveforum'])) {
	// save new/existing forum
	$fid = $_GET['fid'];
	$cat = (int)$_POST['cat'];
	$title = $_POST['title'];
	$descr = $_POST['descr'];
	$ord = (int)$_POST['ord'];

	$minread = (int)$_POST['minread'];
	$minthread = (int)$_POST['minthread'];
	$minreply = (int)$_POST['minreply'];

	if (!trim($title))
		$error = 'Please enter a title for the forum.';
	else {
		if ($fid == 'new') {
			$fid = result("SELECT MAX(id) FROM z_forums");
			if (!$fid) $fid = 0;
			$fid++;
			query("INSERT INTO z_forums (id,cat,title,descr,ord) VALUES (?,?,?,?,?,?,?)",
				[$fid, $cat, $title, $descr, $ord]);
		} else {
			$fid = (int)$fid;
			if (!result("SELECT COUNT(*) FROM z_forums WHERE id=?",[$fid]))
				redirect('manageforums.php');
			query("UPDATE z_forums SET cat=?, title=?, descr=?, ord=?, minread=?, minthread=?, minreply=? WHERE id=?",
				[$cat, $title, $descr, $ord, $minread, $minthread, $minreply, $fid]);
		}
		redirect('manageforums.php?fid='.$fid);
	}
} else if (isset($_POST['delforum'])) {
	// delete forum
	$fid = (int)$_GET['fid'];
	query("DELETE FROM z_forums WHERE id=?",[$fid]);
	redirect('manageforums.php');
}

if ($error) error("Error", $error);

ob_start();

if (isset($_GET['cid']) && $cid = $_GET['cid']) {
	// category editor
	if ($cid == 'new') {
		$cat = ['id' => 0, 'title' => '', 'ord' => 0];
	} else {
		$cid = (int)$cid;
		$cat = fetch("SELECT * FROM z_categories WHERE id=?",[$cid]);
	}
	?><form action="" method="POST">
		<table class="c1">
			<tr class="h"><td class="b h" colspan="2"><?=($cid == 'new' ? 'Create' : 'Edit') ?> category</td></tr>
			<tr>
				<td class="b n1 center">Title:</td>
				<td class="b n2"><input type="text" name="title" value="<?=esc($cat['title']) ?>" size="50" maxlength="500"></td>
			</tr><tr>
				<td class="b n1 center">Display order:</td>
				<td class="b n2"><input type="text" name="ord" value="<?=$cat['ord'] ?>" size="4" maxlength="10"></td>
			</tr>
			<tr class="h"><td class="b h" colspan="2">&nbsp;</td></tr>
			<tr>
				<td class="b n1 center"></td>
				<td class="b n2">
					<input type="submit" name="savecat" value="Save category">
						<?=($cid == 'new' ? '' : '<input type="submit" name="delcat" value="Delete category" onclick="if (!confirm("Really delete this category?")) return false;"> ') ?>
					<button type="button" id="back" onclick="window.location='manageforums.php';">Back</button>
				</td>
			</tr>
		</table>
	</form><?php
} else if (isset($_GET['fid']) && $fid = $_GET['fid']) {
	// forum editor
	if ($fid == 'new') {
		$forum = [
			'id' => 0, 'cat' => 1, 'title' => '', 'descr' => '',
			'ord' => 0,
			'minread' => -1, 'minthread' => 1, 'minreply' => 1];
	} else {
		$fid = (int)$fid;
		$forum = fetch("SELECT * FROM z_forums WHERE id=?",[$fid]);
	}
	$qcats = query("SELECT id,title FROM z_categories ORDER BY ord, id");
	$cats = [];
	while ($cat = $qcats->fetch())
		$cats[$cat['id']] = $cat['title'];
	$catlist = fieldselect('cat', $forum['cat'], $cats);

	?><form action="" method="POST">
		<table class="c1">
			<tr class="h"><td class="b h" colspan="2"><?=($fid == 'new' ? 'Create' : 'Edit') ?> forum</td></tr>
			<tr>
				<td class="b n1 center">Title:</td>
				<td class="b n2"><input type="text" name="title" value="<?=esc($forum['title']) ?>" size="50" maxlength="500"></td>
			</tr><tr>
				<td class="b n1 center">Description:<br><small>HTML allowed.</small></td>
				<td class="b n2"><textarea wrap="virtual" name="descr" rows="3" cols="50"><?=esc($forum['descr']) ?></textarea></td>
			</tr><tr>
				<td class="b n1 center">Category:</td>
				<td class="b n2"><?=$catlist ?></td>
			</tr><tr>
				<td class="b n1 center">Display order:</td>
				<td class="b n2"><input type="text" name="ord" value="<?=$forum['ord'] ?>" size="4" maxlength="10"></td>
			</tr>
			<tr class="h"><td class="b h" colspan="2">Permissions</td></tr>
			<tr>
				<td class="b n1 center">Who can view:</td>
				<td class="b n2"><?=fieldselect('minread', $forum['minread'], $powerlevels) ?></td>
			</tr><tr>
				<td class="b n1 center">Who can make threads:</td>
				<td class="b n2"><?=fieldselect('minthread', $forum['minthread'], $powerlevels) ?></td>
			</tr><tr>
				<td class="b n1 center">Who can reply:</td>
				<td class="b n2"><?=fieldselect('minreply', $forum['minreply'], $powerlevels) ?></td>
			</tr>
			<tr class="h"><td class="b h" colspan="2">&nbsp;</td></tr>
			<tr>
				<td class="b n1 center"></td>
				<td class="b n2">
					<input type="submit" name="saveforum" value="Save forum">
					<?=($fid == 'new' ? '' : '<input type="submit" name="delforum" value="Delete forum" onclick="if (!confirm("Really delete this forum?")) return false;">') ?>
					<button type="button" id="back" onclick="window.location='manageforums.php'">Back</button>
				</td>
			</tr>
		</table>
	</form><?php
} else {
	// main page -- category/forum listing

	$qcats = query("SELECT id,title FROM z_categories ORDER BY ord, id");
	$cats = [];
	while ($cat = $qcats->fetch())
		$cats[$cat['id']] = $cat;

	$qforums = query("SELECT f.id,f.title,f.cat FROM z_forums f LEFT JOIN z_categories c ON c.id=f.cat ORDER BY c.ord, c.id, f.ord, f.id");
	$forums = [];
	while ($forum = $qforums->fetch())
		$forums[$forum['id']] = $forum;

	$catlist = ''; $c = 1;
	foreach ($cats as $cat) {
		$catlist .= sprintf('<tr><td class="b n%s"><a href="manageforums.php?cid=%s">%s</a></td></tr>', $c, $cat['id'], $cat['title']);
		$c = ($c == 1) ? 2 : 1;
	}

	$forumlist = ''; $c = 1; $lc = -1;
	foreach ($forums as $forum) {
		if ($forum['cat'] != $lc) {
			$lc = $forum['cat'];
			$forumlist .= sprintf('<tr class="c"><td class="b c">%s</td></tr>', $cats[$forum['cat']]['title']);
		}
		$forumlist .= sprintf('<tr><td class="b n%s"><a href="manageforums.php?fid=%s">%s</a></td></tr>', $c, $forum['id'], $forum['title']);
		$c = ($c == 1) ? 2 : 1;
	}

	?><table class="fullwidth">
		<tr>
			<td class="nb" style="width:50%; vertical-align:top;">
				<table class="c1">
					<tr class="h"><td class="b">Categories</td></tr>
					<?=$catlist ?>
					<tr class="h"><td class="b">&nbsp;</td></tr>
					<tr><td class="b n1"><a href="manageforums.php?cid=new">New category</a></td></tr>
				</table>
			</td>
			<td class="nb" style="width:50%; vertical-align:top;">
				<table class="c1">
					<tr class="h"><td class="b">Forums</td></tr>
					<?=$forumlist ?>
					<tr class="h"><td class="b">&nbsp;</td></tr>
					<tr><td class="b n1"><a href="manageforums.php?fid=new">New forum</a></td></tr>
				</table>
			</td>
		</tr>
	</table><?php
}

$content = ob_get_contents();
ob_end_clean();

$twig = _twigloader();
echo $twig->render('_legacy.twig', [
	'page_title' => 'Forum management',
	'content' => $content
]);
