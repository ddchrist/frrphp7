<?php include("login_headerscript.php"); ?>

<?php

// *******************************************************
// ******************** Definitions **********************
// *******************************************************
define("FILTER_DB",             "testdb");      
define("FILTER_COLLECTION",     "PreselectedFilters");  

//echo $_GET["FilterCommand"];
//exit(0);

// *******************************************************
// ********* GET / POST Control logic ********************
// *******************************************************
if ($_GET["FilterCommand"]=="Get") {
   // Get Preselected filter settings from DB
   $m = new Mongo();
   $db = $m->selectDB(FILTER_DB);
   $collection = new MongoCollection($db, FILTER_COLLECTION);

   if(substr($_GET["SelectedFilter"],0,1)=="_") {  // if global filter (starts with "_")
      $query = array('FilterType' => $_GET["FilterType"]);
      $query = array( '$and' => array( array('FilterName' => $_GET["SelectedFilter"]), $query));    
   }
   else {  // normal local filter
      $query = array('user' => $_SESSION['username']);
      $query1 = array( '$and' => array( array('FilterType' => $_GET["FilterType"]), $query));
      $query2 = array( '$and' => array( array('FilterName' => $_GET["SelectedFilter"]), $query));
      $query = array( '$and' => array($query1, $query2));     
   }

   $cursor = $collection->findone($query);
   $_SESSION["FilterName"]=$_GET["SelectedFilter"];

   // Dependent on what script called, store proper variables in DB
   if($_GET["FilterType"]=="PCRSearch") {
      for($i=0;$i<4;$i++) {
         $_SESSION["select_key".$i] = $cursor["key$i"];
         if($i<3) $_SESSION["select_log".$i] = $cursor["log$i"];
         $_SESSION["select_op".$i] = $cursor["op$i"];
         $_SESSION["select_val".$i] = $cursor["val$i"];
      }
      header("location:search_input.php");
      exit;  // make sure code below is not executed after execution of header
   }
   elseif($_GET["FilterType"]=="IRFSearch") ;
   elseif($_GET["FilterType"]=="RiskSearch") ;



}
elseif ($_GET["FilterCommand"]=="Save") {
   // Clean FilterName from unwanted characters.
   $FilterName=str_replace(" ","_",$_GET["FilterName"]);
   $FilterName=preg_replace('/[^a-zA-Z0-9_æøåÆØÅöÖäÄ]/','',$FilterName);
   $_SESSION["FilterName"]=$FilterName;
   
   
   // Store Preselected filter settings in DB
   $m = new Mongo();
   $db = $m->selectDB(FILTER_DB);
   $collection = new MongoCollection($db, FILTER_COLLECTION);
   $query = array('user' => $_SESSION['username']);
   $query1 = array( '$and' => array( array('FilterType' => $_GET["FilterType"]), $query));
   $query2 = array( '$and' => array( array('FilterName' => $FilterName), $query));
   $query = array( '$and' => array($query1, $query2));
   $cursor = $collection->findone($query);

   $cursor["user"]=$_SESSION['username'];
   $cursor["FilterType"]=$_GET["FilterType"];
   $cursor["FilterName"]=$FilterName;
   
   // Dependent on what script called, store proper variables in DB
   if($_GET["FilterType"]=="PCRSearch") {
      for($i=0;$i<4;$i++) {
         $cursor["key$i"] = $_SESSION["select_key".$i];
         if($i<3) $cursor["log$i"] = $_SESSION["select_log".$i];
         $cursor["op$i"] = $_SESSION["select_op".$i];
         $cursor["val$i"] = $_SESSION["select_val".$i];
      }
      $collection->save($cursor);
      header("location:search_input.php");
      exit;  // make sure code below is not executed after execution of header
   }
   elseif($_GET["FilterType"]=="IRFSearch") ;
   elseif($_GET["FilterType"]=="RiskSearch") ;

   
}
elseif ($_GET["FilterCommand"]=="Delete") {
   // Delete Preselected filter settings from DB
   $m = new Mongo();
   $db = $m->selectDB(FILTER_DB);
   $collection = new MongoCollection($db, FILTER_COLLECTION);
   $query = array('user' => $_SESSION['username']);
   $query1 = array( '$and' => array( array('FilterType' => $_GET["FilterType"]), $query));
   $query2 = array( '$and' => array( array('FilterName' => $_GET["SelectedFilter"]), $query));
   $query = array( '$and' => array($query1, $query2));
   $cursor = $collection->remove($query);
   $_SESSION["FilterName"]=""; // Clear text input on search page
   header("location:search_input.php");
   exit;  // make sure code below is not executed after execution of header

}


?>
<?php
// ******************************************************
// ***************** FUNCTIONS START ********************
// ******************************************************


// ******************************************************
// ****************** FUNCTIONS END**********************
// ******************************************************
?>

