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
<!--
http://freqdec.github.io/datePicker/
http://freqdec.github.io/datePicker/demo/
https://github.com/freqdec/datePicker
-->
<script src="./datepicker.min.js">{"describedby":"fd-dp-aria-describedby"}</script>
<link href="./datepicker.min.css" rel="stylesheet" type="text/css" />
</head>
<body>

<?php include("res_headerline.html"); ?>

<?php

function GetResOwnToRoleIfDifferent($Role,$ResOwn) {
   $obj=new db_handling(array("User"=>"_Admin_"),"");
   $obj->DataBase="Projects";
   $obj->CollectionName="Admin";
   $obj->get_cursor_findone();

   $arrRoles=$obj->cursor["Roles"];
   foreach($arrRoles as $r) {
      if($r["Role"]==$Role) {
         if($r["ResOwn"]<>$ResOwn) {
            if(trim($r["ResOwn"])=="") {
               echo "Something is wrong with role: ".$Role.' as it does not have a resource owner in list "Define Roles". Please check and correct mapping between roles and resource owners before continueing';
               exit;
            }
            return $r["ResOwn"];
         }
      }
   }   
   return "";   
}            
            
 
if (isset($_GET['SubjToUnit'])) {   // Define/add Roles
   if(!UserRightsChecks("Roles")) {
      echo "You have no rights to do this - sorry";
      exit();
   }
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Subjects";
   $obj->ReturnScript="res_admin.php?SubjToUnit=1";
   $obj->arrHeaders=array("Subj"=>"left", "Unit"=>"left");

   echo $obj->get_table_as_html();

?>
   <form action="res_admin.php" method="post">
   Initials of subject (avoid dublicates!):
   <input type="text" name="Subject" size="16" value=""/><br>
   Unit of subject:
   <input type="text" name="Unit" size="16" value=""/><br>

   <input type="submit" name="SubjToUnit2" value="Add Subject"/>
   </form>
<?php
   exit;
}
else if (isset($_POST['SubjToUnit2'])) { 
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Subjects";
   $arrArrayToInsert=array("Subj"=>strtoupper($_POST['Subject']), 
                           "Unit"=>strtoupper($_POST['Unit']));
   $obj->insert($arrArrayToInsert);
   header("location:res_admin.php?SubjToUnit=1");
   exit;
}


