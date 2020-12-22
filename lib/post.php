<?php

function userlink_by_name($name) {
	global $sql;
	$u = $sql->fetch("SELECT ".userfields()." FROM users WHERE UPPER(name)=UPPER(?) OR UPPER(displayname)=UPPER(?)", [$name, $name]);
	if ($u)
		return userlink($u, null);
	else
		return 0;
}

function get_username_link($matches) {
	$x = str_replace('"', '', $matches[1]);
	$nl = userlink_by_name($x);
	if ($nl)
		return $nl;
	else
		return $matches[0];
}

function securityfilter($msg) {
	$tags = 'script|iframe|embed|object|textarea|noscript|meta|xmp|plaintext|base';
	$msg = preg_replace("'<(/?)({$tags})'si", "&lt;$1$2", $msg);

	$msg = preg_replace('@(on)(\w+\s*)=@si', '$1$2&#x3D;', $msg);

	$msg = preg_replace("'-moz-binding'si", ' -mo<z>z-binding', $msg);
	$msg = str_ireplace("expression", "ex<z>pression", $msg);
	$msg = preg_replace("'filter:'si", 'filter&#58;>', $msg);
	$msg = preg_replace("'javascript:'si", 'javascript&#58;>', $msg);
	$msg = preg_replace("'transform:'si", 'transform&#58;>', $msg);

	return $msg;
}

function makecode($match) {
	$code = esc($match[1]);
	$list = ["[", ":", ")", "_", "@", "-"];
	$list2 = ["&#91;", "&#58;", "&#41;", "&#95;", "&#64;", "&#45;"];
	return '<code class="microlight">' . str_replace($list, $list2, $code) . '</code>';
}

function makeirc($match) {
	$code = esc($match[1]);
	$list = ["\r\n", "[", ":", ")", "_", "@", "-"];
	$list2 = ["<br>", "&#91;", "&#58;", "&#41;", "&#95;", "&#64;", "&#45;"];
	return '<table style="width:90%;min-width:90%;"><tr><td class="b n3"><code>' . str_replace($list, $list2, $code) . '</code></table>';
}

function filterstyle($match) {
	$style = $match[2];

	// remove newlines.
	// this will prevent them being replaced with <br> tags and breaking the CSS
	$style = str_replace("\n", '', $style);

	return $match[1] . $style . $match[3];
}

function postfilter($msg) {
	global $smilies;

	//[code] tag
	$msg = preg_replace_callback("'\[code\](.*?)\[/code\]'si", 'makecode', $msg);

	//[irc] variant of [code]
	$msg = preg_replace_callback("'\[irc\](.*?)\[/irc\]'si", 'makeirc', $msg);

	$msg = preg_replace_callback("@(<style.*?>)(.*?)(</style.*?>)@si", 'filterstyle', $msg);

	$msg = securityfilter($msg);

	$msg = str_replace("\n", '<br>', $msg);

	foreach ($smilies as $smiley)
		$msg = str_replace($smiley['text'], sprintf('<img src="%s" align=absmiddle alt="%s" title="%s">', $smiley['url'], $smiley['text'], $smiley['text']), $msg);

	//Relocated here due to conflicts with specific smilies.
	$msg = preg_replace("@(</?(?:table|caption|col|colgroup|thead|tbody|tfoot|tr|th|td|ul|ol|li|div|p|style|link).*?>)\r?\n@si", '$1', $msg);

	$msg = preg_replace("'\[(b|i|u|s)\]'si", '<\\1>', $msg);
	$msg = preg_replace("'\[/(b|i|u|s)\]'si", '</\\1>', $msg);
	$msg = preg_replace("'\[spoiler\](.*?)\[/spoiler\]'si", '<span class="spoiler1" onclick=""><span class="spoiler2">\\1</span></span>', $msg);
	$msg = preg_replace("'\[url\](.*?)\[/url\]'si", '<a href=\\1>\\1</a>', $msg);
	$msg = preg_replace("'\[url=(.*?)\](.*?)\[/url\]'si", '<a href=\\1>\\2</a>', $msg);
	$msg = preg_replace("'\[img\](.*?)\[/img\]'si", '<img src=\\1>', $msg);
	$msg = preg_replace("'\[quote\](.*?)\[/quote\]'si", '<blockquote><hr>\\1<hr></blockquote>', $msg);
	$msg = preg_replace("'\[color=([a-f0-9]{6})\](.*?)\[/color\]'si", '<span style="color: #\\1">\\2</span>', $msg);

	$msg = preg_replace_callback('\'@\"((([^"]+))|([A-Za-z0-9_\-%]+))\"\'si', "get_username_link", $msg);

	$msg = preg_replace("'\[reply=\"(.*?)\" id=\"(.*?)\"\]'si", '<blockquote><span class="quotedby"><small><i><a href=showprivate.php?id=\\2>Sent by \\1</a></i></small></span><hr>', $msg);
	$msg = preg_replace("'\[quote=\"(.*?)\" id=\"(.*?)\"\](.*?)\[/quote\]'si", '<blockquote><span class="quotedby"><small><i><a href=thread.php?pid=\\2#\\2>Posted by \\1</a></i></small></span><hr>\\3<hr></blockquote>', $msg);
	$msg = preg_replace("'\[quote=(.*?)\](.*?)\[/quote\]'si", '<blockquote><span class="quotedby"><i>Posted by \\1</i></span><hr>\\2<hr></blockquote>', $msg);
	$msg = preg_replace("'>>([0-9]+)'si", '>><a href=thread.php?pid=\\1#\\1>\\1</a>', $msg);

	$msg = preg_replace("'\[youtube\]([\-0-9_a-zA-Z]*?)\[/youtube\]'si", '<iframe width="427" height="240" src="http://www.youtube.com/embed/\\1" frameborder="0" allowfullscreen></iframe>', $msg);

	return $msg;
}

