<?php
// forbid to open this file directly from the browser
if (preg_match("/zip_functions.php/i", $_SERVER['PHP_SELF'])) header("Location: index.php");

function createZIP($taskId,$blogName) {
	global $db_conn,$rootdir,$start_time;
	$fpath = $rootdir."/books/".$taskId."/".$blogName.".fb2";
	if (file_exists($fpath)) {
		$db_conn->exec("UPDATE gmj_tasks SET status=7 WHERE id='".$taskId."'");
		$zip = new ZipArchive();
		$zip->open($fpath.".zip",ZipArchive::CREATE);
		$zip->addFile($fpath,$blogName.".fb2");
		$zip->close();
		unlink($fpath);
		$busy = round(microtime(true),4)-$start_time;
		$db_conn->exec("UPDATE gmj_tasks SET status=8,busy='".$busy."' WHERE id='".$taskId."'");
	} else {
		// File with FB2 book is absent for some reason
		// Notification!
		// ...
		$db_conn->exec("UPDATE gmj_tasks SET status=2, time=time+INTERVAL 1 YEAR WHERE id='".$taskId."'");
		return exit();
	}
}

?>
