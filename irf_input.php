<?php include("login_headerscript.php"); ?>


<?php

// IRF form inputs to be inserted into mongodb
//$array=array("irfcountry","irfdate","irftime","irfuser",
//        "irfcwpnum","irftestid", "irfrigid", "irftestsession",
//        "irfswversion", "irfdataset", "irftitle", "irfdetails");
// Store all irf inputs into session
//foreach($array as $i) {
//   $_SESSION[$i]=$_POST[$i];
//}


   // var_dump(iterator_to_array($cursor));

// Make all inputs in IRF default empty if not already set
$irf=array('irfdetails','irftitle','irfdataset','irfswversion','irfsite',
      'irfbuild', 'irfrigid', 'irftestid', 'irfcwpnum','irftestsession');
foreach($irf as $i) {
   if(!isset($_SESSION[$i])) $_SESSION[$i]="";
}

// Clear session if requested when called
if(isset($_GET['clearform'])) {
   $_SESSION['irftitle']="";
   $_SESSION['irftestid']="";
   $_SESSION['irfdetails']="LFUNC:\nCall Sign:\nSSR:\nADEP:\nADES:\nGeographical Position:\nProblem:\n....\n....\n";  
}

$myusername=GetVarFromSession('username');


$db_name="admin"; // Database name
$tbl_name="members"; // Collection name

$m = new Mongo();
$db = $m->selectDB($db_name);
$collection = new MongoCollection($db, $tbl_name);

$query=array('user' => $myusername);
$cursor = $collection->findone($query);
$country=$cursor['country'];
if(!isset($_SESSION['irfcountry'])) $_SESSION['irfcountry']=$country;

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
function ListTestSessions($name, $defaultselection) {
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'testsessions');
   $cursor = $collection->find();
   $array=array();
   $array[]="Test Sessions"; // make first element in drop down list empty
   foreach ($cursor as $k) {
      $array[]=$k['sessionname'];
   }
   //var_dump($array);
   // Generate drop down and make last element in testsessions collection default selection
   // echo GenerateSelect($name = 'testsessions', $array, $array[count($array)-1]);

   echo GenerateSelect($name, $array, $defaultselection);
}


function GenerateSelect($name = '', $options = array(), $nameselection) {
// Generate a table based on the array input. 
// Drop down list gets named: $name
// $options: The key of the array
// $value: The values of the array
// $nameselection: If set it is matched all $value and if it matches, it makes this value the default selection
// http://www.kavoir.com/2009/02/php-drop-down-list.html
   $html = '<select name="'.$name.'">';
   foreach ($options as $option => $value) {
      if ($value==$nameselection) {
         $html .= '<option selected value='.$value.'>'.$value.'</option>';
      }
      else $html .= '<option value='.$value.'>'.$value.'</option>';
   }
   $html .= '</select>';
   return $html;
}
?>


<!-- ****************************************************** -->
<!--                    Main programme                      -->
<!-- ****************************************************** -->

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title>Search Turning Torso</title>
</head>
<body>
<?php include("headerline.html"); ?>
<font size=+1><b>IRF Input form</b></font>
<form action="irf_insert_db.php" method="post">


<table border="0">
<!-- row 1 -->
<tr>
<td><?php ListTestSessions('irftestsession', $_SESSION['irftestsession']); ?></td>
<td><input type="submit" name="updatetestsession" value="Get Session"/></td>
</tr>
</table>

<table border="0">
<!-- row 1 -->
<tr>
<td>
Country:
</td>
<td><?php echo GenerateSelect("irfcountry",array("dk","hr","ie","at","se"),$_SESSION['irfcountry']);?></td>
<td>Build:</td>
<td><?php echo GenerateSelect("irfbuild",array("","B1","B2","B3"),$_SESSION['irfbuild']);?></td>
<td>Site:</td>
<td><?php echo GenerateSelect("irfsite",array("","LOWW","EKDK","EIDW","EISN","LDZO","ESMM","ESOS"),$_SESSION['irfsite']);?></td>
</tr>
<!-- row 2 -->
<tr>
<td>Originator:</td>
<td><input type="text" name="irfuser" size="8" value="<?php echo $myusername;?>"/></td>
<td>CWP no.:</td>
<td><input type="text" name="irfcwpnum" size="8" value="<?php echo $_SESSION['irfcwpnum'];?>"/></td>
<td>TestID:</td>
<td><input type="text" name="irftestid" size="8" value="<?php echo $_SESSION['irftestid'];?>"/></td>
</tr>
<!-- row 3 -->
<tr>
<td>RigID:</td>
<td><input type="text" name="irfrigid" size="8" value="<?php echo $_SESSION['irfrigid'];?>"/></td>
<td>Date UTC:</td>
<td><input type="text" name="irfdate" size="10" value="<?php echo gmdate("Y-m-d");?>"/></td>
<td>Time UTC:</td>
<td><input type="text" name="irftime" size="8" value="<?php echo gmdate("H:i:s");?>"/></td>
</tr>
</table> 

<table border="0">
<!-- row 1 -->
<tr>
<td>SW Version:</td>
<td><input type="text" name="irfswversion" value="<?php echo $_SESSION['irfswversion'];?>"/></td>
<td>Dataset:</td>
<td><input type="text" name="irfdataset" value="<?php echo $_SESSION['irfdataset'];?>"/></td>
</tr>
</table>

<table border="0">
<!-- row 1 -->
<tr>
<td>IRF Title:</td>
<td><input type="text" name="irftitle" size="63" value="<?php echo $_SESSION['irftitle'];?>"/></td>
</tr>
<!-- row 2     -->
<tr>
<td valign="top">Details:</td>
<td><textarea name="irfdetails" COLS=80 ROWS=15><?php echo $_SESSION['irfdetails'];?></textarea></td>
</tr>
<!-- row 3     -->
<tr>
<td></td>
<td><a href="irf_select_upload.php">Attach files (Does not work - coming soon)</a></td>
</tr>
<!-- row 4     -->
<tr>
<td><input type="submit" name="insertirf" value="Submit IRF"/></td>
<td><?php echo GenerateSelect("irfstate",array("IRF_States","P_Potential_PR", "T_Thales_Potential_PCR", "I_Internal_ATCO_issue", "U_Update_of_Checklist", "D_DPR_Analysis", "C_Closed_by_ATCO"),"");?></td>
</tr>
</table>
</form>
<?php
session_write_close();  // remember to close session fast to avoid locks in response
?>

</body>
</html> 




