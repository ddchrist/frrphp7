<?php include("login_headerscript.php"); ?>
<?php include("class_lib.php"); ?>
<?php
define('HTML_INPUT', 1);
define('HTML_CHECK', 2);
define('_GLOBAL_TABLE', '_Admin_');  // This is used for the Admin collection to store tables that must be global/common for all users. A document is created with key "User" = "_Admin_"
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Admin Project</title>
</head>
<body>

<?php include("chk_headerline.html"); ?>
<?php include("admin_functions.php"); ?>

<?php
// This routine can change roles in the checklist database on all test cases
if (isset($_GET['MapRoles'])) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   $obj=new db_handling(array(),"");
   $obj->DataBase="Checklist";
   $obj->CollectionName="MASTER";
   $obj->get_cursor_find();
   foreach ($obj->cursor as $o) {
      //echo $o["WPNum"]." ";
      if(isset($o["Team_at_ACG_MASTER"])) {
         $arr=$o["Team_at_ACG_MASTER"];
         $n=array();
         foreach($arr as $a) {
            $n[]=substr($a,4);
            //echo $n."   ";
         }
         unset($o["Team_at_ACG_MASTER"]);
         $o["Team_at_ACGMASTER"]=$n; // remember to change name in collection Admin 
         $obj->save_collection($o);
      }
   }
   exit;
}

