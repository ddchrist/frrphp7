<?php include("login_headerscript.php"); ?>


<?php
// All priorities and severities are stored two places in the database
// 1) They are store in the 'pcr' collection in the given PCRID number.
// 2) It is also stored in collection 'prisev'
// If the database is updated, the 'prisev' collection can be used to
// copy all the priorities and severities to the 'pcr' collection. This
// is not done in this routine - though!
$pcrnum=$_POST['pcrnum'];
// If risk button, show risk assestment scheme
if(isset($_POST['risk'])) {
   header("location:risk_view.php?pcr=$pcrnum&resetregularid=1");
}
$m = new Mongo();
$db = $m->selectDB('testdb');
$collection = new MongoCollection($db, 'pcr');

$build=GetCOOPANSBuild($pcrnum);
$pcrnumint=GetCOOPANSPCRNum($pcrnum);

$query = array('RegularID' => $pcrnumint);
$query = array( '$and' => array( array('ProductName' => $build), $query));
$cursor = $collection->findone($query);

// $countries is also set in pcr_severity_priority.php
$countries=array("co","th","se","hr","ie","at","dk");
$cursor2=array();
foreach($countries as $i) {
   // dkS7221 - Severities
   $cursor2=array_merge((array)$cursor2, (array)array("Severity_$i"=>$_POST[$i."S".$pcrnum]));
   // dkP7221 - Priorities
   $cursor2=array_merge((array)$cursor2, (array)array("Priority_$i"=>$_POST[$i."P".$pcrnum]));
}
// make sure it is possible to trace when last time prioritis and severities were updated
$cursor2['lasttime_Pri_Sev_updated']=date("Y-m-d").' '.date("H:i:s");

// Store in 'pcr' collection
$cursor=array_merge((array)$cursor, (array)$cursor2);
$collection->save($cursor);

// Store in 'prisev' collection
$collection = new MongoCollection($db, 'prisev');

$query = array('PCRID' => $pcrnum);
$cursor = $collection->findone($query);

$cursor2=array_merge((array)array('PCRID' => $pcrnum), (array)$cursor2);
$cursor=array_merge((array)$cursor, (array)$cursor2);
$collection->save($cursor);

echo "PCR $pcrnum is stored in database<br>";
echo '<a href="print.php?pcr='.$pcrnum.'">Back</a>';

// ******************************************
// ************** FUNCTIONS *****************
// ******************************************

// Return COOPANS Product name from PCRID
// Convert "B1_7221" into "COOPANS"
function GetCOOPANSBuild($PCRID)
{
   $build=strtoupper(strstr($PCRID,"_",true));
   if($build=="B1") $build="COOPANS";
   elseif($build=="B2") $build="COOPANS_B2";
   else {
      echo "Something is wrong with the build - sorry !!!. Build: $build";
      exit(0);
   }
   return $build;
}

// Return COOPANS RegularID from PCRID
// Convert "B1_7221" into integer 7221
function GetCOOPANSPCRNum($PCRID)
{
  $pcrnumint=strstr($PCRID,"_");
  $pcrnumint=substr($pcrnumint,1);  // take out "_" so only number is left
  $pcrnumint=intval($pcrnumint); // make sure that value to search for is integer
  return $pcrnumint;
}

function ListSeverityPrioritySelections($pcrnum)
{
  $severity=array("S","s1","s2","s3","s4","s5");
  $priority=array("P","p0","p1","p2","p3","p4","p5");
  $countries=array("co","th","se","hr","ie","at","dk");

  $m = new Mongo();
  $db = $m->selectDB('testdb');
  $collection = new MongoCollection($db, 'pcr');

  $build=GetCOOPANSBuild($pcrnum);
  $pcrnumint=GetCOOPANSPCRNum($pcrnum);

  $query = array('RegularID' => $pcrnumint);
  $query = array( '$and' => array( array('ProductName' => $build), $query));
  $cursor = $collection->findone($query);

  $html='<table border="0" >';
  // row 1
  $html.='<tr>';
  // insert country names - severity.
  foreach($countries as $i) {
     $html.='<td align="center">'.$i.'</td>';
  }
  $html.='<td align="center">---</td>'; // make a seperation 
  // insert country names - priority.
  foreach($countries as $i) {
     $html.='<td align="center">'.$i.'</td>';
  }
  $html.='</tr>';
  // row 2
  $html.='<tr>';
  // insert drop downs for severity
  // Set default values if they exist in databse
  foreach($countries as $i) {
     if(strtoupper($i)=="TH") $default=$cursor['Severity'];
     elseif(isset($cursor["Severity_$i"])) $default=$cursor["Severity_$i"];
     else $default="";  // this is default selection in drop down
     // Severity. Name e.g. dkS7221
     $html.='<td>'.GenerateSelect($i.'S'.$pcrnum, $severity, $default).'</td>';
  }
  $html.="<td></td>"; // make a seperation 
  // insert drop downs for priority
  // Set default values if they exist in databse
  foreach($countries as $i) {
     if(strtoupper($i)=="TH") $default=$cursor['Priority'];
     elseif(isset($cursor["Priority_$i"])) $default=$cursor["Priority_$i"];
     else $default="";  // this is default selection in drop down
     // Priority. Name e.g. dkP7221
     $html.='<td>'.GenerateSelect($i.'P'.$pcrnum, $priority, $default).'</td>';
  }
  $html.='</tr>';
  $html.='</table>';

  //echo $html;
  //exit(0);
  return $html;
}
?>
<?php
function GenerateSelect($name = '', $options = array(), $nameselection) {
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
         $html .= '<option selected value='.$value.'>'.$value.'</option>';
      }
      else $html .= '<option value='.$value.'>'.$value.'</option>';
   }
   $html .= '</select>';
   return $html;
}
?>
