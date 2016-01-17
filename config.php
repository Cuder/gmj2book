<?php
// Forbid to open this file directly from browser
if (preg_match("/config.php/i", $_SERVER['PHP_SELF'])) header("Location: index.php");

// Database authentication data
$db_host = 'localhost';
$db_name = 'test';
$db_user = 'root';
$db_pass = '';

// Maximum tasks of all kinds in the DB
$max_tasks = 5;
// Maximum emails for one task
$max_emails = 20;
// Maximum time in sec upon exceeding of which the task is considered to be hunged
$page_timeout = 10;
// Lists of public blogs
$publicBlogs0 = array(
	"интервью",
	"информация",
	"мы",
	"анекдоты",
	"избушка",
	"достало!",
	"курилка",
	"помощь",
	"про это",
	"тех.помощь",
	"топ",
	"третий тайм",
	"центр звука",
	"госреестр",
	"сонник",
	"ассоциации",
	"dtr",
	"softnet",
	"siemens"
);
$publicBlogs1 = array(
	"tech.support",
	"we"
);
?>
