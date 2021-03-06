<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL); 

$rootdir = dirname(dirname(__FILE__));

require_once $rootdir."/html/text_strings.php";
require_once $rootdir."/lib/core.php";
require_once $rootdir."/config.php";
require_once $rootdir."/lib/db.php";

$sth = $db_conn->prepare("SELECT gmj_tasks.*,gmj_blogs.name FROM gmj_tasks INNER JOIN gmj_blogs WHERE gmj_tasks.author_id=gmj_blogs.id AND time<NOW() AND (status=0 OR (status<>8 AND (NOW()-time)>'".$page_timeout."')) ORDER BY time ASC LIMIT 1");
$sth->execute();
$task = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($task) {
	
	if ($task[0]["status"] == 7) {
		// The task is hung while trying to make an archive of FB2 (7)
		// Notifying 
		// ...
		$db_conn->exec("UPDATE gmj_tasks SET time=time+INTERVAL 1 YEAR WHERE id='".$task[0]["id"]."'");
		exit();
	}
	if ($task[0]["start_time"] == 0) {
		// Beginning to count time
		$db_conn->exec("UPDATE gmj_tasks SET start_time=NOW() WHERE id='".$task[0]["id"]."'");
	}
	if ($task[0]["status"] == 0 || $task[0]["status"] == 1) {
		require_once $rootdir."/lib/simple_html_dom.php";
	}
	require_once $rootdir."/lib/gmj_functions.php";
	
	if ($task[0]["status"] == 0 || $task[0]["status"] == 1) {
		// Parsing blog
		blog2DB($task);
		$task[0]["status"] = 2;
	}
	if ($task[0]["status"] == 2 || $task[0]["status"] == 3 || $task[0]["status"] == 4 || $task[0]["status"] == 5) {
		require_once $rootdir."/lib/fb2_functions.php";
	}
	if ($task[0]["status"] == 2) {
		// Making FB2
		blog2FB2($task);
		$task[0]["status"] = 4;
	}
	if ($task[0]["status"] == 3) {
		// Continue making FB2
        require_once $rootdir."/engine/continueWritingFB2.php";
		$task[0]["status"] = 4;
	}
	if ($task[0]["status"] == 4 || $task[0]["status"] == 5) {
		if ($task[0]["images"] == 1) {
			// Embedding images in FB2
            require_once $rootdir."/engine/embedImages.php";
		}
		$task[0]["status"] = 6;
	}
	if ($task[0]["status"] == 6) {
		// Archiving FB2
		require_once $rootdir."/lib/zip_functions.php";
		createZIP($task[0]["id"],$task[0]["name"]);
		$task[0]["status"] = 8;
	}
	if ($task[0]["status"] == 8) {
		// The book is ready, notifying
		// ...

		// Inserting task processing time
		$sth = $db_conn->prepare("SELECT start_time FROM gmj_tasks WHERE id='".$task[0]["id"]."'");
		$sth->execute();
		$startTime = new DateTime($sth->fetchColumn());
		$now = new DateTime('NOW');
		$busy = $now->getTimestamp()-$startTime->getTimestamp();
		$db_conn->exec("UPDATE gmj_tasks SET busy='".$busy."' WHERE id='".$task[0]["id"]."'");
	}
}
