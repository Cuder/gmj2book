<?php
// Forbid to open this file directly from browser
if (preg_match("/continueWritingFB2.php/i", $_SERVER['PHP_SELF'])) header("Location: ../index.php");

$fp = '';
foreach ($task as $parameters) {
    $fpath = $rootdir."/books/".$parameters["id"]."/".$parameters["name"].".fb2";
    if (!file_exists($fpath)) {
        // FB2 file is missing for some reason
        blog2FB2($task);
        exit();
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
        if ($nextMonth) {
            $old_month = $lastPostMonth->getElementsByTagName('title')->item(0)->nodeValue;
            $old_month = printMonth($old_month,true);
            $lastPostYear->removeChild($nextMonth);
        }
        if ($nextYear) {
            $old_year = $lastPostYear->getElementsByTagName('title')->item(0)->nodeValue;
            $commonSection->removeChild($nextYear);
        }
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
            // Determining the last successfully written post
            $postCount = $postCount-1;
            $lastPostReal = $dom->getElementsByTagName('subtitle')->item($postCount);
            $lastPostId = $lastPostReal->getAttribute('id');
        } else {
            $lastPostId = $lastPostIdDB;
        }
        // Removing the last <subtitle> section
        $lastPostMonth->removeChild($lastPost);
    }
    $db_conn->exec("UPDATE gmj_posts SET post_in_book='0' WHERE id>'".$lastPostId."' AND post_in_book='1' AND author='".$parameters["author_id"]."' AND site='".$parameters["site"]."'");

    if (!isset($old_month)) $old_month = "";
    if (!isset($old_year)) $old_year = "";

    // Selecting the first and the last year among all posts not yet written in file
    $sth = $db_conn->prepare("SELECT YEAR(min(time)),YEAR(max(time)) FROM gmj_posts WHERE site='".$parameters["site"]."' AND author='".$parameters["author_id"]."' AND id>'".$lastPostId."'");
    $sth->execute();
    $years = $sth->fetchAll(PDO::FETCH_ASSOC);

    // Setting parameters of image selection
    if ($parameters["images"] == 1) { $images = ",image"; } else { $images = ""; }

    // Selecting posts from the DB
    for ($i=$years[0]["YEAR(min(time))"];$i<=$years[0]["YEAR(max(time))"];$i++) {
        // Gettings posts for a specific year $i
        $sth = $db_conn->prepare("SELECT id,title,post,time".$images." FROM gmj_posts WHERE site='".$parameters["site"]."' AND author='".$parameters["author_id"]."' AND LEFT(time,4)=".$i." AND id>'".$lastPostId."' ORDER BY id ASC");
        $sth->execute();
        $posts = $sth->fetchAll(PDO::FETCH_ASSOC);

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
                    $old_month = $month;
                }

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
                        if ($post["title"]) $messagePart = "<strong>".$post["title"]."</strong> ".$messagePart;
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
                    if (isset($ext)) {
                        $node = $dom->createElement("image");
                        $attribute = $dom->createAttribute('xlink:href');
                        $attribute->value = '#image'.$post["id"].'.'.$ext;
                        $node->appendChild($attribute);
                        $lastPostMonth->appendChild($node);
                    }
                }
                // Saving changes in XML
                $xml_string = $dom->saveXML();
                $xml_string = preg_replace('/^  |\G  /m', "\t", $xml_string);
                $fp = fopen($fpath, 'w');
                fwrite($fp,$xml_string);
                $db_conn->exec("UPDATE gmj_posts SET post_in_book='1' WHERE id='".$post["id"]."' AND site='".$parameters["site"]."'");
            }
            $lastPostId = $post["id"];
        }
        $old_month = 99;
    }
    // Updating posts counter and status
    $sth = $db_conn->prepare("SELECT COUNT(*) FROM gmj_posts WHERE author='".$parameters["author_id"]."' AND site='".$parameters["site"]."' AND post_in_book='1'");
    $sth->execute();
    $postsCount = $sth->fetchColumn();
    $db_conn->exec("UPDATE gmj_tasks SET posts_count='".$postsCount."', status='4' WHERE id='".$parameters["id"]."'");
}
fclose($fp);