function esc($text) {
	$text = str_replace('&', '&amp;', $text);
	$text = str_replace('<', '&lt;', $text);
	$text = str_replace('"', '&quot;', $text);
	$text = str_replace('>', '&gt;', $text);
	return $text;
}

function posttoolbutton($name, $title, $leadin, $leadout) {
	return sprintf(
		'<td style="text-align:center">
			<a href="javascript:toolBtn(\'%s\',\'%s\')"><input style="font-size:10pt;" type="button" title="%s" value="%s"></a>
		</td>',
	$leadin, $leadout, $title, $name);
}

function posttoolbar() {
	return '<table><tr>'
		. posttoolbutton("B", "Bold", "[b]", "[/b]")
		. posttoolbutton("I", "Italic", "[i]", "[/i]")
		. posttoolbutton("U", "Underline", "[u]", "[/u]")
		. posttoolbutton("S", "Strikethrough", "[s]", "[/s]")
		. "<td>&nbsp;</td>"
		. posttoolbutton("/", "URL", "[url]", "[/url]")
		. posttoolbutton("!", "Spoiler", "[spoiler]", "[/spoiler]")
		. posttoolbutton("&#133;", "Quote", "[quote]", "[/quote]")
		. posttoolbutton(";", "Code", "[code]", "[/code]")
		. "<td>&nbsp;</td>"
		. posttoolbutton("[]", "IMG", "[img]", "[/img]")
		. posttoolbutton("YT", "YouTube", "[youtube]", "[/youtube]")
		. '</tr></table>';
}

function LoadBlocklayouts() {
	global $blocklayouts, $loguser, $log, $sql;
	if (isset($blocklayouts) || !$log) return;

	$blocklayouts = [];
	$rBlocks = $sql->query("SELECT * FROM blockedlayouts WHERE blockee = ?",[$loguser['id']]);
	while ($block = $rBlocks->fetch())
		$blocklayouts[$block['user']] = 1;
}

