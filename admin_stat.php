<?php include("login_headerscript.php"); ?>


<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Statistics</title>
</head>
<body>
<?php include("res_headerline.html"); ?>
<?php
if($_SESSION['username']<>"lbn") {
   echo "You have no access to this area!";
   exit;
}
$host="localhost"; // Host name
$db_name="admin"; // Database name
$tbl_name="members"; // Collection name

// Connect to server and select database.
$m = new Mongo();
$db = $m->selectDB($db_name);
$collection = new MongoCollection($db, $tbl_name);
$cursor = $collection->find();

$arrMembers=array();
$t=0;
foreach($cursor as $o) {
   if(!isset($o['LoginStat'])) $o['LoginStat']=0;
   if(!isset($o['SearchPCR'])) $o['SearchPCR']=0;
   if(!isset($o['SearchCombined'])) $o['SearchCombined']=0;
   if(!isset($o['SearchFullText'])) $o['SearchFullText']=0;
   $arrMembers[]=array("LoginStat"=>$o['LoginStat'], "user"=>$o['user'], "firstname"=>$o['firstname'], "lastname"=>$o['lastname'], "country"=>$o['country'], "SearchPCR"=>$o['SearchPCR'], "SearchFullText"=>$o['SearchFullText'], "SearchCombined"=>$o['SearchCombined']);
   $t++;
}
rsort($arrMembers);

echo "Number of members: ".$t."<br><br>";
echo '<table border="1">';
echo '<tr><th>User</th><th>First Name</th><th>Fam. Name</th><th>country</th><th>No of logins</th><th>PCRSearch</th><th>FTS</th><th>CombSearch</th></tr>';
foreach($arrMembers as $Member) {
   echo "<tr><td>".$Member["user"]."</td><td>".$Member["firstname"]."</td><td>".$Member["lastname"]."</td><td>".$Member["country"]."</td><td>".$Member["LoginStat"]."</td><td>".$Member["SearchPCR"]."</td><td>".$Member["SearchFullText"]."</td><td>".$Member["SearchCombined"]."</td></tr>";
}
echo "</table>";

?>
</body>
</html>

