<?php include("login_headerscript.php"); ?>
<?php

define('_GLOBAL_TABLE', '_Admin_');  // This is used for the Admin collection to store tables that must be global/common for all users. A document is created with key "User" = "_Admin_"
define('HTML_INPUT', 1);
define('NEXT_WP_NUM', 1);
define('PREVIOUS_WP_NUM', 0);
define('NEXT_FREE_WP_NUM', 2);


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
<?php
if(isset($_GET["WPNum"])) echo "<title>".$_GET["WPNum"]."</title>";
else echo "<title>Manage CheckList</title>";
?>
<?php include("class_lib.php"); ?>
<style>

A{text-decoration:none}     <!--  remove underline in links -->
</style>
<style>
.row {
        float: left;
        width: 50%;
    }
        
    .label {
        float:left;
    }

    input.datatext {
/*        BORDER-TOP: #000000 1px solid;
        BORDER-LEFT: #000000 1px solid;
        COLOR: #000000;
        BORDER-BOTTOM: #000000 1px solid;
        height:18px; */
    position:relative;
    left:3px;
    }
    select.contentselect {
/*        position:absolute;
        BORDER-TOP: #000000 1px solid;
        height:20px;
        COLOR: #000000;
        BORDER-BOTTOM: #000000 1px solid; */

        border-left:none;
    }
</style>

<script src="scrollfix.js" type="text/javascript"></script>
</head>
<body onunload="unloadP('res_manage_input_table')" onload="loadP('res_manage_input_table')">

<?php include("chk_headerline.html"); ?>
<?php


