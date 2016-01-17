<?php
// Forbid to open this file directly from browser
if (preg_match("/addTask.php/i", $_SERVER['PHP_SELF'])) header("Location: index.php");

// Checking access to blog
$blogPosts = getPostsTable($blogId,$_POST['site']);
if ($blogPosts == "noaccess") exit($error[0].$textCommon[1]." <b>".$_POST['blogName']."</b> ".$textErrors[7].$error[1]);
if ($blogPosts == "na") exit($error[0].naErrorMessage($_POST['site']).$error[1]);

// Inserting a new task into the DB
$sth = $db_conn->prepare("INSERT INTO gmj_tasks (site,author_id,coauthor_name,real_name,real_surname,images) VALUES (:site,:author_id,:coauthor_name,:real_name,:real_surname,:images)");
$sth->bindParam(':site', $_POST['site']);
$sth->bindParam(':author_id', $blogId);
$sth->bindParam(':coauthor_name', $coAuthorName);
$sth->bindParam(':real_name', $realName);
$sth->bindParam(':real_surname', $realSurname);
$sth->bindParam(':images', $images);
$sth->execute();

// Selecting ID of the newly added task
$taskId = $db_conn->lastInsertId();

if ($coAuthorName != "") {
	// Trying to get coauthor's ID from the DB
	$sth = $db_conn->prepare("SELECT id FROM gmj_blogs WHERE lower(name)='".$coAuthorName."' AND site='".$_POST['site']."'");
	$sth->execute();
	$coauthorId = $sth->fetchColumn();
} else {
	$coauthorId = 0;
}

if ($coAuthorName == "" || ($coAuthorName != "" && $coauthorId != "")) {
	// Inserting topics ($blogPosts) from the first (0) page
	insertBlogPosts($blogPosts,$blogId,$_POST['site'],$coauthorId);
	$sth = $db_conn->prepare("UPDATE gmj_tasks SET pages_parsed=pages_parsed+1 WHERE id='".$taskId."'");
	$sth->execute();
}

// Notifying me about a new task
$message = "Some guy with IP ".$_SERVER['REMOTE_ADDR']." has just added a new task for blog ".$_POST['blogName']." (email ".$_POST['email'].").";
mail('me@nikitakovin.ru','New task for GMJ book',$message);
?>
