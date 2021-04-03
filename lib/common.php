<?php
$start = microtime(true);

$rankset_names = ['None'];

// List of bots (web crawlers)
$botlist = ['ia_archiver','baidu','yahoo','bot','spider'];

// List of smilies
$smilies = [
	['text' => '-_-', 'url' => 'img/smilies/annoyed.gif'],
	['text' => 'o_O', 'url' => 'img/smilies/bigeyes.gif'],
	['text' => ':D', 'url' => 'img/smilies/biggrin.gif'],
	['text' => 'o_o', 'url' => 'img/smilies/blank.gif'],
	['text' => ':x', 'url' => 'img/smilies/crossmouth.gif'],
	['text' => ';_;', 'url' => 'img/smilies/cry.gif'],
	['text' => '^_^', 'url' => 'img/smilies/cute.gif'],
	['text' => '@_@', 'url' => 'img/smilies/dizzy.gif'],
	['text' => ':@', 'url' => 'img/smilies/dropsmile.gif'],
	['text' => 'O_O', 'url' => 'img/smilies/eek.gif'],
	['text' => '>:]', 'url' => 'img/smilies/evil.gif'],
	['text' => ':eyeshift:', 'url' => 'img/smilies/eyeshift.gif'],
	['text' => ':(', 'url' => 'img/smilies/frown.gif'],
	['text' => '8-)', 'url' => 'img/smilies/glasses.gif'],
	['text' => ':LOL:', 'url' => 'img/smilies/lol.gif'],
	['text' => '>:[', 'url' => 'img/smilies/mad.gif'],
	['text' => '<_<', 'url' => 'img/smilies/shiftleft.gif'],
	['text' => '>_>', 'url' => 'img/smilies/shiftright.gif'],
	['text' => 'x_x', 'url' => 'img/smilies/sick.gif'],
	['text' => ':|', 'url' => 'img/smilies/slidemouth.gif'],
	['text' => ':)', 'url' => 'img/smilies/smile.gif'],
	['text' => ':P', 'url' => 'img/smilies/tongue.gif'],
	['text' => ':B', 'url' => 'img/smilies/vamp.gif'],
	['text' => ';)', 'url' => 'img/smilies/wink.gif'],
	['text' => ':-3', 'url' => 'img/smilies/wobble.gif'],
	['text' => ':S', 'url' => 'img/smilies/wobbly.gif'],
	['text' => '>_<', 'url' => 'img/smilies/yuck.gif'],
	['text' => ':box:', 'url' => 'img/smilies/box.png'],
	['text' => ':yes:', 'url' => 'img/smilies/yes.png'],
	['text' => ':no:', 'url' => 'img/smilies/no.png'],
	['text' => 'OwO', 'url' => 'img/smilies/owo.png']
];

// Ranksets
require('img/ranks/rankset.php'); // Default (Mario) rankset

require('../conf/config.php'); // include principia-web config

foreach (glob("lib/*.php") as $filename)
	require_once($filename);

header("Content-type: text/html; charset=utf-8");

$userip = $_SERVER['REMOTE_ADDR'];

$log = false;
$logpermset = [];

// Authentication code.
if (isset($_COOKIE[$cookieName])) {
	$user_id = $sql->result("SELECT id FROM principia.users WHERE token = ?", [$_COOKIE[$cookieName]]);

	if ($user_id) {
		// Valid password cookie.
		$log = true;
		$loguser = $sql->fetch("SELECT * FROM principia.users WHERE id = ?", [$user_id]);
		load_user_permset();
	} else {
		// Invalid password cookie.
		$log = false;
		load_guest_permset();
	}
} else {
	// No password cookie.
	$log = false;
	load_guest_permset();
}

if (!$log) {
	$loguser = [];
	$loguser['id'] = 0;
}

// todo
$loguser['dateformat'] = "Y-m-d";
$loguser['timeformat'] = "H:i";
$loguser['ppp'] = 20;
$loguser['tpp'] = 20;
$loguser['timezone'] = 'UTC';
date_default_timezone_set($loguser['timezone']);

if ($loguser['ppp'] < 1) $loguser['ppp'] = 20;
if ($loguser['tpp'] < 1) $loguser['tpp'] = 20;

$dateformat = $loguser['dateformat'].' '.$loguser['timeformat'];

if (str_replace($botlist, "x", strtolower($_SERVER['HTTP_USER_AGENT'])) != strtolower($_SERVER['HTTP_USER_AGENT'])) {
	$bot = 1;
	load_bot_permset();
} else {
	$bot = 0;
}