if (isset($_POST["GetPreviousWP"]) ) {
   $WPNum=GetWPNum($_POST["WPNum"],PREVIOUS_WP_NUM);
   header("location:chk_manage_input_table.php?WPNum=".$WPNum);
   exit;   
}
elseif (isset($_POST["GetNextWP"]) ) {
   $WPNum=GetWPNum($_POST["WPNum"],NEXT_WP_NUM);
   header("location:chk_manage_input_table.php?WPNum=".$WPNum);
   exit;
}
elseif (isset($_POST["AddTC"]) ) {
   $WPNum=GetWPNum($_POST["WPNum"],NEXT_FREE_WP_NUM);
   header("location:chk_manage_input_table.php?WPNum=".$WPNum);
   exit;
}
elseif (isset($_POST["submit"]) or isset($_POST["submitWPList"])) {
   $WPNum=$_POST["WPNum"];
   // Check id user has rights to the given access
   if(!UserRights($WPNum,"Checklist")) {
      // header("location:chk_manage_input_table.php?WPNum=$WPNum");
      echo "You have no rights to perform this action";
      exit();
   }
   $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
   $obj->DataBase="Checklist";
   $obj->get_cursor_findone();

   // If WP does not exist then create the new number in the collection
   if(!isset($obj->cursor["WPNum"])) {
      $obj->cursor["WPNum"]=$WPNum;
      $obj->cursor["Show_".$_SESSION['username']]="1";  // make it visible in listing
   }

   // In case description is on heading level 3 store it as a level 3 name i.e. WPName.
   if(strlen($WPNum)==8) $obj->cursor["WPName"]=$_POST["Description"];
   else $obj->cursor["Description"]=$_POST["Description"];
   $obj->cursor["StartDate"]=CheckDateFormat($_POST["StartDate"]);
   $obj->cursor["EndDate"]=CheckDateFormat($_POST["EndDate"]);
   $obj->cursor["Initiator"]=strtoupper($_POST["Initiator"]);
   $obj->cursor["Modified"]=date("Y-m-d");
   $obj->cursor["ModifiedBy"]=$_SESSION["username"];

   $obj->cursor["HighCol"]=$_POST["HighlightColour"];
   $obj->cursor["TextCol"]=$_POST["TextColour"];
   $obj->cursor["SuggestedChange"]=$_POST["SuggestedChange"];
   $obj->cursor["Roles"]=$_POST["Roles"];
   $obj->cursor["Note"]=$_POST["Note"];
   $obj->cursor["Note_".$_SESSION["country"]]=$_POST["NoteCountry"];
   $obj->cursor["References"]=$_POST["References"];
   
   
   // Store site relevance status e.g. EKDK ...
   $arrSites=array("EKDK"=>"dk", "LOWW"=>"at", "EIDW"=>"ie", "EISN"=>"ie", "LDZO"=>"hr", "ESMM"=>"se", "ESOS"=>"se");
   // Set all sites false i.e. no interes
   foreach($arrSites as $s=>$country) {
      $obj->cursor[$s]="F";
   }
   // add only sites of relevance
   if (isset($_POST['sites'])) {
      $optionArray = $_POST['sites'];
      for ($i=0; $i<count($optionArray); $i++) {
         $obj->cursor[$optionArray[$i]]="T";
      }
   }

   // Set $ManHours    
   $_POST["ManHours"]=str_replace(",",".",$_POST["ManHours"]);
   $obj->cursor["ManHours"]=floatval($_POST["ManHours"]);

   // as $obj and $obj3 interact in the same collection it is necessary to save
   // $obj temporarely then wotk on $obj3 before reopening and writing to $obj again
   $obj->save_collection();

   AddHTMLofWPToDB($WPNum,"Checklist","chk");
   UpdateHTMLColoursofSubWPs($WPNum,"Checklist","chk");
   AlignKeyManHoursWithResources($WPNum,"Checklist");
								
   // if other button is hit return to WP List view
   if(isset($_POST["submitWPList"])) {
      header("location:chk_manage_wps.php?ListWPs=1");  
      exit();
   }

}
elseif (isset($_GET["EditRessources"])) {
   $WPNum=$_GET["EditRessources"];
   // Check id user has rights to the given access
   if(!UserRights($WPNum,"Checklist")) {
      header("location:chk_manage_input_table.php?WPNum=$WPNum");  
      exit();
   }
   $obj=new TableHandling($WPNum);
   $obj->Name="Ressources";
   $obj->ReturnScript="chk_manage_input_table.php?EditRessources=$WPNum";
   $obj->arrHeaders=array("Roles"=>"left",   "ReqRes"=>"left",
                           "ResOwn"=>"left",  "ManHours"=>"left",
                           "ManDays"=>"left", "AllRes"=>"left"  );
   $obj->arrHTMLfunctions=array(HTML_INPUT, HTML_INPUT, HTML_INPUT,HTML_INPUT, HTML_INPUT, HTML_INPUT);
?>
 <form action="chk_manage_input_table.php" method="post">
 WP: <input type="text" name="WPNum" size="10" value="<?php echo $WPNum;?>" readonly="readonly"/> 
 
<?php
 // Total Manhours is stored in order to correct the database with the difference from before and after the table has been edited.
 echo 'Total ManHours: <input type="text" name="ManHours" size="10" value="';
 echo $obj->get_total_from_table("ManHours");
 echo '" readonly="readonly"/>';

 echo $obj->get_table_as_html();
?>
<input type="submit" name="EditRessources2" value="Submit"/>
</form>
<?php
exit;
}
elseif (isset($_POST["EditRessources2"])) {
   $WPNum=$_POST["WPNum"];
   $obj=new TableHandling($WPNum);
   $obj->Name="Ressources";
   $obj->ReturnScript="chk_manage_input_table.php?EditRessources=$WPNum";
   $obj->arrHeaders=array("Roles"=>"left",   "ReqRes"=>"left",
                           "ResOwn"=>"left",  "ManHours"=>"left",
                           "ManDays"=>"left", "AllRes"=>"left"  );
   $obj->insert_input_to_table();

   // Correct total ManHours with difference from table
   $obj->obj->cursor["ManHours"]+=($obj->get_total_from_table("ManHours") - floatval($_POST["ManHours"]));
   $obj->obj->save_collection();
   header("location:chk_manage_input_table.php?WPNum=$WPNum");
   exit;
}
elseif(isset($_GET["WPNum"])) {
   $WPNum=$_GET["WPNum"];
}

$obj=new db_handling(array('WPNum' => $WPNum),"Backup");
$obj->DataBase="Checklist";
?>
<form action="chk_manage_input_table.php" method="post">
<b>Checklist:</b>
<?php echo $_SESSION["ProjectName"]."&nbsp;&nbsp;&nbsp;";?>
<b>TC#:</b>
<input type="text" name="WPNum" size="10" value="<?php echo $WPNum;?>" readonly="readonly"/>
<?php
// Colour code chart: http://www.computerhope.com/htmcolor.htm#03
$colours = array(
    'TextCol' => "",
    'Black' => "#000000",
    'White' => "#FFFFFF",
    'Gray' => "#736F6E",
    'Red' => "#FF0000",
    'Fire' => "#C11B17",
    'Orange' => "#FFA500",
    'Green' => "#00FF00",
    'Greed D' => "#387C44",
    'Blue' => "#0000FF",
    'Steel' => "#4863A0",   
    'Yellow' => "#FFFF00"
);