if (isset($_GET['Roles'])) {   // Define/add Roles
   if(!UserRightsChecks("Roles","Checklist")) {
      echo "You have no rights to do this - sorry";
      exit();
   }
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Checklist");
   $obj->Name="Roles";
   $obj->ReturnScript="chk_admin.php?Roles=1";
   $obj->arrHeaders=array("Role"=>"left", "ReqRes"=>"left");

   echo $obj->get_table_as_html();

?>
   <a href="chk_admin.php?EditRoles=1">Edit Roles</a>
   <form action="chk_admin.php" method="post">
   Name of Role (avoid dublicates!):
   <input type="text" name="NameRole" size="16" value=""/><br>
   Requested Ressource:
   <input type="text" name="ReqRes" size="16" value=""/><br>
   <br>
   <input type="submit" name="Roles2" value="Add Role"/>
   <input type="submit" name="RolesDefault" value="Insert default roles"/>
   </form>
<?php
   // header("location:chk_admin.php");
   exit();
}
else if (isset($_POST['Roles2'])) { 
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Checklist");
   $obj->Name="Roles";
   $arrArrayToInsert=array("Role"=>strtoupper($_POST['NameRole']), "ReqRes"=>strtoupper($_POST['ReqRes']) );
   $obj->insert($arrArrayToInsert);
   header("location:chk_admin.php?Roles=1");
}
else if (isset($_POST['RolesDefault'])) {
$RolesArray = 
array(
"FDA01"=>"TRG", 
"COIF01"=>"TRG", 
"COIF02"=>"TRG", 
"MIL01"=>"MLJ",
"ACC01_EAST"=>"JHR", 
"ACC02_EAST"=>"JHR", 
"ACC03_EAST"=>"JHR",
"ACC04_EAST"=>"JHR", 
"ACC05_EAST"=>"JHR", 
"ACC06_EAST"=>"JHR",
"ACC01_WEST"=>"JHR", 
"ACC02_WEST"=>"JHR", 
"ACC03_WEST"=>"JHR",
"ACC04_WEST"=>"JHR",
"APP01"=>"JLH", 
"APP02"=>"JLH", 
"APP03"=>"JLH",
"EKRK01"=>"JHI",
"EKBI01"=>"PSM", 
"CCAM_EXP"=>"TAG", 
"SYSEXP01"=>"TAG",
"SYSEXP01"=>"TAG", 
"SYSEXP01"=>"TAG", 
"SYSEXP01"=>"TAG",
"DPR01_COOPANS"=>"TSS", 
"OTA01"=>"SKM", 
"OTA02"=>"SKM",
"SUP_EXP"=>"TSS", 
"ACC_COOR"=>"TAG", 
"ACC_EXP_WEST"=>"TSS",
"ACC_EXP_EAST"=>"TSS",
"APP_COOR"=>"TAG", 
"APP_CH_EXP"=>"TSS",
"FDA_EXP"=>"TSS", 
"COIF_EXP"=>"TSS", 
        );

   ksort($RolesArray);
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Checklist");
   $obj->Name="Roles";
   foreach($RolesArray as $key=>$val) {
      // only insert role if it does not already exist
      // There is a problem here as if a new person is connected to a role
      // then it will write the same role again but with the new person
      // This means that two of the same roles can be created.
      if(!($obj->entry_in_table(array("Role"=>$key, "ReqRes"=>"")))) {
         $obj->insert(array("Role"=>$key, "ReqRes"=>""));
      }
   }
   header("location:chk_admin.php?Roles=1");
   exit;
}
else if (isset($_GET['Teams'])) {   // Define Teams
   if($_GET['Teams']=="1") {  // list roles to be selected for team
      $name="Roles";
      $key=_GLOBAL_TABLE;
   }
   else {  // Use given team name to list teams
      $name=$_GET['Teams'];
      $key="_Country_".$_SESSION["country"];  
   }

//   $obj=new TableHandling($key,"User","Admin","Checklist");
   $obj=new TableHandling($key,"User","Admin","Checklist");
   $obj->Name=$name;

   $obj->arrHeaders=array("Role"=>"left", "ReqRes"=>"left");
   $obj->ShowRemoveEntry=False; // Deactivate "delete entry" posibility
   $obj->AddCheckBox=True;
?>
   <form action="chk_admin.php" method="post">
<?php
   echo $obj->get_table_as_html();
?> <br>Select team members and indicate name of team (use only 'a' to 'z' and 'A' to 'Z' and '0' to '9' and '-'):
   <input type="text" name="NameTeam" size="16" value=""/>
   <br>
   <input type="submit" name="Teams2" value="Submit"/>
   </form>
<?php
   exit();
}
else if (isset($_POST['Teams2'])) {
   // Store team in DB
   // obj: object pointing to roles to fetch role data from
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Checklist");
   $obj->Name="Roles";
   // obj2: object pointing to where to store selected team data
   // name of table is "Team_".$_SESSION["country"]."_" + given team name.
   //      E.G. dk_Team_TeamName
   $obj2=new TableHandling("_Country_".$_SESSION["country"],"User","Admin","Checklist");
   $NameTeam=preg_replace('/[^A-Za-z0-9-]/','',$_POST['NameTeam']);
   
   $obj2->Name="Team_".$_SESSION["country"]."_".$NameTeam;
   // If 'Team_...' table already exist, then delete it
   if(isset($obj2->obj->cursor[$obj2->Name])) unset ($obj2->obj->cursor[$obj2->Name]);

//print_r($obj->obj->cursor);
//echo "<br>";
   if (isset($_POST['check'])) {
      $arrOptions = $_POST['check'];
      for ($i=0; $i<count($arrOptions); $i++) {
         $obj->EntryPointer=intval(substr($arrOptions[$i],2));
//       echo $arrOptions[$i]." ".intval(substr($arrOptions[$i],2))."<br />";
         $TeamEntry=$obj->get_entry();
         // fill in keys not given by team selection
         if(!isset($TeamEntry["ReqRes"])) $TeamEntry["ReqRes"]="";
         $obj2->insert($TeamEntry);
      }
  }
  header("location:chk_admin.php?ListTeams=1");
  exit;
}
else if (isset($_GET['ListTeams'])) {
   $obj=new db_handling(array('User' => "_Country_".$_SESSION["country"]),"Backup");
   $obj->DataBase="Checklist";
   $obj->CollectionName="Admin";
   $obj->get_cursor_findone();
//print_r($obj->cursor);
//echo "<br><br>";
   if($obj->cursor <> NULL) {
      foreach ($obj->cursor as $oList=>$v) {
         if(substr($oList,0,8)=="Team_".$_SESSION["country"]."_") {
            echo '<a href="chk_admin.php?TeamDel='.$oList.'">X</a>'."&nbsp;"."&nbsp;";
            echo '<a href="chk_admin.php?TeamView='.$oList.'">View</a>'."&nbsp;"."&nbsp;";
            echo substr($oList,8);
            echo "<br>";
         }
      }
   }
   echo '<br><a href="chk_admin.php?Teams=1">Define new team</a>';
exit;
}
else if (isset($_GET['TeamDel'])) {
   if(!UserRightsChecks("Roles","Checklist")) {
      echo "You have no rights to do this - sorry";
      exit();
   }
   // Remove RoleTeam from DB=Checklist, Collection=Admin
   $obj=new db_handling("","");
   $obj->DataBase="Checklist";
   $obj->CollectionName="Admin";
   $obj->collection = $obj->get_collection();
   $Filter=array('User' => "_Country_".$_SESSION["country"]);
   $Update=array('$unset'=>array($_GET['TeamDel']=>1));
   $obj->collection->update($Filter,$Update);

   // Remove RoleTeam occurences from master checklist 
   // i.e. DB=Checklist, Collection=MASTER

   $query=array($_GET['TeamDel'] => array('$exists' => true));
   $obj=new db_handling("", "");  // do not list backup WPs
   $obj->DataBase="Checklist";
   $obj->CollectionName="MASTER";
   $obj->collection = $obj->get_collection();
   $Update=array('$unset'=>array($_GET['TeamDel']=>1));
   $Multiple=array('multiple' => true);
   $obj->collection->update($query,$Update,$Multiple);

   header("location:chk_admin.php?ListTeams=1");
   exit;
}
else if (isset($_GET['TeamView'])) {
   $team=$_GET['TeamView'];
   header("location:chk_admin.php?Teams=$team");
   exit;
}
else if (isset($_GET['CloneTeam'])) {
   if(!UserRightsChecks("Roles","Checklist")) {
      echo "You have no rights to do this - sorry";
      exit();
   }
?>
   <form action="chk_admin.php" method="post">
   Select team to be cloned:
   <?php
   // Handle Filter selection
   $obj=new db_handling(array('User' => "_Country_".$_SESSION["country"]),"Backup");
   $obj->DataBase="Checklist";
   $obj->CollectionName="Admin";
   $obj->get_cursor_findone();
   
   $options = array("RoleTeam"=>"");
   if($obj->cursor<>NULL) {   // only create list if roleteams have been defined
      foreach ($obj->cursor as $oList=>$v) {
         if(substr($oList,0,8)=="Team_".$_SESSION["country"]."_") {
            $options=array_merge($options, array(substr($oList,8) => $oList));
         }
      }
   }
   $selected=GetAdminOnUser("SelectedRoleTeam","Checklist");
   echo generateSelect("RoleTeam", $options, $selected);   
   ?>
   <br>
   <input type="submit" name="CloneTeam1" value="Submit"/>
   </form>
   <?php
   exit;
}
else if (isset($_POST['CloneTeam1'])) {
// Select Roles to be copied/cloed
   $obj=new db_handling(array('User' => "_Country_".$_SESSION["country"]),"Backup");
   $obj->DataBase="Checklist";
   $obj->CollectionName="Admin";
   $obj->get_cursor_findone();
   $RoleTeam=$_POST["RoleTeam"];
   ?>
   <form action="chk_admin.php" method="post">
   <input type="text" name="RoleTeam" size="16" value="<?php echo $_POST["RoleTeam"]; ?>" readonly />
   <br>
   Select roles to be copied:<br>
   <?php
   foreach($obj->cursor[$RoleTeam] as $o) {
      echo '<input type="checkbox" name="Role[]" value="'.$o["Role"].'" checked />';
      echo $o["Role"]."<br>";
   }
   ?>
   Name to new cloned team:
   <input type="text" name="ClonedTeam" size="16" value="" />
   <br>
   <input type="submit" name="CloneTeam2" value="Submit"/>
   </form>
   <?php

   exit;

}
else if (isset($_POST['CloneTeam2'])) {
   // Clone Checklist / Admin to increase RoleTeam List with new item
//   $ClonedName=preg_replace("/^[a-z][0-9a-z]/i","",$_POST['ClonedTeam']);
   $ClonedName=$_POST['ClonedTeam'];
   if(!preg_match("/^[a-z][a-z0-9]/i",$ClonedName)) {
      echo "Name to clone to must be given. Name must start with alphabetical character e.g. MyTeam1. Only letters and numbers are allowed";
      exit;
   }

   echo "Cloned name: ".$ClonedName."<br>";
   echo "RoleTeam: ".$_POST["RoleTeam"]."<br>";
   $ClonedName="Team_".$_SESSION["country"]."_".$ClonedName;

   $obj=new db_handling(array('User' => "_Country_".$_SESSION["country"]),"");
   $obj->DataBase="Checklist";
   $obj->CollectionName="Admin";
   $obj->get_cursor_findone();
   // take out Roles not selected from Checklist / Admin
   foreach($obj->cursor[$_POST["RoleTeam"]] as $o) {
      if(in_array($o["Role"],$_POST['Role'])) $clone[]=array("Role"=>$o["Role"], "ReqRes"=>$o["ReqRes"]);
   }
   $obj->cursor[$ClonedName]=$clone;
   $obj->save_collection();

   $obj2=new db_handling("","");
   $obj2->DataBase="Checklist";
   $obj2->CollectionName="MASTER";
   $obj2->collection = $obj2->get_collection();

   // Copy/Clone all RoleTeam entries in Checklist / MASTER database/collection
   $obj=new db_handling(array($_POST["RoleTeam"] => array('$exists' => true)),"");
   $obj->DataBase="Checklist";
   $obj->CollectionName="MASTER";
   $obj->get_cursor_find();
//echo $ClonedName."<br>";
   foreach ($obj->cursor as $o) {
      echo $o["WPNum"]." ";
      // Only copy selected Roles into Checklist / MASTER
      $clone=array();
      foreach($o[$_POST["RoleTeam"]] as $osel) {
         if(in_array($osel,$_POST['Role'])) $clone[]=$osel;
      }
      $Filter=array("WPNum"=>$o["WPNum"]);
      $Update=array('$set'=>array($ClonedName=>$clone));
      $obj2->collection->update($Filter,$Update); 
   }
   exit;
}
// ************ EDIT ROLES *****************
elseif (isset($_GET["EditRoles"])) {
   if(!UserRightsChecks("Roles","Checklist")) {
      echo "You have no rights to do this - sorry";
      exit();
   }
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Checklist");
   $obj->Name="Roles";
   $obj->ReturnScript="chk_admin.php?EditRoles=1";
   $obj->arrHeaders=array("Role"=>"left", "ReqRes"=>"left");
   $obj->arrHTMLfunctions=array(HTML_INPUT, HTML_INPUT);
?>
 <form action="chk_admin.php" method="post">
<?php
 echo $obj->get_table_as_html();
?>
<input type="submit" name="EditRoles2" value="Submit"/>
</form>
<?php
exit;
}
elseif (isset($_POST["EditRoles2"])) {
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Checklist");
   $obj->Name="Roles";
   $obj->ReturnScript="chk_admin.php?Roles=1";
   $obj->arrHeaders=array("Role"=>"left", "ReqRes"=>"left");
   $obj->FixDaysHours=False;  // deactivate fixing of mandays/manhours 
   $obj->insert_input_to_table();
//   $obj->obj->save_collection();
   header("location:chk_admin.php?Roles=1");
   exit;
}

