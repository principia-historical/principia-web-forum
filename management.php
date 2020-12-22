<?php
require('lib/common.php');
pageheader('Management');

$mlinks = [];
if (has_perm("edit-forums"))
	$mlinks[] = ['url' => "manageforums.php", 'title' => 'Manage forums'];
if (has_perm("edit-ip-bans"))
	$mlinks[] = ['url' => "ipbans.php", 'title' => 'Manage IP bans'];
if (has_perm("edit-groups"))
	$mlinks[] = ['url' => "editgroups.php", 'title' => 'Manage groups'];
if (has_perm("edit-attentions-box"))
	$mlinks[] = ['url' => "editattn.php", 'title' => 'Edit news box'];

if (!empty($mlinks)) {
	$mlinkstext = '';
	foreach ($mlinks as $l)
		$mlinkstext .= sprintf(' <a href="%s"><input type="submit" name="action" value="%s"></a> ', $l['url'], $l['title']);
} else {
	$mlinkstext = "You don't have permission to access any management tools.";
}

?>
<table class="c1">
	<tr class="h"><td class="b">Board management tools</td></tr>
	<tr><td class="b n1 center">
		<br><?=$mlinkstext ?><br><br>
	</td></tr>
</table>
<?php pagefooter();