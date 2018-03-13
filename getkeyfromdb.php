<?php
   $id=$_GET["id"];
   $key=$_GET["key"];
   $db_name="testdb"; // Database name
   $tbl_name="pcr"; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $query=array("id"=>$id);
   $cursor=$collection->findone($query);
   echo $cursor[$key];  
?>