if (isset($_GET['UserRights'])) {   // set user rights for access to DB
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Checklist");
   
   $obj->Name="Rights";
   $obj->ReturnScript="chk_admin.php?UserRights=1";
   $obj->arrHeaders=array("User"=>"left", "WriteLevel"=>"left", "Roles"=>"left");

   echo $obj->get_table_as_html();

?>
   <a href="chk_admin.php?EditUserRights=1">Edit User Rights</a>
   <form action="chk_admin.php" method="post">
   <table border="0">
   <tr>
   <td>Name of user (login name - case insensitive!):</td>
   <td><input type="text" name="User" size="16" value=""/></td>
   </tr>
   <tr>
   <td>Write Level:</td>
   <td><input type="text" name="WriteLevel" size="16" value=""/></td>
   </tr>
   <tr>
   <td>Change Roles:</td>
   <td><input type="checkbox" name="checkbox" value="1"/></td>
   </tr>
   </table>
   <input type="submit" name="UserRights2" value="Submit"/>
   </form>

<span title="Each 'WriteLevel' must start with '#' otherwise the value could be converted to float by PHP. This is a bug but costly to remove.
Admin rights - type: #*
User rights to '21.10' - type: #21.10
User rights to '21.10', 23.10 and 24.05 - type: #21.10&23.10&24.05
User rights to '21.02', 22.02, 23.02, ... - type: #??.02
'?' is the same as 'any of'"><p style="color:#C00000">How User Rights work (pop-up)</p></span>


<?php
   exit();
}
else if (isset($_POST['UserRights2'])) {
   if(!$_SESSION['username']="lbn") exit; 
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Checklist");
   $obj->Name="Rights";
   if(substr($_POST['WriteLevel'],0,1)<>"#") $_POST['WriteLevel']="#".$_POST['WriteLevel'];
   if($_POST['checkbox']) $Roles="T";
   else $Roles="F";
   $arrArrayToInsert=array("User"=>strtoupper($_POST['User']), "WriteLevel"=>$_POST['WriteLevel'], "Ch_Roles"=>$Roles);
   $obj->insert($arrArrayToInsert);
   header("location:chk_admin.php?UserRights=1");
   exit;
}
elseif (isset($_GET["EditUserRights"])) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Checklist");
   $obj->Name="Rights";
   $obj->ReturnScript="chk_admin.php?EditUserRights=1";
   $obj->arrHeaders=array("User"=>"left", "WriteLevel"=>"left",
                           "Roles"=>"left");
   $obj->arrHTMLfunctions=array(HTML_INPUT, HTML_INPUT, HTML_CHECK);
