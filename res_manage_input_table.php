<?php include("login_headerscript.php"); ?>
<?php

define('_GLOBAL_TABLE', '_Admin_');  // This is used for the Admin collection to store tables that must be global/common for all users. A document is created with key "User" = "_Admin_"
define('HTML_INPUT', 1);
define('HTML_CHECK', 2);
define('HTML_TEXT', 3);


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
<!--  www.bulgaria-web-developers.com/projects/javascript/calendar/  
<link type="text/css" rel="stylesheet" href="./calendar.css" />   -->


<!--
http://freqdec.github.io/datePicker/
http://freqdec.github.io/datePicker/demo/
https://github.com/freqdec/datePicker
-->
<script src="./datepicker.min.js">{"describedby":"fd-dp-aria-describedby"}</script>
<link href="./datepicker.min.css" rel="stylesheet" type="text/css" />

<?php include("class_lib.php"); ?>
<?php
if(isset($_GET["WPNum"]) or isset($_GET["EditRessources"])) {
   if(isset($_GET["EditRessources"])) $Header=$_GET["EditRessources"];
   elseif(isset($_GET["WPNum"])) $Header=$_GET["WPNum"];   
   $obj=new db_handling(array('WPNum' => $Header),"Backup");
   $obj->get_cursor_findone();
   if(isset($obj->cursor["StartDate"])) $Header.=" : ".$obj->cursor["StartDate"];
   echo "<title>".$Header."</title>";
}
else echo "<title>Manage Work Packages</title>";
?>

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




<script>
function GetFromDB(Id, DB, Collection, key, val, SearchKey)
{

   var xmlhttp;
   xmlhttp=new XMLHttpRequest();
   xmlhttp.onreadystatechange=function()
   {
      if (xmlhttp.readyState==4 && xmlhttp.status==200)
      {
alert("hha:"+xmlhttp.responseText);
         document.getElementById(Id).innerHTML=xmlhttp.responseText;
      }
   }
alert("g");
   xmlhttp.open("GET","db_collection_key_response.php?DB="+DB+"&Collection="+Collection+"&key="+key+"&val="+val+"&SearchKey="+SearchKey,true);
   xmlhttp.send();
}

</script>






</head>
<body>
<?php include("res_headerline.html"); ?>
<?php