if ($log) {
	$sql->query("UPDATE principia.users SET lastview = ?, ip = ? WHERE id = ?",
		[time(), $userip, $loguser['id']]);
}
$count = $sql->fetch("SELECT (SELECT COUNT(*) FROM users) u, (SELECT COUNT(*) FROM threads) t, (SELECT COUNT(*) FROM posts) p");
$date = date("m-d-y", time());

/**
 * Print page header
 *
 * @param string $pagetitle Title of page.
 * @param integer $fid Forum ID of the page.
 * @return void
 */
function pageheader($pagetitle = '', $fid = null) {
	global $log, $loguser;

	if ($log) {
		if ($fid && is_numeric($fid))
			$markread = '<a href="./?action=markread&fid='.$fid.'">Mark forum read</a>';
		else
			$markread = '<a href="./?action=markread&fid=all">Mark all forums read</a>';

		$links = [];

		$links[] = ['url' => "./", 'title' => 'Forum'];
		$links[] = ['url' => "activeusers.php", 'title' => 'Active users'];
		$links[] = ['url' => "thread.php?time=86400", 'title' => 'Latest posts'];

		if ($log) {
			if (has_perm('view-own-pms'))
				$links[] = ['url' => "private.php", 'title' => 'Private messages'];
			if (has_perm('manage-board'))
				$links[] = ['url' => 'management.php', 'title' => 'Management'];
		}
	}

	?><!DOCTYPE html>
	<html>
		<head>
			<meta charset="utf-8">
			<title>Principia - <?=$pagetitle?></title>
			<link rel="icon" type="image/png" href="theme/fav.png">
			<link rel="stylesheet" href="../assets/css/style.css" type="text/css">
			<link rel="stylesheet" href="../assets/css/darkmodehack.css" type="text/css">
			<link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
			<link rel="stylesheet" href="theme/common.css">
			<link rel="stylesheet" href="theme/principia/principia.css">
			<script src="lib/js/microlight.js"></script>
			<script src="lib/js/tools.js"></script>
		</head>
		<body>
			<div class="top">
				<a href="../"><img class="picon" src="../assets/icon.png"/></a>
				<ul class="menu left">
					<li><a href="../selected.php" class="btn">Selected</a></li>
					<li><a href="../top.php" class="btn">Top</a></li>
					<li><a href="../latest.php" class="btn">New</a></li>
					<li><a href="../chat.php" class="btn">Chat</a></li>
					<li><a href="./" class="btn">Forum</a></li>
					<li><a href="../contests.php" class="btn">Contests</a></li>
					<li><a href="../download.php" class="btn download">Download</a></li>
					<li><a href="../search.php" class="btn"><img src="../assets/icons/search.svg" class="search"></a></li>
				</ul>
				<ul class="menu right">
					<li><em><?=($log ? userlink($loguser) : '<a href="../login.php">Login</a>')?></em></li>
				</ul>
			</div>
			<div class="top">
				<ul class="menu left">
					<?php foreach ($links as $link) { ?>
						<li><a href="<?=$link['url']?>" class="btn"><?=$link['title']?></a></li>
					<?php } ?>
				</ul>
				<ul class="menu right">
					<li><?=$markread?></li>
				</ul>
			</div><br>
			<div class="home content forum">
	<?php
}

/**
 * Print a notice message.
 *
 * @param string $name Header text
 * @param string $msg Message
 * @param bool $error Is it an error? (Break the page loading)
 * @return void
 */
function noticemsg($name, $msg, $error = false) {
	if ($error) {
		pageheader('Error');
	}
	?><table class="c1">
		<tr class="h"><td class="b h center"><?=$name ?></td></tr>
		<tr><td class="b n1 center"><?=$msg ?><?=($error ? '<br><a href="./">Back to main</a>' : '') ?></td></tr>
	</table><?php
	if ($error) {
		pagefooter(); die();
	}
}

/**
 * Print page footer.
 *
 * @return void
 */
function pagefooter() {
	global $start;
	$time = microtime(true) - $start;
	?></div><br><div class="footer">
	<a href="../about.php">About</a><br><?=sprintf("Page rendered in %1.3f seconds. (%dKB of memory used)", $time, memory_get_usage(false) / 1024); ?>
</div>
<script type="text/javascript" src="assets/base.js"></script>
</body>
</html><?php
}

