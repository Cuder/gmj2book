<?php

require_once "html/text_strings.php";
require_once "lib/core.php";

if (!isset($submit)) {
	
	require_once "config.php";
	require_once "lib/db.php";
	
	// Checking if there are less than 5 non-finished tasks in DB
	// You may change this value in config.php
	$sth = $db_conn->prepare("SELECT COUNT(*) FROM gmj_tasks WHERE status<>8 AND time<NOW()");
	$sth->execute();
	$taskCount = $sth->fetchColumn();
	if ($taskCount >= $max_tasks) exit($error[0].$textErrors[11].$error[2]);
	
	require_once "html/header.php";
	require_once "html/submit.php";
	require_once "html/footer.php";

} elseif ($submit == "" && isset($_POST['submitButton'])) {
	
	// Script runtime counter starts here
	$start_time = microtime(true);
	
	require_once "config.php";
	require_once "engine/inputProcessing.php";
	require_once "lib/db.php";
	require_once "lib/simple_html_dom.php";
	require_once "lib/gmj_functions.php";
	
	require_once "engine/checkTask.php";
	
	if ($taskId == "") require_once "engine/addTask.php";
	require_once "engine/addEmail.php";
	
	// Prompting about successful queue
	echo $textTask[0].$_POST['blogName'].$textTask[1].$_POST['email'];
	echo $error[0].$textStatus[4].$error[3];
	
	// Printing script running time
	echo $textFooter[2].round((microtime(true)-$start_time), 4).$textFooter[3];

} else {
	fallback("index.php");
}

?>
