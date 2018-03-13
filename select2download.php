<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>
<?php

$db_name="testdb"; // Database name
if(isset($_GET['db'])) $db_name=$_GET['db'];
$tbl_name=$_GET['collection']; // Collection name
if(isset($_GET['selectall'])) $select="checked";
else $select="";
$user1=$_SESSION['username'];
if(isset($_GET['deleteall'])) StoreAdminData($user1."_DownloadKeyList",array());

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title>Select fields to download</title>
</head>
<body>
<?php // include("headerline.html"); ?>
<?php

//$m = new Mongo();
//$db = $m->selectDB($db_name);
//$collection = new MongoCollection($db, $tbl_name);
   $m = new MongoDB\Client();
   $db = $m->$db_name;
   $collection = $db->$tbl_name;


// get a list of all keys in database
$cursor = $collection->findOne();
reset($cursor);
while (list($user[], $val)=each($cursor));
//sort($user);
?>
<h2>Select keys to download as .csv format (ISO-8859-1 encoding)</h2>
<form action="downloadcsv.php" method="get">
<table border="1">
<?php

// Get file delimitor from DB. Make default if non is available.
$username=$_SESSION['username'];
$CSVdelimiter=GetAdminData($username."_csvdelimitor");
if($CSVdelimiter=="") $CSVdelimiter=",";

$arrDownloadKeys=GetAdminData($username."_DownloadKeyList");
if($arrDownloadKeys=="") $arrDownloadKeys=array();

//print_r($arrDownloadKeys);
if($select<>"checked") $UseMemorisedKeys=true;
else $UseMemorisedKeys=false;

echo 'DB: <input type="text" name="db" value="'.$db_name.'" readonly>';
echo 'Collection: <input type="text" name="collection" value="'.$tbl_name.'" readonly>';
echo '<br>CSV delimiter: <input type="text" name="csvdelimiter" size="2" value="'.$CSVdelimiter.'">';
$i=0;
foreach ($user as $key) {
   if($key!="" && $key!="_id") {   // skip blank- and mongo ID keys
      if(!($i % 4)) echo '<tr>';   // take modulus 3 to create 3 columns
      $i=$i+1;
      if($UseMemorisedKeys) {
         if(in_array($key,$arrDownloadKeys)) $select="checked";
         else $select="";
      }
      echo '<td><input type="checkbox" name='.$key.' value="yes" '.$select.'>'.$key.'</td>';
      if(!($i % 4)) echo '</tr>';
   }
}
?>
</table> <br>
<input type="submit" value="Submit" />
<?php
echo '<a href="select2download.php?collection='.$tbl_name.'&db='.$db_name.'&selectall=1">Select All</a>';
echo ' <a href="select2download.php?collection='.$tbl_name.'&db='.$db_name.'&deleteall=1">Clear Selections</a>';
?>
</form>


</body>
</html> 
