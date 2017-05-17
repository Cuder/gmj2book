<?php
// forbid to open this file directly from the browser
if (preg_match("/gmj_functions.php/i", $_SERVER['PHP_SELF'])) header("Location: ../index.php");

// Function to get ID of a user by its name
function getUserId($username,$site) {
	// List of variables
	$url = getURL($site);
	$userNameTextBox='ctl00$tbxQGo';
	$searchButtonId1='ctl00$ibQGo.x';
	$searchButtonId2='ctl00$ibQGo.y';
	$searchButtonVal1 ='10';
	$searchButtonVal2 ='5';
	$hiddenname = 'ctl00$cph1$sw';
	$hiddenvalue = 'rbSBlog';
	
	// RegExp to parse out __VIEWSTATE and __EVENTVALIDATION
	$regexViewstate = '/__VIEWSTATE\" value=\"(.*)\"/i';
	$regexEventVal  = '/__EVENTVALIDATION\" value=\"(.*)\"/i';
	
	$regs=array();
	
	// Function for parsing __VIEWSTATE and __EVENTVALIDATION
	function regexExtract($text, $regex, $regs, $nthValue) {
		if (preg_match($regex, $text, $regs)) {
		 $result = $regs[$nthValue];
		}
		return $result;
	}
	
	// Initiate cURL session
	$ch = curl_init();
	
	// GET request to retrieve __VIEWSTATE and __EVENTVALIDATION
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	$data=curl_exec($ch);
	
	if ($data) {
		
		// from the returned html, parse out the __VIEWSTATE and __EVENTVALIDATION values
		$viewstate = regexExtract($data,$regexViewstate,$regs,1);
		$eventval = regexExtract($data,$regexEventVal,$regs,1);
		
		// POST request
		$postData = '__VIEWSTATE='.rawurlencode($viewstate)
			  .'&__EVENTVALIDATION='.rawurlencode($eventval)
			  .'&'.$userNameTextBox.'='.$username
			  .'&'.$searchButtonId1.'='.$searchButtonVal1
			  .'&'.$searchButtonId2.'='.$searchButtonVal2
			  .'&'.$hiddenname.'='.$hiddenvalue
			  ;
		curl_setOpt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		
		// Get new location URL header
		$curl_info = curl_getinfo($ch);
		$headers = substr($data, 0, $curl_info["header_size"]);
		preg_match("!\r\n(?:Location|URI): *(.*?) *\r\n!", $headers, $matches);
		
		// Extract ID from the retrieved header
		$id = preg_replace("/[^0-9]/",'',$matches[1]);
	
	} else {
		$id = "na";
	}
	// Close cURL session
    curl_close($ch);
    return $id;
}

// Get a table with all posts on a page
function getPostsTable($blogId,$site,$blogPage = 0) {
	$url = getURL($site);
	$html = file_get_html(''.$url.'/Blog/Blog.aspx?bid='.$blogId.'&sidx='.$blogPage.'');
	if ($html) {
		$error = $html->find('span[id=ctl00_cph1_lblError]',0);
		if ($error) {
			$error = "noaccess";
			return $error;
		} else {
			$topicsTable = $html->find('table[class=BlogDG]',0);
			if ($topicsTable) return $topicsTable;
			return null;
		}
	} else {
		$error = "na";
		return $error;
	}
}

// Generate NA error
function naErrorMessage($site) {
	global $textErrors;
	if ($site == 0) {
		$siteName = "My.GMJ.Ru";
	} else {
		$siteName = "My.2GMJ.Com";
	}
	$error = $textErrors[5]." <b>".$siteName."</b> ".$textErrors[8];
	return $error;
}

// GMJ date & time to UNIX timestamp
function gmjTime($time) {
	$month = array(
	" янв ",
	" фев ",
	" мар ",
	" апр ",
	" май ",
	" июн ",
	" июл ",
	" авг ",
	" сен ",
	" окт ",
	" ноя ",
	" дек "
	);
	$d_month = array(
	"-1-",
	"-2-",
	"-3-",
	"-4-",
	"-5-",
	"-6-",
	"-7-",
	"-8-",
	"-9-",
	"-10-",
	"-11-",
	"-12-"
	);
	$time = str_replace($month,$d_month,$time);
	return strtotime($time);
}

// Get ID from link
// $type can be 'bid' for blog id, 'rid' for post id and 'aid' for attachment id
function getID($link,$type) {
	$link = parse_url($link,PHP_URL_QUERY);
	$link = parse_str($link,$params);
	$id = $params[$type];
	return $id;
}

