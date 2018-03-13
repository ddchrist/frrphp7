<?php include("login_headerscript.php"); ?>
<?php include("class_lib.php"); ?>
<?php include("admin_functions.php"); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>

<script> 
function addText(target,text) {
    document.getElementById(target).innerHTML = text;
} 
</script>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Admin Project</title>
<style type="text/css">

table {
    border-collapse: collapse;
    border: 1px solid #000;
    background-color: white;
}
table td, table th {
    border: 1px solid #000;

}

</style>
</head>
<body>
<?php include("res_headerline.html"); ?>
<?php

$debug=false;

// ********************************************************
// ************** Handling of $_GET, $_POST ***************
// ********************************************************
if (isset($_GET["CopyToLastQ"])) {
   CopyCockpitToCockpitLastQ();
   echo "Copy from Cockpit to Last Quarter Cockpit done!";
   exit;
}
elseif (isset($_GET["BackupCockpit"])) {
   BackupCockpit(1,$_GET["BackupCockpit"]);
   exit;
}
elseif (isset($_POST["BackupCockpit"])) {
   BackupCockpit(2,$_POST["CockpitSelection"]);
   echo "Backup Cockpit To This Quarter Cockpit is done!";
   exit;
}
elseif (isset($_GET["RestoreCockpit"])) {
   RestoreCockpit(1,$_GET["RestoreCockpit"]);
   exit;
}
elseif (isset($_GET["SelectedCockpit"])) {
   RestoreCockpit(2,$_GET["RestoreCockpit2"]);
   echo "Restore of <b>".GetAdminData("Cockpit_ThisQuarterName")."</b> is done!";
   exit;
}
elseif (isset($_GET["Subj"])) {
   ListRessourcesFromLink();
   exit;
}
elseif (isset($_POST["Cockpit"])) {
   if($_POST["Cockpit"]=="Calculate") {   
      PrepareCockpit();
      CalculateCockpit();
      ClearUnitSelections();
      StoreAdminData("Cockpit_ThisQuarterName","WP Based");
   }
   if($_POST["Cockpit"]=="View") PrepareCockpit();
   elseif($_POST["Cockpit"]=="ViewNUAC") PrepareCockpit(true);
}
elseif(isset($_POST["ListAllSubjects"])) ListAllSubjects();
elseif(isset($_GET["DefineMappings"])) DefineWP_COST_PSP_Mappings();
elseif(isset($_POST["Mappings"])) DefineWP_COST_PSP_Mappings2();
elseif(isset($_GET["ClearUnits"])) ClearUnitSelections();
elseif(isset($_GET["ShowFilterExamples"])) ShowFilterExamples();

$query=unserialize(GetAdminData($_SESSION['username']."_CockpitQuery"));  // used to search on units
if($query==NULL) $query=array();
// ********************************************************
// ************ Main - Handling of Input forms ************
// ********************************************************
$username=$_SESSION['username'];
$Start=GetAdminData($username."_CockpitStartDate");
$WPFilter=GetAdminData("CockpitWPFilter_".$_SESSION["ProjectName"]);
$WPFilterStartDate=GetAdminData("CockpitWPFilterStartDate_".$_SESSION["ProjectName"]);
if($Start<$WPFilterStartDate) $WarningCalculate="<mark><b>Warning: </b>Cockpit is calculated after requested Start view date. Do a calculate!</mark>";
$SAPDays=GetAdminOnUser("SAPDays","Projects");
$LastQ=GetAdminOnUser("LastQ","Projects");
$UnitHours=GetAdminOnUser("UnitHours","Projects");
$UnitFormat=GetAdminOnUser("UnitFormat","Projects"); 
$LightView=GetAdminOnUser("LightView","Projects");
$DebugView=GetAdminOnUser("DebugView","Projects");

if($LastQ=="") {
   StoreAdminOnUser("CockpitCollection","COOPANS_Cockpit");
   $LastQ=GetAdminOnUser("LastQ","Projects");
}

if($Start=="") {
   $Start=date("Y-m");
   StoreAdminData($username."_CockpitStartDate",$Start);
}
$End=GetAdminData($username."_CockpitEndDate");
if($End=="") {
   $End=date("Y-m", strtotime("+2 year -1 month"));
   StoreAdminData($username."_CockpitEndDate",$End);
}
$Level=GetAdminData($username."_CockpitLevel");  // ManDays level for which cell must be highligted

if($Level=="") {
   $Level=20;
   StoreAdminData($username."_CockpitLevel",$Level);
}

