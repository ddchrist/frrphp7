<!DOCTYPE html>
<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<?php
if(isset($_GET["key"])) echo '<title>'.$_GET["key"].'</title>';
else echo '<title>Reports</title>';
?>
</head>
<body>
<?php include("headerline.html"); ?>

<?php
if(isset($_GET["key"])) {
   StatOnCreatedBy($_GET["key"]);
   exit; 
}
?>

<a href="search_report_accumulated_comp.php">Report on accumulated PCRs created and closed (ClearQuest)</a><br>
<a href="search_report_accumulated.php">Report on accumulated PCRs created and closed (old GAIA Database)</a><br>
<a href="search_report_phases.php">Report on ORIGIN and DetectionPhase (GAIA DB and does not function anymore)</a><br>
<a href="search_reports.php?key=Originator_FullNm">PCRs created by</a><br>

</body>
</html>


<?php
function StatOnCreatedBy($key)
{
   echo "This view is only for ClearQuest PCRs i.e. PCRs starting with id COMP...<br>";
   $username=$_SESSION['username'];
   $DetectedRelease=trim(GetAdminData($username."_DetectedRelease"));
   echo "Detected Release: <b>";
   if($DetectedRelease=="") echo "All releases";
   else echo $DetectedRelease;
   echo "</b><br>";
   
//   $m = new Mongo();
//   $db = $m->selectDB('testdb');
//   $collection = new MongoCollection($db, 'pcr');
   $m = new MongoDB\Client();
   $db = $m->testdb;
   $collection = $db->pcr;

   $r=$collection->distinct($key, array('id' => new MongoRegex("/^COMP/")));
   $NumOfDiffValues=sizeof($r);
   echo "Found <b>".$NumOfDiffValues."</b> different values for the key <b>".$key."</b>.<br>";
   if($NumOfDiffValues>600) {
      echo "As more than 600 different values are detected. It makes no sense to continue with this key!";
      exit;
   }
   foreach($r as $value) {
   
      $query = array($key=>$value, 'id' => new MongoRegex("/^COMP/"));
      $query = array( '$and' => array( array('Detected_Release' => new MongoRegex("/".$DetectedRelease."/i")), $query));
   
   
      $cursor=$collection->find($query);
      if(trim($value)=="") $value="NO_INFO";  
      $arrKeyNum[$value]=$cursor->count();
   }
   arsort($arrKeyNum);
   echo '<table border="1">';
   echo "<tr><th>Values of ".$key."</th><th>Count of value</th></tr>";
   $Total=0;
   foreach($arrKeyNum as $name=>$Number) {
      echo "<tr>";
      echo "<td>".$name.'</td><td align="right">'.$Number."</td>";
      echo "</tr>";
      $Total+=$Number;
   }
   echo '<tr><td><b>Total count</b></td><td align="right"><b>'.$Total."</b></td></tr>";
   echo "</table>";
}


?>
