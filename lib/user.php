<?php

function getrank($set, $posts) {
	global $rankset_data, $rankset_names;

	if ($set == 0 || $set == 1) return '';

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
	$fields = ['id', 'name', 'customcolor'];

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
	$ufields = ['posts','joined','lastpost','lastview','title','avatar','signature'];
	$fieldlist = '';
	foreach ($ufields as $field)
		$fieldlist .= "u.$field u$field,";
	return $fieldlist;
}

function userlink_by_id($uid) {
	$u = fetch("SELECT ".userfields()." FROM users WHERE id=?", [$uid]);
	return userlink($u);
}

function userlinkByName($name) {
	$u = fetch("SELECT ".userfields()." FROM users WHERE UPPER(name)=UPPER(?)", [$name]);
	if ($u)
		return userlink($u, null);
	else
		return 0;
}

function getUsernameLink($matches) {
	$x = str_replace('"', '', $matches[1]);
	$nl = userlinkByName($x);
	if ($nl)
		return $nl;
	else
		return $matches[0];
}