?>
   <form action="res_cockpit.php" method="post">
   <font size=-2>
   <table><tr style="vertical-align: text-top;">
   <td><input type="checkbox" name="UnitFormat" <?php echo $UnitFormat; ?>/>','-format</td>
   <td><input type="checkbox" name="UnitHours" <?php echo $UnitHours; ?>/>Units in hours</td>
   <td><input type="checkbox" name="LightView" <?php echo $LightView; ?>/>LightView</td>
   <td>Highlight Level:<input type="text" name="Level" size="3" value="<?php echo $Level; ?>"/></td>
   <td><input type="checkbox" name="DebugView" <?php echo $DebugView; ?>/>DebugView</td>
   <td><input type="checkbox" name="SAPDays" <?php echo $SAPDays; ?>/>SAP Days <a href="res_admin_uploadSAP.php?UpdateCockpitOnly=1">Insert SAP</a></td>
   <td><input type="checkbox" name="LastQ" <?php echo $LastQ; ?>/>Last Q</td>
   <td>Cockpit_1=<b><?php echo GetAdminData("Cockpit_ThisQuarterName");  ?></b></td>
   <td>Cockpit_0 (Last Q)=<b><?php echo GetAdminData("Cockpit_LastQuarterName");  ?></b></td>
   </tr>
   </table>
   </font>
   
   <font size="+3">Resource Cockpit</font> (Total of selected resources: <a id="ResTotal">0</a>
   <?php
   if($UnitHours<>"") echo " hours)";
   else echo " days)"; 
   ?>
   <font style="background-color:orange;" size="+2"><?php if($LastQ<>"") echo "(Last Q Active)";?></font>
   <br>
   <b>Start:</b><input type="text" name="StartYearMonth" size="6" value="<?php echo $Start?>"/>
   <b>End:</b><input type="text" name="EndYearMonth" size="6" value="<?php echo $End?>"/>
   <input type="submit" name="Cockpit" value="View"/>
   <input type="submit" name="Cockpit" value="ViewNUAC"/>
   <br>
   
   <?php echo GetUnitsIntoCheckboxes(); ?>
   <hr>
   <span style="color:#C00000" title="Examples:
   '24' : Will filter only WP# 24 and below.
   '24|^25|^30' : Will filter only WP# 24, 25 and 30 and anything below.
   '24.05' : Will filter 24.05 and below i.e. V&V under WP# 24.
   '(?!99.*$)' : All WPs except 99
   '3[12].05.8' List all WPs starting with 31.05.8 or 32.05.8"><a style="color:#C00000;" href="res_cockpit.php?ShowFilterExamples">WP filter:</a></span>
   <input type="text" name="WPFilter" size="10" value="<?php echo $WPFilter?>"/>
   <span style="color:#C00000" title="StartDate is date from which Cockpit is calculated forwardly. This is to save processesing time as calculation of unnessary old dates are avoided.">StartDate:</span>
   <input type="text" name="WPFilterStartDate" size="10" value="<?php echo $WPFilterStartDate?>"/>
   <input type="submit" name="Cockpit" value="Calculate"/>
   <font size="-1">Updated:<?php echo GetAdminData("CockpitLastTimeCalculate");?></font>
   <?php if(isset($WarningCalculate)) echo $WarningCalculate;?>
   <hr>   
<?php
WriteHTMLofCockpit($query);

?>
   <input type="submit" name="ListAllSubjects" value="List Subjects"/>
<?php
   if(GetAdminOnUser("ListSubjectsFullView","Projects")=="1") $checked="checked";
   else $checked="";
?>
   Full View<input type="checkbox" name="FullView" value="1" <?php echo $checked; ?>/>
   </form>   
<?php
goto ExitHtml;


function GetUnitsIntoCheckboxes() {
   $arr=GetUniqueListOfUnits();
   $arrCheck=GetAdminData($_SESSION['username']."_CockpitQueryUnits");
   // Make html to return
   //$html='<table style="border: none;"><tr>';
   $html='<font size="-1"><table><tr>';
   //$html.='<td style="border: none;">Units:';
   $html.='<td"><b>Selected units: </b>'.'<a href="res_cockpit.php?ClearUnits">Clear All</a>';
   $t=0;
   foreach ($arr as $unit) {
      if($arrCheck<>array()) {
         if(in_array($unit,$arrCheck)) $checked="checked";
         else $checked="";
      }
      else $checked="";
      $html.='<td style="padding-left: 4px; text-align:left"><input type="checkbox" name="Units[]" value="'.$unit.'" '.$checked.'/>'.$unit.'</td>';
      if($t++>9) {
         $t=0;
         $html.="</tr><tr>";  // move down to next row in table
      }
   }
   $html.='</tr></table></font>';
   return $html;
}
function GetUniqueListOfUnits() {
   // returns an sorted array containing all unit names from Cockpit
   // array(unit1, unit2, unit...)
   $m = new MongoClient();
   $db = $m->Projects;
   $collection = new MongoCollection($db, GetAdminOnUser("CockpitCollection","Projects"));
   //$collection = new MongoCollection($db, "COOPANS_Cockpit");
   $cursor=$collection->find();
   $arr=array();
   foreach ($cursor as $obj) {
      $arr[]=$obj['Unit'];
   }
   $arr=array_unique($arr);
   sort($arr);  // $arr is now a unique list of sorted Units
   return $arr;
}


// ----------------------------------------
// Prepare resource Cockpit
// ----------------------------------------
function PrepareCockpit($ViewNUAC=false) {
   $username=$_SESSION['username'];
   // Store variables for viewing data
   StoreVariablesForViewingData($ViewNUAC);
   $query=array();
   if(isset($_POST['Units'])) {
      foreach($_POST['Units'] as $Unit) $query[]=array("Unit"=>$Unit);
      $query=array('$or'=>$query);
   }
   
   $ser=serialize($query); // to avoid '$' in queries that are illegal
   StoreAdminData($username."_CockpitQuery","$ser");
   return;
}

function ClearUnitSelections()
{
   $username=$_SESSION['username'];
   StoreAdminData($username."_CockpitQueryUnits",array());
}


function StoreVariablesForViewingData($ViewNUAC) {
   $username=$_SESSION['username'];
   // Store input criterias
   if(isset($_POST['StartYearMonth'])) StoreAdminData($username."_CockpitStartDate",$_POST['StartYearMonth']);
   if(isset($_POST['EndYearMonth'])) StoreAdminData($username."_CockpitEndDate",$_POST['EndYearMonth']);
   if(isset($_POST['Level'])) StoreAdminData($username."_CockpitLevel",str_replace(",",".",$_POST['Level']));
   // Store selected Units   
   if($ViewNUAC) {  // make sure list only contains NUAC units
      $arrNUACUnits=array("NAC","NAP","NCO","NCOIF","NDS","NFD","NOCP","NOCP CH",
                          "NSIM","OSO","OSR","OST-E","OST-A","NATCC Sup", "NCEO",
                          "NATCC","ND","NCFO","NCOO"); // All possible NUAC units
      $_POST['Units']=$arrNUACUnits;
   }
   if(isset($_POST['Units'])) StoreAdminData($username."_CockpitQueryUnits",$_POST['Units']);
   else StoreAdminData($username."_CockpitQueryUnits",array());
   // Store WPFilter
   StoreAdminData("CockpitWPFilter_".$_SESSION["ProjectName"],$_POST["WPFilter"]);
   StoreAdminData("CockpitWPFilterStartDate_".$_SESSION["ProjectName"],$_POST["WPFilterStartDate"]);
   if(isset($_POST['SAPDays'])) StoreAdminOnUser("SAPDays","checked");
   else StoreAdminOnUser("SAPDays","");
   if(isset($_POST['LastQ'])) {
      StoreAdminOnUser("LastQ","checked");
      StoreAdminOnUser("CockpitCollection","COOPANS_Cockpit_LQ");
   }
   else {
      StoreAdminOnUser("LastQ","");
      StoreAdminOnUser("CockpitCollection","COOPANS_Cockpit");
   }
   if(isset($_POST['UnitHours'])) {
      StoreAdminOnUser("UnitHours","checked");
   }
   else {
      StoreAdminOnUser("UnitHours","");
   }
   if(isset($_POST['UnitFormat'])) {
      StoreAdminOnUser("UnitFormat","checked");
   }
   else {
      StoreAdminOnUser("UnitFormat","");
   }
   if(isset($_POST['LightView'])) {
      StoreAdminOnUser("LightView","checked");
   }
   else {
      StoreAdminOnUser("LightView","");
   }
      if(isset($_POST['DebugView'])) {
      StoreAdminOnUser("DebugView","checked");
   }
   else {
      StoreAdminOnUser("DebugView","");
   }
   
}

// ----------------------------------------
// Handling of links from resource Cockpit
// ----------------------------------------
function ListRessourcesFromLink($Subj="", $YearMonth="", $FullView=false) {
   if(GetAdminOnUser("UnitHours","Projects")=="checked") {
      $UnitHoursDays=7.4;  // factor to multiply mandays either 1 for mandays or 7.4 for manhours
      $TimeTxt="MHs";
   }
   else {
      $UnitHoursDays=1;
      $TimeTxt="MDs";
   }       
   if(GetAdminOnUser("UnitFormat","Projects")=="checked") $UnitNumberFormat=",";  // comma format for cockpit view
   else $UnitNumberFormat=".";


   if($Subj=="") $Subj=$_GET["Subj"];
   if($YearMonth=="") $YearMonth=$_GET["YearMonth"];
   echo "<b>".$Subj." - ".date("F",mktime(0,0,0,substr($YearMonth,5,2),10))." ".substr($YearMonth,0,4)."</b><br>";
   $m = new MongoClient();
   $db = $m->Projects;
   $collection = new MongoCollection($db, GetAdminOnUser("CockpitCollection","Projects"));
   $query=array('Subj' => $Subj);
   //$query=array($YearMonth => $YearMonth);
   $cursor=$collection->findone($query); // gets all documents containing indicated "Subj"
   //$cursor=$collection->find;
   //var_dump($cursor);
   $Record=$cursor[$YearMonth];
   if($UnitHoursDays==1) echo "Total ManDays of Month: ".number_format($Record['ManDays'], 1, $UnitNumberFormat, ' ')."<br>";
   else echo "Total ManHours of Month: ".number_format($Record['ManDays']*$UnitHoursDays, 1, $UnitNumberFormat, ' ')."<br>";
   echo '<table>';
   echo '<tr>';
   echo '<th>'.$TimeTxt.'</th>'.'<th align="left">'."WP#".'</th>';
   if($FullView) echo '<th align="left">'."Role".'</th>'.'<th align="left">'."ReqRes".'</th>'.'<th align="left">'."AllRes".'</th>';
   echo '<th align="left">'."Tasks".'</th>';
   echo '</tr>';
   foreach ($Record['Tasks'] as $o) {
      echo '<tr>';
      echo '<td align="right">'.number_format($o['ManDays']*$UnitHoursDays, 1, $UnitNumberFormat, ' ')."</td>";
      $WPLink='<a href="res_manage_input_table.php?WPNum='.$o['WPNum'].'">'.$o['WPNum'].'</a>';
      echo "<td>".$WPLink."</td>";
      if($FullView) {
         echo "<td>".$o['Roles']."</td>";
         echo "<td>".$o['ReqRes']."</td>";
         echo "<td>".$o['AllRes']."</td>";
      }
      echo "<td>".$o['Text']."</td>";
      echo "</tr>";
   }
   echo '</table>';
   return;
}

// ********************************************************
// ***** Calculate and Organise Cockpit DB in memory ******
// ********************************************************

function CalculateCockpit() {
// Store time of last calculation
StoreAdminData("CockpitLastTimeCalculate",date("Y-m-d G:i:s"));

// disable LastQ selection id enabled
StoreAdminOnUser("LastQ","");
StoreAdminOnUser("CockpitCollection","COOPANS_Cockpit");

// Check if WPFilter is active and eventual search for selected WPs
$WPFilter=GetAdminData("CockpitWPFilter_".$_SESSION["ProjectName"]);
$WPFilterStartDate=GetAdminData("CockpitWPFilterStartDate_".$_SESSION["ProjectName"]);
if($WPFilter<>"") {
   $query=array('WPNum' => new MongoRegex("/^$WPFilter/"));
}
else $query=array("Backup" => array('$exists' => false));


$query=array('$and'=>array( 
                           $query, 
                           array("EndDate"=>array('$gte'=>$WPFilterStartDate))
                          ));


// Get connection to all WPs
//$obj=new db_handling(array("Backup" => array('$exists' => false)),"");
$obj=new db_handling($query,"");
$obj->get_cursor_find_sort();

// Drop content of DB
$m = new MongoClient();
$db = $m->Projects;
$collection = new MongoCollection($db, "COOPANS_Cockpit");
$response=$collection->drop();

$Header=array();
// Count over all WPs
   foreach ($obj->cursor as $oList) {
      // Store super header names
      $HeaderLevel=substr_count($oList["WPNum"], ".");
      $Header[$HeaderLevel]=$oList["WPName"];
      // clear the following headers
      for($t=$HeaderLevel+1;$t<5;$t++) {
         $Header[$t]=""; 
      }

      if(isset($oList["Ressources"])) {
         // if dates are not given or incorrect then exit
         if(isset($oList["StartDate"]) and isset($oList["EndDate"]) and $oList["StartDate"]<>"" and $oList["EndDate"]<>"") {
       
            $StartDate=$oList["StartDate"];
            $EndDate=$oList["EndDate"];
            // http://stackoverflow.com/questions/4233605/elegant-way-to-get-the-count-of-months-between-two-dates
//            $date1 = new DateTime(substr($StartDate,0,7));
//            $date2 = new DateTime(substr($EndDate,0,7));
//            $interval = date_diff($date1, $date2);
//            $NumOfMonths = ($interval->format('%m'))+1;  // number of months between date1 and date2
            // Calculate number of whole months between two dates e.g. 2013-01-15 and 2013-02-08 = 2
            // although that there actually are less than a months between the dates
            $NumOfMonths=(substr($EndDate,0,4)-substr($StartDate,0,4))*12+
                          substr($EndDate,5,2)-substr($StartDate,5,2)+1;

            // list all resources in WP
            foreach($oList["Ressources"] as $oSub) {
               if(trim($oSub["AllRes"])<>"") $Subj=trim($oSub["AllRes"]); // Subj. = Allocated resource
               elseif(trim($oSub["ReqRes"])<>"") $Subj=trim($oSub["ReqRes"]); // Subj. = Requested resource
               else $Subj=trim($oSub["Roles"]); // Subj. = Requested resource               

               $objDate=new db_handling(array('Subj' => $Subj),"Backup");
               $objDate->CollectionName=GetAdminOnUser("CockpitCollection","Projects");
               //$objDate->CollectionName="COOPANS_Cockpit";
               $objDate->get_cursor_findone();
               
               $Cost=GetKeyFromAdminDB($Subj, 'Cost#');
               if(substr($Cost,0,1)=="#") $Cost=substr($Cost,1);  // remove "#" if in start
               $objDate->cursor['Cost#']=$Cost;
               
               $objDate->cursor['Subj']=$Subj;   // overwrite if exist otherwise creates a new
               $objDate->cursor['Unit']=GetUnitFromAdminDB($oSub["ResOwn"], $Subj);

               $objDate->cursor['AktArt']=GetKeyFromAdminDB($Subj, 'AktArt');
               

//if($oSub["ResOwn"]=="STW" or $Subj=="STW") echo $oList["WPNum"]." ".$oSub["ResOwn"]." ".$Subj." ".$objDate->cursor['Unit']."<br>";

               $objDate->cursor['ResOwn']=$oSub["ResOwn"];

               // Store all dates
               $StartMonth=intval(substr($StartDate,5,2));
               //$StartMonth=date("m",strtotime($StartDate));
               $StartYear=intval(substr($StartDate,0,4));
//if($Subj=="ARK") {
//  echo $StartDate." ".$EndDate." ".$NumOfMonths." ".$StartYear."<br>";
//}
               // Build headers into text
               $HeaderText="";
               for($u=1;$u<4;$u++) {
                  if(isset($Header[$u])) {
                     $HeaderText.=$Header[$u]." : ";
                  }
               }

               // Each resource entry is aligned within a start date and an end date for the WP
               // The following counts over this period in order to create an entry for each month.
               for($t=0;$t<$NumOfMonths;$t++) {
                  $ManDays=$oSub["ManDays"]/$NumOfMonths;
                  $ManDaysFormatted=number_format($ManDays, 1, ',', ' ');
                  $text=$HeaderText;
                  $FormattedMonth=str_pad($StartMonth, 2, '0', STR_PAD_LEFT); // add leading zero to months 1 to 9
                  if(isset($objDate->cursor["$StartYear-$FormattedMonth"])) {
                     $arrDateExisting=$objDate->cursor["$StartYear-$FormattedMonth"];
                     if($arrDateExisting["Subj"] == $Subj) {
                        $objTasks=$objDate->cursor["$StartYear-$FormattedMonth"]['Tasks'];
                        $objTasks[]=array("ManDays" => $ManDays,
                                       "WPNum" => $oList["WPNum"],
                                       "Text" => $text,
                                       "Roles" => $oSub["Roles"],  
                                       "ReqRes" => $oSub["ReqRes"],
                                       "AllRes" => $oSub["AllRes"] );
                        $objDate->cursor["$StartYear-$FormattedMonth"]['ManDays']+=$ManDays;
                        $objDate->cursor["$StartYear-$FormattedMonth"]['Tasks']=$objTasks;
                     }

                  }
                  else {
                     $YearMonth=date("F",mktime(0,0,0,$StartMonth,10))." ".$StartYear;
                     $objDate->cursor["$StartYear-$FormattedMonth"] = array("Subj" => $Subj,
                             "ManDays" => $ManDays, "Tasks" => array(array(
                                       "ManDays" => $ManDays,
                                       "WPNum" => $oList["WPNum"],
                                       "Text" => $text,
                                       "Roles" => $oSub["Roles"],  
                                       "ReqRes" => $oSub["ReqRes"],
                                       "AllRes" => $oSub["AllRes"] )));
                  }
                  $StartMonth++;
                  // Count up year if months exceeds 12
                  if($StartMonth==13) {
                     $StartMonth=1;
                     $StartYear++;
                  }  
               }
               $objDate->save_collection($objDate->cursor);
            }
         }
         else {
            // The following could be activated to track forgotten dates
            //echo "<br>Resource without start and end dates - WP#: ".$oList["WPNum"]."<br>";
         }
      }
   }
//exit;
}

// ********************************************************
// *********** Output Cockpit DB on screen/html ***********
// ********************************************************


function WriteHTMLofCockpit($query) {   // remove query !!!!!!!!!!!!!!!!!!
   if(GetAdminOnUser("UnitHours","Projects")=="checked") $UnitHoursDays=7.4;  // factor to multiply mandays either 1 for mandays or 7.4 for manhours
   else $UnitHoursDays=1;
   if(GetAdminOnUser("UnitFormat","Projects")=="checked") $UnitNumberFormat=",";  // comma format for cockpit view
   else $UnitNumberFormat=".";
   $username=$_SESSION['username'];
   $StartYearMonth=GetAdminData($username."_CockpitStartDate");
   $EndYearMonth=GetAdminData($username."_CockpitEndDate");
   $Level=GetAdminData($username."_CockpitLevel");  // level for which days are highlighted
   if(GetAdminOnUser("SAPDays","Projects")<>"") $ShowRealDays=true;
   else $ShowRealDays=false;

   $StartYear=substr($StartYearMonth,0,4);
   $StartMonth=substr($StartYearMonth,5,2); // 2013-04
   $EndYear=substr($EndYearMonth,0,4);
   $EndMonth=substr($EndYearMonth,5,2); // 2013-04
   $DiffMonths=(($EndYear-$StartYear)*12)+($EndMonth-$StartMonth)+1;
   // Sanity checks
   if($DiffMonths>60) {
      echo "Period is limited to max. 60 months";
      exit;
   }

   // Get units to be listed. In case none in selected checkboxes then list all
   $arrSelectedUnits=GetAdminData($_SESSION['username']."_CockpitQueryUnits");
   if($arrSelectedUnits==NULL) $arrSelectedUnits=GetUniqueListOfUnits();
   echo '<table>';
   WriteTableHeader($StartYear,$StartMonth,$DiffMonths,true,$ShowRealDays);  // Year, Month
   $FullHeaderLine=true;
   foreach($arrSelectedUnits as $Unit) {
      if($FullHeaderLine) $FullHeaderLine=false;
      else WriteTableHeader($StartYear,$StartMonth,$DiffMonths,false,$ShowRealDays);  // Year, Month
      $arrEst=array();
      $arrSubTotalsEst=array();
      $arrSubTotalsAct=array();
      GetArrayOfEstimates($arrEst,$Unit,$StartYear,$StartMonth,$DiffMonths,$UnitNumberFormat);
      $arrAct=array();
      GetArrayOfSAPRegisteredDays($arrAct,$Unit,$StartYear,$StartMonth,$DiffMonths);

      echo GetHTMLOfEstimates($arrEst,$arrAct,$arrSubTotalsEst,$arrSubTotalsAct,$Unit,$StartYear,$StartMonth,$DiffMonths,$UnitHoursDays,$UnitNumberFormat);
      
      for($t=0;$t<count($arrSubTotalsEst);$t++) {
         if(isset($arrTotalsEst[$t])) $arrTotalsEst[$t]+=$arrSubTotalsEst[$t];
         else $arrTotalsEst[$t]=$arrSubTotalsEst[$t];
         if($ShowRealDays) {
            if(isset($arrTotalsAct[$t])) $arrTotalsAct[$t]+=$arrSubTotalsAct[$t];
            else $arrTotalsAct[$t]=$arrSubTotalsAct[$t];
         }
      }
   }
   
   // show totals   
   echo '<tr style="font-weight: bold; font-size: 13pt; text-align:right; color:#AA0000"><td align="left"><b>Total</b></td><td align="center">-</td>';
   for($t=0;$t<count($arrTotalsEst);$t++) {
      echo '<td>'.number_format($UnitHoursDays*$arrTotalsEst[$t], 1, $UnitNumberFormat, ' ').'</td>';
      if($ShowRealDays) echo '<td>('.number_format($UnitHoursDays*$arrTotalsAct[$t], 1, $UnitNumberFormat, ' ').')</td>';      
   }
        
   // Show difference of total
   if($ShowRealDays) echo '<td>'.number_format($UnitHoursDays*($arrTotalsEst[$t-1]-$arrTotalsAct[$t-1]), 1, $UnitNumberFormat, ' ').'</td>';  
   echo "</tr>";
   echo '</table>';

   // update javascript with total of all units
   $LastTotal=number_format($UnitHoursDays*$arrTotalsEst[$t-1], 0, $UnitNumberFormat, ' ');
   echo '<script> addText(\'ResTotal\',\''.$LastTotal.'\'); </script>';

   if(GetAdminOnUser("LightView","Projects")!="checked") {
      CalcAktArtForCostNumbers($StartYear,$StartMonth,$DiffMonths,$query,$UnitNumberFormat);   
      ListPSP_CostNumbers_Hours($query,$StartYear,$StartMonth,$DiffMonths,$UnitNumberFormat);
   }
}

function CalcAktArtForCostNumbers($StartYear,$StartMonth,$DiffMonths,$query,$UnitNumberFormat) {
// Calculation of AktArt etc.
   $arrAktArt=array();  // AktArt Cost numbers ....

   $m = new MongoClient();
   $db = $m->Projects;
   $collection = new MongoCollection($db, GetAdminOnUser("CockpitCollection","Projects"));
   //$collection = new MongoCollection($db, "COOPANS_Cockpit");
   // find selected Units to list
   $cursor=$collection->find($query)->sort(array("Unit" => 1,"Subj" => 1));

   foreach($cursor as $obj) {
      $Year=$StartYear;
      $Month=$StartMonth;

      // AktArt, Cost number ...
      $AktArt=$obj['AktArt'];
      $Cost=$obj['Cost#'];
        
      for($t=0;$t<$DiffMonths;$t++) {
         if($Month==13) {
            $Month=1;
            $Year++;
         }
         $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);

         if(isset($obj[$YearMonth]) and isset($obj[$YearMonth]["ManDays"])) {
             $YearMonthText=date("F",mktime(0,0,0,$Month,10))." ".$Year;
             $ManDays=number_format($obj[$YearMonth]["ManDays"], 1, $UnitNumberFormat, ' ');

             // Calculate OPERA1, OPERA2.... for each month and Cost number e.g. 4310, 4320...
             if(!isset($arrAktArt[$Cost][$AktArt][$YearMonth])) $arrAktArt[$Cost][$AktArt][$YearMonth]=0;
             $arrAktArt[$Cost][$AktArt][$YearMonth]+=$ManDays; 
         }
         $Month++;
      }      
   }
   WriteAktArtForEachCostToTable($arrAktArt,$StartYear,$StartMonth,$DiffMonths,$UnitNumberFormat);
}