?>
 <form action="chk_admin.php" method="post">
<?php
 echo $obj->get_table_as_html();
?>
<input type="submit" name="EditUserRights2" value="Submit"/>
</form>
<span title="Each 'WriteLevel' must start with '#' otherwise the value could be converted to float by PHP. This is a bug but costly to remove.
Admin rights - type: #*
User rights to '21.10' - type: #21.10
User rights to '21.10', 23.10 and 24.05 - type: #21.10&23.10&24.05
User rights to '21.02', 22.02, 23.02, ... - type: #??.02
'?' is the same as 'any of'"><p style="color:#C00000">How User Rights work (pop-up)</p></span>
<?php
exit;
}
elseif (isset($_POST["EditUserRights2"])) {
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Checklist");
   $obj->Name="Rights";
   $obj->ReturnScript="chk_admin.php?UserRights=1";
   $obj->arrHeaders=array("User"=>"left", "WriteLevel"=>"left", "Roles"=>"left");
   $obj->arrHTMLfunctions=array(HTML_INPUT, HTML_INPUT, HTML_CHECK);
   $obj->FixDaysHours=False;  // deactivate fixing of mandays/manhours 
   $obj->insert_input_to_table();
//   $obj->obj->save_collection();
   header("location:chk_admin.php?UserRights=1");
   exit;
}
elseif (isset($_GET["Renumber"])) {
   ?>
   <b>Renumber</b>
   <form action="chk_admin.php" method="post">
   <table border="0">
   <tr>
   <td>Number of first Test Case to renumbered e.g. '07.01.03':</td>
   <td><input type="text" name="FirstTCNum" size="8" value=""/></td>
   </tr>
   <tr>
   <td>New first subnumber of Test Cases e.g. '10' if starting at '07.01.10':</td>
   <td><input type="text" name="NewTCNum" size="8" value=""/></td>
   </tr>
   <tr>
   <td>Step e.g. '2':</td>
   <td><input type="text" name="Step" size="2" value=""/></td>
   </tr>
   </table>
   <input type="submit" name="Renumber2" value="Submit"/>
   </form>
   Note that renumber is only possible within the same seria of numbers. E.g. If first TC number is 07.10.03, the new number must be within 07.01.xx
   <?php
   exit;
}
elseif (isset($_POST["Renumber2"])) {
   if(!UserRights($_POST["NewTCNum"],"Checklist")) {
      echo "You do not have user rights to renumber this/these test case(s)";  
      exit();
   }
   // Sanity checks
   if(!TestCaseNumber($_POST["FirstTCNum"])) exit;
   $arrWP=explode(".",$_POST["FirstTCNum"]);
   $_POST["NewTCNum"]=$arrWP[0].".".$arrWP[1].".".$_POST["NewTCNum"];
   if(!TestCaseNumber($_POST["NewTCNum"])) exit;

   Renumber(false); // show conversion of numbers without modifying the DB
   ?>
   <form action="chk_admin.php" method="post">
   <input type="text" name="FirstTCNum" size="6" value="<?php echo $_POST["FirstTCNum"];?>" readonly/>
   <input type="text" name="NewTCNum" size="6" value="<?php echo $_POST["NewTCNum"];?>" readonly/>
   <input type="text" name="Step" size="4" value="<?php echo $_POST["Step"];?>" readonly/>
   <br>
   <input type="submit" name="Renumber3" value="Cancel"/>
   <input type="submit" name="Renumber3" value="Confirm"/>
   </form>
   <?php
   exit;
}
elseif (isset($_POST["Renumber3"])) {
   if($_POST["Renumber3"]=="Confirm") {
      Renumber(true); // do conversion by modifying the DB
      echo "Renumbering done!";
      exit;
   }
   echo "Renumbering aborted!";
   exit;
}