if (isset($_POST["submit"]) or isset($_POST["submitWPList"])) {
   $WPNum=$_POST["WPNum"];
   $_SESSION["DefaultManDays"]=$_POST["ManDays"];
   // Check id user has rights to the given access
   if(!UserRights($WPNum)) {
      // header("location:res_manage_input_table.php?WPNum=$WPNum");
      echo "You have no rights to change information in this WP"; 
      exit;
   }
   // Get and insert Resource Owner if not already given
   if(trim($_POST["ResOwn"])=="") $_POST["ResOwn"]=InsertRessourceOwner($_POST["Roles"]);

   $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
   $obj->get_cursor_findone();

   // If WP does not exist then create the new number in the collection
   if(!isset($obj->cursor["WPNum"])) {
      $obj->cursor["WPNum"]=$WPNum;
      $obj->cursor["Show_".$_SESSION['username']]="1";  // make it visible in listing
   }

   // Only get value of PSP key if it is not inherited from super level
   if(substr($_POST["PSPNaviair"],0,1)!="#") $obj->cursor["PSPNaviair"]=strtoupper($_POST["PSPNaviair"]);
   if(substr($_POST["PSPNuac"],0,1)!="#") $obj->cursor["PSPNuac"]=strtoupper($_POST["PSPNuac"]);
   if(isset($_POST["Milestone"])) $obj->cursor["Milestone"]="T";
   else $obj->cursor["Milestone"]="F";

   $obj->cursor["Description"]=$_POST["Description"];
   
   // In order to save processing time Dates in super WPs are only updated if changes happen
   if(!isset($obj->cursor["StartDate"])) $obj->cursor["StartDate"]="";
   if(!isset($obj->cursor["EndDate"])) $obj->cursor["EndDate"]="";
   if($_POST["StartDate"]<>$obj->cursor["StartDate"] or $_POST["EndDate"]<>$obj->cursor["EndDate"]) {
      $MarkDateChanged=true;
   }
   else $MarkDateChanged=false;
   $obj->cursor["StartDate"]=CheckDateFormat($_POST["StartDate"]);
   $obj->cursor["EndDate"]=CheckDateFormat($_POST["EndDate"]);
   if(CheckDateFormat($_POST["EndDate"])=="") $obj->cursor["EndDate"]=$obj->cursor["StartDate"];
   

   $obj->cursor["Initiator"]=strtoupper($_POST["Initiator"]);
   $obj->cursor["Tags"]=$_POST["Tags"];

   $obj->cursor["HighCol"]=$_POST["HighlightColour"];
   $obj->cursor["TextCol"]=$_POST["TextColour"];

   // Set $ManHours based on either input in ManHours or Mandays.
   $_POST["ManDays"]=str_replace(",",".",$_POST["ManDays"]);
   $_POST["ManHours"]=str_replace(",",".",$_POST["ManHours"]);
   if($_POST["ManDays"]!="") $ManHours=floatval($_POST["ManDays"])*7.4;
   else if($_POST["ManHours"]!="") $ManHours=floatval($_POST["ManHours"]);
   else $ManHours=0.0;
   $_POST["ManDays"]=$ManHours/7.4;
   $_POST["ManHours"]=$ManHours;

   // as $obj and $obj3 interact in the same collection it is necessary to save
   // $obj temporarely then wotk on $obj3 before reopening and writing to $obj again
   $obj->save_collection();

   if(trim($_POST["Roles"])!="") {  // If roles exist then add resource
      $obj3=new TableHandling($WPNum);
      $obj3->Name="Ressources";
      if(substr($_POST["Roles"],0,1)=="#") { // Role is a team (starts with '#') insert all members
         $TeamName="Team_".substr($_POST["Roles"],1);  // insert team header and get rid off "#"
         // insert team members into resource list
         $obj2=new db_handling(array('User' => $_SESSION["username"]),"Backup");
         $obj2->CollectionName="Admin";
         $obj2->get_cursor_findone();
         $arrTeam=$obj2->cursor[$TeamName]; // array of required team
         foreach ($arrTeam as $v) {
            $arrArrayToInsert =
                array("Roles"=>$v["Role"],"ReqRes"=>$v["ReqRes"], "ResOwn"=>$v["ResOwn"], "ManHours"=>$v["ManHours"], "ManDays"=>$v["ManDays"], "AllRes"=>$v["AllRes"]);
            $obj3->insert($arrArrayToInsert);
         }
      }
      else {  // Role is not a team, insert resource line        
         $arrArrayToInsert =
           array(  "Roles"=>strtoupper($_POST["Roles"]),
                    "ReqRes"=>strtoupper($_POST["ReqRes"]),
                    "ResOwn"=>strtoupper($_POST["ResOwn"]),
                    "ManHours"=>$_POST["ManHours"],
                    "ManDays"=>$_POST["ManDays"], 
                    "AllRes"=>strtoupper($_POST["AllRes"])  );
         
         // Add ManHours to total ManHours of cursor (just called ManHours as well)
         // Note!! it is added to $obj3 and not $obj. This is due to that they both
         // go into the same collection and could potentially interact making
         // funny results. Solution is to tweak $obj3 which already point to the
         // same object.
         if(isset($obj3->obj->cursor["ManHours"])) $obj3->obj->cursor["ManHours"]+=$ManHours;
         else $obj3->obj->cursor["ManHours"]=$ManHours;  // if not initialised, then initialise index
         $obj3->insert($arrArrayToInsert);
      }
   }
   
   // Fix all WP levels of Start and End Dates if date is changes
   // Code must be placed after new dates are stored into the DB
   if($MarkDateChanged) {
      $WPLevels=explode(".",$WPNum);
      FindMinMaxOfSubWPs($WPLevels[0]);  // only submit changes to super level of WP e.g. '23'
      //FindMinMaxOfSubWPs("");
   }
    
   AddHTMLofWPToDB($WPNum);
   UpdateHTMLColoursofSubWPs($WPNum);
   AlignKeyManHoursWithResources($WPNum);
    
   // if other button is hit return to WP List view
   if(isset($_POST["submitWPList"])) {
      header("location:res_manage_wps.php?ListWPs=1");  
      exit();
   }
}
elseif (isset($_GET["EditRessources"])) {
   $WPNum=$_GET["EditRessources"];
   // Check id user has rights to the given access
   if(!UserRights($WPNum)) {
      header("location:res_manage_input_table.php?WPNum=$WPNum");  
      exit();
   }
   $obj=new TableHandling($WPNum);
   $obj->Name="Ressources";
   $obj->ReturnScript="res_manage_input_table.php?EditRessources=$WPNum";
   $obj->arrHeaders=array("Roles"=>"left",   "ReqRes"=>"left",
                           "ResOwn"=>"left",  "ManHours"=>"left",
                           "ManDays"=>"left", "AllRes"=>"left"  );
   $obj->arrHTMLfunctions=array(HTML_INPUT, HTML_INPUT, HTML_INPUT,HTML_INPUT, HTML_INPUT, HTML_INPUT);

   // Make Allocate Resource view if requested
   if (isset($_GET["AllRes"])) {
      $obj->arrHTMLfunctions=array(HTML_TEXT, HTML_TEXT, HTML_TEXT,HTML_TEXT, HTML_TEXT, HTML_INPUT);
      $obj->ShowRemoveEntry=False;
   }

?>
 <form action="res_manage_input_table.php" method="post">
 <b>WP:</b> <input type="text" name="WPNum" size="10" value="<?php echo $WPNum;?>" readonly="readonly"/> 
 
<?php
 // Total Manhours is stored in order to correct the database with the difference from before and after the table has been edited.
 echo '<b>Total ManHours:</b> <input type="text" name="ManHours" size="10" value="';
 echo $obj->get_total_from_table("ManHours");
 echo '" readonly="readonly"/>';

 $obj2=new db_handling(array('WPNum' => $WPNum),"Backup");
 echo "<br><b>Heading:</b> ".$obj2->get_value("WPName");
 echo " <b>Start date:</b> ".$obj2->get_value("StartDate");
 echo " <b>End date:</b> ".$obj2->get_value("EndDate");
 
 
 $arrSubjAccepted=$obj2->get_value("SubjAccepted");
 if($arrSubjAccepted<>"" && $arrSubjAccepted<>NULL) {
    echo "<br><b>Subjects Accepted: </b>";
    foreach($arrSubjAccepted as $subj=>$v) {
       if(SubjectAllocatedAsResource($subj,$obj2)) echo "<u>".$subj."</u>".", ";
       else echo $subj.", ";
    }
 }

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
   $obj->ReturnScript="res_manage_input_table.php?EditRessources=$WPNum";
   $obj->arrHeaders=array("Roles"=>"left",   "ReqRes"=>"left",
                           "ResOwn"=>"left",  "ManHours"=>"left",
                           "ManDays"=>"left", "AllRes"=>"left"  );
   $obj->insert_input_to_table();

   // Correct total ManHours with difference from table
   $obj->obj->cursor["ManHours"]+=($obj->get_total_from_table("ManHours") - floatval($_POST["ManHours"]));
   $obj->obj->save_collection();
   header("location:res_manage_input_table.php?WPNum=$WPNum");
   exit;
}
elseif(isset($_GET["WPNum"])) {
   $WPNum=$_GET["WPNum"];
}

