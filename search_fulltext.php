<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<script>
function StoreTextInDB(strVariable,Id)  // Id is string and e.g. '02' pointing to e.g. id=TextInput01
{
   var xmlhttp;
   xmlhttp=new XMLHttpRequest();
   strValue = document.getElementById('TextInput'+Id).value;
   //alert(strVariable+"   "+Id+"   "+strValue);
   xmlhttp.open("GET","search_input_response.php?SetVarInDB="+strVariable+"&Value="+strValue);
   xmlhttp.send();
}

</script>
<?php
// This routine does Full Text Search and list all hits from a collection
// The routine can be called as is and inputs must be given in forms afterwards
// The routine can also be called with parameters where execution is done
// automatically:
//    Method: get
//    fts_pcrirfid: e.g. 'DK_EKDK_B1_0012' for IRF or '734' for PCR number 734
//    fts_searchtext: Search Text
//    fts_collection: Collection to search in database 'testdb'. Default 'pcr'
//    fts_collection_key: The key in collection to be searched/ranked. Default 'Headline' 
//    fts_key_id: Name of ID key. Default: 'Id'
//
// How it works: The search string is seperated into an array.
// For each array element, a regex search is done in given key.
// All hits are stored with their IDs in an array. This array is counted
// for number of occurences of each ID. It is then sorted so the IDs with most
// hits are put on top of array. Also session regularID is updated for usage
// on other routines.

$username=$_SESSION['username'];

if(isset($_GET['fts_pcrirfid'])) {
   $irfpcrnum=$_GET['fts_pcrirfid'];
   $_SESSION['fts_pcrirfid']=$irfpcrnum;
}
elseif (isset($_POST['fulltextsearch'])) {
   if(isset($_SESSION['fts_pcrirfid'])) $irfpcrnum=$_SESSION['fts_pcrirfid'];
   else $irfpcrnum="";
}
else {   // script is called without input and no id must be visualised
   $irfpcrnum="";
   $_SESSION['fts_pcrirfid']="";
} 

if(isset($_GET['fts_searchtext'])) $searchtext=$_GET['fts_searchtext'];
else $searchtext="";
if(isset($_GET['fts_collection'])) $targetcollection=$_GET['fts_collection'];
else $targetcollection="pcr";
if(isset($_GET['fts_collection_key'])) $targetkey=$_GET['fts_collection_key'];
else $targetkey="Headline";
$targetprintkey=$targetkey;
if(isset($_GET['key_id'])) $keyid=$_GET['key_id'];
else $keyid="id";

