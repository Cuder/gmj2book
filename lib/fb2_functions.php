<?php
// forbid to open this file directly from the browser
if (preg_match("/fb2_functions.php/i", $_SERVER['PHP_SELF'])) header("Location: ../index.php");

function printMonth($month,$reverse=false) {
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
	if ($reverse == false) {
		return $months[$month-1];
	} else {
		return array_search($month,$months)+1;
	}
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

function blog2FB2($task) {
	global $db_conn,$rootdir;
	
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
			$sth = $db_conn->prepare("SELECT id,title,post,time".$images." FROM gmj_posts WHERE site='".$parameters["site"]."' AND author='".$parameters["author_id"]."' AND LEFT(time,4)=".$i." ORDER BY id ASC");
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
							if (isset($ext)) {
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
	
	// Updating posts counter and status
	$sth = $db_conn->prepare("SELECT COUNT(*) FROM gmj_posts WHERE author='".$parameters["author_id"]."' AND site='".$parameters["site"]."' AND post_in_book='1'");
	$sth->execute();
	$postsCount = $sth->fetchColumn();
	$db_conn->exec("UPDATE gmj_tasks SET posts_count='".$postsCount."', status='4' WHERE id='".$parameters["id"]."'");
	}
}
