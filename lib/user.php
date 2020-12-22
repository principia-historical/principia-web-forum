<?php

$userbirthdays = [];

function dobirthdays() { //Function for calling after we get the timezone for the user set
	global $sql, $userbirthdays;
	// Check for birthdays globally.
	// Makes stuff like checking for rainbow usernames a lot easier.
	$rbirthdays = $sql->query("SELECT id FROM users WHERE birth LIKE ?", [date('m-d').'%']);
	while ($bd = $rbirthdays->fetch())
		$userbirthdays[$bd['id']] = true;
	return;
}

function checkuser($name, $pass) {
	global $sql;
	$id = $sql->result("SELECT id FROM users WHERE (name = ? OR displayname = ?) AND pass = ?", [$name, $name, $pass]);
	if (!$id) $id = 0;
	return $id;
}

function checkuid($userid, $pass) {
	global $sql;
	$user = $sql->fetch("SELECT * FROM users WHERE id = ? AND pass = ?", [$userid, addslashes($pass)]);
	return $user;
}

function checkctitle($uid) {
	global $loguser, $defaultgroup;

	if (!$loguser['id']) return false;
	if (has_perm_revoked('edit-own-title')) return false;

	if ($uid == $loguser['id'] && has_perm('edit-own-title')) {
		if ($loguser['group_id'] != $defaultgroup) return true;

		return false;
	}

	if (has_perm('edit-titles')) return true;

	return false;
}

function checkcusercolor($uid) {
	global $loguser;

	if (!$loguser['id']) return false;
	if (has_perm_revoked('has-customusercolor')) return false;

	if ($uid == $loguser['id'] && has_perm('has-customusercolor')) return true;

	if (has_perm('edit-customusercolors')) return true;

	return false;
}

function checkcdisplayname($uid) {
	global $loguser, $defaultgroup;

	if (!$loguser['id']) return false;
	if (has_perm_revoked('has-displayname')) return false;

	if ($uid == $loguser['id'] && has_perm('has-displayname')) {
		if ($loguser['group_id'] != $defaultgroup) return true;

		return false;
	}

	if (has_perm('edit-displaynames')) return true;

	return false;
}

function getrank($set, $posts) {
	global $rankset_data, $rankset_names;

	if ($set == 0) return '';

	$i = 1;
	foreach ($rankset_data[$rankset_names[$set]] as $ranksetname => $rankset) {
		$neededposts = $rankset['p'];
		if (isset($rankset_data[$ranksetname][$i]['p']))
			$nextneededposts = $rankset_data[$ranksetname][$i]['p'];
		else
			$nextneededposts = 2147483647;

		if (($posts >= $neededposts) && ($posts < $nextneededposts)) {
			return $rankset['str'];
		}
		$i++;
	}
	return '';
}

function randnickcolor() {
	/* OLD HACKISH CODE FOR APRIL 5 */
	$stime = gettimeofday();
	$h = (($stime['usec'] / 5) % 600);
	if ($h < 100) {
		$r = 255;
		$g = 155 + $h;
		$b = 155;
	} elseif ($h < 200) {
		$r = 255 - $h + 100;
		$g = 255;
		$b = 155;
	} elseif ($h < 300) {
		$r = 155;
		$g = 255;
		$b = 155 + $h - 200;
	} elseif ($h < 400) {
		$r = 155;
		$g = 255 - $h + 300;
		$b = 255;
	} elseif ($h < 500) {
		$r = 155 + $h - 400;
		$g = 155;
		$b = 255;
	} else {
		$r = 255;
		$g = 155;
		$b = 255 - $h + 500;
	}
	$rndcolor = substr(dechex($r * 65536 + $g * 256 + $b), -6);
	return $rndcolor;
}

function userfields($tbl = '', $pf = '') {
	$fields = ['id', 'name', 'displayname', 'group_id', 'nick_color', 'enablecolor'];

	$ret = '';
	foreach ($fields as $f) {
		if ($ret)
			$ret .= ',';
		if ($tbl)
			$ret .= $tbl . '.';
		$ret .= $f;
		if ($pf)
			$ret .= ' ' . $pf . $f;
	}

	return $ret;
}

function userfields_post() {
	$ufields = ['posts','regdate','lastpost','lastview','rankset','title','usepic','head','sign','signsep'];
	$fieldlist = '';
	foreach ($ufields as $field)
		$fieldlist .= "u.$field u$field,";
	return $fieldlist;
}

function userlink_by_id($uid) {
	global $sql;
	$u = $sql->fetch("SELECT ".userfields()." FROM users WHERE id=?", [$uid]);
	return userlink($u);
}

function userlink($user, $u = '') {
	if (!$user[$u.'name']) $user[$u.'name'] = 'null';

	return '<a href="profile.php?id='.$user[$u.'id'] . '">'.userdisp($user, $u).'</a>';
}

function userdisp($user, $u = '') {
	global $usergroups, $userbirthdays;

	if ($user[$u.'nick_color'] && $user[$u.'enablecolor']) { //Over-ride for custom colours
		$nc = $user[$u.'nick_color'];
	} else {
		$group = $usergroups[$user[$u.'group_id']];
		$nc = $group['nc'];
	}
	//Random Nick Color on Birthday
	if (isset($userbirthdays[$user[$u.'id']]))
		$nc = randnickcolor();

	$n = ($user[$u.'displayname'] ? $user[$u.'displayname'] : $user[$u.'name']);

	$userdisname = "<span style='color:#$nc;'>".str_replace(" ", "&nbsp;", esc($n)).'</span>';

	return $userdisname;
}
