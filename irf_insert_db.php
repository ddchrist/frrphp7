<?php include("login_headerscript.php"); ?>
<?php include("headerline.html"); ?>
<?php
// IRF form inputs to be inserted into mongodb
$array=array("irfbuild","irfcountry","irfsite","irfdate","irftime","irfuser",
        "irfcwpnum","irftestid", "irfrigid", "irftestsession",
        "irfswversion", "irfdataset", "irftitle", "irfdetails", "irfstate");
// Store all irf inputs into session
foreach($array as $i) {
   $_SESSION[$i]=$_POST[$i];
}
$array[]='irflasttimeupdated';  // insert date & time for last modification
$_SESSION['irflasttimeupdated']=date("Y-m-d").' '.date("H:i:s");


//print_r($doc);
//$doc=array('jeg'=>'er', 'du'=>'har');
//print_r($doc);
//exit(0);

// Fetch (from DB) SW version, Dataset and Rig ID based on testsession name as key.
if (isset($_POST['updatetestsession'])) { 
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'testsessions');
   $cursor = $collection->findone(array('sessionname' => $_SESSION['irftestsession']));
   $_SESSION['irfswversion']=$cursor['swversion'];
   $_SESSION['irfdataset']=$cursor['dataset'];
   $_SESSION['irfrigid']=$cursor['rigid'];
   $_SESSION['irfbuild']=$cursor['build'];
   $_SESSION['irfsite']=$cursor['site'];
   $_SESSION['irfcountry']=$cursor['country'];
 
   header("location:irf_input.php");
   exit(0);
}  

// Insert IRF to MongoDB
if (isset($_POST['insertirf'])) {
   $m = new Mongo();
   $db = $m->selectDB('testdb');

   // Do sanity check on number. Must be format e.g. dk_ekdk_b1_0001
   $sanity=1;
   if(!$_SESSION['irfcountry']) {
      echo "Error: Country is missing<br>";
      $sanity=0;
   }
   if(!$_SESSION['irfbuild']) {
      echo "Error: Build is missing<br>";
      $sanity=0;
   }
   if(!$_SESSION['irfsite']) {
      echo "Error: Site is missing<br>";
      $sanity=0;
   }
   if(!$_SESSION['irfuser']) {
      echo "Error: Originator is missing<br>";
      $sanity=0;
   }
   if(!$_SESSION['irftitle']) {
      echo "Error: Title is missing<br>";
      $sanity=0;
   }
   if(!$_SESSION['irfcwpnum']) {
      echo "Error: CWP no. is missing<br>";
      $sanity=0;
   }
   if(!$_SESSION['irfrigid']) {
      echo "Error: RigID is missing<br>";
      $sanity=0;
   }
   
   if(!$_SESSION['irfdate']) {
      echo "Error: CWP no. is missing<br>";
      $sanity=0;
   }
   if(strlen($_SESSION['irfdate'])!=10 ||
      substr($_SESSION['irfdate'],4,1) != "-" ||
      substr($_SESSION['irfdate'],7,1) != "-") {
      echo "Error: Date format must be yyyy-mm-dd e.g. 2012-03-22<br>";
      $sanity=0;
   }
   if(!$_SESSION['irftime']) {
      echo "Error: Time is missing<br>";
      $sanity=0;
   }
   if(strlen($_SESSION['irftime'])!=8 ||
      substr($_SESSION['irftime'],2,1) != ":" ||
      substr($_SESSION['irftime'],5,1) != ":") {
      echo "Error: Time format must be hh:mm:ss e.g. 23:10:12<br>";
      $sanity=0;
   }
   if(!$sanity) {
      ?><a href="irf_input.php">Back</a><?php
      exit(0);
   }
   // Find next IRF number from collection irfcounters
   $collection = new MongoCollection($db, 'irfcounters');
   $cursor = $collection->findone(array('country' => $_SESSION['irfcountry']));
   $irfnum=0;
   $irfnum=$cursor['lastnum'.$_SESSION['irfbuild']];   // collection entry is e.g. 'lastnumB1'
   $irfnum+=1;   // count IRF number up
   // Store new IRF number back in collection irfcounters
   $cursor['lastnum'.$_SESSION['irfbuild']]=$irfnum;
   $collection->save($cursor);
   // Now insert the new irf into collection irf 
   $combinedirfnumber=strtoupper($_SESSION['irfcountry']."_".$_SESSION['irfsite']."_".$_SESSION['irfbuild']."_".str_pad($irfnum,4,"0",STR_PAD_LEFT));
   $doc=array('irfnum'=>$combinedirfnumber);
   $doc=array_merge((array)$doc, (array)array('irfsimplenum'=>$irfnum));
   $collection = new MongoCollection($db, 'irf');
   // Create array to be inserted into mongodb

   // $doc=array();
   foreach($array as $i) {
      $doc=array_merge((array)$doc, (array)array($i=>$_SESSION[$i]));
   }

   $collection->insert($doc);
   // Make sure that if there are attachment that the irfnum is inserted into
   // the GrifFS fs.files collection so a link is established
   header("location:irf_select_upload.php?irf=$combinedirfnumber&updatenumber=1");
}
exit(0);
?>
<?php
   // var_dump(iterator_to_array($cursor));


// maybe the following code is not used at all
$myusername=GetVarFromSession('username');


$db_name="admin"; // Database name
$tbl_name="members"; // Collection name

$m = new Mongo();
$db = $m->selectDB($db_name);
$collection = new MongoCollection($db, $tbl_name);

$query=array('user' => $myusername);
$cursor = $collection->findone($query);
$country=$cursor['country'];

?>

<?php
