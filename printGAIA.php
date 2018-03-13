<?php include("login_headerscript.php"); ?>

<?php
function RemoveHTMLTags($string) {
   $string=str_replace("<!--", "----",$string);
   $string=str_replace("-->", "----",$string);
   return $string;
}

// Will lookup given pcr number given in url line
// Call by using e.g.: http://localhost/print.php?pcr=B1_7566
// Also highlights text based on tags from $_SESSION['highlighttags']
// E.g. http://localhost/print.php?pcr=B1_7566&highlight=1
// If called with breaktext, the long text are not <pre> formatted but
// made possible to brake in order to be on one page
// E.g. print.php?pcr=B1_7410&breaktext=breaktext&highlight=1

// Check if session is not registered , redirect back to main page.
// Put this code in first line of web page.

// Insert highlighted tags into text
// This function read an array from session and for each element
// in the array: If it is present in $html, it is highlighted yellow
function HighlightTags($html)
{
   if(isset($_SESSION['highlighttags'])) {
      $tagarray=$_SESSION['highlighttags'];
      foreach($tagarray as $i) {
         // in order not get nested <font...>, unique tags are used before the font
         // is changed
         $html=str_ireplace($i,'#£#'.$i.'#@#',$html);
      }
      $html=str_ireplace('#£#','<font style="BACKGROUND-COLOR: yellow">',$html);
      $html=str_ireplace('#@#','</font>',$html);
   } 
   return $html;
}

?>

<?php
// Include routines to handle selection of priority and severity for all countries
include 'pcr_severity_priority.php';
?>


<?php
// Administrate moving forward and backwards based on outcome of search
if (isset($_POST['pcrforward'])) {
   $pcrarray=$_SESSION["regularid"];
   $pointer=$_SESSION["pcrnumpointer"]+1;
   if($pointer>=count($pcrarray)) $pointer=0;
   $_SESSION["pcrnumpointer"]=$pointer;
   $pcrnum=$pcrarray[$pointer];
   if($pcrnum=="") exit(0);
}
elseif (isset($_POST['pcrbackward'])) {
   $pcrarray=$_SESSION["regularid"];
   $pointer=$_SESSION["pcrnumpointer"]-1;
   if($pointer<0) $pointer=count($pcrarray)-1;
   $_SESSION["pcrnumpointer"]=$pointer;
   $pcrnum=$pcrarray[$pointer];
   if($pcrnum=="") exit(0);
}
elseif (isset($_POST['addfavorite'])) {
   header("location:admin_add_favorite.php");
   exit;  // make sure code below is not executed after execution of header
}

if(isset($_GET["pcr"])) {
   $pcrnum=strtoupper($_GET["pcr"]);
   // Make PCR_COOPANS_B2_1234 to B2_1234
   // Make PCR_COOPANS_7221 to B1_7221
   $pcrnum=str_replace("PCR_COOPANS_","",$pcrnum);
   if(substr($pcrnum,0,1)<>"B") $pcrnum="B1_".$pcrnum;
   // Make B2_0915 to B2_915 or B1_0915 to B1_915
   $arrpcr=explode("_",$pcrnum);
   $pcrnum=$arrpcr[0]."_".((integer) $arrpcr[1]);
   
   
   $_SESSION["pcrnumpointer"]=0;   // used to move forward/backward in searches
   // clear higlighting text with tags if not requested
   if(!isset($_GET['highlight'])) unset($_SESSION['highlighttags']);
}
if ($pcrnum == "") exit(0);
// Remember pcr number in session if requested (POST: mem=remember)
//if (isset($_GET["mem"])) $_SESSION["pcrnum"]=$pcrnum;
if (isset($_SESSION["breaktext"])) $breaktext=$_SESSION["breaktext"];
else $breaktext="";
if (isset($_GET["breaktext"])) {

   $breaktext=$_GET["breaktext"];
   // if ($breaktext=="no") $breaktext="";
}


