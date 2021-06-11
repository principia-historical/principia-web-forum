<?php

/**
 * Renders a table in HTML using $headers for column definition and $data to fill cells with data.
 *
 * @param array $headers An associative array of column definitions:
 *	key				column key
 *	value['name']	Display text for the column header
 *	value['width']	Specify a fixed width size (CSS width:)
 *	value['align']	Align the contents in the column
 *
 * @param array $data An associative array of cell data values:
 *	key				column key (must match the header column key)
 *	value			cell value
 */
function RenderTable($data, $headers) {
	$zebra = 1;

	echo '<table class="c1"><tr class="h">';
	foreach ($headers as $headerID => $headerCell) {
		$width = (isset($headerCell['width']) ? ' style="width:'.$headerCell['width'].'"' : '');
		echo "<td class=\"b h\" $width>".$headerCell['name']."</td>";
	}
	echo "</tr>";
	foreach ($data as $dataCell) {
		echo "<tr>";
		foreach ($dataCell as $id => $value) {
			$align = (isset($headers[$id]['align']) ? $headers[$id]['align'] : '');
			echo "<td class=\"b n$zebra $align\">$value</td>";
		}
		echo "</tr>";
		$zebra = ($zebra == 1 ? 2 : 1);
	}
	echo "</table>";
}

function rendernewstatus($type) {
	switch ($type) {
		case "n":
			$text = "NEW";
			$statusimg = "new.png";
		break;
		case "o":
			$text = "OFF";
			$statusimg = "off.png";
		break;
		case "on":
			$text = "OFF";
			$statusimg = "offnew.png";
		break;
	}

	return "<img src=\"assets/status/$statusimg\" alt=\"$text\">";
}

function RenderActions($actions, $ret = false) {
	$out = '';
	$i = 0;
	foreach ($actions as $action) {
		if (isset($action['confirm'])) {
			if ($action['confirm'] === true)
				$confirmmsg = 'Are you sure you want to ' . $action['title'] . '?';
			else
				$confirmmsg = str_replace("'", "\\'", $action['confirm']);

			$href = sprintf(
				"javascript:if(confirm('%s')) window.location.href='%s'; else void('');",
			$confirmmsg, $action['href']);
		} else {
			$href = (isset($action['href']) ? $action['href'] : '');
		}
		if ($i++)
			$out .= ' | ';
		if (isset($action['href'])) {
			$out .= sprintf('<a href="%s">%s</a>', htmlentities($href, ENT_QUOTES), $action['title']);
		} else {
			$out .= $action['title'];
		}
	}
	if ($ret)
		return $out;
	else
		echo $out;
}

function RenderBreadcrumb($breadcrumb) {
	foreach ($breadcrumb as $action) {
		printf('<a href=%s>%s</a> &raquo; ', '"'.htmlentities($action['href'], ENT_QUOTES).'"', $action['title']);
	}
}

function RenderPageBar($pagebar) {
	if (empty($pagebar)) return;

	echo "<table width=100%><td class=nb>";
	if (!empty($pagebar['breadcrumb']))
		RenderBreadcrumb($pagebar['breadcrumb']);
	echo $pagebar['title'].'</td><td class="nb right">';
	if (!empty($pagebar['actions']))
		RenderActions($pagebar['actions']);
	else
		echo "&nbsp;";
	echo "</td></table>";
	if (!empty($pagebar['message'])) {
		echo '<table width=100% class=c1><tr><td class="center">'.$pagebar['message'].'</td></tr></table><br>';
	}
}

function fieldrow($title, $input) {
	return sprintf('<tr><td class="b n1 center">%s:</td><td class="b n2">%s</td>', $title, $input);
}

function fieldinput($size, $max, $field, $value = null) {
	global $user;
	$val = str_replace('"', '&quot;', (isset($value) ? $value : $user[$field]));
	return sprintf('<input type="text" name="%s" size="%s" maxlength="%s" value="%s">', $field, $size, $max, $val);
}

function fieldoption($field, $checked, $choices) {
	$text = '';
	foreach ($choices as $k => $v)
		$text .= sprintf('<label><input type="radio" name="%s" value="%s" %s>%s </label>', $field, $k, ($k == $checked ? ' checked' : ''), $v);
	return $text;
}

function fieldselect($field, $checked, $choices) {
	$text = sprintf('<select name="%s">', $field);
	foreach ($choices as $k => $v)
		$text .= sprintf('<option value="%s"%s>%s</option>', $k, ($k == $checked ? ' selected' : ''), $v);
	$text .= '</select>';
	return $text;
}

function pagelist($total, $limit, $url, $sel = 0, $showall = false, $tree = false) {
	$pagelist = '';
	$pages = ceil($total / $limit);
	if ($pages < 2) return '';
	for ($i = 1; $i <= $pages; $i++) {
		if (	$showall	// If we don't show all the pages, show:
			|| ($i < 7 || $i > $pages - 7)		// First / last 7 pages
			|| ($i > $sel - 5 && $i < $sel + 5)	// 10 choices around the selected page
			|| !($i % 10)						// Show 10, 20, etc...
		) {
			if ($i == $sel)
				$pagelist .= " $i";
			else
				$pagelist .= " <a href=\"$url&page=$i\">$i</a>";
		} else if (substr($pagelist, -1) != '.') {
			$pagelist .= ' ...';
		}
	}

	if ($tree)
		$listhtml = '<span class="sfont">(pages: %s)</span>';
	else
		$listhtml = '<div class="pagelist">Pages: %s</div>';

	return sprintf($listhtml, $pagelist);
}

/**
 * Display $message if $result (the result of a SQL query) is empty (has no lines).
 */
function if_empty_query($result, $message, $colspan = 0, $table = false) {
	if ($result == 1) {
		if ($table) echo '<table class="c1">';
		echo '<tr><td class="b n1 center" '.($colspan != 0 ? "colspan=$colspan" : '')."><p>$message</p></td></tr>";
		if ($table) echo '</table>';
	}
}

function if_empty_query2($message, $colspan = 0, $table = false) {
	if ($table) echo '<table class="c1">';
	echo '<tr><td class="b n1 center" '.($colspan != 0 ? "colspan=$colspan" : '')."><p>$message</p></td></tr>";
	if ($table) echo '</table>';
}

function _twigloader($subfolder = '') {
	global $dateformat;

	$twig = twigloader($subfolder, function () use ($subfolder) {
		return new \Twig\Loader\FilesystemLoader('templates/' . $subfolder);
	}, function ($loader, $doCache) {

		return new \Twig\Environment($loader, [
			'cache' => ($doCache ? "../".$doCache : $doCache),
		]);
	});

	$twig->addExtension(new PrincipiaForumExtension());

	$twig->addGlobal('forum_dateformat', $dateformat);

	return $twig;
}