if (isset($_GET['Roles'])) {   // Define/add Roles
   if(!UserRightsChecks("Roles")) {
      echo "You have no rights to do this - sorry";
      exit();
   }
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Roles";
   $obj->ReturnScript="res_admin.php?Roles=1";
   $obj->arrHeaders=array("Role"=>"left", "ReqRes"=>"left", 
                          "ResOwn"=>"left", "Unit"=>"left",
                          "Cost#"=>"left", "AktArt"=>"left");

   echo $obj->get_table_as_html();

?>
   <a href="res_admin.php?EditRoles=1">Edit Roles</a>
   <form action="res_admin.php" method="post">
   Name of Role (avoid dublicates!):
   <input type="text" name="NameRole" size="16" value=""/><br>
   Requested Ressource:
   <input type="text" name="ReqRes" size="16" value=""/><br>
   Ressource Owner:
   <input type="text" name="ResOwn" size="16" value=""/><br>
   Unit of Ressource Owner:
   <input type="text" name="Unit" size="16" value=""/><br>
   Cost number (OmkSted):#
   <input type="text" name="Cost" size="8" value=""/><br>
   AktArt:
   <select name="AktArt">
     <option value="NA" selected>Not Selected</option>
     <option value="ADMIN1">ADMIN1</option>
     <option value="ADMIN2">ADMIN2</option>
     <option value="FSPEC1">FSPEC1</option>
     <option value="FSPEC2">FSPEC2</option>
     <option value="LEDER1">LEDER1</option>
     <option value="LEDER2">LEDER2</option>
     <option value="OPERA1">OPERA1</option>
     <option value="OPERA2">OPERA2</option>
     <option value="TEKN1">TEKN1</option>
     <option value="TEKN2">TEKN2</option>
   </select><br>
   <input type="submit" name="Roles2" value="Add Role"/>
<!--
   <input type="submit" name="RolesDefault" value="Insert default roles"/>
-->
   </form>
<?php
   // header("location:res_admin.php");
   exit();
}
else if (isset($_POST['Roles2'])) { 
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Roles";
   $arrArrayToInsert=array("Role"=>strtoupper($_POST['NameRole']), 
                           "ReqRes"=>strtoupper($_POST['ReqRes']), 
                           "ResOwn"=>strtoupper($_POST['ResOwn']),
                           "Unit"=>strtoupper($_POST['Unit']),
                           "Cost#"=>"#".strtoupper($_POST['Cost']),
                           "AktArt"=>strtoupper($_POST['AktArt']));
   $obj->insert($arrArrayToInsert);
   header("location:res_admin.php?Roles=1");
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
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Roles";
   foreach($RolesArray as $key=>$val) {
      // only insert role if it does not already exist
      // There is a problem here as if a new person is connected to a role
      // then it will write the same role again but with the new person
      // This means that two of the same roles can be created.
      if(!($obj->entry_in_table(array("Role"=>$key, "ReqRes"=>"", "ResOwn"=>$val)))) {
         $obj->insert(array("Role"=>$key, "ReqRes"=>"", "ResOwn"=>$val, "Unit"=>"NA"));
      }
   }
   header("location:res_admin.php?Roles=1");
   exit;
}
else if (isset($_GET['Teams'])) {   // Define Teams
   if($_GET['Teams']=="1") {
      $name="Roles";
      $key=_GLOBAL_TABLE;
   }
   else {
      $name=$_GET['Teams'];
      $key=$_SESSION["username"];  
   }

   $obj=new TableHandling($key,"User","Admin","Projects");
   $obj->Name=$name;

   $obj->arrHeaders=array("Role"=>"left", "ReqRes"=>"left", "ResOwn"=>"left", 
                           "ManHours"=>"left", "ManDays"=>"left", "AllRes"=>"left");
   $obj->ShowRemoveEntry=False; // Deactivate "delete entry" posibility
   $obj->AddCheckBox=True;
?>
   <form action="res_admin.php" method="post">
<?php
   echo $obj->get_table_as_html();
?> <br>Select team members and indicate name of team:
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
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Roles";
   // obj2: object pointing to where to store selected team data
   // name of table is 'Team_' + given team name.
   $obj2=new TableHandling($_SESSION["username"],"User","Admin","Projects");
   $obj2->Name="Team_".$_POST['NameTeam'];
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
         $TeamEntry["ManHours"]=0;
         $TeamEntry["ManDays"]=0;
         $TeamEntry["AllRes"]="";
//print_r($TeamEntry);
         $obj2->insert($TeamEntry);
      }
  }
  header("location:res_admin.php?ListTeams=1");
  exit;
}
else if (isset($_GET['ListTeams'])) {
   $obj=new db_handling(array('User' => $_SESSION["username"]),"Backup");
   $obj->CollectionName="Admin";
   $obj->get_cursor_findone();
//print_r($obj->cursor);
//echo "<br><br>";
   foreach ($obj->cursor as $oList=>$v) {
      if(substr($oList,0,5)=="Team_") {
         echo '<a href="res_admin.php?TeamDel='.$oList.'">X</a>'."&nbsp;"."&nbsp;";
         echo '<a href="res_admin.php?TeamView='.$oList.'">View</a>'."&nbsp;"."&nbsp;";
         echo substr($oList,5);
         echo "<br>";
      }
   }
   echo '<br><a href="res_admin.php?Teams=1">Define new team</a>';
exit;
}
else if (isset($_GET['TeamDel'])) {
   $obj=new db_handling(array('User' => $_SESSION["username"]),"Backup");
   $obj->CollectionName="Admin";
   $obj->get_cursor_findone();
   unset($obj->cursor[$_GET['TeamDel']]);
   $obj->save_collection();
   header("location:res_admin.php?ListTeams=1");

   exit;
}
else if (isset($_GET['TeamView'])) {
   $team=$_GET['TeamView'];
   header("location:res_admin.php?Teams=$team");
   exit;
}
// ************ EDIT ROLES *****************
elseif (isset($_GET["EditRoles"])) {
   if(!UserRightsChecks("Roles")) {
      echo "You have no rights to do this - sorry";
      exit();
   }
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Roles";
   $obj->ReturnScript="res_admin.php?EditRoles=1";
   $obj->arrHeaders=array("Role"=>"left", "ReqRes"=>"left", "ResOwn"=>"left",
                          "Unit"=>"left", "Cost#"=>"left", "AktArt"=>"left");
   $obj->arrHTMLfunctions=array(HTML_INPUT, HTML_INPUT, HTML_INPUT, HTML_INPUT, HTML_INPUT, HTML_INPUT);
?>
 <form action="res_admin.php" method="post">
<?php
 echo $obj->get_table_as_html();
?>
<input type="submit" name="EditRoles2" value="Submit"/>
</form>
<?php
exit;
}
elseif (isset($_POST["EditRoles2"])) {
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Roles";
   $obj->ReturnScript="res_admin.php?Roles=1";
   $obj->arrHeaders=array("Role"=>"left", "ReqRes"=>"left", "ResOwn"=>"left", 
                          "Unit"=>"left", "Cost#"=>"left", "AktArt"=>"left");
   $obj->FixDaysHours=False;  // deactivate fixing of mandays/manhours 
   $obj->insert_input_to_table();
//   $obj->obj->save_collection();
   header("location:res_admin.php?Roles=1");
   exit;
}
elseif(isset($_GET["WPAllocation"])) {
   ?>
   <form action="res_admin.php" method="post">
   <b>Super WP name to get sub roles in relation to dates from:</b>
   <input type="text" name="SupWPNum" size="10" value=""/>
   <input type="submit" name="WPAllocation2" value="Submit"/>
   </form>
   <?php
   exit;
}
elseif(isset($_POST["WPAllocation2"])) {
   $SupWPNum=$_POST["SupWPNum"];
   $obj=new db_handling(array('WPNum' => new MongoRegex("/^$SupWPNum/")),"");
   $obj->get_cursor_find_sort();

   // Find all unique roles in SubWPs and place them in $arrAllRoles
   $arrAllRoles=array();
   foreach($obj->cursor as $o) {
      if(isset($o["Ressources"])) {
         foreach($o["Ressources"] as $res) {
            $arrAllRoles[]=$res["Roles"];         
         }
      }
   }
   $arrAllRoles=array_unique($arrAllRoles);
   asort($arrAllRoles);
   //print_r($arrAllRoles);
   
   
   // Make table headings as dates from the individual sub WPs
   echo '<table border="1">';
   echo "<tr>";
   echo "<th>Role</th>";
   foreach($obj->cursor as $o) {
      if(isset($o["StartDate"])) {
         echo "<th>".$o["StartDate"]."</th>";
      }
      else echo "<th>Missing Date</th>";
   }
   echo "</tr>";
   
   // Over all $arrAllRoles, lookup subject to role for each day
   $MailList=array();
   foreach($arrAllRoles as $Role) {
      echo "<tr>";
      echo "<td>".$Role."</td>";  
      foreach($obj->cursor as $o) {
         echo"<td>"; 
         if(isset($o["Ressources"])) {
            $RoleFound=false;
            foreach($o["Ressources"] as $res) {
               if($res["Roles"]==$Role) {
                  if(trim($res["AllRes"])<>"") {
                     echo $res["AllRes"];
                     $MailList[]=$res["AllRes"];
                  }
                  else echo "requested"; // indicate that role is requested but not allocated
                  $RoleFound=true;
                  break;
               }         
            }
            if(!$RoleFound) echo "-";
         }
         else echo "-";
         echo"</td>";   
      }
      echo "</tr>";   
   }   
      
   echo '</table>';
   
   // Print maillist
   $MailList=array_unique($MailList);
   asort($MailList);
   echo "<br><b>Mail list:</b><br>";
   foreach ($MailList as $mail) {
      echo $mail."@naviair.dk; ";
   }
   
   
   exit;
}