$TextCol=$obj->get_value("TextCol");
if($TextCol=="") $TextCol="Inherit";
echo generateSelect('TextColour', $colours, $TextCol);
$colours = array(
    'HighCol' => "",
    'Black' => "#000000",
    'White' => "#FFFFFF",
    'Red' => "#FF0000",
    'Orange' => "#FFA500",
    'Plum' => "#F9B7FF",
    'Green' => "#00FF00",
    'See Green' => "#C3FDB8",
    'Blue' => "#0000FF",
    'Blue L' => "#BDEDFF",   
    'Yellow' => "#FFFF00",    
    'Khaki' => "#FFF380"
);
$HighCol=$obj->get_value("HighCol");
if($HighCol=="") $HighCol="Inherit";
echo generateSelect('HighlightColour', $colours, $HighCol);

?>
<br>
<?php
$font="<font";
if(isset($obj->cursor['TextCol'])) $font.=' color="'.$obj->cursor['TextCol'].'"';
if(isset($obj->cursor['HighCol'])) $font.=' style="background:'.$obj->cursor['HighCol'].'"';
$font.=">";
echo $font;
// Get headings
$HeadingArray=GetListOfHeadings($WPNum);
for($t=1;$t<count($HeadingArray)+1;$t++) {
   if($t==3) {
      break;
   }
   echo "<b>H".$t.":</b> ".$HeadingArray[$t-1]."&nbsp;&nbsp;";
}
echo "</font>";
?>
<br>
<b>Description:</b><br>
<textarea name="Description" rows="6" cols="70" wrap="soft">
<?php

$Description="";
if($t==3) if(isset($HeadingArray[$t-1])) $Description=$HeadingArray[$t-1];
else $Description=$obj->get_value("Description");
if($Description!="") echo $Description;
?>
</textarea> 

<br>
<b>References:</b><br>
<textarea name="References" rows="1" cols="70" wrap="soft">
<?php
$Ref="";
$Ref=$obj->get_value("References");
echo $Ref;
?>
</textarea> 

<br>
<b>Note: (Applicable to all countries)</b><br>
<textarea name="Note" rows="3" cols="70" wrap="soft">
<?php
$Note=$obj->get_value("Note");
if($Note!="") echo $Note;
?>
</textarea> 

<br>
<b>Note_<?php
echo $_SESSION["country"];
?>
: (Country specific note)</b><br>
<textarea name="NoteCountry" rows="3" cols="70" wrap="soft">
<?php
$NoteCountry=$obj->get_value("Note_".$_SESSION["country"]);
if($NoteCountry!="") echo $NoteCountry;
?>
</textarea> 

<br>
<b>Suggested Change:</b><br>
<textarea name="SuggestedChange" rows="6" cols="70" wrap="soft">
<?php
$change=$obj->get_value("SuggestedChange");
echo $change;
?>
</textarea> 
<br>
<b>Test Case Initiator:</b>
<?php
if($obj->get_value("Initiator")<>"") $Initiator=$obj->get_value("Initiator");
else $Initiator=strtoupper($_SESSION['username']);   
?>
<input type="text" name="Initiator" size="10" value="<?php echo $Initiator;?>"/>
<b>Man Hours:</b>
<input type="text" name="ManHours" size="10" value="<?php echo $obj->get_value("ManHours");?>"/>
<br>
<b>TC Start Date (YYYY-MM-DD):</b><?php
echo '<input type="text" name="StartDate" size="10" value="';
echo $obj->get_value("StartDate");
?>"/>
<b>End date:</b><?php
echo '<input type="text" name="EndDate" size="10" value="';
echo $obj->get_value("EndDate");
?>"/>
<?php

if($obj->get_value("Milestone")=="T") $Milestone='checked="checked"';
else $Milestone='';
echo '<b>Milestone:</b>';
echo '<input type="checkbox" name="Milestone" value="1" '.$Milestone.'/>';

