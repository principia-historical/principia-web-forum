<?php
// ** Acmlmboard Configuration **
// Please look through the file and fill in the appropriate information.

$sqlhost = 'localhost';
$sqluser = 'sqlusername';
$sqlpass = 'sqlpassword';
$sqldb   = 'sqldatabase';

$pwdsalt = 'Please change me!';
$pwdsalt2 = 'Addtional Salt. Please change me!';
$ckey = "Cookie key. Please change me!";
$civ = "Initialization Vector. This should be 16 characters long.";

$trashid = 2; // Designates the id for your trash forum.

$boardtitle = "Insert title here"; // This is what will be displayed at the top of your browser window.
$defaultlogo = "theme/abII.png"; // Replace with the logo of your choice.
$favicon = "theme/fav.png"; // Replace with your favicon of choice
$defaulttheme = "0"; // Select the default theme to be used.
$boardlogo = "<a href='./'><img src='$defaultlogo'></a>"; // This defines the logo.
$meta = '<meta name="description" content="Stuff goes here!">'; // This is used for search engine keywords.

// Registration Bot Protection
$puzzle = true;
$puzzleQuestion = "What forum software does this board run?";
$puzzleAnswer = "Acmlmboard";

$override_theme = ''; // If you want to lock everyone to a specific theme.
$lockdown = false; // Put board in lockdown mode.

// List of bots (web crawlers)
$botlist = ['ia_archiver','baidu','yahoo','bot','spider'];

// List of smilies
$smilies = [
	['text' => '-_-', 'url' => 'img/smilies/annoyed.gif'],
	['text' => 'o_O', 'url' => 'img/smilies/bigeyes.gif'],
	['text' => ':D', 'url' => 'img/smilies/biggrin.gif'],
	['text' => 'o_o', 'url' => 'img/smilies/blank.gif'],
	['text' => ':x', 'url' => 'img/smilies/crossmouth.gif'],
	['text' => ';_;', 'url' => 'img/smilies/cry.gif'],
	['text' => '^_^', 'url' => 'img/smilies/cute.gif'],
	['text' => '@_@', 'url' => 'img/smilies/dizzy.gif'],
	['text' => ':@', 'url' => 'img/smilies/dropsmile.gif'],
	['text' => 'O_O', 'url' => 'img/smilies/eek.gif'],
	['text' => '>:]', 'url' => 'img/smilies/evil.gif'],
	['text' => ':eyeshift:', 'url' => 'img/smilies/eyeshift.gif'],
	['text' => ':(', 'url' => 'img/smilies/frown.gif'],
	['text' => '8-)', 'url' => 'img/smilies/glasses.gif'],
	['text' => ':LOL:', 'url' => 'img/smilies/lol.gif'],
	['text' => '>:[', 'url' => 'img/smilies/mad.gif'],
	['text' => '<_<', 'url' => 'img/smilies/shiftleft.gif'],
	['text' => '>_>', 'url' => 'img/smilies/shiftright.gif'],
	['text' => 'x_x', 'url' => 'img/smilies/sick.gif'],
	['text' => ':|', 'url' => 'img/smilies/slidemouth.gif'],
	['text' => ':)', 'url' => 'img/smilies/smile.gif'],
	['text' => ':P', 'url' => 'img/smilies/tongue.gif'],
	['text' => ':B', 'url' => 'img/smilies/vamp.gif'],
	['text' => ';)', 'url' => 'img/smilies/wink.gif'],
	['text' => ':-3', 'url' => 'img/smilies/wobble.gif'],
	['text' => ':S', 'url' => 'img/smilies/wobbly.gif'],
	['text' => '>_<', 'url' => 'img/smilies/yuck.gif'],
	['text' => ':box:', 'url' => 'img/smilies/box.png'],
	['text' => ':yes:', 'url' => 'img/smilies/yes.png'],
	['text' => ':no:', 'url' => 'img/smilies/no.png'],
	['text' => 'OwO', 'url' => 'img/smilies/owo.png']
];

// Ranksets
require('img/ranks/rankset.php'); // Default (Mario) rankset

// Random forum descriptions.
// It will be replacing the value %%%RANDOM%%% in the forum description.
$randdesc = [
	"Value1",
	"Value2"
];
