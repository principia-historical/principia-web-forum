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

if (!empty($_COOKIE['user']) && !empty($_COOKIE['pass'])) {
	if ($user = checkuid($_COOKIE['user'], unpacklcookie($_COOKIE['pass']))) {
		$log = true;
		$loguser = $user;
		load_user_permset();
	} else {
		setcookie('user',0);
		setcookie('pass','');
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
dobirthdays(); //Called here to account for timezone bugs.

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

$sql->query("DELETE FROM guests WHERE ip = ? OR date < ?", [$userip, (time() - 300)]);
if ($log) {
	$sql->query("UPDATE users SET lastview = ?, ip = ?, url = ? WHERE id = ?",
		[time(), $userip, addslashes($url), $loguser['id']]);
} else {
	$sql->query("INSERT INTO guests (date, ip, bot) VALUES (?,?,?)", [time(),$userip,$bot]);
}

if (!$bot) {
	$sql->query("UPDATE misc SET views = views + 1");
} else {
	$sql->query("UPDATE misc SET botviews = botviews + 1");
}

$views = $sql->result("SELECT views FROM misc");

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

$sql->query("DELETE FROM ipbans WHERE expires < ? AND expires > 0", [time()]);

$r = $sql->fetch("SELECT * FROM ipbans WHERE ? LIKE ipmask", [$userip]);
if ($r) {
	if ($r['hard']) {
		pageheader('IP banned');
		echo '<table class="c1"><tr class="n2"><td class="b n1 center">Sorry, but your IP address has been banned.</td></tr></table>';
		pagefooter();
		die();
	} else if (!$r['hard'] && (!$log || $loguser['group_id'] == $bannedgroup)) {
		if (!strstr($_SERVER['PHP_SELF'], "login.php")) {
			pageheader('IP restricted');
			echo '<table class="c1"><tr class="n2"><td class="b n1 center">Access from your IP address has been limited.<br><a href=login.php>Login</a></table>';
			pagefooter();
			die();
		}
	}
}

/**
 * Print page header
 *
 * @param string $pagetitle Title of page.
 * @param integer $fid Forum ID of the page.
 * @return void
 */
function pageheader($pagetitle = '', $fid = null) {
	global $dateformat, $sql, $log, $loguser, $views, $boardtitle, $boardlogo,
	$theme, $themefile, $meta, $favicon, $count, $bot;

	if ($log) {
		$sql->query("UPDATE users SET lastforum = ? WHERE id = ?", [($fid == null ? 0 : $fid), $loguser['id']]);
	} else {
		$sql->query("UPDATE guests SET lastforum = ? WHERE ip = ?", [($fid == null ? 0 : $fid), $_SERVER['REMOTE_ADDR']]);
	}

	if ($pagetitle) $pagetitle .= " - ";

	$t = $sql->result("SELECT attention FROM misc");

	if ($t != '')
		$extratitle = <<<HTML
<table class="c1 center" width="100%">
	<tr class="h"><td class="b h">News</td></tr>
	<tr class="n1 center"><td class="b sfont">$t</td></tr>
</table>
HTML;

	if (isset($extratitle)) {
		$boardlogo = <<<HTML
<table width="100%"><tr class="center">
	<td class="nb" valign="center">$boardlogo</td>
	<td class="nb" valign="center" width="300">$extratitle</td>
</tr></table>
HTML;
	}

	?><!DOCTYPE html>
<html>
	<head>
		<title><?=$pagetitle.$boardtitle?></title>
		<?=$meta?>
		<link rel="icon" type="image/png" href="<?=$favicon?>">
		<link rel="stylesheet" href="theme/common.css">
		<link rel="stylesheet" href="theme/<?=$theme?>/<?=$themefile?>">
		<script src="lib/js/microlight.js"></script>
		<script src="lib/js/tools.js"></script>
	</head>
	<body>
		<table class="c1">
			<tr class="nt n2 center"><td class="b n1 center" colspan="3"><?=$boardlogo?></td></tr>
			<tr class="n2 center">
				<td class="b"><div style="width: 150px">Views: <?=number_format($views) ?></div></td>
				<td class="b" width="100%">
					<a href="./">Main</a>
					| <a href="faq.php">FAQ</a>
					| <a href="memberlist.php">Memberlist</a>
					| <a href="activeusers.php">Active users</a>
					| <a href="thread.php?time=86400">Latest posts</a>
					| <a href="ranks.php">Ranks</a>
					| <a href="online.php">Online users</a>
					| <a href="search.php">Search</a>
				</td>
				<td class="b"><div style="width: 150px"><?=date($dateformat, time())?></div></td>
				<tr class="n1 center"><td class="b" colspan="3"><?=($log ? userlink($loguser) : 'Not logged in ')?>
<?php
	if ($log && has_perm('view-own-pms')) {
		$unreadpms = $sql->result("SELECT COUNT(*) FROM pmsgs WHERE userto = ? AND unread = 1 AND del_to = 0", [$loguser['id']]);

		printf(
			' <a href="private.php"><img src="img/pm%s.png" width="20" alt="Private messages"></a> %s ',
		(!$unreadpms ? '-off' : ''), ($unreadpms ? "($unreadpms new)" : ''));
	}

	if ($fid && is_numeric($fid))
		$markread = ['url' => "index.php?action=markread&fid=$fid", 'title' => "Mark forum read"];
	else
		$markread = ['url' => "index.php?action=markread&fid=all", 'title' => "Mark all forums read"];

	$userlinks = [];

	if (!$log) {
		if (!$bot) {
			$userlinks[] = ['url' => "register.php", 'title' => 'Register'];
			$userlinks[] = ['url' => "login.php", 'title' => 'Login'];
		}
	} else {
		$userlinks[] = ['url' => "javascript:document.logout.submit()", 'title' => 'Logout'];
	}
	if ($log) {
		if (has_perm("update-own-profile"))
			$userlinks[] = ['url' => "editprofile.php", 'title' => 'Edit profile'];
		if (has_perm('manage-board'))
			$userlinks[] = ['url' => 'management.php', 'title' => 'Management'];
		$userlinks[] = $markread;
	}

	foreach ($userlinks as $v) {
		echo " | <a href=\"{$v['url']}\">{$v['title']}</a>";
	}

	echo "</td></table>";
	if ($log) {
		?><form action="login.php" method="post" name="logout">
			<input type="hidden" name="action" value="logout">
		</form><?php
	}

	echo '<br>';

	if ($fid || $fid == 0) {
		$onusers = $sql->query("SELECT ".userfields().",lastpost,lastview FROM users WHERE lastview > ? ".($fid != 0 ? " AND lastforum =".$fid : '')." ORDER BY name",
			[(time()-300)]);
		$onuserlist = '';
		$onusercount = 0;
		while ($user = $onusers->fetch()) {
			$onuserlist.=($onusercount ? ', ' : '') . userlink($user);
			$onusercount++;
		}

		$result = $sql->query("SELECT COUNT(*) guest_count, SUM(bot) bot_count FROM guests WHERE date > ?".($fid != 0 ? " AND lastforum =".$fid : ''),
			[(time()-300)]);

		while ($data = $result->fetch()) {
			$numbots = $data['bot_count'];
			$numguests = $data['guest_count'] - $numbots;

			if ($numguests)	$onuserlist .= " | $numguests guest" . ($numguests != 1 ? "s" : '');
			if ($numbots)	$onuserlist .= " | $numbots bot" . ($numbots != 1 ? "s" : '');
		}
	}

	if ($fid) {
		$fname = $sql->result("SELECT title FROM forums WHERE id = ?", [$fid]);
		$onuserlist = "$onusercount user" . ($onusercount != 1 ? "s" : '') . " currently in $fname" . ($onusercount > 0 ? ": " : '') . $onuserlist;

		?><table class="c1"><tr class="n1"><td class="b n1 center"><?=$onuserlist ?></td></tr></table><br><?php
	} else if (isset($fid) && $fid == 0) {
		$birthdaylimit = 86400 * 30;
		$rbirthdays = $sql->query("SELECT birth, ".userfields()." FROM users WHERE birth LIKE ? AND lastview > ? ORDER BY name",
			[date('m-d').'%', (time() - $birthdaylimit)]);
		$birthdays = [];
		while ($user = $rbirthdays->fetch()) {
			$b = explode('-', $user['birth']);
			if ($b['2'] <= 0 && $b['2'] > -2) {
				$y = '';
			} else {
				$y = "(" . (date("Y") - $b['2']) . ")";
			}

			$birthdays[] = userlink($user) . " " . $y;
		}

		$birthdaybox = '';
		if (count($birthdays)) {
			$birthdaystoday = implode(", ", $birthdays);
			$birthdaybox = "<tr class=\"n1 center\"><td class=\"b n2 center\">Birthdays today: $birthdaystoday</td></tr>";
		}

		$count['d'] = $sql->result("SELECT COUNT(*) FROM posts WHERE date > ?", [(time() - 86400)]);
		$count['h'] = $sql->result("SELECT COUNT(*) FROM posts WHERE date > ?", [(time() - 86400)]);
		$lastuser = $sql->fetch("SELECT ".userfields()." FROM users ORDER BY id DESC LIMIT 1");

		$onuserlist = "$onusercount user" . ($onusercount != 1 ? 's' : '') . ' online' . ($onusercount > 0 ? ': ' : '') . $onuserlist;

		?><table class="c1">
			<?=$birthdaybox ?>
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
			<tr><td class="b n2 center"><?=$onuserlist ?></td></tr>
		</table><br><?php
	}
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
	?><br>
	<table class="c1">
		<tr>
			<td class="b n2 sfont">
				<span style="float:right; text-align:right;">
					<?=sprintf("Page rendered in %1.3f seconds. (%dKB of memory used)", $time, memory_get_usage(false) / 1024); ?>
				</span>
				<a href="http://github.com/rasmusolle/acmlmboard"><img src="img/poweredbyacmlm.png" title="Acmlmboard 2" style="float:left; margin-right:4px;"></a>
				Acmlmboard v2.5.3MOD<br>
				&copy; 2005-2019 Acmlm, Emuz, <a href="credits.php">et al</a>.
			</td>
		</tr>
	</table><?php
}
