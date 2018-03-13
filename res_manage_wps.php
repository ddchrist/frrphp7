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
<title>Manage Work Packages</title>
<?php include("class_lib.php"); ?>

<script src="scrollfix.js" type="text/javascript"></script>
</head>
<body onunload="unloadP('res_manage_wbs')" onload="loadP('res_manage_wbs')">
<?php include("res_headerline.html"); ?>

<?php
// Mark where to startup next time at login
StoreAdminData($_SESSION['username']."_InitialStartup",$_SESSION["ProjectName"]);

if (isset($_GET['CalcSumsManHours'])) {
   // If WPNum already exist then get and overwrite its _id 
   $obj=new db_handling(array("Backup" => array('$exists' => false)),"");
   $obj->get_cursor_find();
   foreach($obj->cursor as $o) {
      $obj2=new db_handling(array('WPNum' => $o['WPNum']),"Backup");
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
   header("location:res_manage_wps.php?ListWPs=1");
   exit();
}
if (isset($_POST['CreateWP'])) {
   // If WPNum already exist then get and overwrite its _id 
   $obj=new db_handling(array('WPNum' => $_POST['WPNumH1']),"Backup");
   $obj->get_cursor_findone();
   $obj->cursor['WPNum']=$_POST['WPNumH1'];
   $obj->cursor['WPName']=$_POST['WPNameH1'];
   $obj->save_collection();

   header("location:res_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['CreateFilterLink'])) {
   //echo "Link with filter settings: ";
   header("location:res_manage_wps.php?ListWPs=1&filter=".urlencode(GetAdminOnUser("res_Filter"))."&history=".GetAdminOnUser("HistoryDaysBack"));
   exit;
}

elseif (isset($_GET['ListWPs'])) {
   if(isset($_GET['filter'])) {   // filter from URL
      $_GET['Filter']=urldecode($_GET['filter']);
      StoreAdminOnUser("res_Filter",$_GET['Filter']);
   }
   elseif(isset($_GET['Filter'])) StoreAdminOnUser("res_Filter","#".$_GET['Filter']);
   if(isset($_GET['history'])) {   // filter from URL
      StoreAdminOnUser("HistoryDaysBack",trim($_GET['history']));
   }

   // Make a list of all WP numbers names, add, copy, delete
   echo '<a href="res_manage_wps.php?ExpandAll=1">Expand</a>&nbsp;&nbsp;';
   echo '<a href="res_manage_wps.php?CollapseAll=1">Collapse</a>&nbsp;&nbsp;';
   echo '<a href="res_manage_wps.php?CalcSumsManHours=1">Calculate Sums</a>&nbsp;&nbsp;';
//   echo '<a href="res_cockpit.php">Cockpit</a>&nbsp;&nbsp;';
   GetTableProperties();

   ListWPs();
   exit();
}
elseif (isset($_GET['AddWP'])) {
   // Check id user has rights to the given access
   if(!UserRights($_GET['AddWP'])) {
      header("location:res_manage_wps.php?ListWPs=1");  
      exit();
   }

   ListWPs("",$_GET['AddWP']);  // List WPs until and including where to add
?>
   <hr>
   <form action="res_manage_wps.php" method="post">
   Super Header:
   <input type="text" name="WPNum" size="14" value="<?php echo $_GET['AddWP'];?>" readonly="readonly"/>
   <?php
   $obj=new db_handling(array('WPNum' => $_GET['AddWP']),"Backup");
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
   <input type="submit" name="AddWP2" value="Create Sub WP"/>
   </form>
   <hr>
<?php
   ListWPs($_GET['AddWP'],"");
   exit();
}
elseif (isset($_POST['AddWP2'])) {
?>
   <form action="res_manage_wps.php" method="get">
   Super Header:<input type="text" name="WPNum" size="14" value="<?php echo $_POST['WPNum'];?>" readonly="readonly"/>
   <br>New Sub Header Number:<input type="text" name="CreateSubWP" size="14" value="<?php echo $_POST['WPNum'].'.'.trim($_POST['WPNumSub']);?>" readonly="readonly"/>
   <br>Sub Header Name:<input type="text" name="WPNameSub" size="14" value="<?php echo $_POST['WPNameSub'];?>" readonly="readonly"/>
   <br><input type="submit" name="CreateSub" value="Confirm Create Sub WP"/>
   <input type="submit" name="CreateSub" value="Cancel"/>
   </form>
<?php
   // If Work Package already exist then sanity check
   $obj=new db_handling(array('WPNum' => $_POST['WPNum'].".".$_POST['WPNumSub']),"");
   $obj->get_cursor_findone();
   if(isset($obj->cursor["WPNum"])) echo '<font color="red">WP already exist - please confirm</font>';
   else {
      $WPNum=$_POST['WPNum'].".".trim($_POST['WPNumSub']);
      $Name=$_POST['WPNameSub'];
      header("location:res_manage_wps.php?CreateSubWP=$WPNum&WPNameSub=$Name");
   }
   exit;
}
elseif (isset($_GET['CreateSubWP'])) {
   if($_GET['CreateSub']<>"Cancel") {
     if(!ValidFullWPNum($_GET['CreateSubWP'])) {
        echo 'Invalid WP number!: "'.$_GET['CreateSubWP'].'"';
        exit;
     }
     $WPNum=$_GET['CreateSubWP'];


     // Check id user has rights to the given access
     if(!UserRights($WPNum)) {
        echo "You have no access rights to WP#: ".$WPNum;  
        exit;
     }
     $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
     $obj->get_cursor_findone();
     $obj->cursor['WPNum']=$WPNum;
     $obj->cursor['WPName']=$_GET['WPNameSub'];
     $obj->save_collection();
     AddHTMLofWPToDB($WPNum);
     UpdateHTMLColoursofSubWPs($WPNum);
//     MarkExpandCollapseHeadings();
//     MarkDocumentsToBeListed();
   }
   header("location:res_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['DelWP'])) {   
   $DelWP=$_GET['DelWP'];
   // Check id user has rights to the given access
   if(!UserRights($DelWP)) {
      header("location:res_manage_wps.php?ListWPs=1");  
      exit();
   }
   $obj=new db_handling(array('WPNum' => new MongoRegex("/^$DelWP/")),"Backup");  // delete starting with
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
   <form action="res_manage_wps.php" method="post">
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
      $obj->remove();
   }
   $WPLevels=explode(".",$DelWP);  // find sup/sub levels of WPNum
   FindMinMaxOfSubWPs($WPLevels[0]);
   CleanUserViewListFromGhostWPNums();
   header("location:res_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['CopyWP'])) {
   ListWPs("",$_GET['CopyWP']);
?> <hr>
   <form action="res_manage_wps.php" method="post">
   Copy
   <input type="text" name="WPNum" size="10" value="<?php echo $_GET['CopyWP'];?>" readonly="readonly"/>
   to
   <input type="text" name="CloneWPNum" size="10" value="<?php echo $_GET['CopyWP'];?>"/>
   <input type="checkbox" name="InclSubWPs" value="1" checked="checked"> Include sub WPs
   <input type="checkbox" name="DelDates" value="1" checked="checked"> Delete Dates
   <input type="checkbox" name="DelAllRes" value="1" checked="checked"> Delete Allocated Resources
   <br>
   <span title="If a New Start Date is given, the WP and sub WPs are copied and dates are moved as the distance between Start Date and New Start Date. If 'Delete Dates' is checked, it is overruled"><p style="color:#C00000">New Start Date (YYYY-MM-DD):
   <input type="text" name="NewStartDate" size="10" value="">
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
   if(isset($_POST['DelDates'])) $deldates="checked";
   else $deldates="";
   if(isset($_POST['DelAllRes'])) $delAllRes="checked";
   else $delAllRes="";
?> 
   <form action="res_manage_wps.php" method="post">
   Copy
   <input type="text" name="WPNum" size="10" value="<?php echo $WPNumFrom;?>" readonly="readonly"/>
   to
   <input type="text" name="CloneWPNum" size="10" value="<?php echo $WPNumTo;?>" readonly="readonly"/>
   <input type="checkbox" name="InclSubWPs" value="1" <?php echo $inclsub;?>> Include sub WPs
   <input type="checkbox" name="DelDates" value="1" <?php echo $deldates;?>> Delete Dates
   <input type="checkbox" name="DelAllRes" value="1" <?php echo $delAllRes;?>> Delete Allocated Resources
   <br>New Start Date<input type="text" name="NewStartDate" size="10" value="<?php echo $_POST['NewStartDate'];?>" readonly="readonly"/>
   <input type="submit" name="CopyWP3" value="Confirm Copy">
   <input type="submit" name="CopyWP3" value="Cancel">
   </form>
   <b>Please confirm the following</b>
<?php
   if(isset($_POST['InclSubWPs'])) $query=array('WPNum' => new MongoRegex("/^$WPNumFrom/"));
   else $query=array('WPNum' => $WPNumFrom);
   $oList=new db_handling($query,"");
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

   // Check if user has rights to the given access
   if(!UserRights($WPNumTo)) {
      echo "You have no user rights to copy into WP: ".$WPNumTo; 
      exit;
   }
   
   // Find in Unix time how much the WP is moved in time
   if(trim($_POST['NewStartDate'])<>"") {
      $DiffUnixTime=GetDifferenceStartAndNewStartDate($WPNumFrom,$_POST['NewStartDate']);
   }

   if(isset($_POST['InclSubWPs'])) $query=array('WPNum' => new MongoRegex("/^$WPNumFrom/"));
   else $query=array('WPNum' => $WPNumFrom);
   // Find requested WP and all SubWPs (if requested) e.g. '05' and '05.10', '05.20' etc..
   // Foreach WP change WPNum to copy-to number
   //     Check if number exist already and if it does, get '_id' so it can be overwritten
   //     else unset '_id'
   // Save document/WP
   $oList=new db_handling($query,"");
   $oList->get_cursor_find();
   foreach($oList->cursor as $obj) {
      // Change WPNum e.g. if copy WP '05.10' to WP '09' it will be '09' 
      // if change from '05.10.05' to '09.10' it will be '09.10'
      $LengthFrom=strlen($WPNumFrom);
      $WPNum=$WPNumTo.substr($obj['WPNum'],$LengthFrom);
      $obj['WPNum']=$WPNum;
      // Get WP if it exist
      $oWP=new db_handling(array('WPNum' => $WPNum),"");
      $oWP->get_cursor_findone();
      // If a WP exist i.e. has an '_id' then move this ID into the object and save it
      // otherwise remove ID from copy-from and save to create a new document.
      if(isset($oWP->cursor['_id'])) $obj['_id']=$oWP->cursor['_id'];
      else unset($obj['_id']);  // unset so Mongo will create a new ID i.e. copy
         
      // changes to new start date if requested
      if(trim($_POST['NewStartDate'])<>"") {
         if(isset($obj['StartDate'])) $obj['StartDate']=AddDateAndUnixTime($obj['StartDate'], $DiffUnixTime); 
         if(isset($obj['EndDate'])) $obj['EndDate']=AddDateAndUnixTime($obj['EndDate'], $DiffUnixTime); 
      }
      // Do not copy dates - so it can be seen that would have to be set again
      elseif(isset($_POST['DelDates'])) {
         if(isset($obj['StartDate'])) unset($obj['StartDate']);
         if(isset($obj['EndDate'])) unset($obj['EndDate']);
      }
      // Delete Allocated Resources if requested
      if(isset($_POST['DelAllRes']) && isset($obj["Ressources"])) {
         $arrRes=$obj["Ressources"];
         $t=0;
         foreach($arrRes as $res) {
            $arrRes[$t]["AllRes"]="";
            $t++;
         }
         $obj["Ressources"]=$arrRes;
      }
      // Delete Subjects Accepted using link submission
      if(isset($obj["SubjAccepted"])) unset ($obj["SubjAccepted"]);
      
      $oList->save_collection($obj);
      AddHTMLofWPToDB($obj['WPNum']);
      AlignKeyManHoursWithResources($obj['WPNum']);
   }
   UpdateHTMLColoursofSubWPs($WPNumTo);
   $WPLevels=explode(".",$WPNumTo);  // find sup/sub levels of WPNum
   FindMinMaxOfSubWPs($WPLevels[0]);
   cancel:
   FixUserViewListToSeeCopyTo($WPNumTo);
   CleanUserViewListFromGhostWPNums();
   header("location:res_manage_wps.php?ListWPs=1");  
   exit();
}
elseif (isset($_GET['ExpColWP'])) {   // Expand/Collapse Work Package
   $WPNum=$_GET['ExpColWP'];
   $WPLength=strlen($WPNum);
   $arrUserViewList=unserialize(GetAdminOnUser("UserViewList"));
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
   StoreAdminOnUser("UserViewList",serialize($arrUserViewList));

   header("location:res_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['ExpandAll'])) {   // Expand all Work Packages
   $db_name="Projects"; // Database name
   $tbl_name=$_SESSION["ProjectName"]; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);

   // Get all WP and remove 'Show' and 'Exp'
   $cursor=$collection->find();
   $arrUserViewList=array();
   foreach($cursor as $o) {
      $WPNum=$o['WPNum'];
      $cur=$collection->findone(array('WPNum' => new MongoRegex("/^$WPNum\.[^.]*$/")));
      if($cur<>NULL) $arrUserViewList[$WPNum]="-";
   }
   StoreAdminOnUser("UserViewList",serialize($arrUserViewList));
   header("location:res_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['CollapseAll'])) {   // Collapse all Work Packages
// **************

   $query=array('WPNum' => new MongoRegex("/^[^.]*$/")); // match only H1 level
   $obj=new db_handling($query,"");
   $obj->get_cursor_find_sort();
   foreach($obj->cursor as $o) {
      $arrUserViewList[$o["WPNum"]]="+";  
   }
   StoreAdminOnUser("UserViewList",serialize($arrUserViewList));

// **************
/*   $db_name="Projects"; // Database name
   $tbl_name=$_SESSION["ProjectName"]; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);

   // Get all WP and remove 'Show' and 'Exp'
   $cursor=$collection->find();
   $Num="";
   foreach($cursor as $o) {
      $WPNum=$o['WPNum'];
      $cur=$collection->findone(array('WPNum'=>$WPNum));
      if(!strpos($cur['WPNum'],".")) {
         $Num=$cur['WPNum'];
         $cur["Exp_".$_SESSION['username']]="+";
      }
      else {
         if(substr($cur['WPNum'],strlen($Num)-1) == $Num) {
            $cur["Show_".$_SESSION['username']]="0";
         }
      }
      $collection->save($cur);
   }   
   MarkExpandCollapseHeadings();
   MarkDocumentsToBeListed();   */
   header("location:res_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['Backup'])) {
   // backup from indicated heading level and down
   if(isset($_SESSION["BackupLevel"])) $BackupLevel=$_SESSION["BackupLevel"];
   else $BackupLevel="";
?>
   <form action="res_manage_wps.php" method="post">
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
   if(!UserRights($BackupLevel)) {
      echo "No user rights to backup!<br>";
      exit();
   }
   $oList=new db_handling(array('WPNum' => new MongoRegex("/^$BackupLevel/")),"Backup"); // search starting with WPNum
   $oList->get_cursor_find();
   $User=$_SESSION['username'];
   
   foreach($oList->cursor as $obj) {
//print_r($obj);
//echo "<br>*****<br>";
      $oBack=new db_handling(array('Backup' => "$User", 'WPNum' => $obj['WPNum']),"");
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
exit;
   header("location:res_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['Restore'])) {
   // backup from indicated heading level and down
   if(isset($_SESSION["BackupLevel"])) $BackupLevel=$_SESSION["BackupLevel"];
   else $BackupLevel="";
?>
   <form action="res_manage_wps.php" method="post">
   Indictate Restore level e.g. "02.10" means "02.10" and all sub levels e.g. 02.10.10<br>
   <input type="text" name="RestoreLevel" size="10" value="<?php echo $BackupLevel;?>"
   <br><input type="submit" name="Restore2" value="Submit"/>
   </form>   
<?php   
   exit();
}
elseif (isset($_POST['Restore2'])) {
   $BackupLevel=$_POST['RestoreLevel'];
//   $_SESSION["BackupLevel"]=$BackupLevel;
   $User=$_SESSION['username'];
   $oList=new db_handling(array('Backup' => "$User", 'WPNum' => new MongoRegex("/^$BackupLevel/")),""); // search starting with WPNum
   $oList->get_cursor_find();
   
   foreach($oList->cursor as $obj) {
      echo $obj['WPNum']." ";
      echo '<a href="res_manage_wps.php?Restore3WP='.$obj['WPNum'].'">Restore</a><br>';
   }
   exit();
}
elseif (isset($_GET['Restore3WP'])) {
   // Builds up a new cursor based on old cursor from backup and inserting
   // present _id and removing key "Backup" from backup
   $objPresent=new db_handling(array('WPNum' => $_GET['Restore3WP']),"Backup");
   $objPresent->get_cursor_findone();
   $User=$_SESSION['username'];
   $objOld=new db_handling(array('WPNum' => $_GET['Restore3WP'], 'Backup' => "$User"),"");
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
   header("location:res_manage_wps.php?ListWPs=1");
   exit();
}
elseif (isset($_GET['Rename'])) {
   // Rename heading name of WP i.e. WPName
   $WPNum=$_GET["Rename"]; 
   // Check id user has rights to the given access
   if(!UserRights($WPNum)) {
      header("location:res_manage_wps.php?ListWPs=1");  
      exit();
   }

   $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
   $obj->get_cursor_findone();
   if(isset($obj->cursor['Link'])) $Link=$obj->cursor['Link'];
   else $Link="";
   if(isset($obj->cursor['LinkText'])) $LinkText=$obj->cursor['LinkText'];
   else $LinkText="";
   ?>
   <form action="res_manage_wps.php" method="post">
   Present header name of work package number
   <input type="text" name="WPNum" size="10" value="<?php echo $WPNum;?>" readonly=readonly>
   is '<b><?php echo $obj->cursor['WPName'];  ?></b>'<br><br>
   <table>
   <tr>
   <td>New Header Name:</td>
   <td><input type="text" name="WPName" size="55" value="<?php echo $obj->cursor['WPName'];?>"></td>
   </tr>
   <tr>
   <td><a href="<?php echo $Link;?>">Link:</a></td>
   <td><input type="text" name="Link" size="55" value="<?php echo $Link;?>"></td>
   </tr>
   <tr>
   <td>Link hover text:</a></td>
   <td><input type="text" name="LinkText" size="55" value="<?php echo $LinkText;?>"></td>
   </tr>
   </table>
   <br><input type="submit" name="Rename2" value="Submit"/>
   </form>   
<?php   
   exit();
}
elseif (isset($_POST['Rename2'])) {
   $obj=new db_handling(array('WPNum' => $_POST['WPNum']),"Backup");
   $obj->get_cursor_findone();
   $obj->cursor['WPName']=$_POST['WPName'];
   $obj->cursor['Link']=$_POST['Link'];
   $obj->cursor['LinkText']=$_POST['LinkText'];
   $obj->save_collection();
   AddHTMLofWPToDB($_POST['WPNum']);
   header("location:res_manage_wps.php?ListWPs=1");
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
//   header("location:res_manage_wps.php?ListWPs=1");
//   exit();
//}

elseif(isset($_POST['ChangeCalender'])) {
//   StoreAdminOnUser("res_Filter",str_replace("&","&amp",$_POST['res_Filter']));
   if(trim($_POST['res_Filter'])=="#links") {
      echo "<b>Warning:</b> '#links' can not be on its own use e.g. '#vv&#links' !";
      exit;
   }
   StoreAdminOnUser("res_Filter",$_POST['res_Filter']);
   StoreAdminOnUser("CalenderStartDate",$_POST['StartDate']);
   StoreAdminOnUser("CalenderView",$_POST['Calender']);
   StoreAdminOnUser("CalenderColumns",$_POST['Columns']);
   StoreAdminOnUser("HistoryDaysBack",trim($_POST['HistoryDaysBack']));   
   header("location:res_manage_wps.php?ListWPs=1");
   exit;
}
elseif(isset($_GET['ListTags']))  {
   $obj=new db_handling(array(),"");
   $obj->DataBase="Projects";
   // $obj->CollectionName="SAPResources"; Default COOPANS
   $obj->get_cursor_find();
   $arrTags=array();
   foreach($obj->cursor as $o) {
      if(substr($o['WPNum'],0,2)=="99") continue;  // take out holidays
      if(isset($o['Tags'])) {
         if(trim($o['Tags'])=="") continue;
         $tag=explode(" ",trim($o['Tags']));
         foreach($tag as $t) $arrTags[]=$t;
      }
   }
   $arrTags[]="holidays"; // add holidays
   $arrTags=array_unique($arrTags);
   natcasesort($arrTags);
   // print_r($arrTags);
   $arrTagInfo=GetAdminData("res_TagInfo");
   echo '<form action="res_manage_wps.php" method="post">';
   echo '<table border="1">';
   echo '<tr><th>Links</th><th>Info</th></tr>';
   foreach($arrTags as $Tag) {
      echo "<tr>";
      echo '<td><a href="res_manage_wps?ListWPs=1&Filter='.$Tag.'">'."#".$Tag.'</a></td>';
      if(isset($arrTagInfo[$Tag])) {
         echo '<td><input type="text" name="Tag[]" size="60" value="'.$arrTagInfo[$Tag].'"/></td>';
         $arrTagInfo2[$Tag]=$arrTagInfo[$Tag];
      }
      else {
         echo '<td><input type="text" name="Tag[]" size="60" value=""/></td>';
         $arrTagInfo[$Tag]=""; 
         $arrTagInfo2[$Tag]=$arrTagInfo[$Tag]; 
      }
      echo "</tr>";
   }
   echo '</table>';
   echo 'You can update information on tags by change info above and click UpdateTags.<br>';
   echo '<input type="submit" name="UpdateTags" value="UpdateTags"/>';
   echo '</form>';
   StoreAdminData("res_TagInfo",$arrTagInfo2);
   exit;
}
elseif(isset($_POST['UpdateTags'])) {
   //print_r($_POST['Tag']);
   //echo "<br>";
   $arrTags=array();
   foreach($_POST['Tag'] as $Tag) $arrTags[]=$Tag;
   $arrTagInfo=GetAdminData("res_TagInfo");
   $t=0;
   foreach($arrTagInfo as $k=>$v) {
       echo $k."=<b>".$arrTags[$t]."</b><br>";
       $arrTagInfo[$k]=$arrTags[$t++];
   }
   StoreAdminData("res_TagInfo",$arrTagInfo);
exit;
}



echo "<br>Project Name:".$_SESSION["ProjectName"]."<br";
?>
<br><br><br>
A workpackage is numbered using different heading levels. It is possible to work on three levels. The convention is a workpackage number of the form H1.H2.H3 symbolising heading on three levels. Ex. 10.12.03 is a three level workpackage.
<br><br>
<form action="res_manage_wps.php" method="post">
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
function POST_URL($url,$fields) {
  //set POST variables
   $url = 'http://localhost/res_manage_wps.php';
  // $fields = array(
  //                      'lname'=>$last_name,
  //                      'fname'=>$first_name,
  //                      'email'=>$email
  //                );
print_r($url);
echo "<br><br>";
  foreach($fields as $key=>$val) {
     $fields[$key]=urlencode($val);
echo "<br>".$key." ".$val." ".urlencode($val);
  }
print_r($fields);
exit;
  //url-ify the data for the POST
  $fields_string="";
  foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
  rtrim($fields_string,'&');

  //open connection
  $ch = curl_init();

  //set the url, number of POST vars, POST data
  curl_setopt($ch,CURLOPT_URL, $url);
  curl_setopt($ch,CURLOPT_POST, count($fields));
  curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

  //execute post
  $result = curl_exec($ch);

  //close connection
  curl_close($ch);

}



function GetDifferenceStartAndNewStartDate($WPNumFrom,$NewStartDate) {
   if(!ValidDateFormat($NewStartDate)) {
      echo "Incorrect New Start Date!";
      exit;
   }
   $obj=new db_handling(array('WPNum' => $WPNumFrom),"Backup");
   $obj->get_cursor_findone();
   if(isset($obj->cursor['StartDate'])) {
      if(!ValidDateFormat($obj->cursor['StartDate'])) {
         echo "WP#: ".$obj->cursor['WPNum']."<br>Date:".$obj->cursor['StartDate']."<br>Error: Incorrect Start Date! Date must be set and in format YYYY-MM-DD";
         exit;
      }
      return(DifferenceDates($obj->cursor['StartDate'], $NewStartDate));
   }
   else {
      echo $obj->cursor['WPNum'].": Start Date is not set!";
      exit;
   }
}

function right($value, $count){
    return substr($value, ($count*-1));
}

function left($string, $count){
    return substr($string, 0, $count);
}

/* function MarkExpandCollapseHeadings() {
      // if a sub WP/heading exist e.g. 
      // 10       Main heading
      // 10.10    Sub heading to 10
      // 10.10.01 Sub heading to both 10 and 10.10
      // then mark it (heading level above) in the collection on key "Exp" as ['Exp']="+" if
      // if it is collapsed and ['Exp']="-" if exploded. The rest of the record,
      // the keys must be deleted
// Algorithm:
// 10 if there is a document get next document called A otherwise quit algorithm
// 20 Does Exp from A contain "+" or "-" ?
// 30   if "yes" goto 10
// 40   if "no" goto 50
// 50 Is there a next document ?
// 60   if "no" goto 80
// 70   if "yes" goto 100
// 80 set Exp="" of document A
// 90 quit algorithm   
//100 get next document called B
//110 Is WP number of A in start of B (length A)
//120   if "yes" goto 140
//130   if "no" goto 160
//140 set Exp="-" of document A
//150 goto 170
//160 set Exp="" of document A
//170 A=B
//180 goto 20

   $db_name="Projects"; // Database name
   $tbl_name=$_SESSION["ProjectName"]; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);

   // Get all WP and sort them accending
   $cursor=$collection->find()->sort(array("WPNum" => 1));
 
   while( $cursor->hasNext()) {
      $obj=$cursor->getNext();  // A
LoopWithoutNewWP:
      $WPNum=$obj['WPNum'];   
      if(isset($obj["Exp_".$_SESSION['username']])) $Exp=$obj["Exp_".$_SESSION['username']];
      else $Exp="NoExp";
      if($Exp!="+" && $Exp!="-") {
         if($cursor->hasNext()) {
            $obj2=$cursor->getNext();  // B
            if(left($obj2['WPNum'],strlen($WPNum))==$WPNum) {
               $obj["Exp_".$_SESSION['username']]="-";
               $collection->save($obj);
            }
            else {
               $obj["Exp_".$_SESSION['username']]=""; // clear marking
               $collection->save($obj);
            }
            $obj=$obj2;
            goto LoopWithoutNewWP;
         }
         else {
            $obj["Exp_".$_SESSION['username']]=""; // clear marking
            $collection->save($obj);
            return;  // exit function prematurely
         }
      }
   }
} */

/*function MarkDocumentsToBeListed() {
// This function must be excuted after MarkExpandCollapseHeadings()
// It will make sure that WP/Heading numbers that have been
// collapsed, will be marked by key Show="0" and the rest will be "1".
// The any other list routine just have to sort out documents containing
// Show="1" the find what should be listed.
// If a change is done in expading or collapsing, the function must be called
// If a new WP/header has been added/deleted MarkExpandCollapseHeadings()
// must be called before this function.

// The function runs through all sorted documents and if one is collapsed
// it will make sure that all documents below that level are nor shown or
// in relaity marked as Show=0.

// Algorithm:
// 10 if there is a document get next document called A otherwise quit algorithm
// 15 set Show="1" of A
// 20 is Exp of A = "+"
// 30    if yes goto 70
// 40    if no goto 10
// 70 (Exp is "+") set Show="1" of A
// 80 if there is a document get next document called B otherwise quit algorithm
// 90 Is WP number of A in start of B (length A)
//100   if "yes" goto 120
//110   if "no" goto 140
//120 set Show="1" of B
//130 goto 80
//140 A=B
//150 goto 20

   $db_name="Projects"; // Database name
   $tbl_name=$_SESSION["ProjectName"]; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);

   // Get all WP and sort them accending
   $cursor=$collection->find()->sort(array("WPNum" => 1));
 
   while( $cursor->hasNext() ) {
      $obj=$cursor->getNext();  // A
LoopWithoutNewWP:
      $WPNum=$obj['WPNum'];   
      if(isset($obj["Exp_".$_SESSION['username']])) $Exp=$obj["Exp_".$_SESSION['username']];
      else $Exp="NoExp";
      $obj["Show_".$_SESSION['username']]="1";
      $collection->save($obj);
      if($Exp=="+") {   // Alternatively Exp is "-" or ""
         if($cursor->hasNext()) {
            $obj2=$cursor->getNext();  // B
            if(left($obj2['WPNum'],strlen($WPNum))==$WPNum) {
               $obj2["Show_".$_SESSION['username']]="0";
               $collection->save($obj2);
            }
            else $obj=$obj2;
            goto LoopWithoutNewWP;
         }
         else return;  // exit function prematurely
      }
   }
} */
function CleanDBFromShowAndExp() {
   $db_name="Projects"; // Database name
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


function SumOfKeys($WPNum, $Key) {
// Returns sum og level and all sub levels for given $key
   $obj=new db_handling(array('WPNum' => new MongoRegex("/^$WPNum/")),"Backup");  
   return $obj->sum_of_keys($Key);
}
function ListWPs($WPNumStart="", $WPNumEnd="") {
   $arrFilter=GetAdminOnUser("res_Filter");
   $GenSubjAcceptLinks=false;
   $_SESSION["AcceptRejectQuery"]="";
   
   if($arrFilter=="###") {
      $arrUserViewList=unserialize(GetAdminOnUser("UserViewList"));
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
      if(!preg_match("/[^.0-9]/i", $filter)) {
         $query[]=array('WPNum' => new MongoRegex("/^$filter/"));
      }
      elseif(preg_match("/#[0-9]{4}/", $filter)) {  // before preg_match("/#[0-9]{4}-/", $filter)
         $filter=substr($filter,1);  // take out '#'
         $DateEndAppend="9999-99-99";
         $DateEndAppend=$filter.substr($DateEndAppend,strlen($filter));
         
         $DateStartAppend="0000-00-00";
         $DateStartAppend=$filter.substr($DateStartAppend,strlen($filter));
         
         $query[]=array('$or' => array(array('StartDate' => array('$gte' => $DateStartAppend, '$lte' => $DateEndAppend)),
                                 array('EndDate' => array('$gte' => $DateStartAppend, '$lte' => $DateEndAppend)) ));
                                                    
      }
      elseif(substr($filter,0,1)=="#") {
         if($filter=="#links") $GenSubjAcceptLinks=true; // accept/reject links in listing
         else {
            $filter=substr($filter,1);
            $query[]=array('Tags' => new MongoRegex("/$filter/i"));
         }
      }      
      else {
         $query[]=array('WPName' => new MongoRegex("/$filter/i"));
      }
   }

   if($And) $query=array('$and'=>$query);
   else $query=array('$or'=>$query);

//print_r($query);


   // override query and make sure expand/collapse work in case no filter
   if($filter=="") {  
      $query=array();
      $arrUserViewList=unserialize(GetAdminOnUser("UserViewList"));
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
   
   // Manage history setting
   $HistoryDaysBack=GetAdminOnUser("HistoryDaysBack");
   if($HistoryDaysBack=="") {
      $HistoryDaysBack="0";  // make history look back default to 0 days
      StoreAdminOnUser("HistoryDaysBack",$HistoryDaysBack); 
   }
   
   //$Today=date("Y-m-d");
   $Today=date("Y-m-d",(strtotime('now -'.$HistoryDaysBack.' days')));
   
   $arr=array('EndDate' => array('$gte' => $Today));
   $arr2=array('StartDate'=>"");
   $arr3=array("StartDate" => array('$exists' => false));
   $arr=array('$or'=>array($arr,$arr2,$arr3));
   $query=array('$and'=>array($query,$arr));

   $objList=new db_handling($query, "");
   $objList->get_cursor_find_sort();  
   $oCalender=new CalenderHandling($objList);
   echo '<table border="0" cellpadding="0" cellspacing="0">';  
//   echo '<table cellpadding="0" cellspacing="0">';
?>
<tr>
<th></th><th align="left">WP#&nbsp;</th><th align="left">Name&nbsp;</th><th>add&nbsp;</th><th align="left">del&nbsp;</th><th align="left">cop&nbsp;</th><th align="left">Start Date&nbsp;</th><th align="left">End Date&nbsp;</th><th>MH&nbsp;</th><th>MD</th>
<?php
if($GenSubjAcceptLinks) echo "<th>Accept/Reject</th>";  // heading for accept/reject links

?>
</tr>
<?php
   $Superhtml="";
   foreach ($objList->cursor as $obj) {
      $html="";
      if($obj['WPNum'] <= $WPNumStart) continue;      

      // increase visibility of header0 and header1
      $Visual=substr_count($arrFilter[0],"."); // if filter is on lower levels then move visibility down as well
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
           $obj2->get_cursor_findone();
           if($obj2->cursor <> NULL) $html.= $obj['HTML_ExpPlus'];
         }
      }
      $html.= '</td>';

      $html.= '<td>';
      $html.= $obj['HTML_WPNum'];
      $html.= '</td>';

      $html.= '<td>';
      $html.= $obj['HTML_Name'];
      if(isset($obj['Link']) && $obj['Link']<>"") {
         $Link=$obj['Link'];
//         $html.= '<span title="'.$obj['LinkText'].'"><a style="color:#C00000;" href="'.$Link.'" target="_blank">&#8594;</a></span>';
         $html.= '<span title="'.$obj['LinkText'].'"><a style="color:#C00000;" href="'.$Link.'" target="_blank"><img src="attachment.gif" alt="attachment"></a></span>';




      }  
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

      $html.= '<td>';
      $html.= $obj['HTML_StartDate'];
      $html.= '</td>';

      $html.= '<td>';
      $html.= $obj['HTML_EndDate'];
      $html.= '</td>';

      $html.= '<td align="right">';
      $ManHours=SumOfKeys($obj["WPNum"],"ManHours");
      $html.= number_format($ManHours, 0, '.', '')."&nbsp;&nbsp;";
      $html.= '</td>';
      $html.= '<td align="right">';
      $html.= number_format($ManHours/7.4, 1, '.', '').' ';  // ManDays
      $html.= '</td>';

//################      
      // http://localhost/res_register_wps.php?wp=24.05.81&projectname=COOPANS&tag=vv
      if($GenSubjAcceptLinks) $html.='<td><a href="res_register_wps.php?&wp='.$obj["WPNum"].'&projectname='.$_SESSION["ProjectName"].'&tag='.urlencode(serialize($query)).'">Accept/Reject</a></td>';
      
      // disconnect view of calender when copy/delete/add WPs
      if($WPNumStart=="" && $WPNumEnd=="") $html.= $oCalender->GetHtmlOfCalender($obj);
      $html.= '</tr>';
      
      // History: make WPs older than date of today gray
      $TextCol="#000000";
      if(isset($obj['EndDate'])) if($obj['EndDate']<date('Y-m-d')) $TextCol="#777777";
      else {
         if(isset($obj['HTML_TextCol'])) $TextCol=$obj['HTML_TextCol']; // just get whatever colour selected
      }
      $HighCol=$obj['HTML_HighCol'];
      $html=str_replace('#TxCol#',$TextCol,$html);  // substitute text colour tag
      $html=str_replace('#HiCol#',$HighCol,$html);  // substitute Highlight colour tag

      if($WPNumEnd==$obj['WPNum']) break;   // exit if WPNum match funtion criteria     
      $Superhtml.=$html;
   }
   $Superhtml.= '</table>';
   echo $Superhtml;
}
function GetTableProperties() {
      $Filter=GetAdminOnUser("res_Filter");
      $Filter=str_replace("&","&amp;",$Filter); // make sure & can be used in html
?>
      <form action="res_manage_wps.php" method="post">
      <span title="Click on Filter to create a 'filter link'.
Use e.g. 01&02&03.01 to filter view
If input start with '&amp;', then logical 'and' is used on all inputs otherwise 'or' is used. E.g:
&amp;#2013-06&amp;#vv   look for all tagged vv sessions in June 2013.
If '#' is skipped the WP name is searched e.g:
checklist&shadow    list all WPs with 'checklist' or 'shadow' in WP name.
NB! Hit link 'tags' to get a list of all used tags in work packages."><a style="color:#C00000;" href="res_manage_wps.php?CreateFilterLink">Filter:</a></span>
      <input type="text" name="res_Filter" size="8" value="<?php echo $Filter;?>" />
      <a href="res_manage_wps.php?ListTags">tags</a> 
      Start Date:
      <input type="text" name="StartDate" size="6" value="<?php echo GetAdminOnUser("CalenderStartDate");?>" />

<?php
      $c1=$c2=$c3="";
      $CalView=GetAdminOnUser("CalenderView");
      if($CalView=="month") $c1="checked";
      elseif($CalView=="week") $c2="checked";
      elseif($CalView=="day") $c3="checked"; 
?>
      Columns:
      <input type="text" name="Columns" size="2" value="<?php echo GetAdminOnUser("CalenderColumns");?>" />
      <input type="radio" name="Calender" value="month" <?php echo $c1 ?>/>Months
      <input type="radio" name="Calender" value="week" <?php echo $c2 ?>/>Weeks
      <input type="radio" name="Calender" value="day" <?php echo $c3 ?>/>Days
      <span style="color:#C00000;" title="Number of days to look back when listing work packages.
E.g. '10' will show WPs up to 10 days ago in the WP list.
'10000' = 10000 days back.
'-30' = 30 days forward (notice minus).">History:</span>
      <input type="text" name="HistoryDaysBack" size="5" value="<?php echo GetAdminOnUser("HistoryDaysBack");?>"/>
      <input type="submit" name="ChangeCalender" value="Submit"/>
      </form>
<?php
}

function MakeHTMLOfWPs() {
   $db_name="Projects"; // Database name
   $tbl_name=$_SESSION["ProjectName"]; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);

   // Get all WP and sort them accending
   $cursor=$collection->find()->sort(array("WPNum" => 1));
   foreach($cursor as $o) {
      echo $o["WPNum"]." ";
      AddHTMLofWPToDB($o["WPNum"]);
      UpdateHTMLColoursofSubWPs($o['WPNum']);
   }
}

function FixUserViewListToSeeCopyTo($WPNumTo) {
   // see if sub WP exist
   $query=array('WPNum' => new MongoRegex("/^$WPNumTo\.[^.]*$/"));
   $obj=new db_handling($query,"");
   $obj->get_cursor_findone();
   
   $arrUserViewList=unserialize(GetAdminOnUser("UserViewList"));
   if(isset($obj->cursor["WPNum"])) $arrUserViewList[$WPNumTo]="-";
   $arr=explode(".",$WPNumTo);
   $WP="";
   for($t=0;$t<count($arr)-1;$t++) {
      $WP.=$arr[$t];
      $arrUserViewList[$WP]="-";
      CreateWPIfItDoesNotExist($WP);
      $WP.=".";
   }
   StoreAdminOnUser("UserViewList",serialize($arrUserViewList));
}
function CreateWPIfItDoesNotExist($WP) {
   $query=array('WPNum' => $WP);
   $obj=new db_handling($query,"");
   $obj->get_cursor_findone();
   if(isset($obj->cursor["WPNum"])) return;  // WP exist!
   else {
      $obj->cursor["WPNum"]=$WP;
      $obj->cursor["WPName"]="### NO NAME ###";
      $obj->save_collection();
      AddHTMLofWPToDB($WP);
      UpdateHTMLColoursofSubWPs($WP);
   }
}
function CleanUserViewListFromGhostWPNums() {
   $arrUserViewList=unserialize(GetAdminOnUser("UserViewList"));
   foreach ($arrUserViewList as $WP=>$v) {
      $query=array('WPNum' => "$WP");
      $obj=new db_handling($query,"");
      $obj->get_cursor_findone();
      if(!isset($obj->cursor["WPNum"])) unset($arrUserViewList[$WP]);
   }
   StoreAdminOnUser("UserViewList",serialize($arrUserViewList));
}
?>