elseif (isset($_POST["ChangesSubmit"])) {
   $NewDate=$_POST['TestCaseChanges'];
   if(trim($NewDate)=="") {  // add 3 months to todays date if no input
      $NewDate=strtotime("+3 months", time());
      $NewDate=date("Y-m-d",$NewDate);
   }
   if(!ValidDateFormat($NewDate)) {
      echo "Incorrect Date format use e.g. 2013-05-15!";
   }
   else {
      StoreAdminOnUser("TestCaseChangedDate",$NewDate,"Checklist");
   }
}
elseif (isset($_GET["CorrReleaseNo"])) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   ?>
   <form action="chk_admin.php" method="post">
   Existing Release Number:
   <input type="text" name="ReleaseNoOld" size="30" value="" />
   <br>
   New Release Number:
   <input type="text" name="ReleaseNoNew" size="30" value="" />
   <br>
   <input type="submit" name="CorrReleaseNo2" value="Confirm"/>
   </form>
   <?php
   exit;
}
elseif (isset($_POST["CorrReleaseNo2"])) {
   $m = new Mongo();
   $db = $m->selectDB('Checklist');
   $collection = new MongoCollection($db, 'TestChecks');
   $query=array("SoftwareVersion"=>$_POST["ReleaseNoOld"]);
   $queryUpdate=array('$set'=>array("SoftwareVersion"=>$_POST["ReleaseNoNew"]));   
   $collection->update($query, $queryUpdate, array('multiple' => true));
   $doc=$collection->find(array("SoftwareVersion"=>$_POST["ReleaseNoNew"])); 
   echo "Updated the following TCs:<br>";
   foreach($doc as $o) {
      echo $o["TC"]." ";   
   }
   ?>
   <br><a href="chk_admin.php?CorrReleaseNo">Correct release number of TestChecks</a><br>
   <?PHP
   exit;
}

//*********************************
elseif(isset($_POST["StoreDPRValues"])) {
   $arrAllTagsWithValues=GetAdminData("DPRTagsWithValues_".$_SESSION["country"]);
   
   $optionArray = $_POST['DPR_group'];
   $t=0;
   foreach($arrAllTagsWithValues as $k=>$v) {
      $Value=preg_replace("/[^a-zA-Z0-9_\-\+]/", "", $optionArray[$t++]);
      $arrNewAllTagsWithValues[$k]=$Value;
   }
      
   StoreAdminData("DPRTagsWithValues_".$_SESSION["country"], $arrNewAllTagsWithValues);

   foreach($arrNewAllTagsWithValues as $k=>$v) {
      echo $k."=".$v."<br>";
      UpdateCountryNoteInTestCase($k,$v);
   }
   echo "DPR values are now updated!";
   exit;
}

