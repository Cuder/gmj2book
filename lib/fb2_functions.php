<?php
// forbid to open this file directly from the browser
if (preg_match("/fb2_functions.php/i", $_SERVER['PHP_SELF'])) header("Location: index.php");

function printMonth($month) {
	$months = array (
		0 => "Январь",
		1 => "Февраль",
		2 => "Март",
		3 => "Апрель",
		4 => "Май",
		5 => "Июнь",
		6 => "Июль",
		7 => "Август",
		8 => "Сентябрь",
		9 => "Октябрь",
		10 => "Ноябрь",
		11 => "Декабрь"
	);
	$i = $month-1;
	return $months[$i];
}

function rus_date() {
    $translate = array(
		"Monday" => "понедельник",
		"Tuesday" => "вторник",
		"Wednesday" => "среда",
		"Thursday" => "четверг",
		"Friday" => "пятница",
		"Saturday" => "суббота",
		"Sunday" => "воскресенье",
		"January" => "января",
		"February" => "Февраля",
		"March" => "марта",
		"April" => "апреля",
		"May" => "мая",
		"June" => "июня",
		"July" => "июля",
		"August" => "августа",
		"September" => "сентября",
		"October" => "октября",
		"November" => "ноября",
		"December" => "декабря"
    );
    if (func_num_args() > 1) {
        $timestamp = func_get_arg(1);
        return strtr(date(func_get_arg(0), $timestamp), $translate);
    } else {
        return strtr(date(func_get_arg(0)), $translate);
    }
}

function writeAnnotation($fb2,$site,$authorId,$authorName,$realName='',$realSurname='') {	
		// Getting URL of the blog
		$url = getURL($site).'/Blog/Blog.aspx?bid='.$authorId;
	
		// Writing annotation
		$fb2->startElement('FictionBook');
			$fb2->writeAttribute('xmlns:xlink','http://www.w3.org/1999/xlink');
			$fb2->writeAttribute('xmlns','http://www.gribuser.ru/xml/fictionbook/2.0');
			$fb2->startElement('description');
				$fb2->startElement('title-info');
					$fb2->startElement('genre');
						$fb2->writeAttribute('match','100');
						$fb2->text('nonf_biography');
					$fb2->endElement();
					$fb2->startElement('author');
						if ($realName) $fb2->writeElement('first-name',$realName);
						if ($realSurname) $fb2->writeElement('last-name',$realSurname);
						$fb2->writeElement('nickname',$authorName);
						$fb2->writeElement('home-page',$url);
						$fb2->writeElement('id',$authorId);
					$fb2->endElement();
					$fb2->startElement('book-title');
						$fb2->writeRaw('Блог &#171;'.$authorName.'&#187;');
					$fb2->endElement();
					$fb2->writeElement('lang','ru');
					$fb2->startElement('sequence');
						$fb2->writeAttribute('name','Блоги GMJ');
					$fb2->endElement();
				$fb2->endElement();
				$fb2->startElement('document-info');
					$fb2->startElement('author');
						$fb2->writeElement('first-name','Никита');
						$fb2->writeElement('last-name','Ковин');
						$fb2->writeElement('nickname','Cuder');
						$fb2->writeElement('home-page','http://nikitakovin.ru');
						$fb2->writeElement('email','me@nikitakovin.ru');
					$fb2->endElement();
					$fb2->writeElement('program-used','gmj2book');
					$fb2->startElement('date');
						$fb2->writeAttribute('value',date("Y-m-d"));
						$fb2->text(date("Y"));
					$fb2->endElement();
					$fb2->writeElement('src-url','http://nikitakovin.ru/gmj2book/');
					$fb2->writeElement('version','1.0');
				$fb2->endElement();
			$fb2->endElement();
			$fb2->startElement('body');
				$fb2->startElement('section');
					$fb2->startElement('title');
						$fb2->writeRaw('<p>Блог &#171;'.$authorName.'&#187;</p>');
					$fb2->endElement();
					$fb2->startElement('annotation');
						$fb2->writeElement('p','Книга была сгенерирована '.date("Y-m-d").' при помощи сервиса gmj2book: http://nikitakovin.ru/gmj2book/.');
						$fb2->writeElement('empty-line');
						$fb2->writeElement('p','Онлайн-версия блога доступна по адресу: '.$url.'.');
						$fb2->writeElement('empty-line');
						$fb2->writeElement('p','Приятного чтения, друг.');
					$fb2->endElement();
			return $fb2;
}