if (isset($_GET['UserRights'])) {   // set user rights for access to DB
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Rights";
   $obj->ReturnScript="res_admin.php?UserRights=1";
   $obj->arrHeaders=array("User"=>"left", "WriteLevel"=>"left", "Roles"=>"left");

   echo $obj->get_table_as_html();

?>
   <a href="res_admin.php?EditUserRights=1">Edit User Rights</a>
   <form action="res_admin.php" method="post">
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
   if(!$_SESSION['username']=="lbn") exit; 
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Rights";
   if(substr($_POST['WriteLevel'],0,1)<>"#") $_POST['WriteLevel']="#".$_POST['WriteLevel'];
   if($_POST['checkbox']) $Roles="T";
   else $Roles="F";
   $arrArrayToInsert=array("User"=>strtoupper($_POST['User']), "WriteLevel"=>$_POST['WriteLevel'], "Ch_Roles"=>$Roles);
   $obj->insert($arrArrayToInsert);
   header("location:res_admin.php?UserRights=1");
   exit;
}
elseif (isset($_GET["EditUserRights"])) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Rights";
   $obj->ReturnScript="res_admin.php?EditUserRights=1";
   $obj->arrHeaders=array("User"=>"left", "WriteLevel"=>"left",
                           "Roles"=>"left");
   $obj->arrHTMLfunctions=array(HTML_INPUT, HTML_INPUT, HTML_CHECK);
?>
 <form action="res_admin.php" method="post">
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
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Rights";
   $obj->ReturnScript="res_admin.php?UserRights=1";
   $obj->arrHeaders=array("User"=>"left", "WriteLevel"=>"left", "Roles"=>"left");
   $obj->arrHTMLfunctions=array(HTML_INPUT, HTML_INPUT, HTML_CHECK);
   $obj->FixDaysHours=False;  // deactivate fixing of mandays/manhours 
   $obj->insert_input_to_table();
//   $obj->obj->save_collection();
   header("location:res_admin.php?UserRights=1");
   exit;
}
elseif (isset($_GET["UpdateDates"])) {
   FindMinMaxOfSubWPs("");
   header("location:res_manage_wps.php?ListWPs=1");  
   exit;
}
elseif (isset($_GET["ChangeResourceOwner"])) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only LBN is authorised to change resource owner";
      exit;
   }
   ?>
   <form action="res_admin.php" method="post">
   <b>Change Resource Owner of all resources in the COOPANS collection</b><br>
   From:
   <input type="text" name="ChangeOwnerFrom" size="4" value=""/><br>
   To:
   <input type="text" name="ChangeOwnerTo" size="4" value=""/>
   <br>
   <input type="submit" name="ChangeResourceOwner" value="Submit"/>
   </form>
   <?php
   exit;
}
elseif (isset($_POST["ChangeResourceOwner"])) {
   // Change Resource owner of all WPs in COOPANS collection
   $from=strtoupper($_POST["ChangeOwnerFrom"]);
   $to=strtoupper($_POST["ChangeOwnerTo"]);
   $obj=new db_handling(array(),"");
   $obj->DataBase="Projects";
   // $obj->CollectionName="SAPResources"; Default COOPANS
   $obj->get_cursor_find();
   echo "Changed in WP: ";
   foreach($obj->cursor as $o) {
      $obj2=new db_handling(array("WPNum"=>$o["WPNum"]),"");
      $obj2->DataBase="Projects";
      $obj2->get_cursor_findone();            
      if(isset($obj2->cursor["Ressources"])) {
         $arr=$obj2->cursor["Ressources"];
         $t=0;
         foreach($arr as $a) {
            if($a["ResOwn"]==$from) {
               $arr[$t]["ResOwn"]=$to;
               echo $obj2->cursor["WPNum"]."   ";
            }
            $t++;
         }
         $obj2->cursor["Ressources"]=$arr;
         $obj2->collection->save($obj2->cursor);
      }
   
   }
   echo "<br>Done !";
   exit;
}