if(isset($_POST['fulltextsearch'])) {
   UpdateSearchStat($_SESSION['username'],"SearchFullText");
   $_SESSION['fts_ranking']=$_POST['ranking'];
   $_SESSION['fts_numofhits']=$_POST['numofhits'];
   $_SESSION['fts_searchtext']=$_POST['searchstring'];
   $_SESSION['fts_targetkey']=$_POST['targetkey'];  // either 'description' or 'headline'
   $searchtext=$_POST['searchstring'];
   
   $CaseSensitive=$_POST['CaseSensitive'];   
   if($CaseSensitive=="yes") $CaseSensitive=true;
   StoreAdminData($username."_CaseSensitive",$CaseSensitive); // =true or false
   
   if($_POST['targetkey']=="description") {
      $targetkey="Description";
      $targetprintkey="Headline";  // make it print headings and not detailed text when searching in description
   }
   elseif($_POST['targetkey']=="TotalSearch") {
      $targetkey="TotalSearch";
      $targetprintkey="Headline";  // make it print headings and not detailed text when searching in TotalSearch
   }
   else $targetkey="Headline";
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title>Full Text Search</title>
</head>
<body>

<?php include("headerline.html"); ?>
<form action="search_fulltext.php" method="post">
<?php
// if not specified, then only show hits where 3 or more are successfull
if(!isset($_SESSION['fts_ranking'])) $_SESSION['fts_ranking']=1;
// if not specified, limit the number of hits shown to maximum 30
if(!isset($_SESSION['fts_numofhits'])) $_SESSION['fts_numofhits']=30;

$size=strlen($searchtext);
if($size<30) $size=30;

?>
<table border="0">
<tr>
<td>
<span style="color:#C00000" title="Consider to strip out simple words like 'is', 'as', 'on' ,'where' etc. as it will focus search!">
<font size=+1><b>Full Text Search</b></font></span>
</td>
<?php

echo '<td><input type="text" name="searchstring" size="' . $size. '" value="' . $searchtext . '"/>' ;
?>
<input type="submit" name="fulltextsearch" value="Search"/></td>
<?php
echo "<td>";
if($targetkey=="Headline") { 
   echo '<input type="radio" name="targetkey" value="Headline" checked />Headline';
} 
else {
   echo '<input type="radio" name="targetkey" value="Headline"/>Headline';
} 
echo "</td></tr><tr>";   


$username=$_SESSION['username'];
$DetectedRelease=GetAdminData($username."_DetectedRelease");
$CaseSensitive=GetAdminData($username."_CaseSensitive"); // =true or false
if($CaseSensitive) $CaseSensitiveSelected="checked";
else $CaseSensitiveSelected="";
// Clean string for typical typos and store
$DetectedRelease=str_replace(".","_",$DetectedRelease);
$DetectedRelease=str_replace("b","B",$DetectedRelease);
$DetectedRelease=preg_replace("/[^0-9_B]/","",$DetectedRelease);
StoreAdminData($username."_DetectedRelease",$DetectedRelease);
?>

<?php
echo '<td>DetectedRelease</td><td><input type="text" name="DetectedRelease" id="TextInput01" value="'.$DetectedRelease.'" onKeyUp=\'StoreTextInDB("DetectedRelease","01");\'></td>';

echo "<td>";
if($targetkey=="Description") { 
   echo '<input type="radio" name="targetkey" value="description" checked />Description';
} 
else {
   echo '<input type="radio" name="targetkey" value="description" />Description';
} 
echo "</td></tr><tr>"; 

// list IRF which is basis for search
//if(strlen($irfpcrnum)>4) {  // then it is an IRF number as PCRs are only 4 digits
//   echo '<a href="irf_view.php?irf='.$irfpcrnum . '">' . $irfpcrnum . '</a>';
//}
?>

<td>
Minimum ranking: <input type="text" name="ranking" value="<?php echo $_SESSION['fts_ranking']; ?>" size="1"/></td>
<td>
Max number of hits<input type="text" name="numofhits" value="<?php echo $_SESSION['fts_numofhits']; ?>" size="2" />


<input type="checkbox" name="CaseSensitive" value="yes"  <?php echo $CaseSensitiveSelected;?> />CaseSensitive


</td>
<td>


<?php
if($targetkey=="TotalSearch") { 
   echo '<input type="radio" name="targetkey" value="TotalSearch" checked />AllKeySearch';
} 
else {
   echo '<input type="radio" name="targetkey" value="TotalSearch" />AllKeySearch';
} 



?>
</td>
<?php 
/*if($targetkey=="Headline") { 
   echo 'Headline<input type="radio" name="targetkey" value="Headline" checked />';
   echo 'Description<input type="radio" name="targetkey" value="description" />';
   echo 'AllKeySearch<input type="radio" name="targetkey" value="TotalSearch" />';
}
elseif($targetkey=="Description") { 
   echo 'Headline<input type="radio" name="targetkey" value="Headline" checked />';
   echo 'Description<input type="radio" name="targetkey" value="description" checked />';
   echo 'AllKeySearch<input type="radio" name="targetkey" value="TotalSearch" />';
}
else {
   echo 'Headline<input type="radio" name="targetkey" value="Headline" />';
   echo 'Description<input type="radio" name="targetkey" value="description" />';
   echo 'AllKeySearch<input type="radio" name="targetkey" value="TotalSearch" checked />';
} */


?>
</tr>
</table>

</form>
<?php

//echo "targetcollection=".$targetcollection . "   keyid=".$keyid .
//     "   targetprintkey=". $targetprintkey. "   searchtext=".$searchtext. "<br>";

// Do not start full search engine if script is called without input
if(!isset($_GET['fts_pcrirfid']) && !isset($_POST['fulltextsearch']) ) {
   goto terminate;
}

// Make $searcharray contain each word (one per element) from Search String
$searcharray=explode(" ",trim($searchtext));

// Store search tags in session so it can be used in other scripts
// for highlighting text
$_SESSION['highlighttags']=$searcharray;

// If selected number for ranking is bigger than the number of words in
// search string then nothing will be shown. Therefore a warning
if(count($searcharray) < $_SESSION['fts_ranking']) {
   echo 'It does not make sense to have less search words than the given minimum ranking. Adjust minimum ranking and try again';
   goto terminate;
}

// var_dump($searcharray);
$m = new MongoClient();
$db = $m->selectDB('testdb');
$collection = new MongoCollection($db, $targetcollection);
$listpcr=array();



if($CaseSensitive) $CaseSensitive="";
else $CaseSensitive="i";
foreach($searcharray as $i) {
   // Include selected builds into queries
   $query = array($targetkey => new MongoRegex("/$i/$CaseSensitive"));
   if($DetectedRelease<>"") $query = array( '$and' => array( array('Detected_Release' => new MongoRegex("/".$DetectedRelease."/$CaseSensitive")), $query));
  
   $cursor = $collection->find($query);
   foreach($cursor as $obj) {
      $listpcr[]=$obj["id"];
   }
}
?>
<span title="If number of hits differ from what is listed, consider to change 'Ranking' or 'Max number of hits'!">
<?php
echo '<b>Number of hits:</b> '.count($listpcr);
echo '</span>';
echo '<br>';
//print_r($listpcr);
//exit(0);

$ranking=array_count_values($listpcr);

//print_r($ranking);

arsort($ranking);
$arrOfIDs=array();
$hitcounter=0;

?><table border="0"><?php
foreach ($ranking as $key => $val) {
   if($val>=$_SESSION['fts_ranking']) {
      //echo "$key = $val\n";
      $arrOfIDs[]=$key;
      echo '<tr>';
      echo '<td valign="top"><a href="print.php?id='.$key.'&highlight=yes' . '"target="_blank">' .$key. '</a></td>';

      $query=array($keyid => $key);
      //if($DetectedRelease<>"") $query = array( '$and' => array( array('Detected_Release' => new MongoRegex("/".$DetectedRelease."/i")), $query));


      $cursor = $collection->findOne($query);

      echo '<td valign="top">'.$val."</td><td>".$cursor[$targetprintkey].'</td>';
      echo "</tr>";
   }
   $hitcounter++;
   if($hitcounter>=$_SESSION['fts_numofhits']) break;
}
?></table><?php
// Store results so it can be used in other scripts
$_SESSION["arrOfIDs"]=$arrOfIDs;
?>
<font size=-1  face="verdana" color="blue">
If number of hits differ from what is listed, consider to change 'Ranking' or 'Max number of hits'!<br>
If radio button 'Headline' is lit, the database is searched using key 'Headline' of the PCRs. If 'Description' is lit, the full detailed description of the PCR will be searched.
</font>
<?php
terminate:
?>

</body>
</html>