$arrSites=array("EKDK"=>"dk", "LOWW"=>"at", "EIDW"=>"ie", "EISN"=>"ie", "LDZO"=>"hr", "ESMM"=>"se", "ESOS"=>"se");
echo "<br>";
foreach($arrSites as $s=>$country) {
   $chk="";
   if(isset($obj->cursor[$s])) {
      if($obj->cursor[$s]=="T") $chk='checked="checked"';
   } 
   echo $s.":";
   echo '<input type="checkbox" name="sites[]" value="'.$s.'"'.$chk.'/>&nbsp;';
}
echo "<br>";
// Roles
$Roles=$obj->get_value("Roles");
echo '<span style="color:#C00000"; title="ACC, APP, COIF, DACOSY, EXP, FDA, MIL, NITOS, OCEANIC, REDMOD, SUP, TWR, TWR_SID.">Roles (e.g. "ACC, TWR"): </span>';
echo '<input type="text" name="Roles" size="'.(strlen($Roles)+6).'" value="';
echo $Roles;
?>"/>
<?php
echo "<br>Last modified by: ".$obj->get_value("ModifiedBy")." ".$obj->get_value("Modified");
?>
<br>
<input type="submit" name="submitWPList" value="Submit->TC List View"/>
<input type="submit" name="submit" value="Submit"/>
<?php
// only view Add New TC button if WP number contains 3 levels i.e. > 6 character e.g. 07.01.22 
if(strlen($WPNum)>6)
   echo '<input type="submit" name="AddTC" value="Add New TC"/>';
?>
<input type="submit" name="GetPreviousWP" value="<<<"/>
<input type="submit" name="GetNextWP" value=">>>"/>Submit before moving bck/fwd - if any changes!!
</form>
<font style="BACKGROUND-COLOR: yellow">
Warning! </font>If you have made no changes then do NOT submit. Use browser backward or Alt-"cursor left". Otherwise you will change the "Modified By" status!

<?php 


?>

<!--
<div class="row">
      <span class="label">Select Text</span>
    <span class="box"><input type="text" name="data" class="datatext" id="datatext"><select class="contentselect" id="contentselect"><option></option><option value="one">test1</option><option value="two">test2</option></select></span>
    </div>
-->

<script>
// http://jsfiddle.net/xFQMf/3/
textfield = document.getElementById("datatext");
contentselect = document.getElementById("contentselect");

contentselect.onchange = function(){
        var text = contentselect.options[contentselect.selectedIndex].value
        textfield.value = text;
         //if(text != ""){
         //    textfield.value = text; // textfield.value += text;
         //}
    }
</script>

<?php

exit;

?>

<?php

function GetListOfHeadings($WPNum) {

   $s="";
   $ListOfHeading=array();
   $WPNum.=".";
   for($t=0;$t<strlen($WPNum);$t++) {
      if(substr($WPNum,$t,1)==".") {
         $obj=new db_handling(array("WPNum"=>$s),"Backup");
         $obj->DataBase="Checklist";
         $obj->get_cursor_findone();
         $ListOfHeading[]=$obj->cursor["WPName"];
      }
      $s.=substr($WPNum,$t,1);
   }
   return ($ListOfHeading);
}

