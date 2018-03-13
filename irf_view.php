<?php include("login_headerscript.php"); ?>

<?php
define("FILE_DB",             "test");      // DB where Mongo GridFS will be working
define("FILE_COLLECTION",     "fs.files");  // fs.files is set by GridFS and can not be changed
                                            // fs.files contain information of file name length
                                            // and how it is 'chunked' into subfiles 
define("DBKEY_IRFNUM",     "irfnum");       // IRF number key in DB for collection fs.files
?>

<?php
if (isset($_GET["irf"])) {
   $irfnum=$_GET["irf"];
   $_SESSION["irfnumpointer"]=0;   // used to move forward/backward in searches
   if ($irfnum == "") exit(0);
}

// Administrate moving forward and backwards based on outcome of search
if (isset($_POST['irfforward'])) {
   if(isset($_SESSION["irf_regularid"])) {
      $irfarray=$_SESSION["irf_regularid"];
      $pointer=$_SESSION["irfnumpointer"]+1;
      if($pointer>=count($irfarray)) $pointer=0;
      $_SESSION["irfnumpointer"]=$pointer;
      $irfnum=$irfarray[$pointer];
      if($irfnum=="") exit(0);
   }
   else $irfnum=$_POST['irfnum'];
}
elseif (isset($_POST['irfbackward'])) {
   if(isset($_SESSION["irf_regularid"])) {
      $irfarray=$_SESSION["irf_regularid"];
      $pointer=$_SESSION["irfnumpointer"]-1;
      if($pointer<0) $pointer=count($irfarray)-1;
      $_SESSION["irfnumpointer"]=$pointer;
      $irfnum=$irfarray[$pointer];
      if($irfnum=="") exit(0);
   }
   else $irfnum=$_POST['irfnum'];
}
elseif (isset($_POST['irfinator'])) {
   $irfnum=$_POST['irfnum'];
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'irf');
   $cursor = $collection->findOne(array('irfnum' => $irfnum));
   $search=$cursor['irftitle'];
   header("location:fulltextsearch.php?fts_pcrirfid=$irfnum&fts_searchtext=$search&fts_collection=pcr&fts_collection_key=Subject&fts_key_id=RagularID");
}

// Get IRF from submit "irfget" into form
if (isset($_POST['irfget'])) {
   $irfnum=$_POST['irfnum'];
   // If IRF number is simple i.e. just a number, then get the first parts of the number
   if(strlen($irfnum)<=4) {
      $irfnum=strtoupper($_POST['irfcountry']."_".$_POST['irfsite']."_".$_POST['irfbuild']."_".str_pad($irfnum,4,"0",STR_PAD_LEFT));
   }
   header("location:irf_view.php?irf=".$irfnum);
}

// Save/Update IRF (so MongoDB entry is modified and not inserted)
if (isset($_POST['saveirf'])) {
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'irf');
   $cursor = $collection->findone(array('irfnum' => $_SESSION['irfnum']));
   $irf=array('irfdetails','irftitle','irfdataset','irfswversion','irfsite',
      'irfbuild', 'irfrigid', 'irftestid', 'irfcwpnum','irftestsession',
      'irfcountry', 'irfbuild' , 'irfuser', 'irfdate', 'irftime', 'irfstate');
   foreach($irf as $i) {
      if($i=="irfbuild" || $i=="irfsite" || $i=="irfcountry") {
         $cursor[$i]=$_SESSION[$i];   // during update these variables can not be modified as they influence numbering of IRF
      }
      else {
         $cursor[$i]=$_POST[$i];
         //echo "$i: $_POST[$i]<br>";
      }
   }
   $cursor['irflasttimeupdated']=date("Y-m-d").' '.date("H:i:s");
   $collection->save($cursor);
   include("headerline.html");
   echo "IRF has been updated";
   
   echo '<br><a href="irf_view.php?irf='.$_SESSION['irfnum'].'">Back to IRF View<a>';
   exit(0);
   // <a href="select2download.php">Download as csv</a>
}
?>




<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title><?php echo $irfnum;?></title>
</head>
<body>
<?php include("headerline.html"); ?>
<br>

<?php
$m = new Mongo();
$db = $m->selectDB('testdb');
$collection = new MongoCollection($db, 'irf');
$cursor = $collection->findOne(array('irfnum' => $irfnum));

// Make all inputs in IRF default empty if not already set
$irf=array('irfdetails','irftitle','irfdataset','irfswversion','irfsite',
      'irfbuild', 'irfrigid', 'irftestid', 'irfcwpnum','irftestsession',
      'irfcountry', 'irfbuild' , 'irfuser', 'irfdate', 'irftime', 'irfstate',
      'irflasttimeupdated', 'irfnum');
foreach($irf as $i) {
   $_SESSION[$i]=$cursor[$i];
}

// Get number of file attachments for this irfnum
   $dbfile = $m->selectDB(FILE_DB);
   $collfile = new MongoCollection($dbfile, FILE_COLLECTION);
   $curfile = $collfile->find(array(DBKEY_IRFNUM => $irfnum));
   $i=$curfile->count();
   if($i>0) $attachment='<font style="BACKGROUND-COLOR: yellow">Number of files='.$i.'</font>';
else $attachment="";

//$myusername=GetVarFromSession('username');


//$db_name="admin"; // Database name
//$tbl_name="members"; // Collection name