?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title><?php echo $pcrnum;?></title>
</head>
<body>
<?php include("headerline.html"); ?>

<?php
$m = new MongoClient();
$db = $m->selectDB('testdb');
$collection = new MongoCollection($db, 'oldpcr');


$_SESSION["fullpcrnum"]=$pcrnum;

// pcrnum is e.g. B1_7221 where build or product name is the first part
// PCR is looked up in relation to its number and product name / build
$build=strtoupper(strstr($pcrnum,"_",true));
if($build=="B1") $productname="COOPANS";
elseif($build=="B2") $productname="COOPANS_B2";
$pcrnumint=strstr($pcrnum,"_");
$pcrnumint=substr($pcrnumint,1);  // take out "_" so only number is left
$pcrnumint=intval($pcrnumint); // make sure that value to search for is integer

$query=array('RegularID' => $pcrnumint);
$query=array( '$and' => array( array('ProductName' => $productname), $query));
$cursor = $collection->findOne($query);

// These are text to be inserted into table of 2 columns
$arrTableText = array('RegularID',         'ExternalID',
                      'AuthorLogin',       'DateOfCreation',
                      'ProductName',       'Version',
                      'Support',           'Site',
                      'Subject',           'Keywords',
                      'Type',              'Criticality',
                      'PCR_AUTHOR',        'TEST_ID',
                      'CSCI_NAME',         'ORIGIN',
                      'COR_VERS',          'BASELINE',
                      'OTHER_BASE_PCR',    'LINK_PCR',
                      'FREE_KEYWORDS',     'PATCH_COR',
                      'SystemReq',         'Priority',
                      'Safety',            'MeRMAId_PCR',
                      'PCRNo',             'Last_Updated',
                      'DocComp',           'Severity',
                      'S/W_cla/cor_date',  'S/W_status',
                      'Rel_PCR/ECR',       'Resp',
                      'S/W_clo/d??_Date',  'DetectionPhase',
                      'PhaseStage',        'DocStatus',
                      'Doc_cla/cor_date',  'Doc_clo/d??_Date' );
// These are plain text not inserted into a table
$arrPlainText = array('DOCUMENT_LINK', 'Description', 'Report', 'Answer','PCR_History'   );

?>
<form action="print.php" method="post">
<input type="submit" name="pcrbackward" value="<<<"/>
<input type="submit" name="pcrforward" value=">>>"/>
<input type="submit" name="addfavorite" value="Add Favorite"/>

<font size=-1>
<?php
// activate highligting if required
if(isset($_SESSION['highlighttags'])) $highlight="&highlight=yes";
else $highlight="";
echo '<a href="printGAIA.php?pcr='.$pcrnum.'&breaktext=yes'.$highlight.'">Wrap Text</a>&nbsp;';
echo '<a href="printGAIA.php?pcr='.$pcrnum.'&breaktext=no'.$highlight.'">Preformatted Text</a>&nbsp;&nbsp;&nbsp;' ; 
?>
</font>
<?php
// Make quicklinks to long texts
?><font size=-2><?php
foreach ($arrPlainText as $i) {
   echo "<a href=#".$i.">".$i."</a>&nbsp;&nbsp;";
}
?></font></form>
<?php



// List drop down selections for severity and priority for all countries
// Also list selection for risk assestment
/*if(1) {
   echo '<form action="pcr_update_priority_severity.php" method="post">';
   echo '<input type="submit" name="update_priority_severity" value="Store Severity and Priority"/>';
   echo '<input type="text" name="pcrnum" value="'.$pcrnum.'" size="7" readonly/>';
   echo '<input type="submit" name="risk" value="Risk"/>';
   echo ListSeverityPrioritySelections($pcrnum);
   echo '</form>';
}  */

// make PCR heading
echo '<font size=+0.5><b>PCR_'.$cursor['ProductName'].'_'.$cursor['RegularID'].' - "'.HighlightTags($cursor['Subject']).'"</b></font>&nbsp;&nbsp;';