function GetHTMLOfEstimates(&$arrEst,&$arrAct,&$ColTotalEst,&$ColTotalAct,$Unit,
                            $StartYear,$StartMonth,$DiffMonths,$UnitHoursDays,$UnitNumberFormat) {
   $html="";
   $username=$_SESSION['username'];
   $Level=GetAdminData($username."_CockpitLevel");  // level for which days are highlighted
   if(GetAdminOnUser("SAPDays","Projects")<>"") $ShowRealDays=true;
   else $ShowRealDays=false;
   $RowTotalEst=0;  // total row sum of Estimates
   $RowTotalAct=0;  // total row sum of Actual values

   foreach($arrEst as $Subj=>$arrSubj) {
      $Year=$StartYear;
      $Month=$StartMonth;
      $html.='<tr>';
      $html.='<td align="left">';
      $html.=$Subj; // Subject
      $html.='</td>';
      $html.='<td align="left">';
      $html.=$Unit;
      $html.='</td>';
      
      for($t=0;$t<$DiffMonths;$t++) {
         if($Month==13) {
            $Month=1;
            $Year++;
         }
         $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);
         $Month++;

         $ManDaysEst=$arrSubj[$t]["Est"];
         // Highlight cell of estimate if ManDays are above level
         if(floatval($ManDaysEst) >= floatval($Level)) {
            $html.='<td bgcolor="#FFCCCC" align="right">';
         }
         else $html.='<td align="right">';
         // Insert Estimates hours as link with tasks to do for the month         
         if($ManDaysEst=="-") $ManDaysLink="-";  // no estimates i.e. no link
         else $ManDaysLink='<a href="res_cockpit.php?Subj='.$Subj.'&YearMonth='.$YearMonth.'">'.number_format($UnitHoursDays*$ManDaysEst, 1, $UnitNumberFormat, ' ').'</a>';
         $html.='<div title="'.$arrSubj[$t]["Tasks"].'">'.$ManDaysLink."</div>";
         $html.='</td>';

         // Handle Real/Actual Days from SAP import
         if($ShowRealDays) {
            $html.='<td align="right">';
            if(isset($arrAct[$Subj][$t])) {
               if($arrAct[$Subj][$t]=="-") $html.="-";
               else $html.="(".number_format($arrAct[$Subj][$t], 1, $UnitNumberFormat, ' ').")";
               
            }
            else $html.='-';
            $html.='</td>';
         }
         
         // Calculate Row totals of estimates
         $RowTotalEst+=$ManDaysEst;
         // Calculate Column totals of estimates
         if(isset($ColTotalEst[$t])) $ColTotalEst[$t]+=$ManDaysEst;
         else $ColTotalEst[$t]=$ManDaysEst;

         if($ShowRealDays) {
            if(isset($arrAct[$Subj][$t])) {
               // Calculate Row totals of actual days
               $RowTotalAct+=$arrAct[$Subj][$t];
               // Calculate Column totals of actual days
               if(isset($ColTotalAct[$t])) $ColTotalAct[$t]+=$arrAct[$Subj][$t];
               else $ColTotalAct[$t]=$arrAct[$Subj][$t];
            }
            elseif(!isset($ColTotalAct[$t])) $ColTotalAct[$t]=0;
         }  
      }
      // Insert total sum of row
      $html.='<td align="right"><b>'.number_format($UnitHoursDays*$RowTotalEst, 1, $UnitNumberFormat, ' ').'</b></td>';
      // Calculate Column totals of "row total" estimates
      if(isset($ColTotalEst[$t])) $ColTotalEst[$t]+=$RowTotalEst;
      else $ColTotalEst[$t]=$RowTotalEst;
            
      if($ShowRealDays) {
         // Insert total sum of row actual days
         $html.='<td align="right"><b>('.number_format($UnitHoursDays*$RowTotalAct, 1, $UnitNumberFormat, ' ').')</b></td>';
         // Calculate Column totals of "row total" actual days
         if(isset($ColTotalAct[$t])) $ColTotalAct[$t]+=$RowTotalAct;
         else $ColTotalAct[$t]=$RowTotalAct;
         // Insert difference between estimate and actual
         $html.='<td align="right"><b>'.number_format($UnitHoursDays*($RowTotalEst-$RowTotalAct), 1, $UnitNumberFormat, ' ').'</b></td>';
         
         $RowTotalAct=0;
      }
      $RowTotalEst=0;
      $html.='</tr>';
   }
   
   // Insert column totals into table
   $html.='<tr>';
   $html.='<td><b>Subtotal</b></td><td><b>'.$Unit.'</b></td>';
   // Estimated and actual days
   for($t=0;$t<$DiffMonths+1;$t++) {
      if(!isset($ColTotalEst[$t])) $ColTotalEst[$t]=0; // in case no hour/days exist in period, ColTotalEst will not be set. So set it to zero.
      if(!isset($ColTotalAct[$t])) $ColTotalAct[$t]=0; // in case no hour/days exist in period, ColTotalAct will not be set. So set it to zero.
      if($ColTotalEst[$t]=="-") $ColTotalEst[$t]=0;
      $html.='<td align="right"><b>'.number_format($UnitHoursDays*$ColTotalEst[$t], 1, $UnitNumberFormat, ' ').'</b></td>';
      if($ShowRealDays) {
         if($ColTotalAct[$t]=="-") $ColTotalAct[$t]=0;
         $html.='<td align="right"><b>('.number_format($UnitHoursDays*$ColTotalAct[$t], 1, $UnitNumberFormat, ' ').')</b></td>';
      }
   }
   // insert difference of subtotals
   if($ShowRealDays) {
         $html.='<td align="right"><b>'.number_format($UnitHoursDays*($ColTotalEst[$t-1]-$ColTotalAct[$t-1]), 1, $UnitNumberFormat, ' ').'</b></td>';
      }

   $html.='</tr>';
   return $html;   
}


