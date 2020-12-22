-- Adminer 4.7.8 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE `blockedlayouts` (
  `user` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `blockee` mediumint(8) unsigned NOT NULL DEFAULT 0,
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `categories` (
  `id` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `ord` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `categories` (`id`, `title`, `ord`) VALUES
(1,	'General',	2),
(2,	'Staff Forums',	0);

CREATE TABLE `forums` (
  `id` int(5) NOT NULL DEFAULT 0,
  `cat` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `ord` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `descr` varchar(255) NOT NULL,
  `threads` mediumint(8) NOT NULL DEFAULT 0,
  `posts` mediumint(8) NOT NULL DEFAULT 0,
  `lastdate` int(11) NOT NULL DEFAULT 0,
  `lastuser` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `lastid` int(11) NOT NULL,
  `private` int(1) NOT NULL,
  `readonly` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `forums` (`id`, `cat`, `ord`, `title`, `descr`, `threads`, `posts`, `lastdate`, `lastuser`, `lastid`, `private`, `readonly`) VALUES
(1,	1,	1,	'General Forum',	'General topics forum',	0,	0,	0,	0,	0,	0,	0),
(2,	2,	1,	'General Staff Forum',	'Generic Staff Forum',	0,	0,	0,	0,	0,	1,	0);

CREATE TABLE `forumsread` (
  `uid` mediumint(9) NOT NULL,
  `fid` int(5) NOT NULL,
  `time` int(11) NOT NULL,
  UNIQUE KEY `uid` (`uid`,`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `nc` varchar(6) NOT NULL,
  `inherit_group_id` int(11) NOT NULL,
  `sortorder` int(11) NOT NULL DEFAULT 0,
  `visible` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `groups` (`id`, `title`, `nc`, `inherit_group_id`, `sortorder`, `visible`) VALUES
(1,	'Banned',	'888888',	3,	0,	1),
(2,	'Base User',	'',	0,	100,	0),
(3,	'Normal User',	'4f77ff',	2,	200,	1),
(4,	'Staff',	'',	3,	300,	0),
(5,	'Moderator',	'47B53C',	4,	600,	1),
(6,	'Administrator',	'd8b00d',	5,	700,	1),
(7,	'Root Administrator',	'AA3C3C',	0,	800,	1);

CREATE TABLE `guests` (
  `date` int(11) NOT NULL DEFAULT 0,
  `ip` varchar(15) NOT NULL,
  `bot` tinyint(4) NOT NULL DEFAULT 0,
  `lastforum` int(10) DEFAULT NULL,
  UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `ipbans` (
  `ipmask` varchar(15) NOT NULL,
  `hard` tinyint(1) NOT NULL,
  `expires` int(12) NOT NULL,
  `banner` varchar(25) NOT NULL,
  `reason` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `misc` (
  `views` int(11) NOT NULL DEFAULT 0,
  `botviews` int(11) NOT NULL DEFAULT 0,
  `attention` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `misc` (`views`, `botviews`, `attention`) VALUES
(0,	0,	'<b>Welcome to your new Acmlmboard!</b>');

CREATE TABLE `perm` (
  `id` varchar(64) NOT NULL,
  `title` varchar(255) NOT NULL,
  `permbind_id` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `perm` (`id`, `title`, `permbind_id`) VALUES
('ban-users',	'Ban Users',	''),
('bypass-lockdown',	'View Board Under Lockdown',	''),
('can-edit-group',	'Edit Group Assets',	'group'),
('can-edit-group-member',	'Edit User Assets',	'group'),
('consecutive-posts',	'Consecutive Posts',	''),
('create-all-private-forum-posts',	'Create All Private Forum Posts',	''),
('create-all-private-forum-threads',	'Create All Private Forum Threads',	''),
('create-forum-announcements',	'Create Forum Announcements',	''),
('create-pms',	'Create PMs',	''),
('create-private-forum-post',	'Create Private Forum Post',	'forums'),
('create-private-forum-thread',	'Create Private Forum Thread',	'forums'),
('create-public-post',	'Create Public Post',	''),
('create-public-thread',	'Create Public Thread',	''),
('delete-forum-post',	'Delete Forum Post',	'forums'),
('delete-forum-thread',	'Delete Forum Thread',	'forums'),
('delete-own-pms',	'Delete Own PMs',	''),
('delete-post',	'Delete Post',	''),
('delete-thread',	'Delete Thread',	''),
('delete-user-pms',	'Delete User PMs',	''),
('edit-all-group',	'Edit All Group Assets',	''),
('edit-all-group-member',	'Edit All User Assets',	''),
('edit-attentions-box',	'Edit Attentions Box',	''),
('edit-customusercolors',	'Edit Custom Username Colors',	''),
('edit-displaynames',	'Edit Displaynames',	''),
('edit-forum-post',	'Edit Forum Post',	'forums'),
('edit-forum-thread',	'Edit Forum Thread',	'forums'),
('edit-forums',	'Edit Forums',	''),
('edit-groups',	'Edit Groups',	''),
('edit-ip-bans',	'Edit IP Bans',	''),
('edit-own-permissions',	'Edit Own Permissions',	''),
('edit-own-title',	'Edit Own Title',	''),
('edit-permissions',	'Edit Permissions',	''),
('edit-titles',	'Edit Titles',	''),
('edit-users',	'Edit Users',	''),
('has-customusercolor',	'Can Edit Custom Username Color',	''),
('has-displayname',	'Can Use Displayname',	''),
('ignore-thread-time-limit',	'Ignore Thread Time Limit',	''),
('manage-board',	'Administration Management Panel',	''),
('no-restrictions',	'No Restrictions',	''),
('override-closed',	'Post in Closed Threads',	''),
('override-readonly-forums',	'Override Read Only Forums',	''),
('rename-own-thread',	'Rename Own Thread',	''),
('update-own-post',	'Update Own Post',	''),
('update-own-profile',	'Update Own Profile',	''),
('update-post',	'Update Post',	''),
('update-profiles',	'Update Profiles',	''),
('update-thread',	'Update Thread',	''),
('use-post-layout',	'Use Post Layout',	''),
('view-all-private-forums',	'View All Private Forums',	''),
('view-own-pms',	'View Own PMs',	''),
('view-post-history',	'View Post History',	''),
('view-post-ips',	'View Post IP Addresses',	''),
('view-private-forum',	'View Private Forum',	'forums'),
('view-user-pms',	'View User PMs',	'');

CREATE TABLE `pmsgs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `date` int(11) NOT NULL DEFAULT 0,
  `ip` char(15) NOT NULL,
  `userto` mediumint(9) unsigned NOT NULL,
  `userfrom` mediumint(9) unsigned NOT NULL,
  `unread` tinyint(4) NOT NULL DEFAULT 1,
  `del_from` tinyint(1) NOT NULL DEFAULT 0,
  `del_to` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `posts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user` mediumint(9) unsigned NOT NULL DEFAULT 0,
  `thread` mediumint(9) unsigned NOT NULL DEFAULT 0,
  `date` int(11) NOT NULL DEFAULT 0,
  `ip` char(15) NOT NULL,
  `num` mediumint(9) NOT NULL DEFAULT 0,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `announce` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `threadid` (`thread`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `poststext` (
  `id` int(11) unsigned NOT NULL DEFAULT 0,
  `text` text NOT NULL,
  `revision` int(5) NOT NULL DEFAULT 1,
  `date` int(11) NOT NULL DEFAULT 0,
  `user` mediumint(9) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`,`revision`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `threads` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `replies` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `views` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `closed` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `sticky` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `forum` int(5) NOT NULL DEFAULT 0,
  `user` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `lastdate` int(11) NOT NULL DEFAULT 0,
  `lastuser` mediumint(8) unsigned NOT NULL DEFAULT 0,
  `lastid` int(11) NOT NULL DEFAULT 0,
  `announce` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `threadsread` (
  `uid` mediumint(9) NOT NULL,
  `tid` mediumint(9) NOT NULL,
  `time` int(11) NOT NULL,
  UNIQUE KEY `uid` (`uid`,`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `users` (
  `id` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `displayname` varchar(32) NOT NULL DEFAULT '',
  `pass` varchar(32) NOT NULL,
  `posts` mediumint(9) NOT NULL DEFAULT 0,
  `threads` mediumint(9) NOT NULL DEFAULT 0,
  `regdate` int(11) NOT NULL DEFAULT 0,
  `lastpost` int(11) NOT NULL DEFAULT 0,
  `lastview` int(11) NOT NULL DEFAULT 0,
  `lastforum` int(10) NOT NULL DEFAULT 0,
  `ip` varchar(15) NOT NULL DEFAULT '0.0.0.0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `tempbanned` tinyint(4) NOT NULL DEFAULT 0,
  `gender` tinyint(4) NOT NULL DEFAULT 2,
  `dateformat` varchar(15) NOT NULL DEFAULT 'Y-m-d',
  `timeformat` varchar(15) NOT NULL DEFAULT 'H:i',
  `ppp` smallint(3) unsigned NOT NULL DEFAULT 20,
  `tpp` smallint(3) unsigned NOT NULL DEFAULT 20,
  `theme` varchar(32) NOT NULL DEFAULT '0',
  `birth` varchar(10) NOT NULL DEFAULT '-1',
  `rankset` int(10) NOT NULL DEFAULT 1,
  `title` varchar(255) NOT NULL DEFAULT '',
  `location` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `showemail` tinyint(4) NOT NULL DEFAULT 0,
  `usepic` tinyint(4) NOT NULL DEFAULT 0,
  `head` text DEFAULT NULL,
  `sign` text DEFAULT NULL,
  `signsep` tinyint(1) NOT NULL DEFAULT 0,
  `bio` text DEFAULT NULL,
  `group_id` tinyint(4) NOT NULL DEFAULT 1,
  `nick_color` varchar(6) NOT NULL DEFAULT '000000',
  `enablecolor` tinyint(1) NOT NULL DEFAULT 0,
  `blocklayouts` tinyint(4) NOT NULL DEFAULT 0,
  `timezone` varchar(128) NOT NULL DEFAULT 'UTC',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `x_perm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `x_id` int(11) NOT NULL,
  `x_type` varchar(64) NOT NULL,
  `perm_id` varchar(64) NOT NULL,
  `permbind_id` varchar(64) NOT NULL,
  `bindvalue` int(11) NOT NULL,
  `revoke` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `x_perm` (`x_id`, `x_type`, `perm_id`, `permbind_id`, `bindvalue`, `revoke`) VALUES
(1,	'group',	'create-public-post',	'',	0,	1),
(1,	'group',	'create-public-thread',	'',	0,	1),
(1,	'group',	'edit-own-title',	'',	0,	1),
(1,	'group',	'rename-own-thread',	'',	0,	1),
(1,	'group',	'update-own-post',	'',	0,	1),
(1,	'group',	'update-own-profile',	'',	0,	1),
(2,	'group',	'create-private-forum-post',	'forums',	2,	1),
(2,	'group',	'create-private-forum-thread',	'forums',	2,	1),
(2,	'group',	'create-public-thread',	'',	0,	0),
(2,	'group',	'delete-forum-post',	'forums',	2,	1),
(2,	'group',	'delete-forum-thread',	'forums',	2,	1),
(2,	'group',	'edit-forum-post',	'forums',	2,	1),
(2,	'group',	'edit-forum-thread',	'forums',	2,	1),
(2,	'group',	'view-private-forum',	'forums',	2,	1),
(3,	'group',	'create-pms',	'',	0,	0),
(3,	'group',	'create-public-post',	'',	0,	0),
(3,	'group',	'create-public-thread',	'',	0,	0),
(3,	'group',	'delete-own-pms',	'',	0,	0),
(3,	'group',	'rename-own-thread',	'',	0,	0),
(3,	'group',	'update-own-post',	'',	0,	0),
(3,	'group',	'update-own-profile',	'',	0,	0),
(3,	'group',	'use-post-layout',	'',	0,	0),
(3,	'group',	'view-own-pms',	'',	0,	0),
(4,	'group',	'create-private-forum-post',	'forums',	2,	0),
(4,	'group',	'create-private-forum-thread',	'forums',	2,	0),
(4,	'group',	'delete-forum-post',	'forums',	2,	0),
(4,	'group',	'delete-forum-thread',	'forums',	2,	0),
(4,	'group',	'edit-forum-post',	'forums',	2,	0),
(4,	'group',	'edit-forum-thread',	'forums',	2,	0),
(4,	'group',	'has-displayname',	'',	0,	0),
(4,	'group',	'view-private-forum',	'forums',	2,	0),
(5,	'group',	'ban-users',	'',	0,	0),
(5,	'group',	'create-forum-announcements',	'',	0,	0),
(5,	'group',	'delete-post',	'',	0,	0),
(5,	'group',	'delete-thread',	'',	0,	0),
(5,	'group',	'override-closed',	'',	0,	0),
(5,	'group',	'update-post',	'',	0,	0),
(5,	'group',	'update-thread',	'',	0,	0),
(5,	'group',	'view-post-history',	'',	0,	0),
(6,	'group',	'create-all-private-forum-posts',	'',	0,	0),
(6,	'group',	'create-all-private-forum-threads',	'',	0,	0),
(6,	'group',	'edit-all-group',	'',	0,	0),
(6,	'group',	'edit-attentions-box',	'',	0,	0),
(6,	'group',	'edit-forums',	'',	0,	0),
(6,	'group',	'edit-ip-bans',	'',	0,	0),
(6,	'group',	'edit-permissions',	'',	0,	0),
(6,	'group',	'edit-titles',	'',	0,	0),
(6,	'group',	'edit-users',	'',	0,	0),
(6,	'group',	'has-customusercolor',	'',	0,	0),
(6,	'group',	'manage-board',	'',	0,	0),
(6,	'group',	'override-readonly-forums',	'',	0,	0),
(6,	'group',	'update-profiles',	'',	0,	0),
(6,	'group',	'view-all-private-forums',	'',	0,	0),
(6,	'group',	'view-post-ips',	'',	0,	0),
(7,	'group',	'no-restrictions',	'',	0,	0);

-- 2020-12-21 17:21:49