// Get new PCR number i.e. COMPL000000...
$collection2 = new MongoCollection($db, 'pcr');
$RealPCRNum=str_replace("B1_","",$pcrnum);
$queryNew=array('OldPCRNum' => "PCR_COOPANS_".$RealPCRNum);
//echo "##PCR_COOPANS_".$pcrnum;
$cursorNew = $collection2->findOne($queryNew);
echo '<br><b>ClearQuest id: </b><a href="print.php?id='.$cursorNew["id"]. '"target="_blank">'.$cursorNew["id"].'</a>';


// make short text in table
?><table border="0"><?php
for($i=0 ; $i<count($arrTableText); $i+=2) {
   echo "<tr>";
   echo '<td valign="top"><font COLOR="0000CC">'.$arrTableText[$i].':</font></td>';
   echo '<td><font size=-1>'.$cursor[$arrTableText[$i]]."</font></td>";
   
   if($arrTableText[$i+1]=="S/W_status") {
   ?><td style="color:#C00000"><span title="S/W_Status explained:
act, act2: Active - Initial Status.
aii: Active - Insufficient Information (from originator).
atd, atd2: Analysed - To be Done. atd2 for not completely satisfied against factory tests.
ata: Active - To be Analysed.
ati: Active - To be Imported.
cla: Clarification required.
cla/dii: Clarification required, Proposed for drop because of insufficient information.
cla/dnp: Clarification required, Proposed for drop because it is not a problem.
cla/dnr: Clarification required, Proposed for drop because it is not reproducible.
cla/dnv: Clarification required, Proposed for drop because it is not in version.
cla/dup: Clarification required, Proposed for drop because it is a duplicated PCR.
cla/jcb: (for doc. PCR only) Waiting for JCCB agreement.  
cla/pcb: (for doc. PCR only) . Waiting for PCCB agreement.
cla/scb: Waiting for SCCB agreement.
cli: Closed Internally.
clo: Closed.
cor: Corrected.
dii: Dropped - Insufficient Information.
dnp: Dropped - Not a Problem.
dnr: Dropped - Not Reproducible.
dnv: Dropped - Not in version.
dup: Dropped - Duplicated PCR.">S/W_status:</span></td>
   <?php
   }
   else {
      echo '<td valign="top"><font COLOR="0000CC">'.$arrTableText[$i+1].':</font></td>';
   }
   echo '<td><font size=-1>'.$cursor[$arrTableText[$i+1]]."</font></td>";
   echo "</tr>";
}
echo '</table><br>';

// make longer texts after table in selected format
foreach ($arrPlainText as $i) {
   // make header text with Anchor
   echo '<font COLOR="0000CC"><a name="'.$i.'">'.$i.':</a></font><br>';
   //echo '<font face="monospace" size=-1>'.$cursor[$i].'</font><br>';

   if ($breaktext=="yes") {
      $newstr=str_ireplace("\n",'<br>',$cursor[$i]); 
      //$newstr=str_ireplace(" ",'&nbsp;',$newstr);
      echo '<br><font face="monospace" size=+.5>'.RemoveHTMLTags(HighlightTags($newstr)).'</font><br><br>';
   }
   else { 
      echo '<pre>'.RemoveHTMLTags(HighlightTags($cursor[$i])).'</pre>'; 
   }
 



}

//   echo $cursor[$i];

//}
//reset($cursor);
//while (list($key[], $val[])=each($cursor)) {

//   $cursoradmin = $collectionadmin->findOne(array('Key' => $key));
//   print_r(each($cursoradmin));

//}

//$arr = array('RegularID','ExternalID');
   

?><br><a href="search_pcr_pr_irf.php">New Search</a><br><?php

session_write_close();  // remember to close session fast to avoid locks in response

?>
</body>
</html> 
