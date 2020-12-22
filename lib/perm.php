<?php

// Mark what group is the root, default and banned group.
$bannedgroup = 1;
$defaultgroup = 3;
$rootgroup = 7;

// preload group data, makes things a lot easier afterwards
$usergroups = [];
$r = $sql->query("SELECT * FROM groups");
while ($g = $r->fetch())
	$usergroups[$g['id']] = $g;

//this processes the permission stack, in this order:
//-user permissions
//-user's primary group permissions, then the parent group's permissions, recursively until it reaches the top
//first encountered occurence of a permission has precendence (+/-)
function load_user_permset() {
	global $logpermset, $loguser;

	//load user specific permissions
	$logpermset = perms_for_x('user',$loguser['id']);
	$logpermset = apply_group_permissions($logpermset,$loguser['group_id']);
}

//Badge permset

function permset_for_user($userid) {
	$permset = [];
	//load user specific permissions
	$permset = perms_for_x('user',$userid);

	$permset = apply_group_permissions($permset,gid_for_user($userid));
	return $permset;
}

function is_root_gid($gid) {
	global $rootgroup;
	if ($gid == $rootgroup)
		return true;
	else
		return false;
}

function gid_for_user($userid) {
	global $sql;
	$row = $sql->fetch("SELECT group_id FROM users WHERE id=?",[$userid]);
	return $row['group_id'];
}

function load_guest_permset() {
	global $logpermset;
	$logpermset = [];
	$loggroups = [1];
	foreach ($loggroups as $gid) {
		$logpermset = apply_group_permissions($logpermset,$gid);
	}
}

function load_bot_permset() {
	global $logpermset;
	$logpermset = [];
	$loggroups = [];
	foreach ($loggroups as $gid) {
		$logpermset = apply_group_permissions($logpermset,$gid);
	}
}

function title_for_perm($permid) {
	global $sql;
	$row = $sql->fetch("SELECT title FROM perm WHERE id=?",[$permid]);
	return $row['title'];
}

function apply_group_permissions($permset,$gid) {
	//apply group permissions from lowest node upwards
	while ($gid > 0) {
		$gpermset = perms_for_x('group',$gid);
		foreach ($gpermset as $k => $v) {
			//remove already added permissions
			if (in_permset($permset,$v)) unset($gpermset[$k]);
		}
		//merge permissions
		$permset = array_merge($permset,$gpermset);
		$gid = parent_group_for_group($gid);
	}
	return $permset;
}

function in_permset($permset,$perm) {
	foreach ($permset as $v) {
		if (($v['id'] == $perm['id']) && ($v['bindvalue'] == $perm['bindvalue']))
			return true;
	}
	return false;
}

function can_edit_post($post) {
	global $loguser;
	if (isset($post['user']) && $post['user'] == $loguser['id'] && has_perm('update-own-post')) return true;
	else if (has_perm('update-post')) return true;
	else if (isset($post['tforum']) && can_edit_forum_posts($post['tforum'])) return true;
	return false;
}

function can_edit_group_assets($gid) {
	if (has_perm('edit-all-group')) return true;
	else if (has_perm_with_bindvalue('can-edit-group', $gid)) return true;
	return false;
}

function can_edit_user_assets($gid) {
	if (has_perm('edit-all-group-member')) return true;
	else if (has_perm_with_bindvalue('can-edit-group-member', $gid)) return true;
	return false;
}

function can_edit_user($uid) {
	global $loguser;

	$gid = gid_for_user($uid);
	if (is_root_gid($gid) && !has_perm('no-restrictions')) return false;
	if ((!can_edit_user_assets($gid) && $uid!=$loguser['id']) && !has_perm('no-restrictions')) return false;

	if ($uid == $loguser['id'] && has_perm('update-own-profile')) return true;
	else if (has_perm('update-profiles')) return true;
	return false;
}

