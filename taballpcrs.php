<?php
// Check if session is not registered , redirect back to main page.
// Put this code in first line of web page.
session_start();
if(!session_is_registered('myusername')){
   echo "Access denied";
   exit();
   header("location:checklogin.php");
   
}
$regularid=$_SESSION["regularid"];

echo '<script type"text/javascript">';
foreach ($regularid as $i) {  
   echo 'window.open("print.php?pcr=' . $i . '");' ;
}
?>

</script>