function ListRessources($WPNum) {
   $obj=new TableHandling($WPNum);
   $obj->Name="Ressources";
   $obj->ReturnScript="chk_manage_input_table.php?WPNum=$WPNum";
   $obj->arrHeaders=array("Roles"=>"left",   "ReqRes"=>"left",
                           "ResOwn"=>"left",  "ManHours"=>"left",
                           "ManDays"=>"left", "AllRes"=>"left"  );
   echo $obj->get_table_as_html();
   // Make possible to edit ressource table by following link
   // only show link if a table with content exist.
   if(isset($obj->obj->cursor["Ressources"])) {
      if($obj->obj->cursor["Ressources"] != array()) {
         echo '<a href="chk_manage_input_table.php?EditRessources='.$WPNum.'">Edit Ressource Table</a>';
      }
   }
return;

   $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
   $obj->get_cursor_findone();
   // find next free ressource number
   $TableKeyArray=array("Roles", "ReqRes", "ResOwn", "ManHours", "ManDays", "AllRes"); 
   $TableTextArray=array("Roles", "Requested<br>ressource", "Ressource<br>owner", "Man<br>Hours", "Man<br>Days", "Allocated<br>ressource");
   echo '<table border="1">';
   echo '<tr>';
      for($u=0;$u<count($TableKeyArray);$u++) {
         echo '<th align="left">';
         echo $TableTextArray[$u];
         echo "</th>";
      }
   echo '</tr>';
   for($t=0;$t<999;$t++) {
      $Res="Res".str_pad($t,3,"0",STR_PAD_LEFT);
      if(isset($obj->cursor[$Res])) {
         echo '<tr>';
         echo WriteTdAligned($obj->cursor[$Res][$TableKeyArray[0]],"left");
         echo WriteTdAligned($obj->cursor[$Res][$TableKeyArray[1]],"left");
         echo WriteTdAligned($obj->cursor[$Res][$TableKeyArray[2]],"left");
         $num=floatval($obj->cursor[$Res][$TableKeyArray[3]]);
         echo WriteTdAligned(number_format($num, 0, '.', ''),"right");
         $num=floatval($obj->cursor[$Res][$TableKeyArray[4]]);
         echo WriteTdAligned(number_format($num, 1, '.', ''),"right");
         echo WriteTdAligned($obj->cursor[$Res][$TableKeyArray[5]],"left");
         // make delete link
         echo "<td>";
         echo '<a href="chk_manage_input_table.php?DelRole='.$Res.'&WPNum='.$WPNum.'">del</a>';
         echo "</td>";
         echo '</tr>';
      }
      else break;   
   }
   echo '</table>';
}

function WriteTdAligned($val,$align) {
   if($val!="") return ('<td align="'.$align.'">'.$val."</td>");
   else return ('<td align="center">-</td>');
}

function InsertRessourceOwner($role) {
   // Lookup in the "Admin" collection for the given role and return its Ressource Owner if any
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Roles";
   foreach ($obj->obj->cursor["Roles"] as $o) {
      if($o["Role"]==$role) return $o["ResOwn"];
   }
   return "";
}

function GetWPNum($CurrentWPNum,$direction) {
   $db_name="Checklist"; // Database name
   $tbl_name=$_SESSION["ProjectName"]; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);

   // Find and return next free WPNum (Actual number + 2) at given level
   if($direction==NEXT_FREE_WP_NUM) {
      $arrWP=explode(".",$CurrentWPNum);
      $WP=$arrWP[0].".".$arrWP[1];  // e.g. "07.01" coming from e.g. "07.01.03";
      // find all documents >= FirstTCNum and less than the last possible TC number 
      // i.e. e.g. "07.01.99"
      $query=array('$and' => array(
               array('WPNum' => array('$gte' => $CurrentWPNum )),
               array('WPNum' => array('$lte' => "$WP.99" ))
                                    )
                   );
      $cursor=$collection->find($query)->sort(array("WPNum" => -1));
      $obj=$cursor->getNext();
      $LastWPNum=$obj['WPNum'];
      $arrWP=explode(".",$LastWPNum);
      $Count=intval($arrWP[2]);
      $Count+=2;
      if($Count>99) {
         echo "<b>TC number larger than 99 e.g. 07.01.100 is not allowed</b>";
         exit;
      }
      $NewWPNum=$arrWP[0].".".$arrWP[1].".".str_pad(strval($Count),2,"0",STR_PAD_LEFT);
      return($NewWPNum);
   }

   // Get all WP and sort them accending
   $cursor=$collection->find()->sort(array("WPNum" => 1));

//echo $CurrentWPNum;
//echo "<br>";

   $ReturnNext=false;
   while( $cursor->hasNext()) {
      $obj=$cursor->getNext();
      $WPNum=$obj['WPNum'];

//echo $WPNum;

      // if requested return WP number after $CurrentWPNum
      if($ReturnNext==true) {
//echo "ee3ee";
         if($WPNum==array()) return $CurrentWPNum;
         else return $WPNum;
      }
      if($WPNum==$CurrentWPNum) {
//echo "ee2ee";
         // if requested return WP number before $CurrentWPNum
         if($direction==PREVIOUS_WP_NUM) {
            if($OldWPNum==array()) return $CurrentWPNum;
            else return $OldWPNum;
         }
         // make sure that number is returned after next loop iteration
         if($direction==NEXT_WP_NUM) $ReturnNext=true;
      }
      $OldWPNum=$WPNum;
   }
//   echo "ee1ee";
   return $CurrentWPNum;
}
?>



</body>
</html>
