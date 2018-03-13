<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>
<?php

if(isset($_POST["ChangeFramesize"])) {
   $_SESSION["framesize"]=$_POST["framesize"];
   header("location:search_input.php");
   exit;
}


?>



<style type="text/css">
a:link{
  color:#0000CC;
}
a:visited{
  color:#0000CC;
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

<title>PCR search</title>
</head>
<body>

<script>
function check(Id)  // Id is either '1' or '2' dependent on build 1,2,3,4,5
// NO LONGER USED !!
{
   alert(Id);
   var xmlhttp;
   xmlhttp=new XMLHttpRequest();
   xmlhttp.onreadystatechange=function()
   {
      if (xmlhttp.readyState==4 && xmlhttp.status==200)
      {
         //document.getElementById(Id).innerHTML=xmlhttp.responseText;
         txt=xmlhttp.responseText;
         //alert(txt);
         if(Checked=="yes") Check=true;
         else Check=false;
         // if returned text contains "error" then something is wrong storing into database
         if(txt.contains("error")) alert("The database seems not to communicate, Please try reload the page (F5) and try again");
      }
   }
   // Prepare sending information $_GET whatever checkbox is checked or not
   if(document.getElementById("Build"+Id).checked) {
      Checked="yes";
      
   }
   else {
      Checked="no";
   }
   xmlhttp.open("GET","search_input_response.php?SetBuild="+Id+"&Checked="+Checked);
   xmlhttp.send();
}

function StoreTextInDB(strVariable,Id)  // Id is string and e.g. '02' pointing to e.g. id=TextInput01
{
   var xmlhttp;
   xmlhttp=new XMLHttpRequest();
   strValue = document.getElementById('TextInput'+Id).value;
   //alert(strVariable+"   "+Id+"   "+strValue);
   xmlhttp.open("GET","search_input_response.php?SetVarInDB="+strVariable+"&Value="+strValue);
   xmlhttp.send();
}
function ClearForms(Id)  
{
   //alert(Id);
   if(Id==0) {
      document.getElementById("ATMFreeKeywords").value="";
      document.getElementById("Headline").value="";
      document.getElementById("TotalSearch").value="";
   }
   else if(Id==1) {
      document.getElementById("PCRNum").value="";
      document.getElementById("Headline").value="";
      document.getElementById("TotalSearch").value="";
   }
   else if(Id==2) {
      document.getElementById("ATMFreeKeywords").value="";
      document.getElementById("PCRNum").value="";
      document.getElementById("TotalSearch").value="";
   }
   else if(Id==3) {
      document.getElementById("ATMFreeKeywords").value="";
      document.getElementById("Headline").value="";
      document.getElementById("PCRNum").value="";
   }     
   //alert(strVariable+"   "+Id+"   "+strValue); TotalSearch Headline PCRNum

}


</script>
<?php

$_SESSION["query"]="";  // coming from csv download and could be large and therefore necessary to delete
// MUST BE REMOVED
//echo session_save_path();
//session_destroy();
//exit(0);

// Store where to start up next time
StoreAdminData($_SESSION['username']."_InitialStartup","PCR");

$db_name="testdb"; // Database name
$tbl_name="pcr"; // Collection name

$m = new MongoClient();
$db = $m->$db_name;
$collection = new MongoCollection($db, $tbl_name);
?>

<?php
// Gets a variable from session if available else it returns an empty variable
// Variables are trimmed, session stored with "" if not exisitng
function GetVarFromSession($var)
{
   if(isset($_SESSION[$var])) $var=trim($_SESSION[$var]);
   else {
      $var="";
      $_SESSION[$var]="";
   }
   return $var;
}
?>


<?php
function WriteDropDownTableOfPCRdb($collection, $id)
{

   // Getting the dropdown table takes time so it is stored in session if not already in.
   if (isset($_SESSION["dropdownfilterkey_$id"])) {
       $dropdowntext=$_SESSION["dropdownfilterkey_$id"];
   }
   else {
      // Take username out of mongodb collection
//      $cursor = $collection->find();

//      $array = iterator_to_array($cursor);   // den her tager fandens lang tid
//      foreach ($array as $k=>$v) {
//         foreach ($v as $a=>$b) {
//            $user[] = $a;
//        }
//      }
//      $user = array_values(array_unique($user));
      // var_dump( $user );

      $cursor = $collection->findOne();
      reset($cursor);
      while (list($user[], $val)=each($cursor));

      // Take out Mongo _id and replace with nothing i.e. no selection.
      for ($i=0; $i<count($user); $i++) {
         if ($user[$i]=="_id") $user[$i]="";
      }

      // sort array so empty string gets to the top
      sort($user);
      
      $dropdowntext='<select id="' . $id . '" name="' . $id . '">';
      foreach ($user as $i) {
         $dropdowntext=$dropdowntext . '<option value="' . $i . '"' . ">$i</option>";
      }
      $dropdowntext=$dropdowntext . '</select>';
      $_SESSION["dropdownfilterkey_$id"]=$dropdowntext;

   }

   // to make default selection the part of selection is replaced with new part inclufing 'selected' within html
   $key=GetVarFromSession("select_".$id);
   $dropdowntext=str_replace('<option value="'.$key.'">'.$key.'</option>',
                             '<option selected value="'.$key.'">'.$key.'</option>',         
                             $dropdowntext );

   echo $dropdowntext;
}
?>

<?php
function WriteDropDownTableOfLogicalOperator($id)
{
  echo '<select id="' . $id . '" name="' . $id . '">';
  ?>
  <option value="and">and</option>
  <option value="or">or</option>
  </select> 
  <?php
}
?>

<?php
function GenerateSelect2($name = '', $options = array(), $nameselection) {
// Generate a table based on the array input. 
// Drop down list gets named: $name
// $options: The key of the array
// $value: The values of the array
// $nameselection: If set it is matched all $value and if it matches, it makes this value the default selection
// http://www.kavoir.com/2009/02/php-drop-down-list.html
// if $value starts with '*' it will be the selected in the drop down
   $html = '<select name="'.$name.'">';
   foreach ($options as $option => $value) {
      if ($value==$nameselection) {
         $html .= '<option selected value='.$value.'>'.$option.'</option>';
      }
      else $html .= '<option value='.$value.'>'.$option.'</option>';
   }
   $html .= '</select>';
   return $html;
}
?>

<?php
function WriteDropDownTableOfMatching($id)
{
   
//   $val[$i]=GetVarFromSession("select_".$id);  // selected="selected"

  echo '<select id="' . $id . '" name="' . $id . '">';
  ?>
  <option value="contains">contains</option>
  <option value="notcontains">does not contains</option>
  <option value="equals">=</option>
  <option value="notequal">&lt;&gt;</option>
  <option selected value="gre">&gt;</option>
  <option value="greequ">&gt;=</option>
  <option value="les">&lt;</option>
  <option value="lesequ">&lt;=</option>
  

<!--Not yet implemented
  <option value="begin">begins with</option>
  <option value="notbegin">does not begin with</option>
  <option value="endwith">ends with</option>
  <option value="notendwith">does not end with</option>
  <option value="notcontains">does not contain</option>     -->
  </select> 
  <?php
}



function ListPreselectedFilters()
{
   if(isset($_SESSION["FilterName"])) $FilterName=$_SESSION["FilterName"];
   else $FilterName="";
   $FilterNameSize=strlen($FilterName);
   if($FilterNameSize < 12) $FilterNameSize=11;
   
?>
Preselected filter:

<?php 

//   $m = new MongoClient();
//   $db = $m->testdb;
//   $collection = new MongoCollection($db, PreselectedFilters);     
$m = new MongoDB\Client();
$db = $m->testdb;
$collection = $db->PreselectedFilters;
   $query = array('user' => $_SESSION['username']);
   $query = array( '$and' => array( array('FilterType' => "PCRSearch"), $query));
   $cursor = $collection->find($query);

   echo '<select name="SelectedFilter">';
   echo '<option value=""></option>';   // empty selection line
   foreach($cursor as $i) {
      if($i["FilterName"]==$FilterName) $selected="selected";
      else $selected="";
      if(substr($i["FilterName"],0,1)<>"_") echo "<option $selected value=".$i["FilterName"].'>'.$i["FilterName"].'</option>';  // if not a global filter, then list
   }

   // Find all global filters i.e. filter names starting with "_" and then list them
   $query = array('FilterName' => new MongoRegex("/^_/"));
   $query = array( '$and' => array( array('FilterType' => "PCRSearch"), $query));
   $cursor = $collection->find($query);
   foreach($cursor as $i) {
      if($i["FilterName"]==$FilterName) $selected="selected";
      else $selected="";
      echo "<option $selected value=".$i["FilterName"].'>'.$i["FilterName"].'</option>';
   }




   echo '</select>'

?>
<input type="submit" name="FilterCommand" value="Get"/>
<input type="submit" name="FilterCommand" value="Delete"/>
<input type="submit" name="FilterCommand" value="Save"/>
<input type="text" size="<?php echo $FilterNameSize;?>" name="FilterName" value="<?php echo $FilterName;?>"/>



<?php 
}

?>

<?php include("headerline.html"); ?>
<?php
$username=$_SESSION['username'];
/*$b1=GetAdminData($username."_searchB1");
$b2=GetAdminData($username."_searchB2");
// If no selection then search in both B1 and B2
if($b1=="" && $b2=="") {
   $b1="checked";
   $b2="checked";
   StoreAdminData($username."_searchB1","checked");
   StoreAdminData($username."_searchB2","checked");
}*/

echo '<pre style="white-space:normal; line-height:.8;">';
echo "<font size=-2>";
echo "ClearQuestVersion=".GetAdminData("ClearQuestVersion");
echo " LastCQPCR=".GetAdminData("LastCQPCRInDB")." ";
//echo "Last B1 PCR =".GetAdminData("B1_");
//echo " Version_B1 =".GetAdminData("GaiaDBVersion_B1")." *** ";
//echo "Last B2 PCR =".GetAdminData("B2_");
//echo " Version_B2 =".GetAdminData("GaiaDBVersion_B2")."<br>";
$ManuallyAdded=GetAdminData("ExtraPCRs");
$arrManuallyAdded=explode(" ",$ManuallyAdded);
$ManuallyAdded="";
foreach($arrManuallyAdded as $id) $ManuallyAdded.='<a href="print.php?id='.$id.'">'.$id.'</a> ';

if($ManuallyAdded=="") $ManuallyAdded="None";

echo '<span style="color:#C00000" title="As the ClearQuest database is only received every 14 days (from Thales), it is tried to manually upload PCRs in the meantime. Manually added PCRs are listed here. They are deleted when a new ClearQuest database dump is recieved.">Manually added ('.GetAdminData("DateOfCQTextImport").'):</span> '.$ManuallyAdded."<br>";
echo "</font>";
echo "</pre>";
echo '<hr>';


?>
<table border="0">
<tr><td style="color:#C00000" nowrap>
<span title="Restrict search on build. E.g. 'B2_4' will limit all searches below to build 2.4. 'B2' will search anything in build 2. 'B2_4|B2_1' will search anything in build 2.4 or build 2.1. If left empty, any build will be searched. 'B2_4_10' will search from B2_4_10 and below">DetectedRelease (E.g. 'B2_4'):</span>
<?php
//echo 'B1<input type="checkbox" name="Build1" id="Build1" value="Build1" onclick=\'check(1);\' '.$b1.'>';
//echo 'B2<input type="checkbox" name="Build2" id="Build2" value="Build2" onclick=\'check(2);\' '.$b2.'>';
//echo 'B2<input type="text" name="Build" id="Build" value="Build" onclick=\'check(3);\' '.$b2.'>';
echo '<input type="text" name="DetectedRelease" id="TextInput01" value="'.GetAdminData($username."_DetectedRelease").'" onKeyUp=\'StoreTextInDB("DetectedRelease","01"); \' onclick="this.select()">';
echo '</td>';
?>
<td>
</td>
<?php
echo '</tr></table>';

?>


<hr style="height:4px;border:none;color:#333;background-color:#333;" />
<font size=+1><b>PCR Search</b></font>
<?php
// If GET method returns 'yes' then clear all forms
if(isset($_GET["clearforms"])) {
/*   $_SESSION["pcrnum"]="";
   $_SESSION["prnum"]="";
   $_SESSION["description"]="";
   $_SESSION["subject"]="";
   $_SESSION["freekey"]=""; */
   for($i=0;$i<4;$i++) {
      $_SESSION["select_key".$i]="";
      $_SESSION["select_log".$i]="";
      $_SESSION["select_op".$i]="";
      $_SESSION["select_val".$i]="";
   }
}

$id=GetVarFromSession("id");
$TotalSearch=GetVarFromSession("TotalSearch");
$Headline=GetVarFromSession("Headline");
$ATMFreeKeywords=GetVarFromSession("ATM_Free_Keywords");
$framesize=GetVarFromSession("framesize");
if($framesize=="") $framesize=600;

// Make same selection/check as by last search

// id="TextInput01" value="'.GetAdminData($username."_DetectedRelease").'" onKeyUp=\'StoreTextInDB("DetectedRelease","01");\' '

?>
<form action="search_list.php" method="post">
<table border="0">
<!-- row 0 -->
<!-- row 1 -->
<tr>
<td style="color:#C00000">
<span title="Type in PCR number e.g. 'COMP00093802', 'b1_7221', 'b2_1234' or just parts of the number e.g. '8880'.">Id (e.g. 'COMP00093802'):</span>

</td>
<td><input type="text" name="id" id="PCRNum" value="<?php echo $id;?>" onKeyDown="ClearForms(0);" onclick="this.select()"/></td>
<td> </td>
<td> </td>
</tr>
<!-- row 2 -->
<tr>
<td>ATM Free Keywords (e.g. JCCB24a):</td>
<td><input type="text" name="ATMFreeKeywords" id="ATMFreeKeywords" value="<?php echo $ATMFreeKeywords;?>" onKeyDown="ClearForms(1);" onclick="this.select()"/></td>
<td> </td>
<td> </td>
</tr>
<!-- row 3 -->
<tr>
<td style="color:#C00000">
<span title="Looking for the needle - 'tim':
tim( |$)   : Matching 'tim ' or 'tim' at the end of the line/string
^MALMÖ   : Means look for any string/line starting with MALMÖ
DK[0-9]{4}  : Looking in Description for IRFs numbered DK0000 to DK9999:
\(DK   :  Matching '(DK'
\(DK0676\)   : Matching '(DK0676)'
[ti]m : will look for 'tm' or 'tm' i.e. all 'm' starting 't' or 'i'
(ti)m : will look for 'tim'
[^A-Za-z0-9]: non-word characters
t.m   : Matches any character in between e.g. 'tom', 'tim', 'tan'
ti*m   : Preceeding - Matches 'tim', 'tiim', 'tiiim', 'tiiiim' etc. 
ti?    : Preceeding - Matches 't', 'ti'.
ti+    : Preceeding - Matches 'ti', 'tii', 'tiii', etc
t(i|o)m  :  matches 'tim', 'tom'
( |^|(\()DK[0-9]{4}( |.|,|;|:|(\))|)  :  Matches DK0000 to DK9999 followed by ' ' or '.' or ')' or ':' or ';' or nothing at end of line. Matches starting with ' ' or nothing or '('. More or less it can probably extract all DK IRF numbers
Learn more: Look for Regular Expression on Wikipedia">Headline:</span></td>
<td><input type="text" name="Headline" id="Headline" value="<?php echo $Headline;?>" onKeyDown="ClearForms(2);" onclick="this.select()"/></td>
<td> </td>
<td> </td>
</tr>
<!-- row 5 -->
<tr>
<td>Search on all keys (e.g. DK0264):</td>
<td><input type="text" name="TotalSearch" id="TotalSearch" value="<?php echo $TotalSearch;?>" onKeyDown="ClearForms(3);" onclick="this.select()"/></td>
<td></td>
</tr>
</table> 
<input type="submit" name="PCRSearch" value="Search"/>
</form>
<hr>

<?php
// ********************************************
// ********************************************
// ********************************************
// Full text search
// ********************************************
// ********************************************
// ********************************************

$username=$_SESSION['username'];
$CaseSensitive=GetAdminData($username."_CaseSensitive"); // =true or false
if($CaseSensitive) $CaseSensitiveSelected="checked";
else $CaseSensitiveSelected="";


?>
<form action="search_fulltext.php" method="post">
<?php
// if not specified, then only show hits where 3 or more are successfull
if(!isset($_SESSION['fts_ranking'])) $_SESSION['fts_ranking']=1;
// if not specified, limit the number of hits shown to maximum 30
if(!isset($_SESSION['fts_numofhits'])) $_SESSION['fts_numofhits']=30;
// if not specified, targetkey is 'Subject'
if(!isset($_SESSION['fts_targetkey'])) $targetkey="subject";
else $targetkey=$_SESSION['fts_targetkey'];
$SelSubj=$SelDesc=$SelAllK="";
if($targetkey=="subject") $SelHead="checked";
elseif($targetkey=="description") $SelDesc="checked";
else $SelAllK="checked";

if(isset($_SESSION['fts_searchtext'])) {
   $searchtext=$_SESSION['fts_searchtext'];
}
else $searchtext="";
$size=strlen($searchtext);
if($size<40) $size=40;

?>
<font size=+1><b>Full Text Search</b></font>
<table border="0">
<tr>
<td>
<?php
//echo 'Full text search';
echo '<input type="text" name="searchstring" size="' . $size. '" value="' . $searchtext . '"/>' ;
?>
</td>
<td style="color:#C00000">
<span title="Consider to strip out simple words like 'is', 'as', 'on' ,'where' etc. as it will focus search!">info</span>
</td>
<td>
<?php
  echo '<input type="radio" name="targetkey" value="subject" '.$SelHead.' />Headline';
?>
</td>
</tr>
<tr>
<td>
Minimum ranking: <input type="text" name="ranking" value="<?php echo $_SESSION['fts_ranking']; ?>" size="1"/>
Max number of hits<input type="text" name="numofhits" value="<?php echo $_SESSION['fts_numofhits']; ?>" size="2" />
</td>
<td>
</td>
<td>
<?php
  echo '<input type="radio" name="targetkey" value="description" '.$SelDesc.' />Description';
?>
</td>
</tr>
<tr>
<td>
<input type="submit" name="fulltextsearch" value="Search"/>
<input type="checkbox" name="CaseSensitive" value="yes"  <?php echo $CaseSensitiveSelected;?> />CaseSensitive
</td>
<td>
</td>
<td>
<?php
  echo '<input type="radio" name="targetkey" value="TotalSearch" '.$SelAllK.' />AllKeySearch';
?>
</td>
</tr>
</table>
</form>
<hr>



<?php
// ********************************************
// ********************************************
// ********************************************
// Combined search form
// ********************************************
// ********************************************
// ********************************************
?>
<form action="search_list.php" method="post">
<?php
for($i=0;$i<4;$i++) {
   //$key[$i]=GetVarFromSession("select_key".$i);
   $log[$i]=GetVarFromSession("select_log".$i);
   $op[$i]=GetVarFromSession("select_op".$i); 
   $val[$i]=GetVarFromSession("select_val".$i);
}

// $html = GenerateSelect2('company', $companies);


// Make table of 4 rows for advanced search query
$operators = array(
//      option => value
	'='        => 'equals',          //   =
	'&lt;&gt;' => 'notequal',        //   <>
	'&gt;'     => 'gre',             //   >
	'&gt;='    => 'greequ',          //   >=
	'&lt;'     => 'les',             //   <
	'&lt;='    => 'lesequ',          //   <=
        'contains'        => 'contains',          //   contains
        'notcontains'        => 'notcontains',          //   does not contains

);

?>
<!--  not yet implemented
  <option value="begin">begins with</option>
  <option value="notbegin">does not begin with</option>
  <option value="endwith">ends with</option>
  <option value="notendwith">does not end with</option>
  <option value="contains">contains</option>
  <option value="notcontains">does not contain</option>     -->
<?php
// Rows of logical drop down
$logic = array(
//      option => value
	'and' => 'and', 
	'or'  => 'or',  
);

?>
<font size=+1><b>Combined PCR Search</b></font>
<?php
echo '<table border="0">';
for($i=0;$i<4;$i++) {
  ?><tr><td><?php  // next row , column 1
  echo WriteDropDownTableOfPCRdb($collection, "key".$i);
  ?></td><td><?php  // column 2 
  $nameselection="";
  foreach ($operators as $option => $value) {
      if ($value == $op[$i]) {
         $nameselection=$value;   // make it default selected in drop down
         break;
      }
  }
  echo GenerateSelect2("op".$i, $operators, $nameselection);
  ?></td><td><?php  // column 3 
  echo '<input type="text" name="val'.$i.'" value="'.$val[$i].'"/>';
  ?></td><td><?php  // column 4
  if($i<3) {   // there are only 3 logical operators so avoid the last   
     $nameselection="";
     foreach ($logic as $option => $value) {
         if ($value == $log[$i]) {
            $nameselection=$value;   // make it default selected in drop down
            break;
         }
     }
     echo GenerateSelect2("log".$i, $logic, $nameselection);
  }
  ?></td><td><?php  // column 5 
  ?></tr><?php  // end of row
}
echo '</table>';
?>
<?php ListPreselectedFilters(); ?>
<br>
<input type="submit" name="CombinedPCRSearch" value="Search"/>
<a href="search_input.php?clearforms=yes">Clear forms</a>
</form>
<hr>
<form action="search_input.php" method="post">
<table border="0">
<tr>
<td style="color:#C00000">
<span title="Framesize is used to limit the amount if lines shown when listing the search. If it is below 200, information on left side of listed table is reduced. If it is 0, then only headings/Subjects are shown.">Framesize:</span>
</td>
<td>
<input type="text" name="framesize" value="<?php echo $framesize;?>" />
<input type="submit" name="ChangeFramesize" value="Change Framesize"/>
</td>
</tr>
</table>
</form>
<hr>
<a href="Exported_fields_description_20131003.html">--Field descriptions--</a>
<a href="ClearQuest.htm">--ClearQuest Explanations--</a>
<?php



session_write_close();  // remember to close session fast to avoid locks in response
?>
</body>
</html> 