function threadpost($post, $pthread = '') {
	global $dateformat, $loguser;

	$post['uhead'] = str_replace("<!--", "&lt;!--", $post['uhead']);

	$post['ranktext'] = getrank($post['urankset'], $post['uposts']);
	$post['utitle'] = $post['ranktext']
			. ((strlen($post['ranktext']) >= 1) ? '<br>' : '')
			. $post['utitle']
			. ((strlen($post['utitle']) >= 1) ? '<br>' : '');

	// Blocklayouts, supports user/user ($blocklayouts) and user/world (token).
	LoadBlockLayouts(); //load the blocklayout data - this is just once per page.
	$isBlocked = (isset($loguser['blocklayouts']) ? $loguser['blocklayouts'] : '');
	if ($isBlocked)
		$post['usign'] = $post['uhead'] = '';

	if (isset($post['deleted']) && $post['deleted']) {
		$postlinks = '';
		if (can_edit_forum_posts(getforumbythread($post['thread']))) {
			$postlinks .= "<a href=\"thread.php?pid=$post[id]&pin=$post[id]&rev=$post[revision]#$post[id]\">Peek</a> | ";
			$postlinks .= "<a href=\"editpost.php?pid=" . urlencode(packsafenumeric($post['id'])) . "&act=undelete\">Undelete</a>";
		}

		if ($post['id'])
			$postlinks .= ($postlinks ? ' | ' : '') . "ID: $post[id]";

		$ulink = userlink($post, 'u');
		$text = <<<HTML
<table class="c1"><tr>
	<td class="b n1" style="border-right:0;width:180px">$ulink</td>
	<td class="b n1" style="border-left:0">
		<table width="100%">
			<td class="nb sfont">(post deleted)</td>
			<td class="nb sfont right">$postlinks</td>
		</table>
	</td>
</tr></table>
HTML;
		return $text;
	}

	$postheaderrow = $threadlink = $postlinks = $revisionstr = '';

	$post['id'] = (isset($post['id']) ? $post['id'] : 0);

	if ($pthread)
		$threadlink = ", in <a href=\"thread.php?id=$pthread[id]\">" . esc($pthread['title']) . "</a>";

	if (isset($post['id']) && $post['id'])
		$postlinks = "<a href=\"thread.php?pid=$post[id]#$post[id]\">Link</a>"; // headlinks for posts

	if (isset($post['revision']) && $post['revision'] >= 2)
		$revisionstr = " (rev. {$post['revision']} of " . date($dateformat, $post['ptdate']) . " by " . userlink_by_id($post['ptuser']) . ")";

	// I have no way to tell if it's closed (or otherwise impostable (hah)) so I can't hide it in those circumstances...
	if (isset($post['isannounce'])) {
		$postheaderrow = '<tr class="h"><td class="b" colspan=2>' . $post['ttitle'] . '</td></tr>';
	} else if (isset($post['thread']) && $post['id'] && $loguser['id'] != 0) {
		$postlinks .= ($postlinks ? ' | ' : '') . "<a href=\"newreply.php?id=$post[thread]&pid=$post[id]\">Reply</a>";
	}

	// "Edit" link for admins or post owners, but not banned users
	if (isset($post['thread']) && can_edit_post($post) && $post['id'])
		$postlinks.=($postlinks ? ' | ' : '') . "<a href=\"editpost.php?pid=$post[id]\">Edit</a>";

	if (isset($post['thread']) && isset($post['id']) && can_delete_forum_posts(getforumbythread($post['thread'])))
		$postlinks.=($postlinks ? ' | ' : '') . "<a href=\"editpost.php?pid=" . urlencode(packsafenumeric($post['id'])) . "&act=delete\">Delete</a>";

	if (isset($post['thread']) && $post['id'])
		$postlinks.=" | ID: $post[id]";

	if (isset($post['thread']) && has_perm('view-post-ips'))
		$postlinks.=($postlinks ? ' | ' : '') . "IP: $post[ip]";

	if (isset($post['maxrevision']) && isset($post['thread']) && has_perm('view-post-history') && $post['maxrevision'] > 1) {
		$revisionstr.=" | Revision ";
		for ($i = 1; $i <= $post['maxrevision']; $i++)
			$revisionstr .= "<a href=\"thread.php?pid=$post[id]&pin=$post[id]&rev=$i#$post[id]\">$i</a> ";
	}

	$tbar1 = (!$isBlocked) ? "topbar" . $post['uid'] . "_1" : '';
	$tbar2 = (!$isBlocked) ? "topbar" . $post['uid'] . "_2" : '';
	$sbar = (!$isBlocked) ? "sidebar" . $post['uid'] : '';
	$mbar = (!$isBlocked) ? "mainbar" . $post['uid'] : '';
	$ulink = userlink($post, 'u');
	$pdate = date($dateformat, $post['date']);
	$text = <<<HTML
<table class="c1" id="{$post['id']}">
	$postheaderrow
	<tr>
		<td class="b n1 $tbar1" style="border-bottom:0;border-right:0;min-width:180px;text-align:center" height="17">$ulink</td>
		<td class="b n1 $tbar2" style="border-left:0;width:100%">
			<table style="width:100%">
				<tr><td class="nb sfont">Posted on $pdate$threadlink $revisionstr</td><td class="nb sfont right">$postlinks</td></tr>
			</table>
		</td>
	</tr><tr valign="top">
		<td class="b n1 sfont $sbar" style="border-top:0;text-align:center">
HTML;

	$lastpost = ($post['ulastpost'] ? timeunits(time() - $post['ulastpost']) : 'none');
	$picture = ($post['uusepic'] ? "<img src=\"userpic/{$post['uid']}\">" : '');

	if ($post['usign']) {
		$signsep = $post['usignsep'] ? '' : '____________________<br>';

		if (!$post['uhead'])
			$post['usign'] = '<br><br><small>' . $signsep . $post['usign'] . '</small>';
		else
			$post['usign'] = '<br><br>' . $signsep . $post['usign'];
	}

	$text .= postfilter($post['utitle'])
		."$picture
		<br>Posts: $post[uposts]
		<br>
		<br>Since: ".date('Y-m-d', $post['uregdate'])."
		<br>
		<br>Last post: $lastpost
		<br>Last view: ".timeunits(time() - $post['ulastview'])."
	</td>
	<td class=\"b n2 $mbar\" id=\"post_".$post['id'].'">'.postfilter($post['uhead'].$post['text'].$post['usign'])."</td>
</table>";

	return $text;
}