function GetArrayOfEstimates(&$arrEst,$Unit,$StartYear,$StartMonth,$NumOfMonths,$UnitNumberFormat) {
   // For the indicated Unit, an array of subjects is returned with estimates hours and a text of WP tasks
   // $arrEst[$obj['Subj']]=array(array("Est"=>ManMonth1, "Tasks"=>text1), array("Est"=>ManMonth2, "Tasks"=>1text2), ...)
   $m = new MongoClient();
   $db = $m->Projects;
   $collection = new MongoCollection($db, GetAdminOnUser("CockpitCollection","Projects"));
   //$collection = new MongoCollection($db, "COOPANS_Cockpit");
   // find selected Units to list
   $cursor=$collection->find(array("Unit"=>$Unit))->sort(array("Subj" => 1));

   // go through all subjects in Cockpit
   foreach($cursor as $obj) {
      // Skip empty rows in indicated period
      if(SkipRowAsItIsEmpty($obj, $StartYear, $StartMonth, $NumOfMonths)) continue;
      $Subj=$obj['Subj'];
      $Year=$StartYear;
      $Month=$StartMonth;

      for($t=0;$t<$NumOfMonths;$t++) {
         if($Month==13) {
            $Month=1;
            $Year++;
         }
         $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);

         if(isset($obj[$YearMonth]) and isset($obj[$YearMonth]["Tasks"])) {
            $YearMonthText=date("F",mktime(0,0,0,$Month,10))." ".$Year;
            // Get all texts for subject in month
            $text=$obj["Subj"]." - ".$YearMonthText."\n";
            foreach($obj[$YearMonth]["Tasks"] as $oTask) {
               $text.=number_format($oTask['ManDays'], 1, $UnitNumberFormat, ' ')." : ".$oTask['WPNum']." : ".$oTask['Text']."\n";
            }
            // Get ManDays of Subject for given months
            $arrTmpEst[$t]=array("Est"=>$obj[$YearMonth]["ManDays"], "Tasks"=>$text);             
         }
         else {
            // write empty estimate for subject in given month to cell
            $arrTmpEst[$t]=array("Est"=>"-", "Tasks"=>"");
         } 
         $arrEst[$obj['Subj']]=$arrTmpEst;       
         $Month++;
      }

   }
}
function GetArrayOfSAPRegisteredDays(&$arrAct,$Unit,$StartYear,$StartMonth,$NumOfMonths) {
   // For the indicated Unit, an array of subjects is returned with extimates hours and a text of WP tasks
   // $arrAct[$obj['Subj']]=array(ManMonth1, ManMonth2, ...)
   $m = new MongoClient();
   $db = $m->Projects;
   $collection = new MongoCollection($db, GetAdminOnUser("CockpitCollection","Projects"));
   //$collection = new MongoCollection($db, "COOPANS_Cockpit");
   // find selected Units to list
   $cursor=$collection->find(array("Unit"=>$Unit))->sort(array("Subj" => 1));

   // go through all subjects in Cockpit
   foreach($cursor as $obj) {

      // Skip empty rows in indicated period
      if(SkipRowAsItIsEmptyActual($obj, $StartYear, $StartMonth, $NumOfMonths)) continue;
      $Subj=$obj['Subj'];
      $Year=$StartYear;
      $Month=$StartMonth;

      for($t=0;$t<$NumOfMonths;$t++) {
         if($Month==13) {
            $Month=1;
            $Year++;
         }
         $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);
         // Get actual hours resgistered
         if(isset($obj[$YearMonth]["RealDays"])) {
            $arrTmpAct[$t]=$obj[$YearMonth]["RealDays"];
         }
         else $arrTmpAct[$t]="-";

         $arrAct[$obj['Subj']]=$arrTmpAct;       
         $Month++;
      }
   }
}

