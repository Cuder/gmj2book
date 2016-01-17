<?php
require_once "../config.php";
require_once "../lib/db.php";
require_once "../html/text_strings.php";

$sth = $db_conn->prepare("
		SELECT
			gmj_tasks.id,
			gmj_tasks.site,
			gmj_tasks.author_id,
			gmj_tasks.coauthor_name,
			gmj_tasks.status,
			gmj_tasks.pages_parsed,
			gmj_tasks.posts,
			gmj_tasks.images_count,
			gmj_tasks.real_name,
			gmj_tasks.real_surname,
			gmj_tasks.images,
			gmj_tasks.time,
			gmj_blogs.name
		FROM gmj_tasks INNER JOIN 
			gmj_blogs
		WHERE
			gmj_tasks.author_id=gmj_blogs.id AND 
			gmj_tasks.site=gmj_blogs.site AND 
			NOW()-time>=0 
		GROUP BY time DESC
		");
$sth->execute();
$tasks = $sth->fetchAll(PDO::FETCH_ASSOC);

echo "<tr style='text-align:center;font-weight:bold'>";
	echo "<td>".$textSubmitForm[0]."</td>";
	echo "<td>".$textSubmitForm[15]."</td>";
	echo "<td>".$textStatus[0]."</td>";
	echo "<td>".$textStatus[1]."</td>";
	echo "<td>".$textStatus[13]."</td>";
	echo "<td>".$textStatus[14]."</td>";
	echo "<td>".$textStatus[2]."</td>";
echo "</tr>";
foreach ($tasks as $task) {
	echo "<tr>";
		echo "<td>";
		echo $task["name"];
		if ($task["coauthor_name"]) echo " (".$task["coauthor_name"].")";
		if ($task["site"] == 0) {
			echo " <a href='http://my.gmj.ru/Blog/Blog.aspx?bid=".$task["author_id"]."' alt='my.gmj.ru' title='Перейти на страничку блога'><img src='../img/rus.png'></a>";
		} else {
			echo " <a href='http://my.2gmj.com/Blog/Blog.aspx?bid=".$task["author_id"]."' alt='my.2gmj.com' title='Перейти на страничку блога'><img src='../img/usa.png'></a>";
		}
		echo "</td>";	
		echo "<td>";
		if ($task["real_name"]) echo $task["real_name"];
		if ($task["real_surname"]) echo " ".$task["real_surname"];
		if ($task["real_name"] == "" && $task["real_surname"] == "") echo "&mdash;";
		echo "</td>";
		echo "<td>";
		if ($task["status"] == 0) echo $textStatus[6];
		if ($task["status"] == 1) echo $textStatus[7];
		if ($task["status"] == 2) echo $textStatus[8];
		if ($task["status"] == 3) echo $textStatus[9];
		if ($task["status"] == 4) echo $textStatus[10];
		if ($task["status"] == 5) echo $textStatus[16];
		if ($task["status"] == 6) echo $textStatus[17];
		if ($task["status"] == 7) echo $textStatus[11];
		if ($task["status"] == 8) echo $textStatus[15]." <a href='../books/".$task["id"]."/".$task["name"].".fb2.zip'>".$textStatus[12]."</a>.";
		echo "</td>";
		echo "<td>";
		echo $task["pages_parsed"];
		echo "</td>";
		echo "<td>";
		echo $task["posts"];
		echo "</td>";
		echo "<td>";
		if ($task["images"] == 0) { echo 0; } else { echo $task["images_count"]; }
		echo "</td>";
		echo "<td>";
		echo $task["time"];
		echo "</td>";
	echo "</tr>";
}
?>
