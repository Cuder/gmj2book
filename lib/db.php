<?php
// Forbid to open this file directly from browser
if (preg_match("/db.php/i", $_SERVER['PHP_SELF'])) header("Location: index.php");

// Open connection to the DB
try {
	$db_conn = new PDO('mysql:host='.$db_host.';dbname='.$db_name.'', $db_user, $db_pass, array(PDO::ATTR_PERSISTENT => true));
	$db_conn->exec('SET NAMES utf8');
} catch (PDOException $e) {
	exit($error[0].$textErrors[10]."<i>".$e->getMessage()."</i>.");
}
?>