elseif(isset($_GET["ListDPRValues"])) {
   //$obj=new db_handling(array($_POST["RoleTeam"] => array('$exists' => true)),"");
   //Reverse
   //$query=array('Note' => new MongoRegex("/#DPR#/"));
   $query=array('Note'."_".$_SESSION["country"] => new MongoRegex("/#DPR#/"));
   $obj=new db_handling($query,"");
   $obj->DataBase="Checklist";
   $obj->CollectionName="MASTER";
   $obj->get_cursor_find_sort();
   $arrAllTagsUnique=array();
   $arrTags=array(); // Associative array of unique Tags/Values
   echo "#DPR# tags subtrached from <b>Note_".$_SESSION["country"]."</b><br>";
   echo "Only allowed characters are: 'a to z', '+', '-', '_', '0 to 9'. Other characters are just deleted<br><br>";
   foreach($obj->cursor as $o) {
      //if(substr($o["WPNum"],0,1)=="8") break; // **LBN
      echo $o["WPNum"]." ";
      
      // Fetch tags with value from TestCase
      //Reverse
      //GetDPRTags($o["Note"],$arrTags);
      GetDPRTags($o["Note"."_".$_SESSION["country"]],$arrTags);
      
      
   }
   StoreAdminData("DPRTagsWithValues_".$_SESSION["country"], $arrTags);
   ListAllTagsWithTCNumLinks($arrTags);
   exit;
}
elseif(isset($_GET["MergeNoteAndCountryNote"])) {
   echo "Need to activate with hard coding! - so exit script";
   exit;
   
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   $query=array();
   $obj=new db_handling(array(),"");
   $obj->DataBase="Checklist";
   $obj->CollectionName="MASTER";
   $obj2=new db_handling("","");
   $obj2->DataBase="Checklist";
   $obj2->CollectionName="MASTER";
   $obj2->collection = $obj2->get_collection();
   
   $obj->get_cursor_find();
//echo $ClonedName."<br>";
   echo '<table border="1">';
   foreach ($obj->cursor as $o) {
      if(!isset($o["Note"])) continue;
      if(!isset($o["Note_dk"])) $o["Note_dk"]="";

      echo '<tr><td><a href="chk_manage_input_table.php?WPNum='.$o["WPNum"].'">'.$o["WPNum"].'</a></td><td>-</td></tr>';
      echo '<tr><td>Note:</td><td>'.$o["Note"].'</td></tr>';
      
      echo '<tr><td>Note_dk:</td><td>'.$o["Note_dk"].'</td></tr>';
      // Only copy selected Roles into Checklist / MASTER
      
      $merge=$o["Note"]."\n".$o["Note_dk"];
      
      echo '<tr><td>Merged:</td><td>'.$merge.'</td></tr>';
      
      $Filter=array("WPNum"=>$o["WPNum"]);
      $Update=array('$set'=>array("Note_dk"=>$merge));
      $obj2->collection->update($Filter,$Update); 
      echo "</tr>";
      echo "<tr><td>********</td><td>*****************************************</td></tr>";
   }
   exit;
}

elseif(isset($_GET["RemoveMultipleNewLinesFromNotes"])) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   $query=array();
   $obj=new db_handling(array(),"");
   $obj->DataBase="Checklist";
   $obj->CollectionName="MASTER";
   $obj2=new db_handling("","");
   $obj2->DataBase="Checklist";
   $obj2->CollectionName="MASTER";
   $obj2->collection = $obj2->get_collection();
   
   $obj->get_cursor_find();
   foreach ($obj->cursor as $o) {
      //if($o["WPNum"]=="01.01.06") {
      //   for($t=0;$t<strlen($o["Note"]);$t++) echo substr($o["Note"],$t,1)."=".ord(substr($o["Note"],$t,1))."<br>";
      //   exit;
      //}
      if(!isset($o["Note"])) continue;
      $note = preg_replace('/(?:(?:\r\n|\r|\n)\s*){2}/s',chr(10),$o["Note"]); // remove all kind of newlines \r\n, \n etc.
      $Filter=array("WPNum"=>$o["WPNum"]);
      $Update=array('$set'=>array("Note"=>$note));
      $obj2->collection->update($Filter,$Update); 
   }
   exit;
}

elseif(isset($_GET["RemoveTestChecks"])) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   ?>
   <form action="chk_admin.php" method="post">
   Enter date/time (of time 'Uploaded') or parts of it for removing all testchecks starting with and including (format e.g: 2014-11-20 09:06:05) :<br>
   <input type="text" name="DateTime" size="18" value="" />
   <br>
   <input type="submit" name="RemoveTestChecks2" value="Submit"/>
   </form>
   <?php
   exit;
}
elseif(isset($_POST["RemoveTestChecks2"])) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   $DateTime=$_POST["DateTime"];
   $m = new MongoClient();
   $db = $m->selectDB('Checklist');
   $collection = new MongoCollection($db, 'TestChecks');   
   $query=array('Uploaded' => new MongoRegex("/$DateTime/"));
   
   $doc=$collection->find($query); 
   echo "Deleted the following ".$collection->count($query)." testchecks:<br>";
   foreach($doc as $o) {
      echo $o["TC"]." ".$o["Uploaded"]."<br>";   
   }
   $collection->remove($query);
   exit;
}






function UpdateCountryNoteInTestCase($k,$v)
{
   $query=array('Note'."_".$_SESSION["country"] => new MongoRegex("/$k/i"));
   $obj=new db_handling($query,"");
   $obj->DataBase="Checklist";
   $obj->CollectionName="MASTER";
   $obj->get_cursor_find();
   foreach($obj->cursor as $o) {      
      $CountryNote=$o['Note'."_".$_SESSION["country"]];
      $TagWithValue=GetTagWithValue($k,$CountryNote);
      $CountryNote=str_ireplace($TagWithValue,$k."=".$v,$CountryNote);
      //echo "__".$CountryNote." ";
      $o['Note'."_".$_SESSION["country"]] = $CountryNote;
      $obj->save_collection($o);
   }
   
}
function GetTagWithValue($Tag,$CountryNote)
// Returns the tag with value e.g. #DPR#TIME_LIMIT=22
// If no value only #DPR#TIME_LIMIT is returned

