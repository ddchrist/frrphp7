<?php
// Check if session is not registered , redirect back to main page.
// Put this code in first line of web page.

//if (session_status() == PHP_SESSION_NONE) {
//    session_start();
//}

//if(session_id() == '') {
//    session_start();
//}

session_start();
//if(!session_is_registered('myusername')){

if(!isset($_SESSION['username'])) {
   echo "Access denied";
   echo "<br>Check that cookies are enabled as 'Medium-Low': Internet Explorer->Wheel (icon)->Internet options->Security->Security Level for this Zone (set to Medium-Low or lower)";
   echo '<br><a href="index.html">login</a>';

   $url="http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
   $_SESSION['url']=$url;

   exit();
   header("location:login_check.php");
   
}
?>
