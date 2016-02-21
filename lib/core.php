<?php
// Forbid to open this file directly from browser
if (preg_match("/core.php/i", $_SERVER['PHP_SELF'])) header("Location: ../index.php");

// Debug level
ini_set('display_errors', 1); 
error_reporting(E_ALL); 
//error_reporting(E_ERROR);

// If register_globals is turned off, extract super globals (php 4.2.0+)
if (ini_get('register_globals') != 1) {
	$supers = array("_REQUEST","_ENV","_SERVER","_POST","_GET","_COOKIE","_SESSION","_FILES","_GLOBALS");
	foreach ($supers as $__s) {
		if ((isset($$__s) == true) && (is_array($$__s) == true)) extract($$__s, EXTR_OVERWRITE);
	}
	unset($supers);
}

// Fallback to safe area in event of unauthorised access
function fallback($location) {
	header("Location: ".$location);
	exit;
}

// Prevent any possible XSS attacks via $_GET.
if (stripget($_GET)) {
	fallback("index.php");
}
function stripget($check_url) {
	$return = false;
	if (is_array($check_url)) {
		foreach ($check_url as $value) {
			if (stripget($value) == true) {
				return true;
			}
		}
	} else {
		$check_url = str_replace(array("\"", "\'"), array("", ""), urldecode($check_url));
		if (preg_match("/<[^<>]+>/i", $check_url)) {
			return true;
		}
	}
	return $return;
}

// Array for error output messages
$error = array("<p>"," <a href='index.php'>".$textCommon[0]."</a></p>","</p>"," <a href='status/index.php'>".$textStatus[5]."</a>.</p>");

// Get site URL
function getURL($site) {
	if ($site == 0) {
		$url = "http://my.gmj.ru";
	} else {
		$url = "http://my.2gmj.com";
	}
	return $url;
}

// Stop script if any Warning or Notice message appears
function errHandle($errNo, $errStr, $errFile, $errLine) {
    $msg = "$errStr in $errFile on line $errLine";
    if ($errNo == E_NOTICE || $errNo == E_WARNING) {
        throw new ErrorException($msg, $errNo);
    } else {
        echo $msg;
    }
}
set_error_handler('errHandle');
?>