// #DPR#AAA_SSS_EEE
// #DPR#aaa_sss_Eee=22 #DPR#aaa_sss_jjj askdljdkl asdklsad #DPR#aaa_sss_Eee=22

{
   $StartPos=stripos($CountryNote,$Tag);
   if($StartPos===false) {
      echo "Could not find start position of tag '".$Tag."' in country note: ".$CountryNote;
      exit;
   }
   $EndPos=GetPosEndOfTag($StartPos, $CountryNote); 
   return substr($CountryNote,$StartPos,$EndPos-$StartPos+1); 
}


function ListAllTagsWithTCNumLinks(&$arrAllTagsUnique)
{

   echo '<form action="chk_admin.php" method="post">';
   echo '<table border="1">';

   foreach($arrAllTagsUnique as $Tag=>$Value) {
      //Reverse
      //$query=array('Note' => new MongoRegex("/$Tag/i"));
      $query=array('Note'."_".$_SESSION["country"] => new MongoRegex("/$Tag/i"));
      $obj=new db_handling($query,"");
      $obj->DataBase="Checklist";
      $obj->CollectionName="MASTER";
      $obj->get_cursor_find();
      echo "<tr>";
      echo "<td>".$Tag."</td>";
      echo "<td>";
      if(strlen($Value)>4) $Size=strlen($Value)+3;
      else $Size=4;
      if($Size>17) $Size=17;
      echo '<input type="text" name="DPR_group[]" size="'.$Size.'" value="'.$Value.'"/>';
      echo "</td>";
      echo "<td>";
      foreach($obj->cursor as $o) {
         echo '<a href="chk_manage_input_table.php?WPNum='.$o["WPNum"].'">'.$o["WPNum"].'</a>';
         echo " &nbsp;";
      }
      echo "</td>";
      echo "</tr>";         
   }
   echo "</table>";
   echo '<input type="submit" name="StoreDPRValues" value="Submit"/>';
   echo '</form>';
}


function GetDPRTags($TagString,&$arrTags) 
// Returns an associative array of tags=>values subtracted from $TagString
// A tag is anything starting with #DPR# until space/tab/newline/return is met
// E.g. '#DPR#TIME_LIMIT' or '#DPR#TIME_LIMIT=22' 
{
   $arrStartPosTags=getocurence($TagString,"#DPR#");
   foreach($arrStartPosTags as $StartPosTag)
   {
      $EndPosTag=GetPosEndOfTag($StartPosTag, $TagString);
      if($EndPosTag==4) {
         echo "<b>Incorrect syntax of tag!</b>";
         exit;
      }
      //echo substr($TagString,$StartPosTag,$EndPosTag-$StartPosTag+1)." ";
      //
      $TagWithValue=strtoupper(substr($TagString,$StartPosTag,$EndPosTag-$StartPosTag+1));

      // Seperate Tag identity from its value e.g. #DPR#TIME=22 -> '#DPR#TIME' and '22'
      $PosEqual=strpos($TagWithValue,"=");
      if($PosEqual!==false) {
         $TagWithoutValue=substr($TagWithValue,0,$PosEqual);
         $Value=substr($TagWithValue,$PosEqual+1);
      }
      else {
         // Tag has no value defined yet, set value as "Not Defined"
         $TagWithoutValue=$TagWithValue;
         $Value="NotDefined";
      }
      // arrTags is associative array of tags=>values
      $arrTags[$TagWithoutValue]=$Value;
      
//echo $TagWithoutValue." ";
//echo preg_match('/^[a-zA-Z0-9_#+-]+$/',$TagWithoutValue)."  --- ";
    if(!preg_match('/^[A-Z0-9_#+-]+$/', $TagWithoutValue)) {
       echo "<b>Syntax Error in tag:</b> $TagWithoutValue"."<br>";
       exit;
    }     
   }
}

function GetPosEndOfTag($StartPosTag, $TagString)
// if $TagString: '#DPR#TIME' it will return 8 as 'E' is string position 8
{
   for($t=$StartPosTag;$t<=strlen($TagString);$t++) {
      //echo ord(substr($TagString,$t,1))." ";
      if(substr($TagString,$t,1)==" " or
         substr($TagString,$t,1)==chr(9) or
         substr($TagString,$t,1)==chr(13) or
         substr($TagString,$t,1)==chr(10) ) break;
   }
   return $t-1;
}
function getocurence($chaine,$rechercher)
// http://stackoverflow.com/questions/15737408/php-find-all-occurrences-of-a-substring-in-a-string
// Return array of first positions of $rechercher in $chaine
{
   $lastPos = 0;
   $positions = array();
   while (($lastPos = strpos($chaine, $rechercher, $lastPos))!== false)
   {
       $positions[] = $lastPos;
       $lastPos = $lastPos + strlen($rechercher);
   }
   return $positions;
}