function forums_with_view_perm() {
	global $sql;
	static $cache = '';
	if ($cache != '') return $cache;
	$cache = "(";
	$r = $sql->query("SELECT f.id, f.private, f.cat FROM forums f");
	while ($d = $r->fetch()) {
		if (can_view_forum($d)) $cache .= $d['id'].',';
	}
	$cache .= "NULL)";
	return $cache;
}

function can_view_forum($forum) {
	//must fulfill the following criteria

	//if the forum is private
	if ($forum['private']) {
		//and can view the forum
		if (!has_perm('view-all-private-forums') && !has_perm_with_bindvalue('view-private-forum',$forum['id'])) return false;
	}
	return true;
}

function needs_login() {
	global $log;
	if (!$log) {
		pageheader('Login required');
		noticemsg("Error", "You need to be logged in to do that!<br><a href=login.php>Please login here.</a>");
		pagefooter();
		die();
	}
}

function can_create_forum_thread($forum) {
	global $log;
	if ($forum['readonly'] && !has_perm('override-readonly-forums')) return false;

	//must fulfill the following criteria

	//can create public threads
	if (!has_perm('create-public-thread')) return false;
	if (!$log) return false;

	//and if the forum is private
	if (isset($forum['private']) && $forum['private']) {
		//can view the forum
		if (!has_perm('create-all-private-forum-threads') && !has_perm_with_bindvalue('create-private-forum-thread',$forum['id'])) return false;
	}
	return true;
}

function can_create_forum_post($forum) {
	global $log;
	if ($forum['readonly'] && !has_perm('override-readonly-forums')) return false;

	//must fulfill the following criteria

	//can create public threads
	if (!has_perm('create-public-post')) return false;
	if (!$log) return false;

	//and if the forum is private
	if ($forum['private']) {
		//can view the forum
		if (!has_perm('create-all-private-forum-posts') && !has_perm_with_bindvalue('create-private-forum-post',$forum['id'])) return false;
	}
	return true;
}

function can_edit_forum_posts($forumid) {
	if (!has_perm('update-post') && !has_perm_with_bindvalue('edit-forum-post',$forumid)) return false;
	return true;
}

function can_delete_forum_posts($forumid) {
	if (!has_perm('delete-post') && !has_perm_with_bindvalue('delete-forum-post',$forumid)) return false;
	return true;
}

function can_edit_forum_threads($forumid) {
	if (!has_perm('update-thread') && !has_perm_with_bindvalue('edit-forum-thread',$forumid)) return false;
	return true;
}

function has_perm($permid) {
	global $logpermset;
	foreach ($logpermset as $k => $v) {
		if ($v['id'] == 'no-restrictions') return true;
		if ($permid == $v['id'] && !$v['revoke']) return true;
	}
	return false;
}

function has_perm_revoked($permid) {
	global $logpermset;
	foreach ($logpermset as $k => $v) {
		if ($v['id'] == 'no-restrictions') return false;
		if ($permid == $v['id'] && $v['revoke']) return true;
	}
	return false;
}

function has_perm_with_bindvalue($permid,$bindvalue) {
	global $logpermset;
	foreach ($logpermset as $k => $v) {
		if ($v['id'] == 'no-restrictions') return true;
		if ($permid == $v['id'] && !$v['revoke'] && $bindvalue == $v['bindvalue'])
		return true;
	}
	return false;
}

function parent_group_for_group($groupid) {
	global $usergroups;

	$gid = $usergroups[$groupid]['inherit_group_id'];
	if ($gid > 0) {
		return $gid;
	} else {
		return 0;
	}
}

function perms_for_x($xtype,$xid) {
	global $sql;
	$res = $sql->query("SELECT * FROM x_perm WHERE x_type=? AND x_id=?", [$xtype,$xid]);

	$out = [];
	$c = 0;
	while ($row = $res->fetch()) {
		$out[$c++] = [
			'id' => $row['perm_id'],
			'bind_id' => $row['permbind_id'],
			'bindvalue' => $row['bindvalue'],
			'revoke' => $row['revoke'],
			'xtype' => $xtype,
			'xid' => $xid
		];
	}
	return $out;
}
