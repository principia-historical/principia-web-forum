<?php
require("lib/common.php");
pageheader("Ranks");

if (!isset($_GET['rankset']) || !is_numeric($_GET['rankset'])) $getrankset = 1;
else $getrankset = $_GET['rankset'];

$linkuser = [];
$allusers = $sql->query("SELECT ".userfields().", posts, lastview FROM users WHERE rankset = ? ORDER BY id", [$getrankset]);

while ($row = $allusers->fetch()) { $linkuser[$row['id']] = $row; }

$rankselection = '';
$ranksetcount = 0;

foreach ($rankset_names as $rankset) {
	if ($ranksetcount != 0) {
		if ($ranksetcount != 1)
			$rankselection .= " | ";
		$rankselection .= "<a href=\"ranks.php?rankset=$ranksetcount\">$rankset</a>";
	}
	$ranksetcount++;
}

if ($ranksetcount != 2) { ?>
<table class="c1 center" style="width:auto">
	<tr class="h"><td class="b">Rank Set</td></tr>
	<tr class="n1"><td class="b n1"><?=$rankselection ?></td></tr>
</table><br>
<?php } ?>
<table class="c1">
	<tr class="h">
		<td class="b" width="150">Rank</td>
		<td class="b" width="40">Posts</td>
		<td class="b" width="50">Users</td>
		<td class="b">Users On Rank</td>
	</tr>
<?php
$i = 1;

foreach ($rankset_data[$rankset_names[$getrankset]] as $rank) {
	$neededposts = $rank['p'];
	if (isset($rankset_data[$rankset_names[$getrankset]][$i]['p']))
		$nextneededposts = $rankset_data['Mario'][$i]['p'];
	else
		$nextneededposts = 2147483647;
	$usercount = 0;
	$idlecount = 0;
	foreach ($linkuser as $user) {
		$postcount = $user['posts'];
		if (($postcount >= $neededposts) && ($postcount < $nextneededposts)) {
			if (isset($_GET['showinactive']) || $user['lastview'] > (time() - (86400 * 30))) {
				$usersonthisrank = '';
				if ($usersonthisrank)
					$usersonthisrank .= ", ";
				$usersonthisrank .= userlink_by_id($user['id']);
			} else
				$idlecount++;
			$usercount++;
		}
	}
	?><tr>
		<td class="b n1"><?=(($usercount - $idlecount) ? $rank['str'] : '???') ?></td>
		<td class="b n2 center"><?=(($usercount - $idlecount) ? $neededposts : '???') ?></td>
		<td class="b n2 center"><?=$usercount ?></td>
		<td class="b n1 center"><?=(isset($usersonthisrank) ? $usersonthisrank : '') . ($idlecount ? " ($idlecount inactive)" : '') ?></td>
	</tr><?php
	unset($usersonthisrank);
	$i++;
}
?></table><?php
pagefooter();