<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>
<?php

// Select startup based on link given before login (no logon initiated)
if(isset($_SESSION['url'])) {
  $url=$_SESSION['url'];
  $InitialStartup="PCR";
  if(strpos($url,"res_")>0) {
    $_SESSION["ProjectName"]="COOPANS";
    $InitialStartup="COOPANS";
  }
  else if(strpos($url,"chk_")>0) {
    $_SESSION["ProjectName"]="MASTER";
    $InitialStartup="Checklist";
  }
  else $_SESSION["ProjectName"]="";
  
  StoreAdminData($_SESSION['username']."_InitialStartup",$InitialStartup); 
  unset($_SESSION['url']);
  header("location:$url");
  exit;
}

// Select startup application based on last used
$InitialStartup=GetAdminData($_SESSION['username']."_InitialStartup");
if($InitialStartup=="") {
   $InitialStartup="PCR";
   StoreAdminData($_SESSION['username']."_InitialStartup",$InitialStartup);
}
if($InitialStartup=="PCR") header("location:search_input.php");
elseif($InitialStartup=="Checklist") header("location:chk_admin_project.php");
else {
   $_SESSION["ProjectName"]=$InitialStartup;
   header("location:res_manage_wps.php?CollapseAll=1");
}
exit;

