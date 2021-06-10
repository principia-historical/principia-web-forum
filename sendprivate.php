<?php
require('lib/common.php');

$action = (isset($_POST['action']) ? $_POST['action'] : null);

needs_login();

$topbot = [
	'breadcrumb' => [['href' => './', 'title' => 'Main'], ['href' => "private.php", 'title' => 'Private messages']],
	'title' => 'Send'
];

if (!has_perm('create-pms')) error('Error', 'You have no permissions to do this!');

// Submitting a PM
if ($action == 'Submit') {
	$userto = $sql->result("SELECT id FROM principia.users WHERE name LIKE ?", [$_POST['userto']]);

	if ($userto && $_POST['message']) {
		$recentpms = $sql->fetch("SELECT date FROM pmsgs WHERE date >= (UNIX_TIMESTAMP() - 30) AND userfrom = ?", [$userdata['id']]);
		$secafterpm = $sql->fetch("SELECT date FROM pmsgs WHERE date >= (UNIX_TIMESTAMP() - 2) AND userfrom = ?", [$userdata['id']]);
		if ($recentpms && (!has_perm('consecutive-posts'))) {
			$msg = "You can't send more than one PM within 30 seconds!";
		} else if ($secafterpm && (has_perm('consecutive-posts'))) {
			$msg = "You can't send more than one PM within 2 seconds!";
		} else {
			$sql->query("INSERT INTO pmsgs (date,userto,userfrom,title,text) VALUES (?,?,?,?,?)",
				[time(),$userto,$userdata['id'],$_POST['title'],$_POST['message']]);

			redirect("private.php");
		}
	} elseif (!$userto) {
		$msg = "That user doesn't exist!<br>Go back or <a href=sendprivate.php>try again</a>";
	} elseif (!$_POST['message']) {
		$msg = "You can't send a blank message!<br>Go back or <a href=sendprivate.php>try again</a>";
	}

	error("Error", $msg);
}

ob_start();

RenderPageBar($topbot);

// Default
if (!$action) {
	$userto = '';
	if (isset($_GET['pid']) && $pid = $_GET['pid']) {
		$post = $sql->fetch("SELECT u.name name, p.title, p.text "
			."FROM pmsgs p LEFT JOIN principia.users u ON p.userfrom = u.id "
			."WHERE p.id = ?" . (!has_perm('view-user-pms') ? " AND (p.userfrom=".$userdata['id']." OR p.userto=".$userdata['id'].")" : ''), [$pid]);
		if ($post) {
			$quotetext = '[reply="'.$post['name'].'" id="'.$pid.'"]'.$post['text'].'[/quote]' . PHP_EOL;
			$title = 'Re:' . $post['title'];
			$userto = $post['name'];
		}
	}

	if (isset($_GET['uid']) && $uid = $_GET['uid']) {
		$userto = $sql->result("SELECT u.name name FROM principia.users WHERE id = ?", [$uid]);
	} elseif (!isset($userto)) {
		$userto = $_POST['userto'];
	}
} else if ($action == 'Preview') { // Previewing PM
	$post['date'] = time();
	$post['num'] = 0;
	$post['text'] = $_POST['message'];
	foreach ($userdata as $field => $val)
		$post['u' . $field] = $val;
	$post['ulastpost'] = time();

	$userto = $_POST['userto'];
	$title = $_POST['title'];
	$quotetext = $_POST['message'];
	$topbot['title'] .= ' (Preview)';
	echo '<br><table class="c1"><tr class="h"><td class="b h" colspan="2">Message preview</table>'.threadpost($post);
}

?><br><form action="sendprivate.php" method="post">
	<table class="c1">
		<tr class="h"><td class="b h" colspan="2">Send message</td></tr>
		<tr>
			<td class="b n1 center" width="120">Send to:</td>
			<td class="b n2"><input type="text" name="userto" size="25" maxlength=25 value="<?=esc($userto) ?>"></td>
		</tr><tr>
			<td class="b n1 center">Title:</td>
			<td class="b n2"><input type="text" name="title" size="80" maxlength="255" value="<?=esc((isset($title) ? $title : '')) ?>"></td>
		</tr><tr>
			<td class="b n1 center" width="120">Format:</td>
			<td class="b n2"><?=posttoolbar() ?></td>
		</tr><tr>
			<td class="b n1 center"></td>
			<td class="b n2"><textarea name="message" id="message" rows="20" cols="80"><?=esc((isset($quotetext) ? $quotetext : '')) ?></textarea></td>
		</tr><tr>
			<td class="b n1"></td>
			<td class="b n1">
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
	'page_title' => 'Send private message',
	'content' => $content
]);