//************************************************
function AllUnitsUsingCostNumber($CostNumber) {
   $m = new MongoClient();
   $db = $m->admin;
   $collection = new MongoCollection($db, 'InitialMappings');
   $cursor = $collection->find(array("Cost#"=>strval($CostNumber)));
   $Units=array();
   foreach($cursor as $o) $Units[]=$o["Unit"];
   $Units=array_unique($Units);
   $RetVal="";
   foreach($Units as $Unit) $RetVal.=$Unit.", ";
   return $RetVal; // return with units in a string
}

function WriteAktArtForEachCostToTable(&$arrAktArt,$StartYear,$StartMonth,$DiffMonths,$UnitNumberFormat) {
$ind=0;
foreach ($arrAktArt as $key => $story){
   $Year=$StartYear;
   $Month=$StartMonth;
   
   echo "<br><b>Cost Number:</b> ".$key;
   echo ' - <font size="-2">Possible units: '.AllUnitsUsingCostNumber($key).'</font>';
   
   echo '<table>';

   // Table header
   echo '<tr>';
   echo '<th align="left">'."AktArt/<br>Hours"."</th>";
   for($t=0;$t<$DiffMonths;$t++) {
      if($Month==13) {
         $Month=1;
         $Year++;
      }
      $MonthName=date("M",mktime(0,0,0,$Month,10));
      echo "<th>".$MonthName."<br>".$Year."</th>";
      $Month++;
   }
   echo "<th>"."Sum"."</th>";
   echo '</tr>';
   // End Table Header

   $arrTotal=array();
   foreach($story as $subkey => $subvalue){
      $Year=$StartYear;
      $Month=$StartMonth;

      echo "<tr>";
      echo "<td>".$subkey."</td>";
      
      $arrTable[$ind]['Cost']=$key;
      $arrTable[$ind]['AkrArt']=$subkey;
      
      $RowTotal=0;
      for($t=0;$t<$DiffMonths;$t++) {
         if($Month==13) {
            $Month=1;
            $Year++;
         }
         $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);

         // Calculate Totals
         if(isset($arrAktArt[$key][$subkey][$YearMonth])) {
            $RowTotal+=$arrAktArt[$key][$subkey][$YearMonth];
            // Calculate column total sums
            if(isset($arrTotal[$YearMonth])) $arrTotal[$YearMonth]+=$arrAktArt[$key][$subkey][$YearMonth];
            else $arrTotal[$YearMonth]=$arrAktArt[$key][$subkey][$YearMonth];
            echo '<td align="right">'.number_format($arrAktArt[$key][$subkey][$YearMonth]*7.4, 1, $UnitNumberFormat, ' ').'</td>';
            $arrTable[$ind][$YearMonth]=number_format($arrAktArt[$key][$subkey][$YearMonth]*7.4,2,$UnitNumberFormat,'');
         }
         else {
            echo '<td align="right">-</td>';
            $arrTable[$ind][$YearMonth]="-";
         }
         $Month++;
      }
      $ind++;
      echo '<td align="right"><b>'.number_format($RowTotal*7.4, 1, $UnitNumberFormat, ' ').'</b></td>';

      echo "</tr>";
   }

   // Write column totals
   echo '<tr>';
   echo '<td align="left">'."<b>Total</b>".'</td>';
   $RowTotal=0;
   $Year=$StartYear;
   $Month=$StartMonth;
   for($t=0;$t<$DiffMonths;$t++) {
      if($Month==13) {
         $Month=1;
         $Year++;
      }
      $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);

      // Calculate Total
      if(isset($arrTotal[$YearMonth])) {
         $RowTotal+=$arrTotal[$YearMonth];
         // write cost number totals
         echo '<td align="right"><b>'.number_format($arrTotal[$YearMonth]*7.4, 1, $UnitNumberFormat, ' ').'</b></td>';
      }
      else echo '<td align="right">-</td>';
      $Month++;
   }
   echo '<td align="right"><b>'.number_format($RowTotal*7.4, 1, $UnitNumberFormat, ' ').'</b></td>';
   echo '</tr>';
   // end Write column totals

   echo '</table>';
   
   
}
   // show table of all cost numbers to copy paste into excel