elseif (isset($_GET["UpdateResourceOwnersOfRoles"])) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only LBN is authorised to change resource owner";
      exit;
   }
   // Change Resource owner of all WPs in COOPANS collection
   $obj=new db_handling(array(),"");
   $obj->DataBase="Projects";
   // $obj->CollectionName="SAPResources"; Default COOPANS
   $obj->get_cursor_find();

   echo "Changed in WP: ";
   foreach($obj->cursor as $o) {
      $obj2=new db_handling(array("WPNum"=>$o["WPNum"]),"");
      $obj2->DataBase="Projects";
      $obj2->get_cursor_findone();            
      if(isset($obj2->cursor["Ressources"])) {
         $arr=$obj2->cursor["Ressources"];
         // Check if Roles map to correct resource owner
         $t=0;
         $StoreDB=false;
         foreach($arr as $a) {
            $ResOwn=GetResOwnToRoleIfDifferent($a["Roles"], $a["ResOwn"]);
            if($ResOwn<>"") {
               $arr[$t]["ResOwn"]=$ResOwn;
               $StoreDB=true;
               echo "<br>".$obj2->cursor["WPNum"]." ".$a["Roles"]." ".$a["ResOwn"]."->".$ResOwn;
            }
            $t++;
                        
         }
         if($StoreDB) {
            $obj2->cursor["Ressources"]=$arr;
            $obj2->collection->save($obj2->cursor);
         }
      }
   
   }
   echo "<br>Done !";
   exit;
}

// ***************************************************

elseif (isset($_GET["ChangeRoleOfResources"])) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only LBN is authorised to change resource owner";
      exit;
   }
   ?>
   <form action="res_admin.php" method="post">
   <b>Change Role of Resources</b><br>
   Role From:
   <input type="text" name="RoleFrom" size="10" value=""/><br>
   Role To:
   <input type="text" name="RoleTo" size="10" value=""/>
   <br>
   Resources for which roles must be changed e.g. "DAR LBN NIS":
   <input type="text" name="ResourceList" size="14" value=""/>
   <br>
   <input type="submit" name="ChangeRoleOfResources" value="Submit"/>
   </form>
   <?php
   exit;
}
elseif (isset($_POST["ChangeRoleOfResources"])) {
   // Change Resource owner of all WPs in COOPANS collection
   $from=strtoupper($_POST["RoleFrom"]);
   $to=strtoupper($_POST["RoleTo"]);
   $ResourceList=" ".strtoupper($_POST["ResourceList"]); // add space to avoid strpos returning false when psoition is zero
   $obj=new db_handling(array(),"");
   $obj->DataBase="Projects";
   // $obj->CollectionName="SAPResources"; Default COOPANS
   $obj->get_cursor_find();
   echo "Changed in WP: ";
   foreach($obj->cursor as $o) {
      $obj2=new db_handling(array("WPNum"=>$o["WPNum"]),"");
      $obj2->DataBase="Projects";
      $obj2->get_cursor_findone();            
      if(isset($obj2->cursor["Ressources"])) {
         $arr=$obj2->cursor["Ressources"];
         $t=0;
         $StoreDB=false;
         foreach($arr as $a) {
            if($a["AllRes"]<>"") $Res=$a["AllRes"];
            elseif($a["ReqRes"]<>"") $Res=$a["ReqRes"];
            else $Res="*****";

            if(strpos($ResourceList, $Res)) {
               if($a["Roles"]==$from) {
                  $arr[$t]["Roles"]=$to;
                  $StoreDB=true;
                  echo "<br>".$obj2->cursor["WPNum"]." ReqRes='".$a["ReqRes"]."' AllRes='".$a["AllRes"]."' ::: ".$a["Roles"]."->".$to;
               }
            }
            $t++;
         }
         if($StoreDB) {
            $obj2->cursor["Ressources"]=$arr;
            $obj2->collection->save($obj2->cursor);
         }
      }
   
   }
   echo "<br>Done !";
   exit;
}
elseif (isset($_GET["CompareCockpits"])) {
   $YearMonth=GetAdminOnUser("CockpitCompareYearMonth","Projects");
   $WPLevel=GetAdminOnUser("CockpitCompareWPLevel","Projects");
   ?>
   <form action="res_admin.php" method="post">
   <b>Compare Cockpit with LastQ Cockpit</b><br>
   Year/Month (e.g. 2014-02):
   <input type="text" name="YearMonth" size="10" value="<?php echo $YearMonth; ?>"/><br>
   Work Package (e.g. '25.05' for '25.05' and below):
   <input type="text" name="WP" size="10" value="<?php echo $WPLevel; ?>"/>
   <br>
   <input type="submit" name="CompareCockpits" value="Submit"/>
   </form>
   <?php
   exit;
}
elseif (isset($_POST["CompareCockpits"])) {
   echo "<b>YearMonth: </b>'".$_POST['YearMonth']."'";
   echo " / <b>WP Level: </b>'".$_POST['WP']."' and below<br><br>";   
   StoreAdminOnUser("CockpitCompareYearMonth",$_POST['YearMonth']);
   StoreAdminOnUser("CockpitCompareWPLevel",$_POST['WP']);
   echo "<b>Cockpit:</b>";
   ListActivitiesForWPsInMonths($_POST['YearMonth'],$_POST['WP'],"COOPANS_Cockpit");
   echo "<br><b>Cockpit LastQ (reported figures):</b>";
   ListActivitiesForWPsInMonths($_POST['YearMonth'],$_POST['WP'],"COOPANS_Cockpit_LQ");
   
   exit;
}