// Insert $blogPosts to the DB
function insertBlogPosts($blogPosts,$blogId,$site,$coauthor=0) {
	global $db_conn;
	for ($i=0; $i<10; $i++) {
		$postTable = $blogPosts->find('table[class=BlogT]',$i);
		if ($postTable) {
			$postAuthorData = $postTable->find('td[align=left] a',0);
			// Post author name
			$postAuthorName = $postAuthorData->innertext;
			// Post author ID
			$postAuthorId = $postAuthorData->href;
			$postAuthorId = getID($postAuthorId,'bid');
			// Insert blog id & blog name with correct case
			$sth = $db_conn->prepare("SELECT name FROM gmj_blogs WHERE id='".$postAuthorId."' AND site='".$site."'");
			$sth->execute();
			$postAuthorNameDB = $sth->fetchColumn();
			if (!$postAuthorNameDB || $postAuthorName != $postAuthorNameDB) {
				if ($postAuthorNameDB) {
					// Updating DB record with correct case
					$db_conn->exec("UPDATE gmj_blogs SET name='".$postAuthorName."' WHERE id='".$postAuthorId."' AND site='".$site."'");
				} else {
					// Inserting a new DB record
					insert2DB('blogs',array($site,$postAuthorId,$postAuthorName));
				}
			}
			if ($postAuthorId == $blogId || $postAuthorId == $coauthor) {
				// Post title
				$postTitle = $postTable->find('th[align=left]',0)->innertext;
				$postTitle = trim($postTitle);
				if (preg_match('/[а-яА-ЯA-Za-z0-9]/',$postTitle) == 0 && $postTitle != "") $postTitle = "";
				// Post time
				$postTime = $postTable->find('th[align=right]',0)->innertext;
				$postTime = gmjTime($postTime);
				// Post ID
				$postId = $postTable->find('td[align=right] a',0)->href;
				$postId = getID($postId,'rid');
				$post = $postTable->find('td[colspan=2]',0);
				$attachment = $post->find('div[class=att]',0);
				$post = $post->innertext;
				$ext = 0;
				if ($attachment) {
					$post = str_replace($attachment, "", $post);
					// Image ID
					$image = $attachment->find('img',0);
					if ($image) {
						$path = getURL($site)."/Blog/Attachment.ashx?aid=".$postId;
						$ext = exif_imagetype($path);	
						if ($ext != 2 && $ext != 3) $ext = 0;
					}
				}
				// Post itself
				$post = preg_replace('#(<br />)+#i', '<br />', $post);
				$pattern = "~(<a href='[^']*' rel='nofollow' target='_blank'>)([^<]*)(</a>)~";
				$post = preg_replace($pattern, '$2', $post);
				// Insert post into the DB		
				insert2DB('posts',array($site,$postId,$postTitle,$post,$postTime,$blogId,$ext));	
			}
		} else {
			break;
		}
	}
}

// Parse blog
function blog2DB($task) {
	global $db_conn;
	foreach ($task as $parameters) {
		// Getting coauthor's ID
		if ($parameters["coauthor_name"]) {
			$sth = $db_conn->prepare("SELECT id FROM gmj_blogs WHERE lower(name)='".$parameters["coauthor_name"]."' AND site='".$parameters["site"]."'");
			$sth->execute();
			$coauthorId = $sth->fetchColumn();
			if ($coauthorId == "") {
				$coauthorId = getUserId($parameters["coauthor_name"],$parameters["site"]);
				if ($coauthorId == "na") {
					// Site is unavailable, temporarily stopping processing
					updateTaskStatus($parameters["id"],0);
					return exit();
				}
				// Coauthor not found
				if ($coauthorId == 0) {
					$db_conn->exec("UPDATE gmj_tasks SET coauthor_name='' WHERE id='".$parameters["id"]."'");
				}
			}
		} else {
			$coauthorId = 0;
		}
		updateTaskStatus($parameters["id"],1);
		// Processing blog's pages one by one
		for ($i=$parameters["pages_parsed"]; ; $i++) {
			$blogPosts = getPostsTable($parameters["author_id"],$parameters["site"],$i);
			if ($blogPosts == "noaccess") {
				// Blog's owner unexpectedly blocked access to his/her blog, terminating
				// Make notification!
				// ...
				$db_conn->exec("DELETE gmj_tasks,gmj_emails FROM gmj_tasks INNER JOIN gmj_emails WHERE gmj_tasks.id='".$parameters["id"]."' AND gmj_emails.id='".$parameters["id"]."'");
				return exit();
				break;
			}
			if ($blogPosts == "na") {
				// Site is unavailable, temporarily stopping processing
				updateTaskStatus($parameters["id"],0);
				return exit();
				break;
			}
			if ($blogPosts) {
				// Inserting posts in the DB
				insertBlogPosts($blogPosts,$parameters["author_id"],$parameters["site"],$coauthorId);
				$db_conn->exec("UPDATE gmj_tasks SET pages_parsed=pages_parsed+1 WHERE id='".$parameters["id"]."'");
			} else {
				updateTaskStatus($parameters["id"],2);
				break;
			}
		}
	}
}