$obj=new db_handling(array('WPNum' => $WPNum),"Backup");
?>
<form action="res_manage_input_table.php" method="post">
<b>Project</b>:
<?php echo $_SESSION["ProjectName"]."&nbsp;&nbsp;&nbsp;";?>
<b>WP#</b>:
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
   echo "<b>H".$t.":</b> ".$HeadingArray[$t-1]."&nbsp;&nbsp;";
}

echo "</font>";
?>

<br>
<b>PSP Naviair</b>:<?php
echo '<input type="text" name="PSPNaviair" size="16" value="';
// If no value in key, inhereit key from super level and mark it by leading "#"
if(!IsKeySet('PSPNaviair',$WPNum)) echo "#";
echo GetSuperLevelValue('PSPNaviair',$WPNum);
?>"/>
<b>PSP NUAC</b>:<?php
echo '<input type="text" name="PSPNuac" size="16" value="';
// If no value in key, inhereit key from super level and mark it by leading "#"
if(!IsKeySet('PSPNuac',$WPNum)) echo "#";
echo GetSuperLevelValue('PSPNuac',$WPNum);
$Description=$obj->get_value("Description");
$NumOfRows=substr_count($Description, "\n")+2;
if($NumOfRows>29) $NumOfRows=29;
if($NumOfRows<10) $NumOfRows=10;
$Link=trim($obj->get_value("Link"));
$LinkText=$obj->get_value("LinkText");
if($LinkText=="") $LinkText="No Link Text";
?>"/>
<a href="psp_elements.html">PSP-info</a>
<br>
<b>Description:</b><br>
<textarea name="Description" rows="<?php echo $NumOfRows; ?>" cols="95" wrap="soft">
<?php
// hhh
if($Description!="") echo $Description;
?>
</textarea> 
<br>
<?php
if($Link<>"") echo "<b>Link:</b> ".'<a style="color:#0000FF;" href="'.$Link.'" target="_blank">'.$LinkText.'</a><br>'
?>

