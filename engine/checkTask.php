<?php
// Forbid to open this file directly from browser
if (preg_match("/checkTask.php/i", $_SERVER['PHP_SELF'])) header("Location: index.php");

// Trying to get blog ID via the DB
$sth = $db_conn->prepare("SELECT id FROM gmj_blogs WHERE lower(name)='".$blogName."' AND site='".$_POST['site']."'");
$sth->execute();
$blogId = $sth->fetchColumn();

if ($blogId == "") {
	// Trying to get blog ID via the Parser
	$blogId = getUserId($blogName,$_POST['site']);
	if ($blogId == "na") exit($error[0].naErrorMessage($_POST['site']).$error[1]);
	if ($blogId == 0) exit($error[0].$textCommon[1]." <b>".$_POST['blogName']."</b> ".$textErrors[6].$error[1]);
	// Adding blog ID to the DB
	insert2DB('blogs',array($_POST['site'],$blogId,$blogName));
	$taskId = "";
	$taskStatus = 0;
} else {
	// Checking if a task already exists
	$sth = $db_conn->prepare("SELECT id,status FROM gmj_tasks WHERE 
		site='".$_POST['site']."' AND 
		author_id='".$blogId."' AND 
		coauthor_name='".$coAuthorName."' AND
		real_name='".$realName."' AND
		real_surname='".$realSurname."' AND
		images='".$images."'
	");
	$sth->execute();
	$taskInfo = $sth->fetch();
	if ($taskInfo != "") {
		if ($taskInfo[1] == 4) {
			// The task exists and the book is ready
			// Check if this book is up-to-date
			// ...
			exit();
		} else {
			$taskId = $taskInfo[0];
			$taskStatus = $taskInfo[1];
		}
	} else {
		$taskId = "";
		$taskStatus = 0;
	}
}
?>
