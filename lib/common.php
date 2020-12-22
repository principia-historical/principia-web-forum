<?php
if (!file_exists('lib/config.php')) {
	die('Please install Acmlmboard.');
}

$start = microtime(true);

$rankset_names = ['None'];
$gender = ['Male', 'Female', 'N/A'];

foreach (glob("lib/*.php") as $filename)
	if ($filename != 'lib/config.sample.php')
		require_once($filename);

header("Content-type: text/html; charset=utf-8");

$userip = $_SERVER['REMOTE_ADDR'];
$url = getenv("SCRIPT_NAME");
if ($q = getenv("QUERY_STRING")) $url .= "?$q";

$log = false;
$logpermset = [];

if (!empty($_COOKIE['acmlm_user']) && !empty($_COOKIE['acmlm_pass'])) {
	if ($user = checkuid($_COOKIE['acmlm_user'], unpacklcookie($_COOKIE['acmlm_pass']))) {
		$log = true;
		$loguser = $user;
		load_user_permset();
	} else {
		setcookie('acmlm_user',0);
		setcookie('acmlm_pass','');
		load_guest_permset();
	}
} else {
	load_guest_permset();
}

if ($lockdown) {
	if (has_perm('bypass-lockdown'))
		echo '<span style="color:red"><center>LOCKDOWN!!</center></span>';
	else {
		echo <<<HTML
<body style="background-color:#C02020;padding:5em;color:#ffffff;margin:auto;">
	Access to the board has been restricted by the administration.
	Please forgive any inconvenience caused and stand by until the underlying issues have been resolved.
</body>
HTML;
		die();
	}
}

if (!$log) {
	$loguser = [];
	$loguser['id'] = 0;
	$loguser['timezone'] = "UTC";
	$loguser['dateformat'] = "Y-m-d";
	$loguser['timeformat'] = "H:i";
	$loguser['theme'] = $defaulttheme;
	$loguser['ppp'] = 20;
	$loguser['tpp'] = 20;
}

date_default_timezone_set($loguser['timezone']);

if ($loguser['ppp'] < 1) $loguser['ppp'] = 20;
if ($loguser['tpp'] < 1) $loguser['tpp'] = 20;

//Unban users whose tempbans have expired.
$sql->query("UPDATE users SET group_id = ?, title = '', tempbanned = 0 WHERE tempbanned < ? AND tempbanned > 0", [$defaultgroup, time()]);

$dateformat = $loguser['dateformat'].' '.$loguser['timeformat'];

if (str_replace($botlist, "x", strtolower($_SERVER['HTTP_USER_AGENT'])) != strtolower($_SERVER['HTTP_USER_AGENT'])) {
	$bot = 1;
	load_bot_permset();
} else {
	$bot = 0;
}

if ($log) {
	$sql->query("UPDATE users SET lastview = ?, ip = ?, url = ? WHERE id = ?",
		[time(), $userip, addslashes($url), $loguser['id']]);
}
$count = $sql->fetch("SELECT (SELECT COUNT(*) FROM users) u, (SELECT COUNT(*) FROM threads) t, (SELECT COUNT(*) FROM posts) p");
$date = date("m-d-y", time());

//Config definable theme override
if ($override_theme) {
	$theme = $override_theme;
} elseif (isset($_GET['theme'])) {
	$theme = $_GET['theme'];
} else {
	$theme = $loguser['theme'];
}

if (is_file("theme/$theme/$theme.css")) {
	$themefile = "$theme.css";
} else {
	$theme = '0';
	$themefile = "$theme.css";
}

/**
 * Print page header
 *
 * @param string $pagetitle Title of page.
 * @param integer $fid Forum ID of the page.
 * @return void
 */
function pageheader($pagetitle = '', $fid = null) {
	global $log, $loguser, $boardtitle, $theme, $themefile, $favicon;

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
			if (has_perm("update-own-profile"))
				$links[] = ['url' => "editprofile.php", 'title' => 'Edit profile'];
			if (has_perm('manage-board'))
				$links[] = ['url' => 'management.php', 'title' => 'Management'];
		}
	}

	?><!DOCTYPE html>
	<html>
		<head>
			<meta charset="utf-8">
			<title><?=$pagetitle.$boardtitle?></title>
			<link rel="icon" type="image/png" href="<?=$favicon?>">
			<link rel="stylesheet" href="../assets/css/style.css" type="text/css">
			<link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
			<link rel="stylesheet" href="theme/common.css">
			<link rel="stylesheet" href="theme/<?=$theme?>/<?=$themefile?>">
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
					<li><a href="../forum.php" class="btn">Forum</a></li>
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
/*function pageheader($pagetitle = '', $fid = null) {
	global $dateformat, $sql, $log, $loguser, $views, $boardtitle,
	$theme, $themefile, $meta, $favicon, $count, $bot;

	if (isset($fid)) {
		$count['d'] = $sql->result("SELECT COUNT(*) FROM posts WHERE date > ?", [(time() - 86400)]);
		$count['h'] = $sql->result("SELECT COUNT(*) FROM posts WHERE date > ?", [(time() - 86400)]);
		$lastuser = $sql->fetch("SELECT ".userfields()." FROM users ORDER BY id DESC LIMIT 1");

		$onuserlist = "$onusercount user" . ($onusercount != 1 ? 's' : '') . ' online' . ($onusercount > 0 ? ': ' : '') . $onuserlist;

		?><table class="c1">
			<tr><td class="b n1">
				<table style="width:100%"><tr>
					<td class="nb" width="170"></td>
					<td class="nb center"><span class="white-space:nowrap">
						<?=$count['t'] ?> threads and <?=$count['p'] ?> posts total.<br><?=$count['d'] ?> new posts
						today, <?=$count['h'] ?> last hour.<br>
					</span></td>
					<td class="nb right" width="170">
						<?=$count['u'] ?> registered users<br> Newest: <?=userlink($lastuser) ?>
					</td>
				</tr></table>
			</td></tr>
		</table><br><?php
	}
}*/

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
	<a href="about">About</a><br><?=sprintf("Page rendered in %1.3f seconds. (%dKB of memory used)", $time, memory_get_usage(false) / 1024); ?>
</div>
<script type="text/javascript" src="assets/base.js"></script>
</body>
</html><?php
}

