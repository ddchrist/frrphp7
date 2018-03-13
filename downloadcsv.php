<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>
<?php


$db_name="testdb"; // Database name
if(isset($_GET['db'])) $db_name=$_GET['db'];
$tbl_name=$_GET['collection']; // Collection name

// Be aware that $_SESSION query is both set from irf list and pcr list
// This means that strange results can happen if not handled the right way
// i.e. doing search first then list then download
$query=unserialize($_SESSION["query"]);
//print_r($query);
//exit(0);

$user=$_SESSION['username'];
$CSVdelimiter=$_GET['csvdelimiter'];
StoreAdminData($user."_csvdelimitor",$CSVdelimiter);

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment;Filename=dboutput.csv");

$m = new Mongo();
$db = $m->selectDB($db_name);
$collection = new MongoCollection($db, $tbl_name);

// get a list of all keys in database
$cursor = $collection->findOne();
reset($cursor);
$key=array();
while (list($k, $v)=each($cursor)) {

//   echo "#".$k."$";  // remove
   if (isset($_GET[$k])) {
//      echo "SET"."&";  // remove
      $key[]=$k;
   }
//echo $k. " -> ". $_GET[$k]."  <br> ";  // remove
}
StoreAdminData($user."_DownloadKeyList",$key);

// Generate csv headings
$outstr="";

foreach($key as $i) {
   $outstr=$outstr . '"'.$i.'"'.$CSVdelimiter;
}
$outstr=substr($outstr,0,-strlen($CSVdelimiter))."\r\n";   // take out last comma in string and add newline. \n must be within double quotes

$cursor = $collection->find($query);  // Get all requested records from DB

foreach($cursor as $obj) {
   foreach($key as $i) {
      $outstr=$outstr . '"'.str_replace('"','""',$obj[$i]).'"'.$CSVdelimiter;
   }  
   $outstr=substr($outstr,0,-strlen($CSVdelimiter))."\r\n";   // take out last comma in string and add newline
}

$outstr=substr($outstr,0,-strlen($CSVdelimiter));

// convert to encoding Excel can handle
echo iconv("UTF-8", "ISO-8859-1//TRANSLIT",$outstr);

//echo "##".strlen($outstr);
?>