elseif (isset($_GET["ChangeExpectedResource"])) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only LBN is authorised to change resource owner";
      exit;
   }
   ?>
   <form action="res_admin.php" method="post">
   <b>Change name of expected resource from a starting YearMonth and onwards</b><br>
   From:
   <input type="text" name="ExpectedFrom" size="4" value=""/><br>
   To:
   <input type="text" name="ExpectedTo" size="4" value=""/>
   <br>
   Start date: (E.g. 2014-03 for start in March and onwards):
   <input type="text" name="YearMonth" size="9" value=""/>
   <br>
   <input type="submit" name="ChangeExpectedResource" value="Submit"/>
   </form>
   <?php
   exit;
}
elseif (isset($_POST["ChangeExpectedResource"])) {
   // Change Resource owner of all WPs in COOPANS collection
   $from=strtoupper($_POST["ExpectedFrom"]);
   $to=strtoupper($_POST["ExpectedTo"]);
   $query=array('StartDate' => array('$gte' => $_POST["YearMonth"]));
   $obj=new db_handling($query,"");
   $obj->DataBase="Projects";
   // $obj->CollectionName="SAPResources"; Default COOPANS
   $obj->get_cursor_find();
   echo "Changed in WP: ";
   foreach($obj->cursor as $o) {
      $obj2=new db_handling(array("WPNum"=>$o["WPNum"]),"");
      $obj2->DataBase="Projects";
      $obj2->get_cursor_findone();            
      if(isset($obj2->cursor["Ressources"])) {
         $arr=$obj2->cursor["Ressources"];
         $t=0;
         foreach($arr as $a) {
            if($a["ReqRes"]==$from) {
               $arr[$t]["ReqRes"]=$to;
               echo $obj2->cursor["WPNum"]."   ";
            }
            $t++;
         }
         $obj2->cursor["Ressources"]=$arr;
         $obj2->collection->save($obj2->cursor);
      }
   
   }
   echo "<br>Done !";
   exit;
}
// *********************************
elseif (isset($_GET["AddHolidays"]) or isset($_POST["UpdateSubject"])) {
   
   if(isset($_POST["UpdateSubject"])) $Subj=strtoupper($_POST["Subject"]);
   else $Subj=strtoupper($_SESSION["username"]);
   
   if(isset($_POST["From"])) $from=$_POST["From"];
   else $from="";
   
   if(isset($_POST["To"])) $to=$_POST["To"];
   else $to="";
   
   if(isset($_POST["ManDays"])) $MD=$_POST["ManDays"];
   else $MD="";
   
   ?>
   <b>Add holiday period</b><br>
   <form action="res_admin.php" method="post">
   <table border="0">
   <tr>
   <td>Subject:</td>
   <td><input type="text" name="Subject" size="4" value="<?php echo $Subj;?>"/></td>
   <td><input type="submit" name="UpdateSubject" value="Update Registered list for subject"/></td>
   </tr>
   <tr>
   <td>From (e.g. 2014-01-20):</td>
   <td><input type="text" name="From" id="calendar_1" size="10" value="<?php echo $from;?>"/></td>
   <td></td>
   </tr><tr>
   <td>To (e.g. 2014-01-31):</td>
   <td><input type="text" name="To" id="calendar_2" size="10" value="<?php echo $to;?>"/></td>
   <td>(if not indicated, it will be equal to "From")</td>
   </tr><tr>
   <td>ManDays:</td>
   <td><input type="text" name="ManDays" size="4" value="<?php echo $MD;?>"/></td>
   <td>(if not indicated, it is calculated counting Monday to Fridays in between 'From' and 'To' dates)</td>
   </tr>
   </table>
   
<script>
// Attach a datepicker to the above input element
datePickerController.createDatePicker({
    formElements:{
        "calendar_1":"%Y-%m-%d"
    },
    // Show the week numbers
    showWeeks:true,
     // Fill the entire grid with dates
    fillGrid:true,
    // Enable the selection of dates not within the current month
    // but rendered within the grid (as we used fillGrid:true)
    constrainSelection:false 
});
// Attach a datepicker to the above input element
datePickerController.createDatePicker({
    formElements:{
        "calendar_2":"%Y-%m-%d"
    },
    // Show the week numbers
    showWeeks:true,
     // Fill the entire grid with dates
    fillGrid:true,
    // Enable the selection of dates not within the current month
    // but rendered within the grid (as we used fillGrid:true)
    constrainSelection:false 
});
</script>

   <input type="submit" name="AddHolidays" value="Submit"/>
   </form>
   <?php
   
   ListRegisteredHolidaysForSubject($Subj);  
   exit;
}
elseif (isset($_POST["AddHolidays"])) {
   echo "<b>Registered your holiday request!</b><br>";
   // Change Resource owner of all WPs in COOPANS collection
   $Subj=strtoupper(trim($_POST["Subject"]));
   $from=CheckDateFormat(trim($_POST["From"]));
   $to=CheckDateFormat(trim($_POST["To"]));
   if($to=="") $to=$from;
   if($from=="" or $to=="") {
      echo "Incorrect Date format. Use e.g. 2014-02-28";
      exit;
   }
   $ManDays=trim($_POST["ManDays"]);

   $StartMonth=intval(substr($from,5,2));
   $StartYear=intval(substr($from,0,4));
   $EndMonth=intval(substr($to,5,2));
   $EndYear=intval(substr($to,0,4));

   if($ManDays<>"") {
      if($StartYear<>$EndYear or $StartMonth<>$EndMonth) {
         echo "If you indicate ManDays, you can only allocate for one month at the time.<br>";
         echo "Please consider having 'from' and 'to' dates in same year and month.";
         exit;
      }
   }


   if($StartYear<2014 or $EndYear>=2022) {
      echo "From Year must equal or larger than year 2014";
      echo "<br>To Year must be less or equal to year 2022";
      exit;
   }
   
   if($ManDays=="") {
      // List all months from $from to $to and number of working days in given months
      $NumOfMonths=($EndYear*12+$EndMonth)-($StartYear*12+$StartMonth)+1;
      $StartY=$StartYear;
      $StartM=$StartMonth;
      for($t=0;$t<$NumOfMonths;$t++) 
      {
         if($t==0) $from1=$from;
         else
         {
            $from1=$StartY."-".str_pad($StartM, 2, '0', STR_PAD_LEFT)."-01";      
         }
         
         if($t==$NumOfMonths-1) $to1=$to;
         else {
            $d = new DateTime($StartY."-".str_pad($StartM, 2, '0', STR_PAD_LEFT)."-01"); 
            $LastDateInMonth=$d->format('Y-m-t'); // t returns last day in month
            $to1=$LastDateInMonth;
         }
               
         $StartM++;
         if($StartM==13) {
            $StartM=1;
            $StartY++;
         }
         $MD=getWorkingDays($from1,$to1);
         echo $Subj." - From: ".$from1." To: ".$to1." ManDays: ".$MD."<br>";
         AllocateHolidayManDaysInWP($Subj,$from1, $to1, $MD); 
      
      }
   }
   else {
      echo $Subj." - From: ".$from." To: ".$to." ManDays: ".$ManDays."<br>";
      AllocateHolidayManDaysInWP($Subj,$from, $to, $ManDays); 
   }
   echo "<br>";
   ListRegisteredHolidaysForSubject($Subj);
   FindMinMaxOfSubWPs("99");  // correct start and end dates in relation to sub wps
   echo '<br><a href="res_admin.php?AddHolidays=1">Add another Holiday</a><br>'; 
   exit;
}