<b>WP Initiator:</b>
<?php
if($obj->get_value("Initiator")<>"") $Initiator=$obj->get_value("Initiator");
else $Initiator=strtoupper($_SESSION['username']);   
?>
<input type="text" name="Initiator" size="10" value="<?php echo $Initiator;?>"/>
<b>Tags:</b>
<?php
if($obj->get_value("Tags")<>"") $Tags=$obj->get_value("Tags");
else $Tags="";   
$obj=new db_handling(array('WPNum' => $WPNum),"Backup");
$StartDate=$obj->get_value("StartDate");
$EndDate=$obj->get_value("EndDate");
?>
<input type="text" name="Tags" size="30" value="<?php echo $Tags;?>"/>
<br>
<b>WP start date (YYYY-MM-DD)</b>:
<input type="text" name="StartDate" id="calendar_1" size="10"/>
<b>End date</b>:
<input type="text" name="EndDate" id="calendar_2" size="10"/>
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


<?php
//echo '<script>';
//echo 'GetFromDB("calendar_1", "Projects", "COOPANS", "WPNum", "'.$StartDate.'", "StartDate")';
//echo '</script>';

?>

<!-- 

<script src="./calendar-1.5.js"></script>
<script>  
var cal_1 = new Calendar({
   element: 'calendar_1',
   weekNumbers: true,
   startDay: 1,
   months: 6,
   minDate: new Date(new Date().getTime() - 60*60*24*1000),
   onOpen: function (element) {
	   //do something
   }
});

var cal_2 = new Calendar({
   element: 'calendar_2',
   weekNumbers: true,
   startDay: 1,
   months: 6,
   minDate: new Date(new Date().getTime() - 60*60*24*1000),
   onOpen: function (element) {
	   //do something
   }
});
</script>

-->

<?php
// Note these getelementbyid lines must come after the scripts otherwise it will not work.
// Also note that input text boxes are not allowed to have value="something" as initial
// setting. This is why a script is used to insert values. 
echo '<script>document.getElementById("calendar_1").value = "'.$StartDate.'"</script>';
echo '<script>document.getElementById("calendar_2").value = "'.$EndDate.'"</script>';

if($obj->get_value("Milestone")=="T") $Milestone='checked="checked"';
else $Milestone='';
echo '<b>Milestone:</b>';
echo '<input type="checkbox" name="Milestone" value="1" '.$Milestone.'/>';
?>
<br>
<font size="-1.5">(If start- and end date are the same, just enter start date. Dates can be entered as eg. '20140920' = '2014-09-20')</font>

<?php
$arrSubjAccepted=$obj->get_value("SubjAccepted");
if($arrSubjAccepted<>"" && $arrSubjAccepted<>NULL) {
   echo "<br><b>Subjects Accepted: </b>";
   foreach($arrSubjAccepted as $subj=>$v) {
      if(SubjectAllocatedAsResource($subj,$obj)) echo "<u>".$subj."</u>".", ";
      else echo $subj.", ";
   }
}
?>
<br><b>Resources</b>:
<?php
ListRessources($WPNum);
echo "<br>";

$TableTextArray=array("Roles", "Requested<br>resource", "Resource<br>owner", "Man<br>Hours", "Man<br>Days", "Allocated<br>resource");
$TableKeyArray=array("Roles", "ReqRes", "ResOwn", "ManHours", "ManDays", "AllRes");    

// Insert Teams into roles selection list
$obj2=new db_handling(array('User' => $_SESSION["username"]),"Backup");
$obj2->CollectionName="Admin";
$obj2->get_cursor_findone();
$RolesArray=array();
if(isset($obj2->cursor)) {
   foreach ($obj2->cursor as $k=>$v) {
      if(substr($k,0,5)=="Team_") $RolesArray[]="#".substr($k,5);
   }
}
// Get list of roles from collection 'Admin' in relation to 'User'='_Admin_' i.e. global user.
$obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
$obj->Name="Roles";
$arrRole=$obj->obj->cursor["Roles"];
// Take out roles and list them in $RolesArray.
foreach($arrRole as $i) {
   $RolesArray[]=$i["Role"];
}
sort($RolesArray);

