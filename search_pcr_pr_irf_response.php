<?php
include("login_headerscript.php");
include("admin_functions.php");
if (isset($_GET['SetBuild'])) {
   $Build=$_GET['SetBuild'];
   $Checked=$_GET['Checked'];
   $username=$_SESSION['username'];
   if($Checked=="yes") StoreAdminData($username."_searchB".$Build,"checked"); // mark as checked
   elseif($Checked=="no") StoreAdminData($username."_searchB".$Build,"");  // mark as unchecked
   else echo "error - No status on checkbox given";
   echo "Checked=".$Checked." SetBuild=".$Build;
}
?>
