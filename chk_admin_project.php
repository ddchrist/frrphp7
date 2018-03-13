<?php include("login_headerscript.php"); ?>
<?php

?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Admin Project</title>
</head>
<body>
<?php include("chk_headerline.html"); ?>

<?php
if (isset($_POST['GetProject'])) {
   $_SESSION["ProjectName"]=strtoupper($_POST['ProjectName']);
   echo "<br>".$_SESSION["ProjectName"];
   header("location:chk_manage_wps.php?CollapseAll=1");
   exit();
}
elseif (isset($_POST['CreateProject'])) {
   $_SESSION["ProjectName"]=strtoupper($_POST['ProjectName']);
   echo "<br>".$_SESSION["ProjectName"];
   
   // **** Check if project already exist and warn ! ****
   header("location:chk_manage_wps.php?UpdateToShow=1");
   exit();
}
elseif (isset($_POST['DeleteProject'])) {
   $_SESSION["ProjectName"]=strtoupper($_POST['ProjectName']);
   echo "<br>".$_SESSION["ProjectName"];
   
   // **** Check if rights to delete Project ****
   // **** Warn that project is about to be deleted ****
   // **** Delete Project ****

   exit();
}


// script does just bypass input so most can in reality be deleted
$_SESSION["ProjectName"]="MASTER";
header("location:chk_manage_wps.php?CollapseAll=1");
exit();
?>


<form action="chk_admin_project.php" method="post">
Checklist Name:
<input type="text" name="ProjectName" size="16" value="Master"/>


<input type="submit" name="GetProject" value="Get Project"/>
<input type="submit" name="CreateProject" value="Create Project / Add Top Level header"/>
<input type="submit" name="DeleteProject" value="Delete Project"/>

</form>
</body>
</html>

