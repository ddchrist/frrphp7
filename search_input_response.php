<?php
include("login_headerscript.php");
include("admin_functions.php");
// Handle response with feedback to calling script
if (isset($_GET['SetBuild'])) {
   $Build=$_GET['SetBuild'];
   $Checked=$_GET['Checked'];
   $username=$_SESSION['username'];
   if($Checked=="yes") StoreAdminData($username."_searchB".$Build,"checked"); // mark as checked
   elseif($Checked=="no") StoreAdminData($username."_searchB".$Build,"");  // mark as unchecked
   else echo "error - No status on checkbox given";
   echo "Checked=".$Checked." SetBuild=".$Build;
}
// Handle storage in DB without feedback
// '$Value' is stored in '$username_$SetVarInDB'
elseif (isset($_GET['SetVarInDB']) && isset($_GET['Value'])) {
   $SetVarInDB=$_GET['SetVarInDB'];
   $Value=$_GET['Value'];
   $username=$_SESSION['username'];
   StoreAdminData($username."_".$SetVarInDB,$Value);
}
?>


?>
