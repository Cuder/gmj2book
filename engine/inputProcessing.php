<?php
// Forbid to open this file directly from browser
if (preg_match("/inputProcessing.php/i", $_SERVER['PHP_SELF'])) header("Location: index.php");

// Checking if all mandatory POST fields are defined and correct
if ($_POST['blogName'] == "" && $_POST['email'] == "") exit($error[0].$textErrors[2].$error[1]);
if ($_POST['blogName'] == "") exit($error[0].$textErrors[0].$error[1]);
if ($_POST['email'] == "") exit($error[0].$textErrors[1].$error[1]);
if (!filter_var($_POST['email'],FILTER_VALIDATE_EMAIL)) exit($error[0].$textErrors[3].$_POST['email'].$textErrors[4].$error[1]);
if (
	// Does not contain forbidden characters?
	!preg_match('/[^а-яА-ЯA-Za-z0-9 \\-=_.]/u',$_POST['blogName']) != 1 || 
	// First characher is a letter?
	(preg_match('/[а-яА-ЯA-Za-z]/',substr($_POST['blogName'], 0, 1)) == 0 && $_POST['blogName'] != 6020) || 
	// Does not contain Cyrillic & Latin characters at the same time?
	(preg_match('/[а-яА-Я]/',$_POST['blogName']) && preg_match('/[a-zA-Z]/',$_POST['blogName']))
	)
{
	exit($error[0].$textCommon[1]." <b>".$_POST['blogName']."</b> ".$textErrors[6].$error[1]);
}

// Checking if blog is not public
$blogName = mb_strtolower($_POST['blogName'],'UTF-8');
$publicBlogs = ${'publicBlogs'.$_POST['site']};
if (in_array($blogName,$publicBlogs)) exit($error[0].$textCommon[1]." <b>".$_POST['blogName']."</b> ".$textErrors[9].$error[1]);

// Removing unwanted characters from real names
if ($_POST['realName']) {
	$realName = trim(preg_replace('/[^а-яА-ЯA-Za-z ]/u', '', $_POST['realName']));
} else {
	$realName = "";
}
if ($_POST['realSurname']) {
	$realSurname = trim(preg_replace('/[^а-яА-ЯA-Za-z ]/u', '', $_POST['realSurname']));
} else {
	$realSurname = "";
}
if ($_POST['coauthor']) {
	$coAuthorName = trim(preg_replace('/[^а-яА-ЯA-Za-z0-9 \-=_.]/u', '', $_POST['coauthor']));
	$coAuthorName = mb_strtolower($coAuthorName,'UTF-8');
} else {
	$coAuthorName = "";
}
if (isset($_POST['images']) && $_POST['images'] == 'include') {
	$images = 1;
} else {
	$images = 0;
}
?>