echo '<table border="1">';
echo "<tr>";
for($t=0;$t<count($TableTextArray);$t++) {
   echo '<th align="left">';
   echo $TableTextArray[$t];
   echo "</th>";
}
echo "</tr>";

echo "<tr>";
echo '<td align="left">';
?>
<div class="row">
      <span class="label"></span>
    <span class="box"><input type="text" name="Roles" class="datatext" id="datatext"><select class="contentselect" id="contentselect">
<option>Find Role</option>
<?php
for($t=0;$t<count($RolesArray);$t++) {
   echo '<option value="'.$RolesArray[$t].'">'.$RolesArray[$t].'</option>';
}
?>
</select></span>
    </div>
<?php
echo "</td>";

for($t=1;$t<count($TableKeyArray);$t++) {
   echo '<td align="left">';
   if($TableKeyArray[$t]=="ManDays") {
      if(isset($_SESSION["DefaultManDays"])) {
         $Default=$_SESSION["DefaultManDays"];
      }
      else $Default="";
   }
   else $Default="";
   echo '<input type="text" name="'.$TableKeyArray[$t].'" size="6" value="'.$Default.'"/>';
   echo "</td>";
}
echo "</tr>";
?>
</table>
<br>
<input type="submit" name="submit" value="Add Role / Submit"/>
<input type="submit" name="submitWPList" value="Add Role / Submit / WP List View"/>
</form>


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

//SaveDB();
//FindDB();
$a=array();
$a[]="lasse";
$a[]="peter";
$a[]="thomas";
StoreTableRowCol("Brothers", "MyTable", $a, "01.10"); 
$a=array();
$a[]="apples";
$a[]="oranges";
$a[]="banana";
StoreTableRowCol("Fruit", "MyTable", $a, "01.10"); 
$a=array();
$a[]="bord";
$a[]="stole";
$a[]="sofaer";
StoreTableRowCol("Moebler", "MyTable", $a, "01.10"); 
//echo "juhu###<br>";
//print_r(GetTableRowCol("Brothers", "MyTable"));
$KeysToGetArray=array();
$KeysToGetArray[]="Brothers";
$KeysToGetArray[]="Fruit";
$KeysToGetArray[]="Moebler";
echo CreateTableInHtml($KeysToGetArray, "MyTable");

?>

<?php