function ListRegisteredHolidaysForSubject($Subj)
{
   // List already booked holidays for subject
   $query=array('Tags' => new MongoRegex("/holidays $Subj/"));;
   $Today=date("Y-m-d");
   $arr=array('EndDate' => array('$gte' => $Today));
   $query=array('$and'=>array($query,$arr));
   $obj=new db_handling($query,"");
   $obj->DataBase="Projects";
   // $obj->CollectionName="SAPResources"; Default COOPANS
   $obj->get_cursor_find_sort_array(array("StartDate"=>1,"WPNum"=>1));  // sort by StartDate
   echo '<b>Already registered holidays (starting from today):</b><br><table border="1">';
   echo '<tr><th>Subject</th><th>From</th><th>To</th><th>ManDays</th></tr>';
   foreach($obj->cursor as $o) {
      echo "<tr>";
      echo "<td>$Subj</td><td>".$o["StartDate"]."</td><td>".$o["EndDate"]."</td><td>".$o["ManHours"]/7.4."</td>";
      echo "</tr>";
   }
   echo "</table>";
   echo "<br><b>Hints!</b><br>";
   echo '<font size="-1">'."To see all holidays, use hash tag '<b>#holidays</b>' in the '<b>Filter:</b>' input field on '<b>WP List View</b>'.<br>";
   echo "To see all holidays for subject, use hash tag e.g. '<b>#holidays kkb</b>' for subject KKBs holidays.</font>";
}   
function AllocateHolidayManDaysInWP($Subj,$from, $to, $ManDays)
// The function puts the number of mandays into WP 99.MM.xx. for subject
// $From and to must be within the same month
// 'MM' is the month number and level 2 heading is creating if not already created.
// 'xx' i.e. level 3 headings are generated counting up by finding next free number
{   
   $StartMonth=intval(substr($from,5,2));
   $StartYear=intval(substr($from,0,4));
   $EndMonth=intval(substr($to,5,2));
   $EndYear=intval(substr($to,0,4));
   
   $Level2Heading=(($StartYear-2014)*12+$StartMonth);
   $Level2Heading=str_pad($Level2Heading, 2, '0', STR_PAD_LEFT);
   $WP="99.".$Level2Heading;  // e.g. '99.01' for January 2014

   
   // Create level 2 heading if missing
   $query=array('WPNum' => $WP);
   $obj=new db_handling($query,"");
   $obj->DataBase="Projects";
   // $obj->CollectionName="SAPResources"; Default COOPANS
   $obj->get_cursor_findone();
   if($obj->cursor==NULL) {
      $obj->cursor["WPNum"]=$WP;
      $MonthName=date("M",mktime(0,0,0,$StartMonth,10));
      $obj->cursor["WPName"]="Holiday : ".$MonthName."-".$StartYear;
      $obj->cursor["StartDate"]=$StartYear."-".str_pad($StartMonth, 2, '0', STR_PAD_LEFT)."-01";
      $d = new DateTime($obj->cursor["StartDate"]); 
      $LastDateInMonth=$d->format('Y-m-t'); // t returns last day in month
      $obj->cursor["EndDate"]=$LastDateInMonth;
      $obj->cursor["Tags"]="holidays";
      $obj->save_collection($obj->cursor);
      AddHTMLofWPToDB($WP);
      //UpdateHTMLColoursofSubWPs($WP);
   }
   
   // Find Next free WPNum on level 3 i.e. '99.01.03'
   $query=array('WPNum' => new MongoRegex("/^$WP/"));
   $obj=new db_handling($query,"");
   $obj->DataBase="Projects";
   // $obj->CollectionName="SAPResources"; Default COOPANS
   $obj->get_cursor_find_sort();
   // find las WPNum in collection
   foreach($obj->cursor as $o) {
      $WPLast=$o["WPNum"];
      //echo $WPLast." # ";
   }
   $arr=explode(".", $WPLast);
   if(!isset($arr[2])) $arr[2]="00";  // create L3 if it is not yet created.
   
   // Add one to level 3 heading and format to proper string
   $WPL1L2L3=$arr[0].".".$arr[1].".".str_pad(($arr[2]+1), 2, '0', STR_PAD_LEFT);

   // Store level 3 heading for relevant subject and given ManDays.
   $obj2=new db_handling("","");
   $obj2->DataBase="Projects";   
   $obj=array();
   $obj["WPNum"]=$WPL1L2L3;
   $obj["WPName"]=$Subj;
   $obj["StartDate"]=$from;
   $obj["EndDate"]=$to;
   $obj["Tags"]="holidays ".$Subj;
   $obj["Ressources"]=array(array("Roles"=>"","ReqRes"=>"","ResOwn"=>"","ManHours"=>$ManDays*7.4, "ManDays"=>$ManDays, "AllRes"=>$Subj));
   $obj["ManHours"]=$ManDays*7.4;
   $obj2->insert($obj);
   //$obj->save_collection($obj->cursor);
   AddHTMLofWPToDB($WPL1L2L3);
   // SortWPsByStartDate($WP);
   
}