//print_r($arrTable);
   aasort($arrTable,"Cost");
   echo "<br><b>Table to copy/paste into excel:</b><br>";
   echo '<table style="font-size:smaller;" border="1">';
   echo '<tr><th>Cost#</th><th>AktArt</th>';
   // Make table header of months/years
   $Year=$StartYear;
   $Month=$StartMonth;
   for($t=0;$t<$DiffMonths;$t++) {
      if($Month==13) {
         $Month=1;
         $Year++;
      }
      //$MonthName=date("M",mktime(0,0,0,$Month,10));
      //echo "<th>".$MonthName."<br>".$Year."</th>";
      $Mon=str_pad($Month, 2, '0', STR_PAD_LEFT);
      echo "<th>$Mon - $Year</th>";
      $Month++;
   }
   echo "<th>"."Sum"."</th>";
   echo '</tr>';
   
   foreach($arrTable as $Entry) {
      echo '<tr>';
      $t=0;
      $RowSum=0.0;   
      foreach($Entry as $k=>$v) {
         // echo $k." ".$v;
         if($t++<2) echo "<td>".$v."</td>";
         else {
            echo '<td style="text-align:right;">'.$v."</td>";  // align only numbers to the right
            $RowSum+=str_replace(",",".",$v);
         }
      }
      echo '<td style="text-align:right;font-weight:bold;">'.number_format($RowSum, 2, $UnitNumberFormat, '')."</td>";
      echo "</tr>";
  
   }
   echo '</table>';


//   for($t=0;$t<$ind;$t++) {
//      echo "<tr>";
//      foreach($arrTable[$t] as $Table) {
//         echo "<td>".$Table."</td>";
//      }
//      echo "</tr>";
//   }
//   echo '</table>';

}

function aasort (&$array, $key) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}



function WriteTableHeader($StartYear,$StartMonth,$DiffMonths,$FullInfo,$ShowRealDays) {
   $Year=$StartYear;
   $Month=$StartMonth;
   echo '<tr>';
   if($FullInfo) {
      if(GetAdminOnUser("UnitHours","Projects")=="checked") echo '<th align="left">'."Subject/<br>ManHours"."</th>";
      else echo '<th align="left">'."Subject/<br>ManDays"."</th>";
   }
   else echo '<th align="left">'."Subject"."</th>";
   echo "<th>"."Unit"."</th>";
   for($t=0;$t<$DiffMonths;$t++) {
      if($Month==13) {
         $Month=1;
         $Year++;
      }
      $MonthName=date("M",mktime(0,0,0,$Month,10));
      if($FullInfo) {
         echo "<th>".$MonthName."<br>".$Year."</th>";
         if($ShowRealDays) echo "<th>Real</th>";
      }
      else {
         echo "<th>".$MonthName."</th>";
         if($ShowRealDays) echo "<th>Real</th>";
      }
      $Month++;
   }
   echo "<th>"."Sum"."</th>";
   if($ShowRealDays) echo "<th>"."Real"."</th>"."<th>"."Diff"."</th>";
   echo '</tr>';
}

function GetUnitFromAdminDB($ResOwn, $Subj) {
// Get Unit name from DB=Projects, Collection=Admin, "User"="_Admin_"
   $m = new MongoClient();
   
   // Priority 1: Check if Unit can be found in SAP import (InitialMappings)
   $db = $m->admin;
   $collection = new MongoCollection($db, 'InitialMappings');
   $cursor = $collection->findone(array("Subject"=>$Subj));
   if(isset($cursor["Unit"])) return $cursor["Unit"];

   // Priority 2: Check if resource owner can be found in DB
   $db = $m->selectDB('Projects');
   $collection = new MongoCollection($db, 'Admin');
   $cursor = $collection->findone(array("User"=>"_Admin_"));
   
//   foreach($cursor['Roles'] as $Role) {
//      if($Role['ResOwn']==$ResOwn) return $Role['Unit'];
//   }
   // Priority 3: Check if subject can be found in DB
   foreach($cursor['Roles'] as $Role) {
      if($Role['Role']==$Subj) return $Role['Unit'];
   }
   return "***";
}

function GetKeyFromAdminDB($RoleToSearch, $ReturnKey) {
// Get Unit name from DB=Projects, Collection=Admin, "User"="_Admin_"

   $m = new MongoClient();
   $db = $m->Projects;
   $collection = new MongoCollection($db, 'Admin');
   $cursor = $collection->findone(array("User"=>"_Admin_"));
   // Check if resource owner can be found in DB - priority 1
   foreach($cursor['Roles'] as $Role) {
      if($Role['Role']==$RoleToSearch) return $Role[$ReturnKey];
   }
   // If Role does not exist in admin e.g. for a known subject like LBN
   // then look up in InitialMappingsDB
   $db = $m->selectDB('admin');
   $collection = new MongoCollection($db, 'InitialMappings');
   $cursor = $collection->findone(array("Subject"=>$RoleToSearch));
   if(isset($cursor[$ReturnKey])) {
      if(trim($cursor[$ReturnKey])=="") $cursor[$ReturnKey]=$RoleToSearch;
      return $cursor[$ReturnKey];
   }
   return "***";
}


function SkipRowAsItIsEmpty($obj, $StartYear, $StartMonth, $DiffMonths) {
      if(GetAdminOnUser("SAPDays","Projects")<>"") $ShowRealDays=true;
      else $ShowRealDays=false;
      $Subj=$obj['Subj'];
      $Year=$StartYear;
      $Month=$StartMonth;
      $SubjectExist=false;
      for($t=0;$t<$DiffMonths;$t++) {
         if($Month==13) {
            $Month=1;
            $Year++;
         }
         $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);

         if(isset($obj[$YearMonth])) {
            if($ShowRealDays) $SubjectExist=true;
            elseif(isset($obj[$YearMonth]["Tasks"])) {
               $SubjectExist=true;
            }
         }
        $Month++;
      }
      if($SubjectExist==false) return true;
      else return false;
}
function SkipRowAsItIsEmptyActual($obj, $StartYear, $StartMonth, $DiffMonths) {
      $Subj=$obj['Subj'];
      $Year=$StartYear;
      $Month=$StartMonth;
      $SubjectExist=false;
      for($t=0;$t<$DiffMonths;$t++) {
         if($Month==13) {
            $Month=1;
            $Year++;
         }
         $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);
         if(isset($obj[$YearMonth]["RealDays"])) $SubjectExist=true;
        $Month++;
      }
      if($SubjectExist==false) return true;
      else return false;
}

function ListAllSubjects() {
   if(isset($_POST["FullView"])) {
      StoreAdminOnUser("ListSubjectsFullView",true,"Projects");
      $FullView=true;
   }
   else {
      StoreAdminOnUser("ListSubjectsFullView",false,"Projects");
      $FullView=false;
   }

   $username=$_SESSION['username'];
   $StartYearMonth=GetAdminData($username."_CockpitStartDate");
   $EndYearMonth=GetAdminData($username."_CockpitEndDate");
   $StartYear=substr($StartYearMonth,0,4);
   $StartMonth=substr($StartYearMonth,5,2); // 2013-04
   $EndYear=substr($EndYearMonth,0,4);
   $EndMonth=substr($EndYearMonth,5,2); // 2013-04
   $DiffMonths=(($EndYear-$StartYear)*12)+($EndMonth-$StartMonth)+1;
   // Sanity checks
   if($DiffMonths>60) {
      echo "Period is limited to max. 60 months";
      exit;
   }

   PrepareCockpit();
   $query=unserialize(GetAdminData($_SESSION['username']."_CockpitQuery"));  // used to search on units
   if($query==NULL) $query=array();
   $m = new MongoClient();
   $db = $m->Projects;
   $collection = new MongoCollection($db, GetAdminOnUser("CockpitCollection","Projects"));
   //$collection = new MongoCollection($db, "COOPANS_Cockpit");
   // find selected Units to list
   $cursor=$collection->find($query)->sort(array("Unit" => 1,"Subj" => 1));
 
   echo "<h1>Resource usage: $StartYearMonth to $EndYearMonth</h1>";
   echo "*******************************************************<br>";
   foreach($cursor as $obj) {
      // Skip empty rows in indicated period
      if(SkipRowAsItIsEmpty($obj, $StartYear, $StartMonth, $DiffMonths)) continue;
      $Subj=$obj['Subj'];
      $Year=$StartYear;
      $Month=$StartMonth;
//      echo $Subj." ";

      for($t=0;$t<$DiffMonths;$t++) {
         if($Month==13) {
            $Month=1;
            $Year++;
         }
         $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);

         if(isset($obj[$YearMonth])) {
            $YearMonthText=date("F",mktime(0,0,0,$Month,10))." ".$Year;
//            echo $YearMonth." ";
            ListRessourcesFromLink($Subj,$YearMonth,$FullView);
            echo "<br>";
//            echo "----------------------------------------------------<br>";
         }
         $Month++;   
      }
//      echo "*******************************************************<br>";
      echo "*******************************************************<br>";
   }
   exit;
}

