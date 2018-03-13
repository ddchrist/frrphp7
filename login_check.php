<?php
require 'vendor/autoload.php';//composer require "mongodb/mongodb=^1.0.0.0"
$host="localhost"; // Host name
$username=""; // MongoDB username - not used
$password=""; // MongoDB password - not used
$db_name="admin"; // Database name
$tbl_name="members"; // Collection name

// Connect to server and select databse.
$m = new MongoDB\Client();
$db = $m->$db_name;
$collection = $db->$tbl_name;

// username and password sent from form
$myusername=$_POST['myusername'];
$mypassword=$_POST['mypassword'];

// To protect MySQL injection (more detail about MySQL injection)
$myusername = stripslashes($myusername);
$mypassword = stripslashes($mypassword);

// Take username out of mongodb collection
$query=array('user' => $myusername);
$cursor = $collection->findone($query);

if($cursor!="" && $cursor['password']==$mypassword) {
         //Register $myusername, $mypassword and redirect to file "login_success.php"
         //Regenerate session ID to prevent session fixation attacks
         session_start(); // ***
         session_regenerate_id();
         //session_register("myusername");
         //$_SESSION['myusername']=$myusername;
         $_SESSION['username'] = $myusername;
         $_SESSION['country'] = $cursor["country"];
         UpdateLoginStat($myusername);
         //Write session to disc
         session_write_close();
         header("location:login_initial_startup.php");
         exit(0);
}

echo "Wrong Username or Password";
echo '<br><a href="index.html">login</a>';
// close session should actually be used for log out of session and not here...
session_start();
session_destroy();
exit(0);

function UpdateLoginStat($myusername) {
   $db_name="admin"; // Database name
   $tbl_name="members"; // Collection name
   // Count every timer a user log in
   $m = new MongoDB\Client();
   $db = $m->$db_name;
   $collection = $db->$tbl_name;
   $query=array('user' => $myusername);
   $cursor = $collection->findone($query);
      //var_dump( $cursor );
      //foreach ($cursor as $k=>$v) {
      //echo $k."  ".$v."<br>";
      //}

   // Statistics since 2014-02-20
   if(isset($cursor["LoginStat"])) $cursor["LoginStat"]+=1;
   else $cursor["LoginStat"]=1;
   if(isset($cursor["LoginTime"])) $cursor["LoginTime"][]=date("Ymd-His");
   else $cursor["LoginTime"]=array(date("Ymd-His"));
   
   $collection->updateOne(
   ['user' => '$myusername'],
   [ '$set' => ['LoginStat' => +1 ]  ]
   );
}

//var_dump( $cursor );
//foreach ($cursor as $k=>$v) {
//   echo $k."  ".$v."<br>";
//}

?>
