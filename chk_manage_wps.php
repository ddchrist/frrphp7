<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>
<?php
define('_GLOBAL_TABLE', '_Admin_');  // This is used for the Admin collection to store tables that must be global/common for all users. A document is created with key "User" = "_Admin_"

//table {border-style: none; border-collapse: collapse; border-width: 2px; border-color: black}
//td {border-style: solid; border-width: 1px;}
?>


<!--<style type="text/css">
 table {border-collapse: collapse; border-width: 2px; border-color: black}
td {border-top: 1px solid #000; border-buttom: 1px solid #000; }  -->
<!-- //table {border-style: none; border-collapse: collapse; borderses-width: 2px; border-color: black}
//td {border-style: solid; border-width: 1px;} -->

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<style type="text/css">
a:link{
  color:black;
}
a:visited{
  color:black;
}
a:hover{
  color:orange; text-decoration: underline;
}
a:focus{
  color:green;
}
a:active{
  color:red;
}

A{text-decoration:none}     <!--  remove underline in links -->
</style>
<title>Manage CheckList</title>
<?php include("class_lib.php"); ?>

<script src="scrollfix.js" type="text/javascript"></script>
<script>
function check(TCNum, RoleTeam, Role)
{
   Id=TCNum+"_"+RoleTeam+"_"+Role;    // e.g. 01.01.01_Team_dk_lasse2_ACC02_WEST
   var xmlhttp;
   xmlhttp=new XMLHttpRequest();
   xmlhttp.onreadystatechange=function()
   {
      if (xmlhttp.readyState==4 && xmlhttp.status==200)
      {
         //document.getElementById(Id).innerHTML=xmlhttp.responseText;
         txt=xmlhttp.responseText;
         arrTxt=txt.split("#");  // is e.g. 01, 01.01, 01.01.01, 01.01.02 ....
         // alert(arrTxt);
         if(Checked=="yes") Check=true;
         else Check=false;
         for (var i=0;i<arrTxt.length-1;i++)
         {
            document.getElementById(arrTxt[i]+"_"+RoleTeam+"_"+Role).checked = Check;
         }
         // if returned text contains "error" then something is wrong storing into database
         if(txt.contains("error")) alert("The database seems not to comminucate, Please try reload the page (F5) and try again");
      }
   }
   // Prepare sending information $_GET whatever checkbox is checked or not
   if(document.getElementById(Id).checked) {
      Checked="yes";
   }
   else {
      Checked="no";
   }
   xmlhttp.open("GET","chk_manage_wps_response.php?UpdateRole="+Id+"&Checked="+Checked,true);
   xmlhttp.send();
}

</script>
</head>
<body onunload="unloadP('chk_manage_wbs')" onload="loadP('chk_manage_wbs')">

<?php include("chk_headerline.html"); ?>

<?php
StoreAdminData($_SESSION['username']."_InitialStartup","Checklist");

if (isset($_GET['CalcSumsManHours'])) {
   // If WPNum already exist then get and overwrite its _id 
   $obj=new db_handling(array("Backup" => array('$exists' => false)),"");
   $obj->DataBase="Checklist";

   $obj->get_cursor_find();
   foreach($obj->cursor as $o) {
      $obj2=new db_handling(array('WPNum' => $o['WPNum']),"Backup");
      $obj2->DataBase="Checklist";
      $obj2->get_cursor_findone();
//print_r($obj2->cursor);
//echo "<br>";
      if(isset($obj2->cursor['Ressources'])) {
         $arr=$obj2->cursor['Ressources'];
         $ManHours=0.0;
         foreach($arr as $a) {
echo $o['WPNum'].":::";
print_r($a);
            $ManHours+=floatval($a["ManHours"]);
         }
echo "<br>:::ManHours=$ManHours";
         $obj2->cursor['ManHours']=$ManHours;
echo "<br><br>";
         $obj2->save_collection($obj2->cursor);
      }
   } 
exit;  
   header("location:chk_manage_wps.php?ListWPs=1");
   exit();
}
if (isset($_POST['CreateWP'])) {
   // If WPNum already exist then get and overwrite its _id 
   $obj=new db_handling(array('WPNum' => $_POST['WPNumH1']),"Backup");
   $obj->DataBase="Checklist";
   $obj->get_cursor_findone();
   $obj->cursor['WPNum']=$_POST['WPNumH1'];
   $obj->cursor['WPName']=$_POST['WPNameH1'];
   $obj->save_collection();

   header("location:chk_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['ListWPs'])) {
   // Make a list of all WP numbers names, add, copy, delete

   echo '<table border="0">';
   echo '<tr>';
   echo '<td valign="baseline">';
   echo '<a href="chk_manage_wps.php?ExpandAll=1">Expand</a>&nbsp;&nbsp;';
   echo '<a href="chk_manage_wps.php?CollapseAll=1">Collapse</a>&nbsp;&nbsp;';
   echo '<a href="chk_export.php?format=csv">Export</a>';
   echo '</td>';
   echo '<td valign="baseline">';
   GetTableProperties();
   echo '</td>';
   echo '</tr>';
   echo '</table>';
   ListWPs();
   exit();
}
elseif (isset($_GET['AddWP'])) {
   // Check id user has rights to the given access
   if(!UserRights($_GET['AddWP'],"Checklist")) {
      echo "You do not have user rights to add test cases";  
      exit();
   }

   ListWPs("",$_GET['AddWP']);  // List WPs until and including where to add
?>
   <hr>
   <form action="chk_manage_wps.php" method="post">
   Super Header:
   <input type="text" name="WPNum" size="14" value="<?php echo $_GET['AddWP'];?>" readonly="readonly"/>
   <?php
   $obj=new db_handling(array('WPNum' => $_GET['AddWP']),"Backup");
   $obj->DataBase="Checklist";
   echo "<b>".$obj->get_value("WPName")."</b>";
   ?>
   <br>
   <table border="0">
   <tr>
   <td>Enter Work Package sub header <b>name</b> (e.g. 'Training'):</td>
   <td><input type="text" name="WPNameSub" size="16" value=""/>
   </tr>
   <tr>
   <td>Enter sub header <b>number</b> (e.g. '10'):</td>
   <td><input type="text" name="WPNumSub" size="16" value=""/></td>
   </tr>
   </table>
   <input type="submit" name="CreateSubWP" value="Create Sub WP"/>
   </form>
   <hr>
<?php
   ListWPs($_GET['AddWP'],"");
   exit();
}
elseif (isset($_GET['DelWP'])) {   
   $DelWP=$_GET['DelWP'];
   // Check id user has rights to the given access
   if(!UserRights($DelWP,"Checklist")) {
      echo "You do not have user rights to delete test cases";  
      exit();
   }
   $obj=new db_handling(array('WPNum' => new MongoRegex("/^$DelWP/")),"Backup");  // delete starting with
   $obj->DataBase="Checklist";
   $obj->get_cursor_find();
   echo "<b>You are about to delete the following packages:</b><br>";
   echo '<table border="0">';
   echo '<tr><th align="left">WP#</th><th align="left">WP Name</th></tr>';
   foreach($obj->cursor as $o) {
         echo '<tr>';
         echo '<td>'.$o['WPNum']."</td><td>".$o['WPName']."</td>";
         echo '<tr>';
   }
   echo "</table>";
?>
   <form action="chk_manage_wps.php" method="post">
   <input type="text" name="WPNum" size="6" value="<?php echo $DelWP;?>" readonly="readonly"/>
   <input type="submit" name="DelWP2" value="Delete"/>
   <input type="submit" name="DelWP2" value="Cancel"/>
   </form>
<?php
   exit();
}
elseif (isset($_POST['DelWP2'])) {
   if($_POST['DelWP2']=="Delete") {
      // Delete WPs
      $DelWP=$_POST['WPNum'];
      $obj=new db_handling(array('WPNum' => new MongoRegex("/^$DelWP/")),"Backup");  // delete starting with
      $obj->DataBase="Checklist";
      $obj->remove();
   }
   header("location:chk_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['CopyWP'])) {
   ListWPs("",$_GET['CopyWP']);
?> <hr>
   <form action="chk_manage_wps.php" method="post">
   Copy
   <input type="text" name="WPNum" size="10" value="<?php echo $_GET['CopyWP'];?>" readonly="readonly"/>
   to
   <input type="text" name="CloneWPNum" size="10" value="<?php echo $_GET['CopyWP'];?>"/>
   <input type="checkbox" name="InclSubWPs" value="1" checked="checked"> Include sub WPs
<!--   <input type="checkbox" name="DelDates" value="1" checked="checked"> Delete Dates  
   <br>
   <span title="If a New Start Date is given, the WP and sub WPs are copied and dates are moved as the distance between Start Date and New Start Date. If 'Delete Dates' is checked, it is overruled"><p style="color:#C00000">New Start Date (YYYY-MM-DD):  -->
<!--   <input type="text" name="NewStartDate" size="10" value=""> -->
   <input type="submit" name="CopyWP2" value="Copy">
   </p></span>
   </form>
   <hr>
   <?php
   ListWPs($_GET['CopyWP'],"");
   ?>
   <span title="If you copy '21' to '23' then the new WP will be '23'
If you copy '21.20.10' to '23.10' then the new WP will be '23.10'
If sub WPs is included they will move under to number moved to e.g:
  From (including sub WPs):
    21.10
    21.10.10
    21.10.10.10
  To:
    24
  Result:
    24
    24.10
    24.10.10"><p style="color:#C00000">How Copy works (pop-up)</p></span> 
<?php   
   exit();
}
elseif (isset($_POST['CopyWP2'])) {
   $WPNumFrom=$_POST['WPNum'];   // from WPNum
   $WPNumTo=$_POST['CloneWPNum']; // to WPNum (the new number)
   if(!ValidFullWPNum($WPNumTo)) {
      echo 'Invalid WP number!: "'.$WPNumTo.'"';
      exit;
   }
   if(isset($_POST['InclSubWPs'])) $inclsub="checked";
   else $inclsub="";
//   if(isset($_POST['DelDates'])) $deldates="checked";
//   else $deldates="";
?> 
   <form action="chk_manage_wps.php" method="post">
   Copy
   <input type="text" name="WPNum" size="10" value="<?php echo $WPNumFrom;?>" readonly="readonly"/>
   to
   <input type="text" name="CloneWPNum" size="10" value="<?php echo $WPNumTo;?>" readonly="readonly"/>
   <input type="checkbox" name="InclSubWPs" value="1" <?php echo $inclsub;?>> Include sub WPs
<!--   <input type="checkbox" name="DelDates" value="1" <?php echo $deldates;?>> Delete Dates 
   <br>New Start Date<input type="text" name="NewStartDate" size="10" value="<?php echo $_POST['NewStartDate'];?>" readonly="readonly"/> -->
   <input type="submit" name="CopyWP3" value="Confirm Copy">
   <input type="submit" name="CopyWP3" value="Cancel">
   </form>
   <b>Please confirm the following</b>
<?php
   if(isset($_POST['InclSubWPs'])) $query=array('WPNum' => new MongoRegex("/^$WPNumFrom/"));
   else $query=array('WPNum' => $WPNumFrom);
   $oList=new db_handling($query,"");
   $oList->DataBase="Checklist";
   $oList->get_cursor_find();
   echo '<table border="1">';
   echo '<tr align="left"><th>Action</th><th>From WP#</th><th>Name</th><th>-></th><th>To WP#</th><th>Name</th></tr>';
   foreach($oList->cursor as $obj) {
      // Change WPNum e.g. if copy WP '05.10' to WP '09' it will be '09' 
      // if change from '05.10.05' to '09.10' it will be '09.10'
      $LengthFrom=strlen($WPNumFrom);
      $WPNum=$WPNumTo.substr($obj['WPNum'],$LengthFrom);
      $FromWPNum=$obj['WPNum'];
      $obj['WPNum']=$WPNum;
      // Get WP if it exist
      $oWP=new db_handling(array('WPNum' => $WPNum),"Backup");
      $oWP->DataBase="Checklist";
      $oWP->get_cursor_findone();
      
      if(isset($oWP->cursor["WPNum"])) {
         echo '<tr bgcolor="#FF6666">';
         echo '<td>overwrite:</td><td>'.$obj["WPNum"]."</td><td>".$obj["WPName"]."</td><td>-></td><td>".$oWP->cursor["WPNum"]."</td><td>".$oWP->cursor["WPName"]."</td>";
         echo '</tr>';
      }
      else echo "<tr><td>copying:</td><td>".$FromWPNum."</td><td>".$obj["WPName"]."</td><td>-></td><td>".$WPNum."</td><td></td></tr>";
   }
   echo "</table>";
   exit;
}
elseif (isset($_POST['CopyWP3'])) {
   if($_POST['CopyWP3']=="Cancel") goto cancel;

   $WPNumFrom=$_POST['WPNum'];   // from WPNum
   $WPNumTo=$_POST['CloneWPNum']; // to WPNum (the new number)

   // Check id user has rights to the given access
   if(!UserRights($WPNumTo,"Checklist")) {
      echo "You have no user rights to copy into TC: ".$WPNumTo; 
      exit;
   }

   // Find in Unix time how much the WP is moved in time
//   if(trim($_POST['NewStartDate'])<>"") {
//      $DiffUnixTime=GetDifferenceStartAndNewStartDate($WPNumFrom,$_POST['NewStartDate']);
//   }

   if(isset($_POST['InclSubWPs'])) $query=array('WPNum' => new MongoRegex("/^$WPNumFrom/"));
   else $query=array('WPNum' => $WPNumFrom);
   // Find requested WP and all SubWPs (if requested) e.g. '05' and '05.10', '05.20' etc..
   // Foreach WP change WPNum to copy-to number
   //     Check if number exist already and if it does, get '_id' so it can be overwritten
   //     else unset '_id'
   // Save document/WP
   $oList=new db_handling($query,"");
   $oList->DataBase="Checklist";
   
   $oList->get_cursor_find();
   foreach($oList->cursor as $obj) {
      // Change WPNum e.g. if copy WP '05.10' to WP '09' it will be '09' 
      // if change from '05.10.05' to '09.10' it will be '09.10'
      $LengthFrom=strlen($WPNumFrom);
      $WPNum=$WPNumTo.substr($obj['WPNum'],$LengthFrom);
      $obj['WPNum']=$WPNum;
      // Get WP if it exist
      $oWP=new db_handling(array('WPNum' => $WPNum),"");
      $oWP->DataBase="Checklist";
      $oWP->get_cursor_findone();
      // If a WP exist i.e. has an '_id' then move this ID into the object and save it
      // otherwise remove ID from copy-from and save to create a new document.
      if(isset($oWP->cursor['_id'])) $obj['_id']=$oWP->cursor['_id'];
      else unset($obj['_id']);  // unset so Mongo will create a new ID i.e. copy
         
      // changes to new start date if requested
//      if(trim($_POST['NewStartDate'])<>"") {
//         if(isset($obj['StartDate'])) $obj['StartDate']=AddDateAndUnixTime($obj['StartDate'], $DiffUnixTime); 
//         if(isset($obj['EndDate'])) $obj['EndDate']=AddDateAndUnixTime($obj['EndDate'], $DiffUnixTime); 
//      }
      // Do not copy dates - so it can be seen that would have to be set again
//      elseif(isset($_POST['DelDates'])) {
//         if(isset($obj['StartDate'])) unset($obj['StartDate']);
//         if(isset($obj['EndDate'])) unset($obj['EndDate']);
//      }
      $oList->save_collection($obj);
      AddHTMLofWPToDB($obj['WPNum'],"Checklist","chk");
      AlignKeyManHoursWithResources($obj['WPNum'],"Checklist");
   }
   UpdateHTMLColoursofSubWPs($WPNumTo,"Checklist","chk");
//   $WPLevels=explode(".",$WPNumTo);  // find sup/sub levels of WPNum
//   FindMinMaxOfSubWPs($WPLevels[0]);
   cancel:
   header("location:chk_manage_wps.php?ListWPs=1");  
   exit;
}
elseif (isset($_POST['CreateSubWP'])) {
   if(!ValidWPNum($_POST['WPNumSub'])) {
      echo 'Invalid WP number!: "'.$_POST['WPNumSub'].'"';
      exit;
   }
   $WPNum=$_POST['WPNum'].".".trim($_POST['WPNumSub']);


   // Check id user has rights to the given access
   if(!UserRights($WPNum,"Checklist")) {
      echo "You do not have user rights to create sub test cases";  
      exit();
   }
   $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
   $obj->DataBase="Checklist";
   $obj->get_cursor_findone();
   $obj->cursor['WPNum']=$WPNum;
   $obj->cursor['WPName']=$_POST['WPNameSub'];
   $obj->save_collection();
   AddHTMLofWPToDB($WPNum,"Checklist","chk");
   UpdateHTMLColoursofSubWPs($WPNum,"Checklist","chk");

   header("location:chk_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['ExpColWP'])) {   // Expand/Collapse Work Package
   $WPNum=$_GET['ExpColWP'];
   $WPLength=strlen($WPNum);
   $arrUserViewList=GetAdminOnUser("UserViewList");
   if($arrUserViewList[$WPNum]=="-") {
      // remove WPNum & all SubWPs except H1
      foreach($arrUserViewList as $k=>$v) {
         if(substr($k,0,$WPLength)==$WPNum) {
            echo $k." ".$v." ".strpos($k,".")."<br>";
            if(strpos($k,".")) unset($arrUserViewList[$k]);
            else $arrUserViewList[$k]="+";
         }
      }
   }
   elseif($arrUserViewList[$WPNum]=="+") {
      $arrUserViewList[$WPNum]="-";
   }
   elseif(!isset($arrUserViewList[$WPNum])) {
      $arrUserViewList[$WPNum]="-";
   }
   StoreAdminOnUser("UserViewList",$arrUserViewList);
   
   header("location:chk_manage_wps.php?ListWPs=1");
   exit;
}
elseif (isset($_GET['ExpandAll'])) {   // Expand all Work Packages
   $db_name="Checklist"; // Database name
   $tbl_name=$_SESSION["ProjectName"]; // Collection name
//   $m = new Mongo();
//   $db = $m->selectDB($db_name);
//   $collection = new MongoCollection($db, $tbl_name);
$m = new MongoDB\Client();
$db = $m->$db_name;
$collection = $db->$tbl_name;

   
   // Get all WP and remove 'Show' and 'Exp'
   $cursor=$collection->find();
   $arrUserViewList=array();
   foreach($cursor as $o) {
      $WPNum=$o['WPNum'];
      $cur=$collection->findone(array('WPNum' => new MongoRegex("/^$WPNum\.[^.]*$/")));
      if($cur<>NULL) $arrUserViewList[$WPNum]="-";
   }
   StoreAdminOnUser("UserViewList",$arrUserViewList);

   header("location:chk_manage_wps.php?ListWPs=1");
   exit;
}
elseif (isset($_GET['CollapseAll'])) {   // Expand all Work Packages
   $query=array('WPNum' => new MongoRegex("/^[^.]*$/")); // match only H1 level
   $obj=new db_handling($query,"");
   $obj->DataBase="Checklist";
   $obj->get_cursor_find_sort();
   foreach($obj->cursor as $o) {
      $arrUserViewList[$o["WPNum"]]="+";  
   }
   StoreAdminOnUser("UserViewList",$arrUserViewList);

   header("location:chk_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['Backup'])) {
   // backup from indicated heading level and down
   if(isset($_SESSION["BackupLevel"])) $BackupLevel=$_SESSION["BackupLevel"];
   else $BackupLevel="";
?>
   <form action="chk_manage_wps.php" method="post">
   Indictate backup level e.g. "02.10" means "02.10" and all sub levels e.g. 02.10.10<br>
   <input type="text" name="BackupLevel" size="10" value="<?php echo $BackupLevel;?>"
   <br><input type="submit" name="Backup2" value="Submit"/>
   </form>   
<?php   
   exit();
}
elseif (isset($_POST['Backup2'])) {
   // gets all WP from indicated level and below e.g. "06" would give 06.XX etc.
   // If a backup WP exist it will be overwritten with found WP
   // otherwise the '_id' is removed and 'Backup' inserted and th WP inserted
   $_SESSION["BackupLevel"]=$_POST['BackupLevel'];
   $BackupLevel=$_SESSION["BackupLevel"];
   // Check id user has rights to the given access
   if(!UserRights($BackupLevel,"Checklist")) {
      echo "No user rights to backup!<br>";
      exit();
   }
   $oList=new db_handling(array('WPNum' => new MongoRegex("/^$BackupLevel/")),"Backup"); // search starting with WPNum
   $oList->DataBase="Checklist";
   $oList->get_cursor_find();
   $User=$_SESSION['username'];
   
   foreach($oList->cursor as $obj) {
//print_r($obj);
//echo "<br>*****<br>";
      $oBack=new db_handling(array('Backup' => "$User", 'WPNum' => $obj['WPNum']),"");
      $oBack->DataBase="Checklist";
      $oBack->get_cursor_findone();
      // Check if a 'Backup' WP already exist
      if(isset($oBack->cursor)) {
         $ID=$oBack->cursor["_id"];
         $oBack->cursor=$obj;
         $oBack->cursor['Backup']=$_SESSION['username']; // 'Backup' contains if it is backup and name of person
         $oBack->cursor["_id"]=$ID;
         $oBack->save_collection();
      }
      else {    
         // WP does not exist - insert new 'Backup' WP
         unset($obj['_id']);  // unset so Mongo will create a new ID
         $obj['Backup']=$_SESSION['username']; // 'Backup' contains if it is backup and name of person
         $oBack->insert($obj);
//print_r($obj);
//echo "<br>#####<br>";
      }
   }
   header("location:chk_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['Restore'])) {
   // backup from indicated heading level and down
   if(isset($_SESSION["BackupLevel"])) $BackupLevel=$_SESSION["BackupLevel"];
   else $BackupLevel="";
?>
   <form action="chk_manage_wps.php" method="post">
   Indictate Restore level e.g. "02.10" means "02.10" and all sub levels e.g. 02.10.10<br>
   <input type="text" name="RestoreLevel" size="10" value="<?php echo $BackupLevel;?>"
   <br><input type="submit" name="Restore2" value="Submit"/>
   </form>   
<?php   
   exit();
}
elseif (isset($_POST['Restore2'])) {
   $BackupLevel=$_POST['RestoreLevel'];
   // Check User Rights
   if(!UserRights($BackupLevel,"Checklist")) {
      echo "You do not have user rights to add test cases";  
      exit();
   }

//   $_SESSION["BackupLevel"]=$BackupLevel;
   $User=$_SESSION['username'];
   $oList=new db_handling(array('Backup' => "$User", 'WPNum' => new MongoRegex("/^$BackupLevel/")),""); // search starting with WPNum
   $oList->DataBase="Checklist";
   $oList->get_cursor_find();
   
   foreach($oList->cursor as $obj) {
      echo $obj['WPNum']." ";
      echo '<a href="chk_manage_wps.php?Restore3WP='.$obj['WPNum'].'">Restore</a><br>';
   }
   exit();
}
elseif (isset($_GET['Restore3WP'])) {
   // Builds up a new cursor based on old cursor from backup and inserting
   // present _id and removing key "Backup" from backup
   $objPresent=new db_handling(array('WPNum' => $_GET['Restore3WP']),"Backup");
   $objPresent->DataBase="Checklist";
   $objPresent->get_cursor_findone();
   $User=$_SESSION['username'];
   $objOld=new db_handling(array('WPNum' => $_GET['Restore3WP'], 'Backup' => "$User"),"");
   $objOld->DataBase="Checklist";
   $objOld->get_cursor_findone();
   $cursorNew=$objOld->cursor;
   $cursorNew['_id']=$objPresent->cursor['_id'];
   unset($cursorNew['Backup']);   // makes it not a backup document
//print_r($cursorNew);
   // Store new cursor
   $objPresent->cursor=$cursorNew;
//print_r($objPresent->cursor);
//exit;
   $objPresent->save_collection();
   header("location:chk_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['Rename'])) {
   // Rename heading name of WP i.e. WPName
   $WPNum=$_GET["Rename"]; 
   // Check id user has rights to the given access
   if(!UserRights($WPNum,"Checklist")) {
      //header("location:chk_manage_wps.php?ListWPs=1");  
      echo "You have no user rights to perform this action";
      exit();
   }

   $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
   $obj->DataBase="Checklist";
   $obj->get_cursor_findone();
   ?>
   <form action="chk_manage_wps.php" method="post">
   Present header name of Test Case number
   <input type="text" name="WPNum" size="10" value="<?php echo $WPNum;?>" readonly=readonly>
   is '<b><?php echo $obj->cursor['WPName'];  ?></b>'<br>
   New Header Name:
   <input type="text" name="WPName" size="25" value="<?php echo $obj->cursor['WPName'];?>">
   <br><input type="submit" name="Rename2" value="Submit"/>
   </form>   
<?php   
   exit;
}
elseif (isset($_GET['RenameTextArea'])) {
   // Rename heading name of WP i.e. WPName
   $WPNum=$_GET["RenameTextArea"]; 

   $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
   $obj->DataBase="Checklist";
   $obj->get_cursor_findone();

   // Check id user has rights to the given access and allow only to change suggested change
   if(!UserRights($WPNum,"Checklist")) $readonly='readonly=readonly';
   else $readonly="";

   ?>
   <form action="chk_manage_wps.php" method="post">
   <b>Test Case number:</b>
   <input type="text" name="WPNum" size="10" value="<?php echo $WPNum;?>" readonly=readonly>
   <br>
   <b>Test Case Description:</b><br>
   <?php
   echo '<textarea name="WPName" rows="9" cols="70" wrap="hard" '.$readonly.'>';
   $Description=$obj->get_value("WPName");
   if($Description!="") echo $Description;
   ?></textarea> 

   <br>
   <b>Note: (Applicable to all countries)</b><br>
   <?php
   echo '<textarea name="Note" rows="3" cols="70" wrap="hard" '.$readonly.'>';
   $Note=$obj->get_value("Note");
   if($Note!="") echo $Note;
   ?></textarea> 
   <br>
   <b>Note_<?php
   echo $_SESSION["country"];
   ?>
   : (Country specific note)</b><br><textarea name="NoteCountry" rows="3" cols="70" wrap="hard"><?php
   $NoteCountry=$obj->get_value("Note_".$_SESSION["country"]);
   if($NoteCountry!="") echo $NoteCountry;
   ?></textarea> 

   <?php
   $arrListOfCountries=array("at","dk","hr","ia","se");
   $NoInputNotes="";   
   foreach($arrListOfCountries as $Country) {
      if($Country==$_SESSION["country"]) continue; // skip country that have modify right
      $NoteCountry=$obj->get_value("Note_".$Country);
      if($NoteCountry=="") {
         $NoInputNotes.='<font size="-1"><br><b>Note_'.$Country.': no input!<b></font>';
      }
      else {      
         ?><br><b>Note_<?php echo $Country;?>:<br>     
         <textarea readonly name="" rows="3" cols="70" wrap="hard"><?php   
         echo $NoteCountry;
         ?></textarea><?php
      }
   }
   ?>

   <br>
   <b>Suggested Change:</b> NB! Insert your initials and date!<br>
   <textarea name="SuggestedChange" rows="9" cols="70" wrap="hard"><?php
   $change=$obj->get_value("SuggestedChange");
   echo $change;
   ?></textarea>
   <br><input type="submit" name="Rename2" value="Submit"/>
   </form>
     
<?php
   echo $NoInputNotes;   
   exit();
}
elseif (isset($_POST['Rename2'])) {
   $obj=new db_handling(array('WPNum' => $_POST['WPNum']),"Backup");
   $obj->DataBase="Checklist";
   $obj->get_cursor_findone();
   $obj->cursor['WPName']=$_POST['WPName'];
   if(isset($_POST['Note'])) $obj->cursor['Note']=$_POST['Note'];
   if(isset($_POST['NoteCountry'])) $obj->cursor["Note_".$_SESSION["country"]]=$_POST["NoteCountry"];  
   //$obj->cursor['SuggestedChange']="***".$_SESSION["username"].":".date("Y-m-d")."***\n".$_POST['SuggestedChange']."\n".$obj->cursor['SuggestedChange'];
   if(isset($_POST['SuggestedChange'])) $obj->cursor['SuggestedChange']=$_POST['SuggestedChange'];
   $obj->cursor["Modified"]=date("Y-m-d");
   $obj->cursor["ModifiedBy"]=$_SESSION["username"];
   $obj->save_collection();
   AddHTMLofWPToDB($_POST['WPNum'],"Checklist","chk");
   header("location:chk_manage_wps.php?ListWPs=1");
   exit();
}
elseif(isset($_GET['CleanDataBase']))  {
   CleanDBFromShowAndExp();
   echo "DB is cleaned for Show and Exp for all users";
   exit;
}
elseif(isset($_GET['MakeHTMLOfWPs']))  {
   MakeHTMLOfWPs();
   echo "HTML generated for all WPs";
   exit;
}

//elseif (isset($_GET['UpdateToShow'])) {
//   MarkExpandCollapseHeadings();
//   MarkDocumentsToBeListed();
//   header("location:chk_manage_wps.php?ListWPs=1");
//   exit();
//}

// submit from Manage-Test-base screen
elseif(isset($_POST['TableProperties']) or isset($_POST['RoleTeam'])) {
   StoreAdminOnUser("ColumnWidth",$_POST['ColumnWidth'],"Checklist");
   StoreAdminOnUser("chk_Filter",$_POST['chk_Filter'],"Checklist");
   StoreAdminOnUser("SelectedRoleTeam",$_POST['RoleTeam'],"Checklist");

   header("location:chk_manage_wps.php?ListWPs=1");
   exit();
}

// Store selected (checked) roles from team in DB   (NO LONGER USED!!!!)
elseif(isset($_POST['StoreTeamRoles'])) {
   if($_POST['RoleTeam']=="") {
      echo "You are trying to store Team Roles on a non existing team.<br>";
      echo "Sorry, I have to stop you here before you corrupt the database.<br>";
      echo 'You are trying to store on "RoleTeam" but likely you wanted to store on "'.substr(GetAdminOnUser("SelectedRoleTeam","Checklist"),5).'"<br>'; 
      echo "Go back and redo your work --- and do not forget to select the right Role Team";
      exit;
   }
   StoreAdminOnUser("SelectedRoleTeam",$_POST['RoleTeam'],"Checklist");
   // Get query related to what is visible listed on the screen
   $query=GetFilterQuery();

   // Delete all "Team_" documents from 'Checklist"
   $objList=new db_handling($query, "");  // do not list backup WPs
   $objList->DataBase="Checklist";
   $objList->get_cursor_find_sort();
   foreach ($objList->cursor as $object) {
//echo $object['WPNum']." ";	
      $obj=new db_handling(array('WPNum' => $object['WPNum']),"Backup");
      $obj->DataBase="Checklist";
      $obj->get_cursor_findone();
      if(isset($obj->cursor[$_POST['RoleTeam']])) {
         unset($obj->cursor[$_POST['RoleTeam']]);    // remove 'Team_SelectedTeam'   
      }
      $obj->save_collection();
   }

   // Get checks of roles if any and store them in Cheklist DB under each TC#
   // In case Role=RoleTeam, the RoleTeam must be disconnected
   // $_POST['Role'] is an array with selected (checkbox) Roles for the test teams
   // $_POST['RoleTeam'] contains name of team selected in drop down e.g. Team_dk_team1 for "team1".
   //        If no teams are selected i.e. value "RoleTeam" in drop down then value will be ""
   if(isset($_POST['Role']) and $_POST['RoleTeam']<>"") {
      foreach($_POST['Role'] as $r) {
         $WPNum=strstr($r,"_",true);
         $r=substr(strstr($r,"_"),1);
         $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
         $obj->DataBase="Checklist";
         $obj->get_cursor_findone();

         if(isset($obj->cursor[$_POST['RoleTeam']])) {
            $Team=$obj->cursor[$_POST['RoleTeam']];
            $Team[]=$r;
            $obj->cursor[$_POST['RoleTeam']]=$Team;    // name will be 'Team_SelectedTeam'
         }
         else {
            $obj->cursor[$_POST['RoleTeam']]=array($r);
         } 
         $obj->save_collection();
//         echo $r;
      }
   }
   MakeSublevelTCsEqualSuperLevel($_POST['RoleTeam']);  // will make TCs on sublevel equal to TeamRole of superlevel
   header("location:chk_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['UpdateRole'])) {
// ####
   echo "lasse";
   exit;

   // explode e.g. "01.01.01_Team_dk_lasse2_ACC02_WEST"
   $arrCheckId=explode("_",$_GET['UpdateRole']);
   $TCNum=$arrCheckId[0];
   $SelectedRoleTeam=$arrCheckId[1].$arrCheckId[2].$arrCheckId[3];
   $t=4;
   $Role="";
   while(isset($arrCheckId[$t])) {
      $t++;
      $Role.=$arrCheckId[$t]."_";
   }
   $Role=substr($Role,0,-1);  // take out last "_" in string

   echo $TCNum." ".$SelectedRoleTeam." ".$Role;
   exit;

   $db_name="Projects"; // Database name
   $tbl_name="COOPANS"; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);

   if(isset($_POST['WPNum'])) $WPNum=$_POST['WPNum'];
   elseif(isset($_GET['WPNum'])) $WPNum=$_GET['WPNum'];
   else {
      echo "No proper input";
      exit;
   }
   $cursor=$collection->findone(array('WPNum'=>$WPNum));
   echo $cursor['WPName'];
   exit;




   if($_POST['RoleTeam']=="") {
      echo "You are trying to store Team Roles on a non existing team.<br>";
      echo "Sorry, I have to stop you here before you corrupt the database.<br>";
      echo 'You are trying to store on "RoleTeam" but likely you wanted to store on "'.substr(GetAdminOnUser("SelectedRoleTeam","Checklist"),5).'"<br>'; 
      echo "Go back and redo your work --- and do not forget to select the right Role Team";
      exit;
   }
   StoreAdminOnUser("SelectedRoleTeam",$_POST['RoleTeam'],"Checklist");
   // Get query related to what is visible listed on the screen
   $query=GetFilterQuery();

   // Delete all "Team_" documents from 'Checklist"
   $objList=new db_handling($query, "");  // do not list backup WPs
   $objList->DataBase="Checklist";
   $objList->get_cursor_find_sort();
   foreach ($objList->cursor as $object) {
//echo $object['WPNum']." ";	
      $obj=new db_handling(array('WPNum' => $object['WPNum']),"Backup");
      $obj->DataBase="Checklist";
      $obj->get_cursor_findone();
      if(isset($obj->cursor[$_POST['RoleTeam']])) {
         unset($obj->cursor[$_POST['RoleTeam']]);    // remove 'Team_SelectedTeam'   
      }
      $obj->save_collection();
   }

   // Get checks of roles if any and store them in Cheklist DB under each TC#
   // In case Role=RoleTeam, the RoleTeam must be disconnected
   // $_POST['Role'] is an array with selected (checkbox) Roles for the test teams
   // $_POST['RoleTeam'] contains name of team selected in drop down e.g. Team_dk_team1 for "team1".
   //        If no teams are selected i.e. value "RoleTeam" in drop down then value will be ""
   if(isset($_POST['Role']) and $_POST['RoleTeam']<>"") {
      foreach($_POST['Role'] as $r) {
         $WPNum=strstr($r,"_",true);
         $r=substr(strstr($r,"_"),1);
         $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
         $obj->DataBase="Checklist";
         $obj->get_cursor_findone();

         if(isset($obj->cursor[$_POST['RoleTeam']])) {
            $Team=$obj->cursor[$_POST['RoleTeam']];
            $Team[]=$r;
            $obj->cursor[$_POST['RoleTeam']]=$Team;    // name will be 'Team_SelectedTeam'
         }
         else {
            $obj->cursor[$_POST['RoleTeam']]=array($r);
         } 
         $obj->save_collection();
//         echo $r;
      }
   }
   MakeSublevelTCsEqualSuperLevel($_POST['RoleTeam']);  // will make TCs on sublevel equal to TeamRole of superlevel
   header("location:chk_manage_wps.php?ListWPs=1");
   exit();




}


echo "<br>Project Name:".$_SESSION["ProjectName"]."<br";
?>
<br><br><br>
A workpackage is numbered using different heading levels. It is possible to work on three levels. The convention is a workpackage number of the form H1.H2.H3 symbolising heading on three levels. Ex. 10.12.03 is a three level workpackage.
<br><br>
<form action="chk_manage_wps.php" method="post">
Enter Work Package name:
<input type="text" name="WPNameH1" size="16" value=""/>
<br>
Enter heading 1 number:
<input type="text" name="WPNumH1" size="16" value=""/>
<br>

<input type="submit" name="CreateWP" value="Create WP"/>

</form>
</body>
</html>

<?php
// ******************************************************
// ******************************************************
// ******************************************************

// will make TCs on sublevel equal to TeamRole of superlevel
// Level 1 have priority over Level 2
function MakeSublevelTCsEqualSuperLevel($RoleTeam) {
   // RoleTeam is the selected RoleTeam from drop down list
   $obj=new db_handling(array("Backup" => array('$exists' => false)),"");
   $obj->DataBase="Checklist";
   $obj->get_cursor_find_sort();
   $level1=$level2=$level="";
   // loop over all TCs
   foreach ($obj->cursor as $o) {
      if(strlen($o['WPNum'])==2) { // header level 1 e.g. 01
         if(isset($o[$RoleTeam])) $level1=$o[$RoleTeam];
         else $level1=$level2=""; 
      }
      elseif(strlen($o['WPNum'])==5) { // header level 2 e.g. 01.02
         if(isset($o[$RoleTeam])) $level2=$o[$RoleTeam];
         else $level2="";
      }
      // Prioritise levels
      if($level1=="" and $level2=="") $level="";
      elseif($level1<>"" and $level2=="") $level=$level1; 
      elseif($level1=="" and $level2<>"") $level=$level2;
      elseif($level1<>"" and $level2<>"") $level=$level1;

      if($level<>"") {
         $Filter=array("WPNum"=>$o["WPNum"]);
         $Update=array('$set'=>array($RoleTeam=>$level));
         $obj->collection->update($Filter,$Update);
      }
   }
}  

function right($value, $count){
    return substr($value, ($count*-1));
}

function left($string, $count){
    return substr($string, 0, $count);
}


function SumOfKeys($WPNum, $Key) {
// Returns sum og level and all sub levels for given $key
   $obj=new db_handling(array('WPNum' => new MongoRegex("/^$WPNum/")),"Backup");
   $obj->DataBase="Checklist";
   return $obj->sum_of_keys($Key);
}

function ListWPs($WPNumStart="", $WPNumEnd="") {
   $query=GetFilterQuery();

   if($query=="") $filter="";
   else $filter="something";
   //print_r($query);
   if($query=="") {  // get filter based on + / - i.e. Expansion / Collapse
      $query=array();
      $arrUserViewList=GetAdminOnUser("UserViewList");
      //print_r($arrUserViewList);
      foreach($arrUserViewList as $WP=>$ec) {   // $ec = expanded / collapsed
         if(!strpos($WP,".")) {  // Level H1
            $query[]=array('WPNum' => "$WP");  // H1
            if($ec=="-") $query[]=array('WPNum' => new MongoRegex("/^$WP\.[^.]*$/")); // H2  
         }
         else $query[]=array('WPNum' => new MongoRegex("/^$WP\.[^.]*$/")); // H3, H4, ...
      }
      $query=array('$or'=>$query);
   }   
   
   $objList=new db_handling($query, "");  // do not list backup WPs
   $objList->DataBase="Checklist"; 
   $objList->get_cursor_find_sort();
//   var_dump($objList);
   $oCalender=new CalenderHandling($objList);
var_dump($objList); 
   echo '<table border="0" cellpadding="0" cellspacing="0">';  
//   echo '<table cellpadding="0" cellspacing="0">';

   // Make $RoleTeam contain headings of roleteams
   $selectedRoleTeam=GetAdminOnUser("SelectedRoleTeam","Checklist");
//print_r($selectedRoleTeam);
   if($selectedRoleTeam<>"") {
      $objRoleTeam=new db_handling(array('User' => "_Country_".$_SESSION["country"]),"Backup");
      $objRoleTeam->DataBase="Checklist";
      $objRoleTeam->CollectionName="Admin";
      $objRoleTeam->get_cursor_findone();
      if(isset($objRoleTeam->cursor[$selectedRoleTeam])) {
         $RoleTeam=$objRoleTeam->cursor[$selectedRoleTeam]; // an array of arrays
      }
      else $RoleTeam=array();
   }
   else $RoleTeam=array();  // if no RoleTeam selected make sure checkboxes are not printed
   //echo InsertHeaderline($RoleTeam);
   $HeaderCounter=20;

   // width of H3
   $ColWidth=GetAdminOnUser("ColumnWidth","Checklist");
   if($ColWidth=="") $ColWidth=25;
//***********************************
   // width of H3
   $ColWidth=GetAdminOnUser("ColumnWidth","Checklist");
   if($ColWidth=="") $ColWidth=25;

   $Superhtml="";
   $ColChange=0;
   foreach ($objList->cursor as $obj) {
      $html="";
      if(substr_count($obj['WPNum'],".")==0 and $HeaderCounter>=12) {
         $html.=InsertHeaderline($RoleTeam,"#CCCC22");
         $HeaderCounter=0;  // make sure that heading will always come first
      }
      if($filter<>"" and substr_count($obj['WPNum'],".")==1 and $HeaderCounter>=2) {
      
         $html.=InsertHeaderline($RoleTeam,"#FFFFAA");
         //$HeaderCounter=0;  // make sure that heading will always come first
      }
      $HeaderCounter++;
      
      if($obj['WPNum'] <= $WPNumStart) continue;      

      // increase visibility of header0 and header1
//      $Visual=substr_count($arrFilter[0],"."); // if filter is on lower levels then move visibility down as well
      $Visual=0;
      $WPLevel=substr_count($obj['WPNum'],".");
      if($WPLevel==$Visual) {
         $s='style="font-weight: bold; font-size: 13pt; ';
      }
      elseif($WPLevel==$Visual+1) {
         $s='style="font-weight: bold; ';
      }
      else $s='style="';
      $s=str_replace('style="',$s,$obj['HTML_Col']);
     
      $html.= "<tr".$s.">";
      $html.= '<td align="center" valign="top">';
      // Manage expanding / collapsing WPs
      if($filter=="") {
         if(isset($arrUserViewList[$obj['WPNum']])) {
            if($arrUserViewList[$obj['WPNum']]=="+") $html.= $obj['HTML_ExpPlus'];
            else $html.= $obj['HTML_ExpMinus'];
         }
         else {
           $WP=$obj['WPNum'];
           $obj2=new db_handling(array('WPNum' => new MongoRegex("/^$WP\.[^.]*$/")), "");
           $obj2->DataBase="Checklist";
           $obj2->get_cursor_findone();
           if($obj2->cursor <> NULL) $html.= $obj['HTML_ExpPlus'];
         }
      } 
      $html.= '</td>';

      $html.= '<td>';
      $html.= $obj['HTML_WPNum'];
      // Mark "C" if suggested changes to test case exist.
      if(MarkIfChangesExitAtLevelAndSubLevels($obj['WPNum'])) $html.='<font color="red">C</font>';
      if(MarkIfChangesSinceGivenDateAtLevelAndSubLevels($obj['WPNum'])) $html.='<font color="green">N</font>';
      $html.='&nbsp;';
      $html.= '</td>';

      $html.= '<td>';
      // make rename of header level 3 to a textarea
      if(strlen($obj['WPNum']) < 6) $html.='<a href="chk_manage_wps.php?Rename='.$obj['WPNum'].'" style="color:#TxCol#">';
      else $html.='<a href="chk_manage_wps.php?RenameTextArea='.$obj['WPNum'].'" style="color:#TxCol#">';
      if(strlen($obj['WPNum']) < 6) $html.=IndentHeadingName($obj['WPNum'],$obj['WPName'])." ";
      else $html.=IndentHeadingName($obj['WPNum'],substr($obj['WPName'],0,$ColWidth))." ";
      $html.= '</a>';
      $html.= '</td>';

      $html.= '<td>';
      $html.= $obj['HTML_Add'];
      $html.= '</td>';

      $html.= '<td>';
      $html.= $obj['HTML_Del'];
      $html.= '</td>';

      $html.= '<td>';
      $html.= $obj['HTML_Cop'];
      $html.= '</td>';

      $html.= '<td align="right">';
      $ManHours=SumOfKeys($obj["WPNum"],"ManHours");
      $html.= number_format($ManHours, 0, '.', '')."&nbsp;&nbsp;";
      $html.= '</td>';
      $html.= '<td align="right">';
      $html.= number_format($ManHours/7.4, 1, '.', '').' ';  // ManDays
      $html.= '</td>';
      
      if($selectedRoleTeam<>"") {
         $html.= '<td style="font-size: 9px">';
         if(isset($obj['Roles'])) $html.= "&nbsp;".$obj['Roles'];
         $html.= '</td>';
         $html.=ListRoles($obj, $RoleTeam, $selectedRoleTeam);
      }
      $html.= '</tr>';
      
      if($selectedRoleTeam<>"") {
         $ColChange++;
         if($ColChange % 2) $HighCol=$obj['HTML_HighCol'];
         else $HighCol="#EEEEEE";
      }
      else $HighCol=$obj['HTML_HighCol']; // just get whatever colour selected
      $TextCol=$obj['HTML_TextCol']; // just get whatever colour selected
      $html=str_replace('#TxCol#',$TextCol,$html);  // substitute text colour tag
      $html=str_replace('#HiCol#',$HighCol,$html);  // substitute highlight colour tag
      
      if($WPNumEnd==$obj['WPNum']) break;   // exit if WPNum match funtion criteria     
      $Superhtml.=$html;
   }
   $Superhtml.= '</table>';
   echo $Superhtml;
}

// GetFilterQuery builds a mongoDB query based on selected "Filter:" submitted.
// In case a filter is not submitted, the query will be based on Test Cases shown
// on screen in relation to normal handling through usage of "+" / "-" expanding collapsing 
function GetFilterQuery() { 
   $arrFilter=GetAdminOnUser("chk_Filter","Checklist");

   if($arrFilter=="###") {   // allow debug info
      $arrUserViewList=GetAdminOnUser("UserViewList");
      print_r($arrUserViewList);
      $arrFilter="";
   }

   // if filter line start with '&' then logical 'and' filter else logical 'or' filter
   if(substr($arrFilter,0,1)=="&") {
      $arrFilter=substr($arrFilter,1);
      $And=true;
   }
   else $And=false;

   $arrFilter=explode("&",$arrFilter);
   $arrFilter=array_map('trim',$arrFilter);
   $query=array();
   foreach($arrFilter as $filter) {
      // Match Text case numbers
      if(!preg_match("/[^.0-9]/i", $filter)) {
         $query[]=array('WPNum' => new MongoRegex("/^$filter/"));
      }
      // Match dates (not used in Test cases)
      //elseif(preg_match("/#[0-9]{4}-/", $filter)) {
      //   $filter=substr($filter,1);  // take out '#'
      //   $query[]=array('StartDate' => new MongoRegex("/$filter/"));
      //}
      // Match tags (not used in Test cases)
      //elseif(substr($filter,0,1)=="#") {
      //   $filter=substr($filter,1);
      //   $query[]=array('Tags' => new MongoRegex("/$filter/i"));
      //}
      // Match Name      
      else {
         $query[]=array('WPName' => new MongoRegex("/$filter/i"));
         //$query[]=array('SuggestedChange' => new MongoRegex("/$filter/i"));
         //$query[]=array('Note' => new MongoRegex("/$filter/i"));
      }
   }
   if($And) $query=array('$and'=>$query);
   else $query=array('$or'=>$query);

   // override query and make sure expand/collapse work in case no filter
   if($filter=="") {  
      $query="";
   }
   return $query;
}
function InsertHeaderline($RoleTeam,$HighLightColour) {
   $html="";
   $html.='<tr bgcolor='.$HighLightColour.'><th></th><th align="left">TC#&nbsp;</th><th align="left">Name&nbsp;</th><th>add&nbsp;</th><th align="left">del&nbsp;</th><th align="left">cop&nbsp;</th><th>MH&nbsp;</th><th>MD</th>';
   if($RoleTeam<>NULL) $html.='<th style="font-size: 9px">Roles</th>';
   foreach($RoleTeam as $Role) {
      $html.= "<th>".'<span style="font-size: 9px">'."&nbsp;".$Role["Role"]."&nbsp;&nbsp;</span></th>";
   }
   $html.= "</tr>";
   return $html;
}

function GetTableProperties() {
   ?>
   <form action="chk_manage_wps.php" method="post">
   Column width:
   <input type="text" name="ColumnWidth" size="3" value="<?php echo GetAdminOnUser("ColumnWidth","Checklist");?>" />
   <span title="Use e.g. 01&02&03.01 to filter view for specified Text Cases
E.g. '&task 1 of 4&select' - will find all 'Task 1 of 4' that contains 'select' (logical 'and')
E.g. 'task 1 of 4&select' - will find all 'Task 1 of 4' or descriptions that contains 'select' (logical 'or')
">Filter:</span>
   <input type="text" name="chk_Filter" size="14" value="<?php echo GetAdminOnUser("chk_Filter","Checklist");?>" />
   <input type="submit" name="TableProperties" value="Submit"/>
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
   echo generateSelect("RoleTeam", $options, $selected,true);   
   ?>
<!--    <input type="submit" name="StoreTeamRoles" value="Store TeamRoles"/> -->
   <?php
   // note </form> is moved into end of calling function
}

function ListRoles($obj, $arrRoleTeam, $selectedRoleTeam)
{
// $obj: point to TC# in DB
// $arrRoleTeam: Array of Roles in Team
// $selectedRoleTeam: Name of Role Team as seen in the drop down menu
   $html="";
   if(isset($obj[$selectedRoleTeam])) $SelectedRoles=$obj[$selectedRoleTeam];
   else $SelectedRoles=array();
   foreach($arrRoleTeam as $Role) {
      if(in_array($Role['Role'],$SelectedRoles)) $chk="checked";
      else $chk="";
      $chkID=$obj["WPNum"]."_".$selectedRoleTeam."_".$Role["Role"];
      $html.='<td align="center"> '.'<input type="checkbox" name="Role[]" id="'.$chkID.'" style="color: #FF0000;" value="'.$chkID.'" onclick=\'check("'.$obj["WPNum"].'","'.$selectedRoleTeam.'","'.$Role["Role"].'");\' '.$chk.'>'."</td>";
   }
   // must be rad by $_POST['Role']

   return $html;
}
function MarkIfChangesExitAtLevelAndSubLevels($WPNum) {
//   $objList=new db_handling(array('WPNum' => new MongoRegex("/^$WPNum/"), "Show_".$_SESSION['username'] => "1"), "Backup");  // do not list backup WPs
   $objList=new db_handling(array('WPNum' => new MongoRegex("/^$WPNum/")), "Backup");  // do not list backup WPs
   $objList->DataBase="Checklist";
   $objList->get_cursor_find_sort(); 
   foreach ($objList->cursor as $obj) {
      if(isset($obj["SuggestedChange"])) if($obj["SuggestedChange"]<>"") return true;
   }
   return false;
}
function MarkIfChangesSinceGivenDateAtLevelAndSubLevels($WPNum) {
   $StoredDate=GetAdminOnUser("TestCaseChangedDate","Checklist");
   if($StoredDate=="") return false;
   $objList=new db_handling(array('WPNum' => new MongoRegex("/^$WPNum/")), "Backup");  // do not list backup WPs
   $objList->DataBase="Checklist";
   $objList->get_cursor_find_sort(); 
   foreach ($objList->cursor as $obj) {
      if(isset($obj["Modified"])) if($obj["Modified"]>=$StoredDate) return true;
   }
   return false;
}
function MakeHTMLOfWPs() {
   $db_name="Checklist"; // Database name
   $tbl_name=$_SESSION["ProjectName"]; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);

   // Get all WP and sort them accending
   $cursor=$collection->find()->sort(array("WPNum" => 1));
   foreach($cursor as $o) {
      echo $o["WPNum"]."   ";
      AddHTMLofWPToDB($o["WPNum"],"Checklist","chk");
      UpdateHTMLColoursofSubWPs($o['WPNum'],"Checklist","chk");
   }
}
function CleanDBFromShowAndExp() {
   $db_name="Checklist"; // Database name
   $tbl_name=$_SESSION["ProjectName"]; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);

   // Get all WP and sort them accending
   $cursor=$collection->find()->sort(array("WPNum" => 1));
   foreach($cursor as $o) {
      echo $o["WPNum"]."<br>";
      foreach($o as $k=>$v) {
         if(substr($k,0,3)=="Exp") unset($o[$k]);
         if(substr($k,0,4)=="Show") unset($o[$k]);
         $collection->save($o);
      }
   }
}
?>