function continueWritingFB2($task) {
	global $db_conn,$rootdir,$start_time;
	foreach ($task as $parameters) {
		$fpath = $rootdir."/books/".$parameters["id"]."/".$parameters["name"].".fb2";
		if (!file_exists($fpath)) {
			// FB2 file is missing for some reason
			blog2FB2($task);
			return;
		}
		// Creating DOMDocument instance to continue writing to an existing file
		libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->recover = true;
		$dom->load($fpath);
		$dom->formatOutput = true;
		
		// Determining last written data
		$postCount = ($dom->getElementsByTagName('subtitle')->length)-1;
		$lastPost = $dom->getElementsByTagName('subtitle')->item($postCount);
		$lastPostId = $lastPost->getAttribute('id');	
		$lastPostMonth = $lastPost->parentNode;
		$lastPostYear = $lastPostMonth->parentNode;
		$commonSection = $lastPostYear->parentNode;
		$nextMonth = $lastPostMonth->nextSibling;
		$nextYear = $lastPostYear->nextSibling;
		
		// Deleting broken sections with a month and year following the last inserted post
		if ($nextMonth || $nextYear) {
			// The post is the last post of the month/year, deleting the broken section of the next month/year
			if ($nextMonth) $lastPostYear->removeChild($nextMonth);
			if ($nextYear) $commonSection->removeChild($nextYear);
			//$postCount = $postCount+1;
		} else {
			$sth = $db_conn->prepare("SELECT id FROM gmj_posts WHERE author='".$parameters["author_id"]."' AND site='".$parameters["site"]."' ORDER BY id ASC LIMIT ".$postCount.",1");
			$sth->execute();
			$lastPostIdDB = $sth->fetchColumn();
			if ($lastPostId == $lastPostIdDB) {
				// Removing post siblings (<p> tags), if any
				$sibling = $lastPost->nextSibling;
				if ($sibling) {
					do {
						$nextSibling = $sibling->nextSibling;
						$lastPostMonth->removeChild($sibling);
						$sibling = $nextSibling;
					} while ($sibling);
				}
				//$postCount = $postCount-1;
				$lastPostNew = $dom->getElementsByTagName('subtitle')->item($postCount);
				$lastPostId = $lastPostNew->getAttribute('id');
			} else {
				$lastPostId = $lastPostIdDB;
			}
			// Removing the last <subtitle> section
			$lastPostMonth->removeChild($lastPost);
		}
		// Selecting the first and the last year among all posts not yet written in file
		$sth = $db_conn->prepare("SELECT YEAR(min(time)),YEAR(max(time)) FROM gmj_posts WHERE site='".$parameters["site"]."' AND author='".$parameters["author_id"]."' AND post_in_book='0'");
		$sth->execute();
		$years = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		// Setting parameters of image selection
		if ($parameters["images"] == 1) { $images = ",image"; } else { $images = ""; }
		
		// Selecting posts from the DB
		$old_year = "";
		for ($i=$years[0]["YEAR(min(time))"];$i<=$years[0]["YEAR(max(time))"];$i++) {
			// Gettings posts for a specific year $i
			$sth = $db_conn->prepare("SELECT id,title,post,time".$images." FROM gmj_posts WHERE site='".$parameters["site"]."' AND author='".$parameters["author_id"]."' AND LEFT(time,4)=".$i." AND post_in_book='0' ORDER BY time ASC");
			$sth->execute();
			$posts = $sth->fetchAll(PDO::FETCH_ASSOC);
			$old_month = "";
			if ($posts) {
				
				// Year processing
				$year = $i;
				if ($old_year == "") $old_year = $year;
				if ($year != $old_year) {
					// Adding <section> for a new year
					$node = $dom->createElement("section");
					$commonSection->appendChild($node);			
					// Changing variable for new <section>
					$lastPostYear = $node;
					// Printing <title> with a new year number
					$node = $dom->createElement("title");
					$rawXML = $dom->createDocumentFragment();
					$rawXML->appendXML('<p>'.$year.'</p>');
					$node->appendChild($rawXML);
					$lastPostYear->appendChild($node);
				}
				
				foreach ($posts as $post) {
					
					// Month processing
					$month = substr($post["time"],5,2);
					if ($old_month == "") $old_month = $month;
					if ($month != $old_month) {
						// Adding <section> for a new month
						$node = $dom->createElement("section");
						$lastPostYear->appendChild($node);				
						// Changing variable for new <section>
						$lastPostMonth = $node;			
						// Printing <title> with a new month name
						$node = $dom->createElement("title");
						$rawXML = $dom->createDocumentFragment();
						$rawXML->appendXML('<p>'.printMonth($month).'</p>');
						$node->appendChild($rawXML);
						$lastPostMonth->appendChild($node);
					}
					$old_month = $month;
					
					// Post processing
					// Deleting last <br/> tag
					$message = preg_replace('/(<br \/>)+$/', '', $post["post"]);
					// Replacing all non-breaking space characters with $#160;
					$message = str_replace('&nbsp;',' ',$message);
					// Exploding post in case it has <br>s inside
					$messageArray = explode('<br />', $message);
					// Preparing post date
					$postDate = rus_date("j F, l. H:i",strtotime($post["time"]));
								
					// Writing date and post ID
					$node = $dom->createElement("subtitle",$postDate);
					$attribute = $dom->createAttribute('id');
					$attribute->value = $post["id"];
					$node->appendChild($attribute);
					$lastPostMonth->appendChild($node);
					// Writing post starts here
					$first = true;
					foreach ($messageArray as $messagePart) {
						$messagePart = htmlspecialchars($messagePart,ENT_XML1);
						// Removing links
						//$pattern = "/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i";
						//$replacement = "[ссылка удалена]";
						//$messagePart = preg_replace($pattern, $replacement, $messagePart);
						if ($first) {
							$first = false;
							$messagePart = "<strong>".$post["title"]."</strong> ".$messagePart;
						}
						$node = $dom->createElement("p");
						$rawXML = $dom->createDocumentFragment();
						$rawXML->appendXML($messagePart);
						$node->appendChild($rawXML);
						$lastPostMonth->appendChild($node);		
					}
					// Writing post ends here
					
					// Including reference to an image, if any
					if ($parameters["images"] == 1 && $post["image"] != 0) {
						if ($post["image"] == 2) $ext = "jpg";
						if ($post["image"] == 3) $ext = "png";
						if ($ext) {
							$node = $dom->createElement("image");
							$attribute = $dom->createAttribute('xlink:href');
							$attribute->value = '#image'.$post["id"].'.'.$ext;
							$node->appendChild($attribute);
							$lastPostMonth->appendChild($node);		
						}
					}
					// Updating counters
					//$db_conn->exec("UPDATE gmj_posts SET post_in_book='1' WHERE id='".$post["id"]."' AND site='".$parameters["site"]."'");
					//$busy = round(microtime(true),4)-$start_time;
					//$db_conn->exec("UPDATE gmj_tasks SET busy='".$busy."' WHERE id='".$parameters["id"]."'");
				}
				$old_month = "";
			}
		}
		// Save changes in XML
		$xml_string = $dom->saveXML();
		$xml_string = preg_replace('/^  |\G  /m', "\t", $xml_string);
		$fpathNew = $rootdir."/books/1/test.fb2";
		$fp = fopen($fpathNew, 'w');
		fwrite($fp,$xml_string);
		// Update status
		// updateTaskStatus($parameters["id"],4);
	}
}