function CopyCockpitToCockpitLastQ() {
   // Removes Last quarter cockpit and copies Cockpit to Cockpit Last quater
   // Drop content of Cockpit Last Q
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   $m = new MongoClient();
   $db = $m->Projects;
   $collection = new MongoCollection($db, "COOPANS_Cockpit_LQ");
   $response=$collection->drop();

   // Address Cockpit
   $obj=new db_handling(array(),"");
   $obj->CollectionName="COOPANS_Cockpit";
   $obj->get_cursor_find();
   foreach($obj->cursor as $o) {
      $collection->insert($o);
   }
   // Name Last Q cickpit to what is copied from
   StoreAdminData("Cockpit_LastQuarterName",GetAdminData("Cockpit_ThisQuarterName"));
}
function BackupCockpit($state, $CockpitSelection) {
   if(!$_SESSION['username']=="lbn") {
      echo "Only lbn can do this - sorry";
      exit;
   }
   
   if($state==1) {
      ?>
      <form action="res_cockpit.php" method="post">
      <b>Indicate NAME to be used for backup.</b> Internally in the database the name of the collection will be 'z_COOPANS_Cockpit_DATE_NAME' where 'DATE' is e.g. 20140130 and 'NAME' is the name to be indicated here:<br>
      <br>Name:
      <input type="text" name="BackupName" size="10" value=""/><br>
      <br>Comments to backup:
      <br>
      <textarea name="BackupComments" rows="6" cols="50" wrap="soft"></textarea>
      <br><input type="text" name="CockpitSelection" readonly size="1" value="<?PHP echo $CockpitSelection?>"/>
      <br>
      <input type="submit" name="BackupCockpit" value="Submit"/>
      </form>
      <?PHP
   }
   elseif($state==2) {
      $BackupName=$_POST['BackupName'];
      $BackupName=preg_replace('/[^A-Za-z0-9-]/','',$BackupName);
      $CockpitCollenctionName="z_COOPANS_Cockpit_".date("Ymd")."_".$BackupName;
      //echo $CockpitCollenctionName;
      
      $arrCockpits=GetAdminData("Cockpit_ListOfStored");
      if($arrCockpits=="") $arrCockpits=array();
      // Check if Cockpit name already exist
      $CockpitExist=false;
      $t=0;
      foreach($arrCockpits as $Cockpit) {
         if($Cockpit["Name"]==$CockpitCollenctionName) {
            $CockpitExist=true;
            // Update comment in already existing
            $arrCockpits[$t]["Comments"]=$_POST['BackupComments'];
            break;
         }
         $t++;         
      }
      // Only add cockpit to list if not already existing
      if(!$CockpitExist) $arrCockpits[]=array("Name"=>$CockpitCollenctionName, "Comments"=>$_POST['BackupComments']);
      
      StoreAdminData("Cockpit_ListOfStored",$arrCockpits);
      //print_r($arrCockpits);


      // remove collection if already exist
      $m = new MongoClient();
      $db = $m->Projects;
      $collection = new MongoCollection($db, $CockpitCollenctionName);
      $response=$collection->drop();

      // Copy COOPANS_Cockpit to Cockpit named $CockpitCollenctionName
      $obj=new db_handling(array(),"");

      if($CockpitSelection==0) $CollName="COOPANS_Cockpit_LQ";
      else $CollName="COOPANS_Cockpit";

      $obj->CollectionName=$CollName;
      $obj->get_cursor_find();
      foreach($obj->cursor as $o) {
         $collection->insert($o);
      }   
   }
}

function RestoreCockpit($state,$CockpitSelection) {
   // $CockpitSelection is either point to this quarter (1) cockpit or last Q (0) cockpit   
   if($state==1) {
      echo '<table border="1">';
      echo '<tr>';
      echo '<th>Collection Name</th><th>Comments</th>';
      echo '</tr>';
      $arrCockpits=GetAdminData("Cockpit_ListOfStored");
      echo "This quarter Cockpit name is: <b>".GetAdminData("Cockpit_ThisQuarterName")."</b><br><br>";
      echo "<b>Click on Cockpit name to restore into ";
      if($CockpitSelection==0) echo "LAST QUARTER";
      else echo "THIS QUARTER";
      echo " Cockpit:</b><br><br>";
      
      $arrCockpits=array_reverse($arrCockpits);
      foreach($arrCockpits as $Cockpit) {
         echo "<tr>";         
         echo '<td><a href="res_cockpit.php?SelectedCockpit='.$Cockpit['Name'].'&RestoreCockpit2='.$CockpitSelection.'">'.$Cockpit['Name'].'</a></td>';
         echo '<td>'.$Cockpit['Comments'].'</td>';
         echo "</tr>";
      }
      echo "</table>";
      exit;
   }
   elseif($state==2) {
      // remove collection if already exist
      $NameOfCockpitToBeRestored=$_GET["SelectedCockpit"];
      $m = new MongoClient();
      $db = $m->Projects;
      if($CockpitSelection==0) $CollName="COOPANS_Cockpit_LQ";
      else $CollName="COOPANS_Cockpit";
      $collection = new MongoCollection($db, $CollName);
      $response=$collection->drop();

      // Copy COOPANS_Cockpit to Cockpit named $CockpitCollenctionName
      $obj=new db_handling(array(),"");
      $obj->CollectionName=$NameOfCockpitToBeRestored;
      $obj->get_cursor_find();
      foreach($obj->cursor as $o) {
         $collection->insert($o);
      }
      if($CockpitSelection==0) StoreAdminData("Cockpit_LastQuarterName",$NameOfCockpitToBeRestored);
      else StoreAdminData("Cockpit_ThisQuarterName",$NameOfCockpitToBeRestored);       
   }
}

// *********************************************************
function ListPSP_CostNumbers_Hours(&$query,$StartYear,$StartMonth,$NumOfMonths,$UnitNumberFormat) 
{
   if(GetAdminOnUser("DebugView","Projects")=="checked") $ShowTable=true;  // if true the total table of all resources is listed
   else $ShowTable=false;
   

   echo "<br>";   
   $arrPSP=array();
   PrepareMappingTableForWP_COST_PSP($arrPSP);
   
   $m = new MongoClient();
   $db = $m->Projects;
   $collection = new MongoCollection($db, GetAdminOnUser("CockpitCollection","Projects"));
   //$collection = new MongoCollection($db, "COOPANS_Cockpit");
   // find selected Units to list
   //$cursor=$collection->find(array("Unit"=>$Unit))->sort(array("Subj" => 1));
   $cursor=$collection->find($query)->sort(array("Unit" => 1, "Subj" => 1));

   if($ShowTable) {
      echo "<b>Debug view:</b>";
      echo '<br><table border="1">';
      echo '<tr>';
      echo '<th>Type</th><th>PSP</th><th>Cost</th><th>AktArt</th><th>Subj</th><th>WP#</th><th>YearMo</th><th>Hours</th>';   
      echo '</tr>';
   }

   // initiale $arrHoursDefault that holds all year dates for tables
   $Year=$StartYear;
   $Month=$StartMonth;
   $arrHoursDefault=array();
   for($t=0;$t<$NumOfMonths;$t++) {
      if($Month==13) {
         $Month=1;
         $Year++;
      }
      $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);
      $arrHoursDefault[$YearMonth]=0.0;
      $Month++;
   }
  
   $arrCollapsed=array();
   // go through all subjects in Cockpit
   $u=0;
   foreach($cursor as $obj) {
      // Skip empty rows in indicated period
      if(SkipRowAsItIsEmpty($obj, $StartYear, $StartMonth, $NumOfMonths)) continue;
      $Year=$StartYear;
      $Month=$StartMonth;

      for($t=0;$t<$NumOfMonths;$t++) {
         if($Month==13) {
            $Month=1;
            $Year++;
         }
         $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);
         if(isset($obj[$YearMonth]) and isset($obj[$YearMonth]["Tasks"])) {
            foreach($obj[$YearMonth]["Tasks"] as $oTask) {
               $PSP=GetPSPNumber($oTask['WPNum'],$obj['Cost#'],$arrPSP);
               $NUACText=GetNUACNaviairText($PSP);
               $Unit=$obj['Unit'];
               if($PSP=="***No PSP***") echo "<b>Warning No PSP element:</b> "."WP: ".$oTask['WPNum']." Subject: ".$obj['Subj']." Cost#: ".$obj['Cost#']."<br>";
               if($Unit=="AC" || $Unit=="AR" || $Unit=="AX" || $Unit=="A") $Subj=$obj['Subj'];
               else $Subj="";
               $u++;
               if($ShowTable) {
                  echo '<tr>';
                  echo "<td>".$NUACText."</td><td>".$PSP."</td><td>".$obj['Cost#']."</td><td>".$obj['AktArt']."</td><td>".$Subj."</td><td>".$oTask['WPNum']."</td><td>".$YearMonth."</td><td>".(7.4*$oTask['ManDays'])."</td>";
               }
               // sum up same activities within month
               $Key=$NUACText."_@@_".$PSP."_@@_".$obj['Cost#']."_@@_".$obj['AktArt']."_@@_".$Subj;  // _@@_ = unique seperator
               if(isset($arrCollapsed[$Key])) {
                  $arrHours=$arrCollapsed[$Key];
                  $arrHours[$YearMonth]+=7.4*$oTask['ManDays'];
                  $arrCollapsed[$Key]=$arrHours;
               }
               else {
                  $arrHours=$arrHoursDefault;
                  $arrHours[$YearMonth]=7.4*$oTask['ManDays'];
                  $arrCollapsed[$Key]=$arrHours;
               }
               if($ShowTable) echo '</tr>';
            }
         }
         $Month++;
      }
   }
   if($ShowTable) {
      echo "</table>";
      echo "Number of items: $u<br>";
   }
   WriteOutCollapsed($arrCollapsed,$StartYear,$StartMonth,$NumOfMonths,$UnitNumberFormat);
}

