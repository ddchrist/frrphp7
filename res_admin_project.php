<?php include("login_headerscript.php"); ?>
<?php

?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Admin Project</title>
</head>
<body>
<?php include("res_headerline.html"); ?>

<?php
if (isset($_POST['GetProject'])) {
   $_SESSION["ProjectName"]=strtoupper($_POST['ProjectName']);
   echo "<br>".$_SESSION["ProjectName"];
   header("location:res_manage_wps.php?CollapseAll=1");
   exit();
}
elseif (isset($_POST['CreateProject'])) {
   $_SESSION["ProjectName"]=strtoupper($_POST['ProjectName']);
   echo "<br>".$_SESSION["ProjectName"];
   
   // **** Check if project already exist and warn ! ****
   header("location:res_manage_wps.php?UpdateToShow=1");
   exit();
}
elseif (isset($_POST['DeleteProject'])) {
   $_SESSION["ProjectName"]=strtoupper($_POST['ProjectName']);
   echo "<br>".$_SESSION["ProjectName"];
   
   // **** Check if rights to delete Project ****
   // **** Warn that project is about to be deleted ****
   // **** Delete Project ****

   exit;
}

$_SESSION["ProjectName"]="COOPANS";
?>


<form action="res_admin_project.php" method="post">
Project Name:
<input type="text" name="ProjectName" size="16" value="COOPANS"/>


<input type="submit" name="GetProject" value="Get Project"/>
<input type="submit" name="CreateProject" value="Create Project / Add Top Level header"/>
<input type="submit" name="DeleteProject" value="Delete Project"/>

</form>
</body>
</html>