//$m = new Mongo();
//$db = $m->selectDB($db_name);
//$collection = new MongoCollection($db, $tbl_name);

//$query=array('user' => $myusername);
//$cursor = $collection->findone($query);
//$country=$cursor['country'];

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

<form action="irf_view.php" method="post">
<table border="0">
<!-- row 1 -->
<tr>
<td><font size=+1><b>IRF View</b></font></td>
<td><input type="text" name="irfnum" size="15" value="<?php echo $_SESSION['irfnum'];?>"/></td>
<td><input type="submit" name="irfget" value="Get IRF"/></td>
<td><input type="submit" name="irfbackward" value="<<<"/></td>
<td><input type="submit" name="irfforward" value=">>>"/></td>
<td><input type="submit" name="irfinator" value="ClaudeInator"/></td>
</tr>
</table>
</form>

<form action="irf_view.php" method="post">

<table border="0">
<!-- row 1 -->
<tr>
<td><font color="blue">IRF number:</font></td>
<td><?php echo $_SESSION['irfnum'];?></td>
<!-- <td><input type="text" name="irfnum" size="13" value="<?php echo $_SESSION['irfnum'];?>"/></td> -->
<td><font color="blue">Test Session:</font></td>
<td><?php ListTestSessions('irftestsession', $_SESSION['irftestsession']); ?></td>
</tr>
<!-- row 2 -->
 
<!--  <tr>
<td>Country:</td>
<td><?php echo GenerateSelect("irfcountry",array("dk","hr","ie","at","se"),$_SESSION['irfcountry']);?></td>
<td>Build:</td>
<td><?php echo GenerateSelect("irfbuild",array("B1","B2","B3"),$_SESSION['irfbuild']);?></td>
<td>Site:</td>
<td><?php echo GenerateSelect("irfsite",array("","VI","CP","RK","BI","MM","ST","SH","DU","CR"),$_SESSION['irfsite']);?></td>
</tr>  -->

<!-- row 2 -->
<tr>
<td><font color="blue">Country:</font></td>
<td><?php echo $_SESSION['irfcountry'];?></td>
<td><font color="blue">Build:</font></td>
<td><?php echo $_SESSION['irfbuild'];?></td>
</tr>

<!-- row 2 -->
<tr>
<td><font color="blue">Site:</font></td>
<td><?php echo $_SESSION['irfsite'];?></td>
<td><font color="blue">Originator:</font></td>
<td><input type="text" name="irfuser" size="8" value="<?php echo $_SESSION['irfuser'];?>"/></td>
</tr>

<!-- row 3 -->
<tr>
<td><font color="blue">CWP no.:</font></td>
<td><input type="text" name="irfcwpnum" size="8" value="<?php echo $_SESSION['irfcwpnum'];?>"/></td>
<td><font color="blue">TestID:</font></td>
<td><input type="text" name="irftestid" size="8" value="<?php echo $_SESSION['irftestid'];?>"/></td>
</tr>

<!-- row 4 -->
<tr>
<td><font color="blue">Date UTC:</font></td>
<td><input type="text" name="irfdate" size="10" value="<?php echo $_SESSION['irfdate'];?>"/></td>
<td><font color="blue">Time UTC:</font></td>
<td><input type="text" name="irftime" size="8" value="<?php echo $_SESSION['irftime'];?>"/></td>
</tr>

<!-- row 5 -->
<tr>
<td><font color="blue">SW Version:</font></td>
<td><input type="text" name="irfswversion" value="<?php echo $_SESSION['irfswversion'];?>"/></td>
<td><font color="blue">Dataset:</font></td>
<td><input type="text" name="irfdataset" value="<?php echo $_SESSION['irfdataset'];?>"/></td>
</tr>

<!-- row 6 -->
<tr>
<td><font color="blue">RigID:</font></td>
<td><input type="text" name="irfrigid" size="8" value="<?php echo $_SESSION['irfrigid'];?>"/></td>
<td><font color="blue">Last time updated:</font></td>
<td><?php echo $_SESSION['irflasttimeupdated'];?></td>
</tr>
</table>

<table border="0">
<!-- row 1 -->
<tr>
<td><font color="blue">IRF Title:</font></td>
<td><input type="text" name="irftitle" size="63" value="<?php echo $_SESSION['irftitle'];?>"/></td>
</tr>
<!-- row 2     -->
<tr>
<td valign="top"><font color="blue">Details:</font></td>
<td><textarea name="irfdetails" COLS=80 ROWS=15><?php echo $_SESSION['irfdetails'];?></textarea></td>
</tr>
<!-- row 3     -->
<tr>
<td></td>
<td><a href="irf_select_upload.php?irf=<?php echo $irfnum;?>">Attach files (Does not work coming soon!)</a>
<?php echo $attachment;?>
</td>
</tr>
<!-- row 4     -->
<tr>
<td><input type="submit" name="saveirf" value="Update IRF"/></td>
<td><?php echo GenerateSelect("irfstate",array("IRF_States","P_Potential_PR", "T_Thales_Potential_PCR", "I_Internal_ATCO_issue", "U_Update_of_Checklist", "D_DPR_Analysis", "C_Closed_by_ATCO"),$_SESSION['irfstate']);?></td>
</tr>
</table>
</form>
<?php
session_write_close();  // remember to close session fast to avoid locks in response
?>

</body>
</html> 