function blog2FB2($task) {
	global $db_conn,$rootdir,$start_time;
	
	foreach ($task as $parameters) {
		
		updateTaskStatus($parameters["id"],3);
		
		// Setting path for FB2 files
		// Permissions for this directory must be set to 777
		
		if (!file_exists($rootdir."/books/".$parameters["id"])) {
			mkdir($rootdir."/books/".$parameters["id"]);
		}
		$fpath = $rootdir."/books/".$parameters["id"]."/".$parameters["name"].".fb2";
		
		// Creating a new file with an XMLWriter instance
		$fb2 = new XMLWriter();
		$fb2->openURI($fpath);
		$fb2->setIndent(true);
		$fb2->setIndentString("\t");
		$fb2->startDocument('1.0', 'windows-1251');
		
		// Writing annotation
		writeAnnotation($fb2,$parameters["site"],$parameters["author_id"],$parameters["name"],$parameters["real_name"],$parameters["real_surname"]);
		
		$sth = $db_conn->prepare("SELECT YEAR(min(time)),YEAR(max(time)) FROM gmj_posts WHERE site='".$parameters["site"]."' AND author='".$parameters["author_id"]."'");
		$sth->execute();
		$years = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		// Setting parameters of image selection
		if ($parameters["images"] == 1) { $images = ",image"; } else { $images = ""; }
		
		// Selecting posts from the DB
		for ($i=$years[0]["YEAR(min(time))"];$i<=$years[0]["YEAR(max(time))"];$i++) {
		
			// Gettings posts for a specific year $i
			$sth = $db_conn->prepare("SELECT id,title,post,time".$images." FROM gmj_posts WHERE site='".$parameters["site"]."' AND author='".$parameters["author_id"]."' AND LEFT(time,4)=".$i." ORDER BY time ASC");
			$sth->execute();
			$posts = $sth->fetchAll(PDO::FETCH_ASSOC);
			
			if ($posts) {
				
				$lastElement = end($posts);
				$lastElement = $lastElement["id"];
				$old_month = "";
						
					// Writing an opening section for a year
					$fb2->startElement('section');
						$fb2->startElement('title');
							$fb2->writeRaw('<p>'.$i.'</p>');
						$fb2->endElement();
						
				foreach ($posts as $post) {
					$month = substr($post["time"],5,2);
					
					$message = preg_replace('/(<br \/>)+$/', '', $post["post"]);
					
					$message = preg_replace('#(<br />)+#i','</p>
					<p>', $message);
					
					$message = str_replace('&nbsp;',' ',$message);
					
					// Removing links
					//$pattern = "/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i";
					//$replacement = "[ссылка удалена]";
					//$message = preg_replace($pattern, $replacement, $message);
					
					if ($month != $old_month) {
						// Writing  an closing section for a previous month, if needed
						if (current($post) != $posts[0]["id"]) $fb2->endElement();		
						// Writing  an opening section for a new month
						$fb2->startElement('section');
							$fb2->startElement('title');
								$fb2->writeRaw('<p>'.printMonth($month).'</p>');
							$fb2->endElement();
					}
							// Writing post date
							$fb2->startElement('subtitle');
								$fb2->writeAttribute('id',$post["id"]);
								$fb2->text(rus_date("j F, l. H:i",strtotime($post["time"])));
							$fb2->endElement();
							$fb2->startElement('p');
								// Writing post title
								if ($post["title"]) $fb2->writeRaw('<strong>'.$post["title"].'</strong> ');
								// Writing post itself
								$fb2->writeRaw($message);
							$fb2->endElement();
							
						// Including reference to an image, if any
						if ($parameters["images"] == 1 && $post["image"] != 0) {
							if ($post["image"] == 2) $ext = "jpg";
							if ($post["image"] == 3) $ext = "png";
							if ($ext) {
								$fb2->startElement('image');
									$fb2->writeAttribute('xlink:href','#image'.$post["id"].'.'.$ext);
								$fb2->endElement();				
							}
						}
					
					if (current($post) == $lastElement) {
						// </section> for month
						$fb2->endElement();				
					// </section> for year
					$fb2->endElement();
					
						if ($i == $years[0]["YEAR(max(time))"]) {				
				// </section> for main section
				$fb2->endElement();
			// </body>
			$fb2->endElement();	
						}
						
					} else {
						$old_month = $month;
					}
					$db_conn->exec("UPDATE gmj_posts SET post_in_book='1' WHERE id='".$post["id"]."' AND site='".$parameters["site"]."'");
					$busy = round(microtime(true),4)-$start_time;
					$db_conn->exec("UPDATE gmj_tasks SET busy='".$busy."' WHERE id='".$parameters["id"]."'");
				}
			}
		}
		if ($parameters["images"] == 0) {
		// </FictionBook>
		$fb2->endElement();
		// Closing the document
		$fb2->endDocument();
		}
		// Flushing buffer
		$fb2->flush();
	updateTaskStatus($parameters["id"],4);
	}
}

function embedImages($authorId,$authorName,$site,$taskId) {
	global $db_conn,$rootdir,$start_time;
	
	updateTaskStatus($taskId,5);
	
	$fpath = $rootdir."/books/".$taskId."/".$authorName.".fb2";
	$xml = "<a></a>";
	$fb2 = new SimpleXMLElement($xml);

	$sth = $db_conn->prepare("SELECT id,image FROM gmj_posts WHERE author='".$authorId."' AND site='".$site."' AND image<>0 AND image_in_book='0' GROUP BY id ASC");
	$sth->execute();
	$images = $sth->fetchAll(PDO::FETCH_ASSOC);
	
	foreach ($images as $image) {
		if ($image["image"] == 2) {
			$imageType = "image/jpeg";
			$ext = "jpg";
		} elseif ($image["image"] == 3) {
			$ext = "png";
			$imageType = "image/".$ext;
		} else {
			break;
		}
		
		$path = getURL($site)."/Blog/Attachment.ashx?aid=".$image["id"];
		$base64 = wordwrap(base64_encode(file_get_contents($path)),120,"\n",TRUE);
		
		// Inserting binary images
		$binary = $fb2->addChild("binary",$base64);
		$binary->addAttribute('id','image'.$image["id"].'.'.$ext);
		$binary->addAttribute('content-type',$imageType);
		$binaries = $binary->asXML()."\n";
		file_put_contents($fpath, $binaries, FILE_APPEND);
		$db_conn->exec("UPDATE gmj_posts SET image_in_book='1' WHERE id='".$image["id"]."' AND site='".$site."'");
		$busy = round(microtime(true),4)-$start_time;
		$db_conn->exec("UPDATE gmj_tasks SET busy='".$busy."' WHERE id='".$taskId."'");
	}
	file_put_contents($fpath, "</FictionBook>", FILE_APPEND);
	updateTaskStatus($taskId,6);
}

?>
