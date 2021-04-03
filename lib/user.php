<?php

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

function userfields($tbl = '', $pf = '') {
	$fields = ['id', 'name', 'group_id'];

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
	$ufields = ['posts','joined','lastpost','lastview','title','avatar','signature_header','signature','signature_separator'];
	$fieldlist = '';
	foreach ($ufields as $field)
		$fieldlist .= "u.$field u$field,";
	return $fieldlist;
}

function userlink_by_id($uid) {
	global $sql;
	$u = $sql->fetch("SELECT ".userfields()." FROM principia.users WHERE id=?", [$uid]);
	return userlink($u);
}

function userlink($user, $u = '') {
	if (!$user[$u.'name']) $user[$u.'name'] = 'null';

	return '<a href="profile.php?id='.$user[$u.'id'] . '">'.userdisp($user, $u).'</a>';
}

function userdisp($user, $u = '') {
	global $usergroups;

	$group = $usergroups[$user[$u.'group_id']];
	$nc = $group['nc'];

	$n = $user[$u.'name'];

	$userdisname = "<span style='color:#$nc;'>".str_replace(" ", "&nbsp;", esc($n)).'</span>';

	return $userdisname;
}