function WriteOutCollapsed(&$arrCollapsed,$StartYear,$StartMonth,$NumOfMonths,$UnitNumberFormat)
{
//   foreach($arrCollapsed as $k=>$v) {
//      echo $k."  --  ";
//      print_r($v);
//      echo "<br><br>";
//   }
//   exit;

   echo "<br><b>Table with PSP to copy-paste into BW:</b>";
   echo '<a href="res_cockpit.php?DefineMappings"> Define WP/Cost/PSP mappings</a>';
   echo "<br>";
   $u=0;
   echo '<table border="1">';
   echo '<tr>';
   // table headers
   echo '<th>Type</th><th>PSP</th><th>Cost</th><th>AktArt</th><th>Subj</th>';
   $Year=$StartYear;
   $Month=$StartMonth;   
   for($t=0;$t<$NumOfMonths;$t++) {
      if($Month==13) {
         $Month=1;
         $Year++;
      }
      $FormattedMonth=str_pad($Month, 2, '0', STR_PAD_LEFT); // add leading zero to months 1 to 9
      echo "<th>$FormattedMonth-$Year</th>";
      $Month++;
   }
 
   // table content  
   echo '</tr>';
   foreach($arrCollapsed as $k=>$arrHours) {
      //echo $k." = ".$v."<br>";
      $arr=explode("_@@_",$k);
      echo "<tr>";
      $t=0;
      foreach($arr as $key) {
         if($t==1) {   // is key PSP?
            if($key=="***No PSP***") $key="<mark>$key</mark>"; // highlight no PSP
         }
         if($t++==3) {   // is key AktArt?
            if($key=="OPERA1" || $key=="OPERA2" || $key=="LEDER1" || $key=="LEDER2" ||
               $key=="TEKN1" || $key=="TEKN2" || $key=="FSPEC1" || $key=="FSPEC2" ||
               $key=="ADMIN1" || $key=="ADMIN2" ) ; // do nothing
            else $key="<mark>$key</mark>";  // highlight AktArt
         }
         echo "<td>$key</td>";
      }
 
      foreach($arrHours as $YearMontHours)
      {
         $YearMontHours=number_format($YearMontHours, 2, $UnitNumberFormat, ' ');
         echo '<td align="right">'.$YearMontHours."</td>";
      }
      
      echo "</tr>";
      $u++;
   }
   echo "</table>";
   echo "Note if AktArt is a subject, it is because there is no AktArt for that person e.g. he is ESK-515.<br>";
   echo "Number of items: $u<br>";
}

function GetNUACNaviairText($PSP)
{
   if(substr($PSP,0,1)=="D") return "";
   elseif(substr($PSP,-2)=="03" || substr($PSP,-2)=="01") return "01-tidsreg";
   elseif(substr($PSP,-2)=="02") return "02-NUAC";
}
function GetPSPNumber($WPNum,$CostNum,&$arrPSP)
{  
   //echo "***$WPNum :".substr($WPNum,0,2)." ".$CostNum."***<br>";
   
   //echo "<br>".$WPNum.": ";
   for($t=strlen($WPNum);$t>0;$t--)
   {
      //echo substr($WPNum,0,$t)." ";
      $WP=substr($WPNum,0,$t);
      if(isset($arrPSP[$WP][$CostNum])) return $arrPSP[$WP][$CostNum];
   }
   return "***No PSP***";
   
//   $WP=substr($WPNum,0,2);
//   if($WP=="60") $WP=substr($WPNum,0,5);
//   if(isset($arrPSP[$WP][$CostNum])) return $arrPSP[$WP][$CostNum];
//   else return "***No PSP***";

}
function DefineWP_COST_PSP_Mappings()
{
   $DefinedMappings=GetAdminData("Cockpit_WPCOSTPSPMappings")
?>
      <form action="res_cockpit.php" method="post">
      Define mappings using the following format:<br>
      <b>WPNum:PSP:CostNumber:#Comment</b><br>
      <pre>E.g.:
      26:B-00716-02:4310 4320 4410 4417 4420: #NUAC B2.6 step 1
      # INEA 60
      60.10:B-00716-03:5140:    #INEA B2.6 step 1 alle fra AX</pre>
      <br>
      <textarea name="DefinedMappings" rows="30" cols="80" wrap="soft"><?php echo $DefinedMappings; ?></textarea>
      <br>
      <input type="submit" name="Mappings" value="Submit"/>
      </form>
      <?PHP
      exit;
}
function DefineWP_COST_PSP_Mappings2()
{
   StoreAdminData("Cockpit_WPCOSTPSPMappings",$_POST["DefinedMappings"]);
   $arrLines=explode("\r\n",$_POST["DefinedMappings"]);
   echo '<table border="1">';
   echo "<tr><th>WPNum</th><th>PSP</th><th>CostNumbers</th><th>Comment</th></tr>";
   foreach($arrLines as $line)
   {
      if(substr(trim($line),0,1)=="#") continue;  // skip comments starting with '#'
      if(trim($line)=="") continue;  // skip empty lines
      $arrCols=explode(":",$line);
      if(sizeof($arrCols)!=4) {
         echo "<br><b>Error:</b> One or more colons ':' are missing in line: <pre>$line</pre>";
         echo "Go back and correct!";
         exit;
      }
      echo "<tr>";
      for($t=0;$t<4;$t++) echo "<td>".trim($arrCols[$t])."</td>";
      echo "</tr>";
   }
   echo "</table>";
   echo "<br>If this table looks incorrect, you should go back and update properly!";
   exit;
}
function ShowFilterExamples()
{
?>
<b>WP filter examples:</b><br>
<table>
<tr>
<td>24</td><td>Will filter only WP# 24 and below</td>
</tr>
<tr>
<td>24|^25|^30</td><td>Will filter only WP# 24, 25 and 30 and anything below</td>
</tr>
<tr>
<td>24.05</td><td>Will filter 24.05 and below i.e. V&V under WP# 24</td>
</tr>
<tr>
<td>(?!99.*$)</td><td>All WPs except 99</td>
</tr>
<tr>
<td>(?!9[89].*$)</td><td>All WPs except any starting with 98 or 99</td>
</tr>
<tr>
<td>3[12].05.8</td><td>List all WPs starting with 31.05.8 or 32.05.8</td>
</td>
<tr>
<td>(?![12345678].*$)</td><td>List all WPs excapt any starting with 1, 2, 3, 4, 5, 6, 7, 8</td>
</td>
</table>
<br>
<?PHP
}




function PrepareMappingTableForWP_COST_PSP(&$arrPSP)
{
   $DefinedMappings=GetAdminData("Cockpit_WPCOSTPSPMappings");
   $arrLines=explode("\r\n",$DefinedMappings);
   foreach($arrLines as $line)
   {
      if(substr(trim($line),0,1)=="#") continue;  // skip comments starting with '#'
      if(trim($line)=="") continue;  // skip empty lines
      $arrCols=explode(":",$line);
      $WP=trim($arrCols[0]);
      $PSP=trim($arrCols[1]);
      $arrCostNums=explode(" ",trim($arrCols[2]));
      foreach($arrCostNums as $CostNum)
      {
         $arrPSP[$WP][$CostNum]=$PSP;
      }
   } 
   
//print_r($arrPSP);
//exit;
}

ExitHtml:
?>
</body>
</html>
