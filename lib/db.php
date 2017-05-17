<?php
// Forbid to open this file directly from browser
if (preg_match("/db.php/i", $_SERVER['PHP_SELF'])) header("Location: ../index.php");

// Open connection to the DB
try {
	$db_conn = new PDO('mysql:host='.$db_host.';dbname='.$db_name.'', $db_user, $db_pass, array(PDO::ATTR_PERSISTENT => true));
	$db_conn->exec('SET NAMES utf8');
} catch (PDOException $e) {
	exit($error[0].$textErrors[10]."<i>".$e->getMessage()."</i>.");
}

// Function of inserting records into the DB
function insert2DB($table,$data) {
	global $db_conn;
	switch ($table) {
		case "blogs":
			$sth = $db_conn->prepare("INSERT INTO gmj_blogs (site,id,name) VALUES (:site,:id,:name)");
			$sth->bindParam(':site', $data[0]);
			$sth->bindParam(':id', $data[1]);
			$sth->bindParam(':name', $data[2]);
			break;
		case "emails":
			$sth = $db_conn->prepare("INSERT INTO gmj_emails (id,email) VALUES (:id,:email)");
			$sth->bindParam(':id', $data[0]);
			$sth->bindParam(':email', $data[1]);
			break;
		case "posts":
			$sth = $db_conn->prepare("INSERT INTO gmj_posts (site,id,title,post,time,author,image) VALUES (:site,:id,:title,:post,FROM_UNIXTIME(:time),:author,:image)");
			$sth->bindParam(':site', $data[0]);
			$sth->bindParam(':id', $data[1]);
			$sth->bindParam(':title', $data[2]);
			$sth->bindParam(':post', $data[3]);
			$sth->bindParam(':time', $data[4]);
			$sth->bindParam(':author', $data[5]);
			$sth->bindParam(':image', $data[6]);
			break;
		case "tasks":
			$sth = $db_conn->prepare("INSERT INTO gmj_tasks (site,author_id,coauthor_name,real_name,real_surname,images) VALUES (:site,:author_id,:coauthor_name,:real_name,:real_surname,:images)");		
			$sth->bindParam(':site', $data[0]);
			$sth->bindParam(':author_id', $data[1]);
			$sth->bindParam(':coauthor_name', $data[2]);
			$sth->bindParam(':real_name', $data[3]);
			$sth->bindParam(':real_surname', $data[4]);
			$sth->bindParam(':images', $data[5]);
			break;
	}
	$sth->execute();
	if ($table == "tasks") return $db_conn->lastInsertId();
	return null;
}

// Function of updating statuses of tasks
function updateTaskStatus($taskID,$status) {
	global $db_conn;
	$db_conn->exec("UPDATE gmj_tasks SET status='".$status."' WHERE id='".$taskID."'");
}
