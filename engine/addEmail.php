<?php
// Forbid to open this file directly from browser
if (preg_match("/addEmail.php/i", $_SERVER['PHP_SELF'])) header("Location: ../index.php");

// Bind email and task
$sth = $db_conn->prepare("SELECT COUNT(*) FROM gmj_emails WHERE id='".$taskId."'");
$sth->execute();
$emailCount = $sth->fetchColumn();

// Not more than 20 emails for each task
// You may change this value in config.php
if ($emailCount >= $max_emails) exit($error[0].$textErrors[11].$error[2]);

$sth = $db_conn->prepare("SELECT COUNT(*) FROM gmj_emails WHERE id='".$taskId."' AND email='".$_POST['email']."'");
$sth->execute();
$emailCount = $sth->fetchColumn();

if ($emailCount == 0) {
	insert2DB('emails',array($taskId,$_POST['email']));
} else {
	// The task is already on for this email
	exit($error[0].$textCommon[1]." ".$_POST['blogName'].$textErrors[12].$textStatus[4].$error[3]);
}
