<?php
// Forbid to open this file directly from browser
if (preg_match("/checkTask.php/i", $_SERVER['PHP_SELF'])) header("Location: ../index.php");

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
		if ($taskInfo[1] == 8) {
			// The task exists and the book is ready
			// Checking if this book is up-to-date
			$sth = $db_conn->prepare("SELECT id FROM gmj_posts WHERE author='".$blogId."' AND site='".$_POST['site']."' AND post_in_book='1' ORDER BY id DESC LIMIT 1");
			$sth->execute();
			$lastPostInBook = $sth->fetchColumn();
			$blogPosts = getPostsTable($blogId,$_POST['site']);
			if ($blogPosts == "noaccess" || $blogPosts == "na") {
				// Blog's owner blocked access to his/her blog or the site is unavailable
				$giveBook = true;
			} else {
				// Scanning the first page for new posts
				for ($i=0; $i<10; $i++) {
					$postTable = $blogPosts->find('table[class=BlogT]',$i);
					if ($postTable) {
						$postAuthorData = $postTable->find('td[align=left] a',0);
						// Post author name
						$postAuthorName = $postAuthorData->innertext;
						// Post author ID
						$postAuthorId = $postAuthorData->href;
						$postAuthorId = getID($postAuthorId,'bid');
						// Checking if this post belongs to the blog's author/co-author
						if ($postAuthorId == $blogId || mb_strtolower($postAuthorName,'UTF-8') == $coAuthorName) {
							$postId = $postTable->find('td[align=right] a',0)->href;
							$postId = getID($postId,'rid');
							$giveBook = ($postId == $lastPostInBook)?true:false;
							$fileName = $postAuthorName;
							break;
						}
					} else {
						// The first page contains no posts that belong to the blog's author/co-author
						$giveBook = true;
						break;
					}
				}
			}
			if ($giveBook == true) {
				if (!isset($fileName)) {
					$sth = $db_conn->prepare("SELECT name FROM gmj_blogs WHERE id='".$blogId."' AND site='".$_POST['site']."'");
					$sth->execute();
					$fileName = $sth->fetchColumn();
				}
				exit($textStatus[15]." <a href='/books/".$taskInfo[0]."/".$fileName.".fb2.zip'>".$textStatus[12]."</a>.");
			} else {
				// The book needs to be updated. Updating status of the task
				// ...
			}
		} else {
			$taskId = $taskInfo[0];
			$taskStatus = $taskInfo[1];
		}
	} else {
		$taskId = "";
		$taskStatus = 0;
	}
}
