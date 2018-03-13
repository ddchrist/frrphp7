<?php
include("login_headerscript.php");
include("class_lib.php");
if (isset($_GET['UpdateRole'])) {
   // explode e.g. "01.01.01_Team_dk_lasse2_ACC02_WEST"
   $arrCheckId=explode("_",$_GET['UpdateRole']);
   $TCNum=$arrCheckId[0]; // e.g. 01.01.01
   $SelectedRoleTeam=$arrCheckId[1]."_".$arrCheckId[2]."_".$arrCheckId[3]; // e.g. Team_dk_lasse2
   $t=4;
   $Role="";
   while(isset($arrCheckId[$t])) {      
      $Role.=$arrCheckId[$t++]."_";
   }
   // take out last "_" in string.
   $Role=substr($Role,0,-1);  // e.g. ACC02_WEST
   $CheckBoxChecked=$_GET['Checked'];
   //   echo $TCNum." ".$SelectedRoleTeam." ".$Role;

   // update database with individual TCNumbers or whole Sub series of numbers
   if(substr_count($TCNum, '.')==2) {
      UpdateRoleInRoleTeam($TCNum,$SelectedRoleTeam,$Role,$CheckBoxChecked); 
   }
   else {
      $arrTCNum=array();
      GetArrayOfSubTCNumbers($arrTCNum, $TCNum);
      foreach($arrTCNum as $TC) {
         UpdateRoleInRoleTeam($TC,$SelectedRoleTeam,$Role,$CheckBoxChecked);
         echo $TC."#";  // send back values of TCs to be checked/unchecked by javascript
      }
   }
}
function GetArrayOfSubTCNumbers(&$arrTCNum, $TCNum) {
   // Get TCNum and any sub TCNumber and return them in arrTCNum
   $obj=new db_handling(array('WPNum' => new MongoRegex("/^$TCNum/")),"Backup");
   $obj->DataBase="Checklist";
   $obj->get_cursor_find_sort();
   //$arrTCNum[]=$TCNum;  // return TCNum
   // return TCNum and Sub TCNumbers
   foreach($obj->cursor as $o) {
      $arrTCNum[]=$o["WPNum"];
   }
}
function UpdateRoleInRoleTeam($TCNum,$SelectedRoleTeam,$Role,$CheckBoxChecked) {
   // Will update DB with with selected or deselcted Role in given RoleTeam
   $obj=new db_handling(array('WPNum' => $TCNum),"Backup");
   $obj->DataBase="Checklist";
   $obj->get_cursor_findone();

   // if team already exit then get it from DB
   if(isset($obj->cursor[$SelectedRoleTeam])) $arrTeam=$obj->cursor[$SelectedRoleTeam];
   else $arrTeam=array();  // Prepare an empty team

   // Handling logic add/remove roles to team
   if($CheckBoxChecked=="no") RemoveRoleFromRoleTeam($arrTeam, $Role);
   else AddRoleToRoleTeam($arrTeam, $Role);

   // remove RoleTeam entry if nothing is in it otherwise store team
   if($arrTeam==array()) unset ($obj->cursor[$SelectedRoleTeam]); 
   else $obj->cursor[$SelectedRoleTeam]=$arrTeam;
   $obj->save_collection();
   if($obj->GetLastError()<>NULL) echo "error";  // indicate that something went wrong in Database
//   print_r($arrTeam);
//   echo "check:".$CheckBoxChecked;
}

function RemoveRoleFromRoleTeam(&$arrTeam, $Role) {
   $arrTeam=array_diff($arrTeam,array($Role));  // remove $Role from $Team
   $arrTeam=array_values($arrTeam);  // reindex array starting from 0
}

function AddRoleToRoleTeam(&$arrTeam, $Role) {
   $arrTeam[]=$Role;  // add role to array
}
?>
