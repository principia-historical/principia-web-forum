<?php
require('lib/common.php');

needs_login();

$page = (isset($_GET['page']) ? $_GET['page'] : null);
if (!$page) $page = 1;
$view = (isset($_GET['view']) ? $_GET['view'] : 'read');

if ($view == 'sent') {
	$fieldn = 'to';
	$fieldn2 = 'from';
	$sent = true;
} else {
	$fieldn = 'from';
	$fieldn2 = 'to';
	$sent = false;
}

$id = (has_perm('view-user-pms') ? (isset($_GET['id']) ? $_GET['id'] : 0) : 0);

if (!has_perm('view-own-pms') && $id == 0) noticemsg("Error", "You are not allowed to do this!", true);

$showdel = isset($_GET['showdel']);

if (isset($_GET['action']) && $_GET['action'] == "del") {
	$owner = $sql->result("SELECT user$fieldn2 FROM pmsgs WHERE id = ?", [$id]);
	if (has_perm('delete-user-pms') || ($owner == $loguser['id'] && has_perm('delete-own-pms'))) {
		$sql->query("UPDATE pmsgs SET del_$fieldn2 = ? WHERE id = ?", [!$showdel, $id]);
	} else {
		noticemsg("Error", "You are not allowed to (un)delete that message.", true);
	}
	$id = 0;
}

$ptitle = 'Private messages' . ($sent ? ' (sent)' : '');
if ($id && has_perm('view-user-pms')) {
	$user = $sql->fetch("SELECT id,name,displayname,enablecolor,nick_color,group_id FROM users WHERE id = ?", [$id]);
	if ($user == null) noticemsg("Error", "User doesn't exist.", true);
	pageheader($user['name']."'s ".strtolower($ptitle));
	$title = userlink($user)."'s ".strtolower($ptitle);
} else {
	$id = $loguser['id'];
	pageheader($ptitle);
	$title = $ptitle;
}

$pmsgc = $sql->result("SELECT COUNT(*) FROM pmsgs WHERE user$fieldn2 = ? AND del_$fieldn2 = ?", [$id, $showdel]);
$pmsgs = $sql->query("SELECT ".userfields('u', 'u').", p.* FROM pmsgs p "
					."LEFT JOIN users u ON u.id = p.user$fieldn "
					."WHERE p.user$fieldn2 = ? "
				."AND del_$fieldn2 = ? "
					."ORDER BY p.unread DESC, p.date DESC "
					."LIMIT " . (($page - 1) * $loguser['tpp']) . ", " . $loguser['tpp'],
				[$id, $showdel]);

$topbot = [
	'breadcrumb' => [['href' => './', 'title' => 'Main']],
	'title' => $title
];

if ($sent)
	$topbot['actions'] = [['href' => 'private.php'.($id != $loguser['id'] ? "?id=$id&" : ''), 'title' => "View received"]];
else
	$topbot['actions'] = [['href' => 'private.php?'.($id != $loguser['id'] ? "id=$id&" : '').'view=sent', 'title' => "View sent"]];

$topbot['actions'][] = ['href' => 'sendprivate.php', 'title' => 'Send new'];

if ($pmsgc <= $loguser['tpp'])
	$fpagelist = '<br>';
else {
	if ($id != $loguser['id'])
		$furl = "private.php?id=$id&view=$view";
	else
		$furl = "private.php?view=$view";
	$fpagelist = pagelist($pmsgc, $loguser['tpp'], $furl, $page).'<br>';
}

RenderPageBar($topbot);
?><br>
<table class="c1">
	<tr class="h">
		<td class="b" width="17">&nbsp;</td>
		<td class="b" width="17">&nbsp;</td>
		<td class="b">Title</td>
		<td class="b" width="130"><?=ucfirst($fieldn) ?></td>
		<td class="b" width="130">Sent on</td>
	</tr>
	<?php
	for ($i = 1; $pmsg = $pmsgs->fetch(); $i++) {
		$status = ($pmsg['unread'] ? rendernewstatus("n") : '');
		if (!$pmsg['title'])
			$pmsg['title'] = '(untitled)';

		$tr = ($i % 2 ? 'n2' : 'n3');
		?>
		<tr class="<?=$tr ?> center">
			<td class="b n2">
				<a href="private.php?action=del&id=<?=$pmsg['id'] ?>&view=<?=$view ?>"><img src="img/smilies/no.png" align="absmiddle"></a>
			</td>
			<td class="b n1"><?=$status ?></td>
			<td class="b left" style="word-break:break-word"><a href="showprivate.php?id=<?=$pmsg['id'] ?>"><?=esc($pmsg['title']) ?></a></td>
			<td class="b"><?=userlink($pmsg, 'u') ?></td>
			<td class="b"><nobr><?=date($dateformat, $pmsg['date']) ?></nobr></td>
		</tr>
		<?php
	}
	if_empty_query($i, "There are no private messages.", 5);
	?>
</table>
<?php
echo $fpagelist;
RenderPageBar($topbot);
pagefooter();