// *****************************

function getWorkingDays($startDate, $endDate)
{
   $begin=strtotime($startDate);
   $end=strtotime($endDate);
   if($begin>$end)
   {
      echo 'startdate is in the future! <br />';
      return 0;
   }
   else
   {
      $no_days=0;
      $weekends=0;
      while($begin<=$end)
      {
         $no_days++; // no of days in the given interval
         $what_day=date('N',$begin);
         if($what_day>5) 
         { // 6 and 7 are weekend days
            $weekends++;
         };
         $begin+=86400; // +1 day
      };
      $working_days=$no_days-$weekends;
      return $working_days;
   }
}



function ListActivitiesForWPsInMonths($YearMonth,$WPNum,$CockpitCollection)
{
   $m = new Mongo();
   $db = $m->selectDB('Projects');
   //$collection = new MongoCollection($db, GetAdminOnUser("CockpitCollection","Projects"));
   $collection = new MongoCollection($db, $CockpitCollection);
   $cursor=$collection->find();
   foreach ($cursor as $obj) {
      if(isset($obj[$YearMonth])) {
         $arrTasks=$obj[$YearMonth];
         if(isset($arrTasks["Tasks"])) $arrTasks=$arrTasks["Tasks"];
         else continue;
         foreach($arrTasks as $Task) {
            $arrTexts[$Task['WPNum']]=$Task['Text'];
            if(isset($arrMandays[$Task['WPNum']])) $arrMandays[$Task['WPNum']]+=$Task['ManDays'];
            else $arrMandays[$Task['WPNum']]=$Task['ManDays'];
         }
      
      }
   }
   $arrCockpit=array();
   $TotalHours=0;
   foreach($arrTexts as $WP=>$Text) {
      //echo $k." # ".$arrMandays[$k]." # ".$v."<br>";
      //echo $WP." ".$WPNum." ".strlen($WPNum)." ".substr($WP,0,strlen($WPNum))."<br>";
      if(substr($WP,0,strlen($WPNum))==$WPNum) {
         $arrCockpit[]=array($WP,$arrMandays[$WP],$Text);
         $TotalHours+=$arrMandays[$WP]*7.4;
      }
   }
   
   sort($arrCockpit);
   echo '<table border="1">';
   echo "<tr><th>WPNum</th><th>Hours</th><th>Activity</th></tr>";
   foreach($arrCockpit as $Cockpit) {
      echo "<tr><td>".$Cockpit[0].'</td><td align="right">'.number_format($Cockpit[1]*7.4, 0, '.', ' ')."</td><td>".$Cockpit[2]."</td></tr>";
   }
   echo '<tr><td><b>Total</b></td><td align="right"><b>'.number_format($TotalHours, 0, '.', ' ')."</b></td><td>-</td></tr>";
   echo "</table>";

}



