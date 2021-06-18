<?php
require('lib/common.php');

$action = (isset($_POST['action']) ? $_POST['action'] : null);

needs_login();

$topbot = [
	'breadcrumb' => [['href' => './', 'title' => 'Main'], ['href' => "private.php", 'title' => 'Private messages']],
	'title' => 'Send'
];

if (!has_perm('create-pms')) error('403', 'You have no permissions to do this!');

// Submitting a PM
if ($action == 'Submit') {
	$userto = $sql->result("SELECT id FROM principia.users WHERE name LIKE ?", [$_POST['userto']]);

	if ($userto && $_POST['message']) {
		$recentpms = $sql->fetch("SELECT date FROM pmsgs WHERE date >= (UNIX_TIMESTAMP() - 30) AND userfrom = ?", [$userdata['id']]);
		if ($recentpms) {
			$error = "You can't send more than one PM within 30 seconds!";
		} else {
			$sql->query("INSERT INTO pmsgs (date,userto,userfrom,title,text) VALUES (?,?,?,?,?)",
				[time(),$userto,$userdata['id'],$_POST['title'],$_POST['message']]);

			redirect("private.php");
		}
	} elseif (!$userto) {
		$error = "That user doesn't exist!";
	} elseif (!$_POST['message']) {
		$error = "You can't send a blank message!";
	}
}

$userto = (isset($_POST['userto']) ? $_POST['userto'] : '');
$title = (isset($_POST['title']) ? $_POST['title'] : '');
$quotetext = (isset($_POST['message']) ? $_POST['message'] : '');

// Default
if (!$action) {
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
	$post['text'] = $_POST['message'];
	foreach ($userdata as $field => $val)
		$post['u' . $field] = $val;
	$post['ulastpost'] = time();

	$userto = $_POST['userto'];
	$title = $_POST['title'];
	$quotetext = $_POST['message'];
	$topbot['title'] .= ' (Preview)';
}

$twig = _twigloader();
echo $twig->render('sendprivate.twig', [
	'post' => (isset($post) ? $post : null),
	'userto' => $userto,
	'title' => $title,
	'quotetext' => $quotetext,
	'topbot' => $topbot,
	'action' => $action,
	'error' => $error
]);