function GetListOfHeadings($WPNum) {

   $s="";
   $ListOfHeading=array();
   $WPNum.=".";
   for($t=0;$t<strlen($WPNum);$t++) {
      if(substr($WPNum,$t,1)==".") {
         $obj=new db_handling(array("WPNum"=>$s),"Backup");
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
   $obj->ReturnScript="res_manage_input_table.php?WPNum=$WPNum";
   $obj->arrHeaders=array("Roles"=>"left",   "ReqRes"=>"left",
                           "ResOwn"=>"left",  "ManHours"=>"left",
                           "ManDays"=>"left", "AllRes"=>"left"  );
   echo $obj->get_table_as_html();
   // Make possible to edit resource table by following link
   // only show link if a table with content exist.
   if(isset($obj->obj->cursor["Ressources"])) {
      if($obj->obj->cursor["Ressources"] != array()) {
         echo '<a href="res_manage_input_table.php?EditRessources='.$WPNum.'">Edit Resource Table</a>';
         echo ' &nbsp;&nbsp; <a href="res_manage_input_table.php?EditRessources='.$WPNum.'&AllRes=1">Allocate Resources</a>';
      }
   }
return;



   $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
   $obj->get_cursor_findone();
   // find next free resource number
   $TableKeyArray=array("Roles", "ReqRes", "ResOwn", "ManHours", "ManDays", "AllRes"); 
   $TableTextArray=array("Roles", "Requested<br>resource", "Resource<br>owner", "Man<br>Hours", "Man<br>Days", "Allocated<br>resource");
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
         echo '<a href="res_manage_input_table.php?DelRole='.$Res.'&WPNum='.$WPNum.'">del</a>';
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
   // Lookup in the "Admin" collection for the given role and return its Resource Owner if any
   $obj=new TableHandling(_GLOBAL_TABLE,"User","Admin","Projects");
   $obj->Name="Roles";
   foreach ($obj->obj->cursor["Roles"] as $o) {
      if($o["Role"]==$role) return $o["ResOwn"];
   }
   return "";
}


// **********************************
// NOT USED ANYMORE the following....
// **********************************

// Used in more routines
function SaveDB() {
   $db_name="Projects"; // Database name
   $tbl_name="Arrays"; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   
   $array=array ("NameTable"=>"NameTable",
                 "NameCol1"=>array("Peter","Lasse","Thomas"),
                 "NameCol2"=>array("Ole","Janne","Hans"),
                 "NameCol3"=>array("Jindra","Birk","Johan"));

   $cursor = $collection->findone(array("TableName" => "Test"));
   $cursor['TableName']="Test";
   $cursor['FFF']=$array;
   $collection->save($cursor);
}
function FindDB() {
   $db_name="Projects"; // Database name
   $tbl_name="Arrays"; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   //$cursor = $collection->findone(array("TableName" => "Test", "FFF.NameCol1" => "Peter"));
   $cursor = $collection->findone(array("FFF.NameTable" => "NameTable"));
   echo "<br>##";
   foreach($cursor as $obj) {
     print_r($obj);
   }
   $array=$cursor['FFF']['NameCol1'];
   echo "<br>##";
   print_r($array);
}

function StoreTableRowCol($RowColName, $TableName, $RowColArray, $WPNum) {
  // The function stores the array $RowColArray in DB Projects collection "Tables"
  // $TableName creates a document (of that name) and store the $RowColArray with key name $RowColName
   $db_name="Projects"; // Database name
   $tbl_name=$_SESSION["ProjectName"]; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $cursor = $collection->findone(array("$TableName.$TableName" => $TableName));
//   $cursor = $collection->findone(array("WPNum"=>$WPNum)); 
   $array=array_merge(array($TableName=>$TableName), array($RowColName=>$RowColArray) );
   $cursor[$TableName]=array_merge($cursor[$TableName],$array);
   $collection->save($cursor);
}
function GetTableRowCol($RowColName, $TableName, $WPNum) {
  // The function gets the array $RowColArray in DB Projects collection "Tables"
  // $TableName name of table
  // Returns an array containing the row or column
  // If $RowColName = "" then the entire array (all columns/rows) of $TableName is returned.
   $db_name="Projects"; // Database name
   $tbl_name=$_SESSION["ProjectName"]; // Collection name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $cursor = $collection->findone(array("WPNum"=>$WPNum, "$TableName.$TableName" => $TableName));
   if($RowColName=="") return $cursor[$TableName];  // return entire array
   else return $cursor[$TableName][$RowColName];    // return column or row
}
function CreateTableInHtml($KeysToGetArray, $TableName) {
  // The function creates a table with columns as indicated in array $KeysToGetArray
  // $TableName = name of table to be looked up in DB
  // The table is returned in html format.
   $html="";
   $TableArray=GetTableRowCol("", $TableName);
   $NumOfTableEntries=count($TableArray[$KeysToGetArray[0]]);  // count how many entried in first column and assume it is the same for the rest of the table. THIS COULD BE FAULTY CODE
   $html='<table>';
   for($i=0;$i<$NumOfTableEntries;$i++) {
      $html.="<tr>";
      for($t=0;$t<count($KeysToGetArray);$t++) {
         $html.="<td>";
         $html.=$TableArray[$KeysToGetArray[$t]][$i];
         $html.="</td>";  
      }
      $html.="</tr>";
   }   
   $html.="</table>";
return $html;
}
function GetKeyFromDocumentInDB($key,$WPNum,$CollectionName) {
   // Will return the value of the required Key in relation to
   // given HeadingNumber/WPNum and Collection
   $db_name="Projects"; // Database name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $CollectionName);
   $cursor = $collection->findone(array("WPNum" => $WPNum, "Backup" => array('$exists' => false)));
   if(isset($cursor[$key])) return $cursor[$key];
   else return "";
}
function DeleteKeyFromDB($key,$WPNum,$CollectionName) {
   // Will take out the given key of a collection
   $db_name="Projects"; // Database name
   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $CollectionName);
   $cursor = $collection->findone(array("WPNum" => $WPNum, "Backup" => array('$exists' => false)));
//echo $key."#".$WPNum."#".$CollectionName."#";
//print_r($cursor);
   unset($cursor[$key]);
//print_r($cursor);
//exit;
   $collection->save($cursor);
}
function SubjectAllocatedAsResource($subj,&$obj2)
// Check in Ressource list if Resource is already there and if is it returns true
// otherwise false.
{
   if(isset($obj2->cursor["Ressources"])) {
      foreach($obj2->cursor["Ressources"] as $arrRes) {
         if(isset($arrRes["AllRes"])) {
            if($arrRes["AllRes"]==$subj) return true;
         }
      }
   }
   return false;
}
?>
</body>
</html>