?>
<b>Manage rights, roles and teams:</b><br>
<a href="res_admin.php?UserRights=1">Users Rights</a><br>
<a href="res_admin.php?Roles=1">Define roles</a><br>
<a href="res_admin.php?Teams=1">Define teams</a><br>
<a href="res_admin.php?ListTeams=1">List teams</a><br>
<br><b>Export CSV (for Excel), MS Project:</b><br>
<a href="res_export.php">Export WPs to CSV format</a><br>
<a href="res_make_ms_project.php?format=xml">Export for MS Project</a><br>
<a href="res_make_ms_project.php?format=csv&national=uk">Export for MS Project (UK) .csv</a><br>
<a href="res_make_ms_project.php?format=csv&national=dk">Export for MS Project (DK) .csv</a><br>
<br><b>Reports</b><br>
<a href="res_admin.php?WPAllocation=1">Create list of WP allocation in relation to sub dates</a><br>
<a href="res_admin.php?CompareCockpits=1">Compare WPs between Cockpit and LastQ Cockpit</a><br>
<br><b>Manage Cockpit, SAP import:</b><br>
<font size="-1">Extract SAP report 'CJI3'<br>
Make Excel file in .xls (Excel 97) format of SAP data<br>
Upload using .xls file:</font><br>
<a href="res_admin_uploadSAP.php">Upload SAP Resources and update Cockpit</a><br>
<font size="-1">Read log and if subjects do not exist then map then manually using:</font><br>
<a href="res_admin.php?SubjToUnit=1">Match Subjets to Units</a><br>
<font size="-1">Go back in 'Cockpit' and toggle (change status) 'Last Q' marking and then:</font><br>
<a href="res_admin_uploadSAP.php?UpdateCockpitOnly=1">Update Cockpit with SAP Data - no upload</a><br>
<font size="-1">If Cockpit is recalculated and SAP data is missing, then run this last update again<br>for the right Cockpit SAP data bank i.e. either 'Last Q' or not (current quarter).</font><br>

<br>
<a href="res_cockpit.php?BackupCockpit=1">Backup (this/current quarter - Cockpit_1) Cockpit</a><br>
<a href="res_cockpit.php?BackupCockpit=0">Backup (last quarter - Cockpit_0) Cockpit</a><br>
<a href="res_cockpit.php?RestoreCockpit=1">Restore Cockpit to (this/current quarter - Cockpit_1) Cockpit</a><br>
<a href="res_cockpit.php?RestoreCockpit=0">Restore Cockpit to (Last quarter - Cockpit_0) Cockpit</a><br>
<a href="res_cockpit.php?CopyToLastQ=1">Copy Cockpit (Cockpit_1) to Last quarter Cockpit (Cockpit_1)</a><br>
<br><b>Clean MongoDB database:</b><br>
<a href="res_admin.php?UpdateDates=1">Update Dates in all WPs</a><br>
<a href="res_manage_wps.php?CleanDataBase=1">Clean COOPANS DB for 'Exp' and 'Show'</a><br>
<a href="res_manage_wps.php?MakeHTMLOfWPs=1">Make HTML of all WPs</a><br>
<a href="res_admin.php?ChangeResourceOwner=1">Change Resource Owner of all resources</a><br>
<font size="-1">Before each BW update the following must be run to assure that all roles have a matching resource owner:</font><br>
<a href="res_admin.php?UpdateResourceOwnersOfRoles=1">Make sure that all roles have a matching resource owner as given in table 'Define Roles'</a><br>
<font size="-1">If resources change role during the project, it is here possible to change these roles for a group of resources. After this is done, the former script that makes sure all resources owners match a role must be executed. Otherwise it is possible to have incorrect resource owners allocated to roles:</font><br>
<a href="res_admin.php?ChangeRoleOfResources=1">Change Role of a list of resources</a><br>
<font size="-1">Use the following to change name of a requested resource starting from a given month:</font><br>
<a href="res_admin.php?ChangeExpectedResource=1">Change name of expected resource</a><br>
</body>
</html>



