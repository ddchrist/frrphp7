<?php include("login_headerscript.php");
require 'vendor/autoload.php';//composer require "mongodb/mongodb=^1.0.0.0"
 ?>

<?php

// *******************************************************
// ******************** Definitions **********************
// *******************************************************
define("ADD_FAVORITE_DB",             "testdb");      
define("ADD_FAVORITE_COLLECTION",     "Favorites");  

?>
<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title>Search PCR</title>
</head>
<body>
<?php include("headerline.html"); ?>
<?php
var_dump($_SESSION["country"]);
$pcrnum = $_SESSION["fullpcrnum"];

if (isset($_POST['addfavorite'])) {
   // Store Preselected filter settings in DB
//   $m = new MongoClient();
//   $db = $m->selectDB(ADD_FAVORITE_DB);
//   $collection = new MongoCollection($db, ADD_FAVORITE_COLLECTION);
   $m = new MongoDB\Client();
   $db = $m->ADD_FAVORITE_DB;
   $collection = $db->ADD_FAVORITE_COLLECTION;

   $query = array('user' => $_SESSION['username']);
   $query1 = array( '$and' => array( array('FavoriteType' => "PCR"), $query));
   $query2 = array( '$and' => array( array('pcrnum' => $pcrnum), $query));
   $query = array( '$and' => array($query1, $query2));
   $cursor = $collection->findone($query);

   $cursor["user"]=$_SESSION['username'];
   $cursor["pcrnum"]=$pcrnum;
   $cursor["FavoriteType"]="PCR";
   $cursor["comments"]=$_POST['comments'];
   $cursor["deadline"]=$_POST['deadline'];
   $cursor["tag"]=$_POST['tag'];
   
//   $collection->save($cursor);
   	  $inResult = $collection->insertOne(
        ['user' => $cursor['user'],
		'pcrnum' => $pcrnum,
		'FavoriteType' => "PCR",
		'comments' => $cursor['comments'],
		'deadline' => $cursor['deadline'],
		'tag' => $cursor['tag']]
      );

   
   echo "PCR: $pcrnum is stored as favorite";
   //header("location:search_pcr_pr_irf.php");
   exit;  // make sure code below is not executed after execution of header
}
elseif (isset($_GET['listfavorites'])) {
//   $m = new MongoClient();
//   $db = $m->selectDB(ADD_FAVORITE_DB);
//   $collection = new MongoCollection($db, ADD_FAVORITE_COLLECTION);
   $m = new MongoDB\Client();
   $db = $m->ADD_FAVORITE_DB;
   $collection = $db->ADD_FAVORITE_COLLECTION;

   $query = array('user' => $_SESSION['username']);
   $query = array( '$and' => array( array('FavoriteType' => "PCR"), $query));
   $cursor = $collection->find($query);
   foreach($cursor as $obj) {
      if(substr($obj['pcrnum'],0,4) == "COMP")
         echo '<a href="print.php?id=' . $obj['pcrnum']. '"target="_blank">' . $obj['pcrnum'].'</a> ';
      else
         echo '<a href="printGAIA.php?pcr=' . $obj['pcrnum']. '"target="_blank">' . $obj['pcrnum'].'</a> ';
      echo '<a href="admin_add_favorite.php?deletefavorite=' . $obj['pcrnum']. '">delete</a>';
      echo " <b>Deadline:</b> ".$obj['deadline'];
      echo " <b>Tag/ID:</b> ".$obj['tag'];
      echo "<br>";  
      echo $obj['comments'];
      echo "<br>";
   }
   exit(0);
}
else if (isset($_GET['deletefavorite'])) {
   // Delete favorite from DB
//   $m = new MongoClient();
//   $db = $m->selectDB(ADD_FAVORITE_DB);
//   $collection = new MongoCollection($db, ADD_FAVORITE_COLLECTION);
   $m = new MongoDB\Client();
   $db = $m->ADD_FAVORITE_DB;
   $collection = $db->ADD_FAVORITE_COLLECTION;

   $query = array('user' => $_SESSION['username']);
   $query = array( '$and' => array( array('pcrnum' => $_GET['deletefavorite']), $query));
   $cursor = $collection->remove($query);
   header("location:admin_add_favorite.php?listfavorites=1");
   exit;  // make sure code below is not executed after execution of header

}

//$m = new MongoClient();
//$db = $m->selectDB(ADD_FAVORITE_DB);
//$collection = new MongoCollection($db, ADD_FAVORITE_COLLECTION);
   $m = new MongoDB\Client();
   $db = $m->ADD_FAVORITE_DB;
   $collection = $db->ADD_FAVORITE_COLLECTION;


$query = array('user' => $_SESSION['username']);
$query1 = array( '$and' => array( array('FavoriteType' => "PCR"), $query));
$query2 = array( '$and' => array( array('pcrnum' => $pcrnum), $query));
$query = array( '$and' => array($query1, $query2));

$cursor = $collection->findone($query);
if(isset($cursor['comments'])) $text=$cursor['comments'];
else $text="";
if(isset($cursor['deadline'])) $deadline=$cursor['deadline'];
else $deadline=date("Y-m-d");
if(isset($cursor['tag'])) $tag=$cursor['tag'];
else 
  {
    // Insert header into Tag/ID as default
    $collection2 = new MongoCollection($db, "pcr");
    // pcrnum is e.g. B1_7221 where build or product name is the first part
    // PCR is looked up in relation to its number and product name / build
    $build=strtoupper(strstr($pcrnum,"_",true));
    if($build=="B1") $productname="COOPANS";
    elseif($build=="B2") $productname="COOPANS_B2";
    $pcrnumint=strstr($pcrnum,"_");
    $pcrnumint=substr($pcrnumint,1);  // take out "_" so only number is left
    $pcrnumint=intval($pcrnumint); // make sure that value to search for is integer
    $query=array('RegularID' => $pcrnumint);
    $query=array( '$and' => array( array('ProductName' => $productname), $query));
    $cursor = $collection2->findOne($query);
    $tag=$cursor["Subject"];
  }

echo '<form action="admin_add_favorite.php" method="post">';
echo 'PCR number: <input type="text" name="pcrnum" value="'.$pcrnum.'" size="7" readonly/><br>';
echo 'Tag/ID: <input type="text" name="tag" value="'.$tag.'" size="60"/><br>';
echo 'Comments:<br><textarea name="comments" COLS=80 ROWS=15>'.$text.'</textarea><br>';
echo 'Deadline: <input type="text" name="deadline" value="'.$deadline.'" size="9"/><br>';
echo '<input type="submit" name="addfavorite" value="Add Favorite"/>';
echo '</form>';
?>


</body>
</html> 

<?php
// ******************************************************
// ***************** FUNCTIONS START ********************
// ******************************************************


// ******************************************************
// ****************** FUNCTIONS END *********************
// ******************************************************
?>