function Renumber($UpdateDB) {
  // If UpdateDB is true the renumber is done otherwise, the number mapping is shown
   $arrWP=explode(".",$_POST["FirstTCNum"]);

   $WP=$arrWP[0].".".$arrWP[1];  // e.g. "07.01" coming from e.g. "07.01.03";
   // find all documents >= FirstTCNum and less than the last possible TC number 
   // i.e. e.g. "07.01.99"
   $query=array('$and' => array(
         //      array('WPNum' => new MongoRegex("/^$WP/")),
               array('WPNum' => array('$gte' => $_POST["FirstTCNum"] )),
               array('WPNum' => array('$lte' => "$WP.99" ))
                            )
               );
   $objList=new db_handling($query, "");  // do not list backup WPs
   $objList->DataBase="Checklist"; 
   $objList->get_cursor_find_sort();
   $arrNew=explode(".",$_POST["NewTCNum"]);
   $Count=$arrNew[2];
   $Step=$_POST["Step"];

   foreach($objList->cursor as $obj) {
      // Sanity check
      if($Count<0 or $Count>99) {
         echo "<b>Error: It is not allowed to renumber to area outside 00 to 99</b>";
         exit;
      }
      $CountTxt=str_pad(strval($Count),2,"0",STR_PAD_LEFT);
      echo "Old TC number:".$obj["WPNum"]." => New TC number: $WP.$CountTxt<br>";
      if($UpdateDB) {
         $Filter=array("WPNum"=>$obj["WPNum"]);
         $Update=array('$set'=>array("WPNum"=>"$WP.$CountTxt"));
         $objList->collection->update($Filter,$Update);
      }
      $Count+=$Step;
   }   
}
function TestCaseNumber($TCNum) {
   if(preg_match("/[^.0-9]/", $TCNum)) {
      echo "Illegal characters in test case number: $TCNum - format is e.g. 07.01.03";
      return false;
   }
   if(strlen($TCNum)<>8) {
      echo "Incorrect length of test case number: $TCNum - format is e.g. 07.01.03 i.e. 8 characters in total";
      return false;
   }
   if(substr($TCNum,2,1)<>"." or substr($TCNum,5,1)<>".") {
      echo "Illegal test case number: $TCNum - format is e.g. 07.01.03";
      return false;
   }
   return true;
}

// Set the date for which changes in checklist are shown. E.g.
// If set today+3 months a mark will be given to the test case
// that it has been changed by somebody
function SetDateForShowingIfNewlyChangedTC() {
   $ChangedSince=GetAdminOnUser("TestCaseChangedDate","Checklist");
   if(trim($ChangedSince)=="") $ChangedSince=Date("Y-m-d");
?>
   <form action="chk_admin.php" method="post">
   Date: Show changes to Test Cases since (e.g. 2013-05-15):
   <input type="text" name="TestCaseChanges" size="16" value="<?php  echo $ChangedSince; ?>"/>
   <br>
   <input type="submit" name="ChangesSubmit" value="Submit"/>
   </form>
<?php
}
?>
<a href="chk_admin.php?Renumber=1">Renumber</a><br>
<a href="chk_admin.php?UserRights=1">Users Rights</a><br>
<a href="chk_admin.php?Roles=1">Define roles</a><br>
<a href="chk_admin.php?Teams=1">Define teams</a><br>
<a href="chk_admin.php?ListTeams=1">List teams</a><br>
<a href="chk_admin.php?CloneTeam=1">Clone Team</a><br>
<a href="chk_export.php?format=csv">Export Test Cases to CSV format</a><br>
<a href="chk_export.php?format=xls">Export Test Cases to XLS for Excel format</a><br>
<a href="chk_export.php?format=xml">Export Test Cases to XML for Excel format</a><br>
<a href="chk_manage_wps.php?CleanDataBase=1">Clean COOPANS DB for 'Exp' and 'Show'</a><br>
<a href="chk_manage_wps.php?MakeHTMLOfWPs=1">Make HTML of all WPs</a><br>
<a href="chk_admin.php?MapRoles=1">Correct ACG ACG_MASTER</a><br>
<a href="chk_view_results.php?ImportTestChecks">Upload zip'ed TestChecks</a><br>
<a href="chk_admin.php?CorrReleaseNo">Correct release number of TestChecks</a><br>
<a href="select2download.php?db=Checklist&collection=TestChecks">Download Test Checks as CSV for Excel</a><br>
<a href="chk_import_testchecks_excel.php?ImportTestChecks">Import TestChecks from Excel .xls file (Excel 97-2003 format)</a><br>
<a href="chk_admin.php?ListDPRValues">List DPR values from test checks</a><br>
<a href="chk_admin.php?MergeNoteAndCountryNote">Merge Note with Note_dk</a><br>
<a href="chk_admin.php?RemoveMultipleNewLinesFromNotes">Remove multiple newline from field 'Note'</a><br>
<a href="chk_admin.php?RemoveTestChecks">Remove testchecks from a given date/time</a><br>

<?php
SetDateForShowingIfNewlyChangedTC();
?>
</body>
</html>



