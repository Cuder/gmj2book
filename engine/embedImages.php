<?php
// Forbid to open this file directly from browser
if (preg_match("/embedImages.php/i", $_SERVER['PHP_SELF'])) header("Location: ../index.php");

updateTaskStatus($task[0]["id"],5);

$fpath = $rootdir."/books/".$task[0]["id"]."/".$task[0]["name"].".fb2";
$xml = "<a></a>";
$fb2 = new SimpleXMLElement($xml);

$sth = $db_conn->prepare("SELECT id,image FROM gmj_posts WHERE author='".$task[0]["author_id"]."' AND site='".$task[0]["site"]."' AND (image='2' OR image='3') AND image_in_book='0' GROUP BY id ASC");
$sth->execute();
$images = $sth->fetchAll(PDO::FETCH_ASSOC);

foreach ($images as $image) {
    if ($image["image"] == 2) {
        $imageType = "image/jpeg";
        $ext = "jpg";
    }
    if ($image["image"] == 3) {
        $ext = "png";
        $imageType = "image/".$ext;
    }
    $path = getURL($task[0]["site"])."/Blog/Attachment.ashx?aid=".$image["id"];
    $base64 = wordwrap(base64_encode(file_get_contents($path)),120,"\n",TRUE);

    // Inserting binary images
    $binary = $fb2->addChild("binary",$base64);
    $binary->addAttribute('id','image'.$image["id"].'.'.$ext);
    $binary->addAttribute('content-type',$imageType);
    $binaries = $binary->asXML()."\n";
    file_put_contents($fpath, $binaries, FILE_APPEND);
    $db_conn->exec("UPDATE gmj_posts SET image_in_book='1' WHERE id='".$image["id"]."' AND site='".$task[0]["site"]."'");
}
file_put_contents($fpath, "</FictionBook>", FILE_APPEND);

// Updating images counter and status
$sth = $db_conn->prepare("SELECT COUNT(*) FROM gmj_posts WHERE author='".$task[0]["author_id"]."' AND site='".$task[0]["site"]."' AND image_in_book='1'");
$sth->execute();
$imagesCount = $sth->fetchColumn();
$db_conn->exec("UPDATE gmj_tasks SET images_count='".$imagesCount."', status='6' WHERE id='".$task[0]["id"]."'");
