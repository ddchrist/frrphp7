<?php
// Check if session is not registered , redirect back to main page.
// Put this code in first line of web page.
session_start();
//if(!session_is_registered('myusername')){
if(!isset($_SESSION['username'])) {
   echo "Access denied";
   echo '<br><a href="index.html">login</a>';
   exit();
   header("location:login_check.php");
   
}
?>
