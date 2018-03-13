<?php
require 'vendor/autoload.php';//composer require "mongodb/mongodb=^1.0.0.0"

function StoreAdminData($key,$val)
{
   $m = new MongoDB\Client();
   $db = $m->admin;
   $collection = $db->variables;
   $cursor = $collection->findone(array('Section' => 'General'));
   $cursor['Section']='General';
   $cursor[$key]=$val;
 //var_dump($key, $cursor[$key]);
   //  old modb $collection->save($cursor);
   $upResult = $collection->updateOne(
   ['General' => $key],
   [ '$set' => [$key => $val ]  ]
   );

   }
function GetAdminData($key)
{
//   $m = new MongoClient();
//   $db = $m->admin;
//   $collection = new MongoCollection($db, 'variables');
   $m = new MongoDB\Client();
   $db = $m->admin;
   $collection = $db->variables;

   $cursor = $collection->findone(array('Section' => 'General'));
   // var_dump($cursor);
   if(isset($cursor[$key])) return $cursor[$key];
   return;
}
function UpdateSearchStat($myusername,$SearchType)
// Count up search stat on type
// $SearchType: SearchPCR, SearchFullText, SearchCombined
{
   $db_name="admin"; // Database name
   $tbl_name="members"; // Collection name
   // Count every timer a user log in
//   $m = new MongoClient();
//   $db = $m->$db_name;
//   $collection = new MongoCollection($db, $tbl_name);
$m = new MongoDB\Client();
$db = $m->$db_name;
$collection = $db->$tbl_name;
   $query=array('user' => $myusername);
   $cursor = $collection->findone($query);
   // Statistics since 2014-05-14   
   if(isset($cursor[$SearchType])) $cursor[$SearchType]+=1;
   else $cursor[$SearchType]=1;
   //if(isset($cursor["LoginTime"])) $cursor["LoginTime"][]=date("Ymd-His");
   //else $cursor["LoginTime"]=array(date("Ymd-His"));
   
   $collection->save($cursor);
}

?>
