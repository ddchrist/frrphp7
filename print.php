<?php include("login_headerscript.php");
require 'vendor/autoload.php';//composer require "mongodb/mongodb=^1.0.0.0"
 ?>

<?php
function RemoveHTMLTags( $string )
{
  $string = str_replace( "<!--", "----", $string );
  $string = str_replace( "-->", "----", $string );

  return $string;
}

// Will lookup given pcr number given in url line
// Call by using e.g.: http://localhost/print.php?pcr=B1_7566
// Also highlights text based on tags from $_SESSION['highlighttags']
// E.g. http://localhost/print.php?pcr=B1_7566&highlight=1
// If called with breaktext, the long text are not <pre> formatted but
// made possible to brake in order to be on one page
// E.g. print.php?pcr=B1_7410&breaktext=breaktext&highlight=1

// Check if session is not registered, redirect back to main page.
// Put this code in first line of web page.

// Insert highlighted tags into text
// This function read an array from session and for each elementCOMP00093802
// in the array: If it is present in $html, it is highlighted yellow
function HighlightTags( $html )
{
  if ( isset( $_SESSION['highlighttags'] ) )
  {
    $tagarray = $_SESSION['highlighttags'];
    foreach( $tagarray as $i )
    {
      // In order not get nested <font...>, unique tags are used before the font
      // is changed
      $html = str_ireplace( $i, '#£#'.$i.'#@#', $html );
    }
    
    $html = str_ireplace( '#£#', '<font style="BACKGROUND-COLOR: yellow">', $html );
    $html = str_ireplace( '#@#', '</font>', $html );
  } 
  
  return $html;
}
?>

<?php
// Include routines to handle selection of priority and severity for all countries
include 'pcr_severity_priority.php';
?>

<?php
if ( isset( $_POST['addtags'] ) )
{
  $pcrnum = $_POST['id'];
//  $m = new MongoClient();
//  $db = $m->testdb;
//  $collection = new MongoCollection( $db, 'pcr' );
   $m = new MongoDB\Client();
   $db = $m->testdb;
   $collection = $db->pcr;

  $query = array( 'id' => $pcrnum );
  $cursor = $collection->findOne( $query );
  $cursor["Tags"] = $_POST['pcrtags'];
  
//  $collection->save( $cursor );
  $upResult = $collection->updateOne(
   ['id' => $pcrnum],
   [ '$set' => [Tags => $_POST['pcrtags'] ]  ]
   );


  // Store in RecordsForPCRs
//  $collection = new MongoCollection( $db, 'RecordsForPCRs' );
   $collection = $db->RecordsForPCRs;

  $query = array( 'id' => $pcrnum );
  $cursor = $collection->findOne( $query );
  $cursor["id"] = $pcrnum;
  $cursor["Tags"] = $_POST['pcrtags'];
  
//  $collection->save( $cursor );
  $upResult = $collection->updateOne(
   ['id' => $pcrnum],
   [ '$set' => [Tags => $_POST['pcrtags'] ]  ]
   );


}
// Administrate moving forward and backwards based on outcome of search
elseif ( isset( $_POST['pcrforward'] ) )
{
  $pcrarray = $_SESSION["arrOfIDs"]; // Session contains all 'id's to be listed
  $pointer  = $_SESSION["pcrnumpointer"] + 1;

  if ( $pointer >= count( $pcrarray ) )
    $pointer = 0;
  
  $_SESSION["pcrnumpointer"] = $pointer;
  $pcrnum = $pcrarray[$pointer];
  
  if ( $pcrnum == "" )
    exit(0);
}
elseif ( isset( $_POST['pcrbackward'] ) )
{
   $pcrarray = $_SESSION["arrOfIDs"];
   $pointer  = $_SESSION["pcrnumpointer"] - 1;
   
   if ( $pointer < 0 )
     $pointer = count( $pcrarray ) - 1;
   
   $_SESSION["pcrnumpointer"] = $pointer;
   $pcrnum = $pcrarray[$pointer];
   
   if ( $pcrnum == "" )
     exit(0);
}
elseif ( isset( $_POST['addfavorite'] ) )
{
  header( "location:admin_add_favorite.php" );

  exit;  // make sure code below is not executed after execution of header
}

if ( isset( $_GET["id"] ) )
{
  $pcrnum = strtoupper( $_GET["id"] );
  $_SESSION["pcrnumpointer"] = 0;   // Used to move forward/backward in searches.

  // Clear higlighting text with tags if not requested.
  if ( !isset( $_GET['highlight'] ) )
    unset( $_SESSION['highlighttags'] );
}

if ( $pcrnum == "" )
  exit(0);

if ( isset( $_SESSION["breaktext"] ) )
  $breaktext = $_SESSION["breaktext"];
else
  $breaktext = "";

if ( isset( $_GET["breaktext"] ) )
  $breaktext = $_GET["breaktext"];
?>

<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

    <style type="text/css">
      a:link { color:#0000CC; }
      a:visited { color:#0000CC; }
      a:hover { color:orange; text-decoration: underline; }
      a:focus { color:green; }
      a:active { color:red; }

      A { text-decoration:none }  <!-- Remove underline in links -->
    </style>
 
    <title><?php echo $pcrnum;?></title>
  </head>
<body>

<?php 
// Headerline section.
include("headerline.html"); 
?>

<?php
//$m = new MongoClient();
//$db = $m->testdb;
//$collection = new MongoCollection( $db, 'pcr' );
   $m = new MongoDB\Client();
   $db = $m->testdb;
   $collection = $db->pcr;

$_SESSION["fullpcrnum"] = $pcrnum;

$query  = array( 'id' => $pcrnum );
$cursor = $collection->findOne( $query );
                     
// These are text to be inserted into table of 2 columns.
$arrTableText = array( 'id',                       'Creation_Date',
                       'OldPCRNum',                'External_id',
                       'State',                    '',
                       'Detected_Release',         'Detection_Date',
                       'Reproducibility',          'Detection_Phase',
                       'ATM_Severity',             'Pcr_Creator_FullNm',
                       'Detection_Origin',         'Originator_FullNm',
                       'Program_product',          'Report_Type',
                       'Element_Category',         'Element_name',
                       'Element_Nature',           '',
                       'ATM_Component_List',       'ATM_Functionality_List',
                       'ATM_Document_List',        'ATM_Test_Reference',
                       'ATM_Free_Keywords',        'ATM_Impacted_Requirement',
                       'ATM_Phase_Stage',          'ATM_Product_Flag',
                       'Headline',                 '',
                       'Injection_Phase',          'Expertise',
                       'Internal_priority',        'Postponed_Until',
                       'Decision_Date',            'Decision_Auth_Rep_FullNm',
                       'Realisation_Target_Date',  'Target_Release',
                       'Realisation_Date',         'Realisation_Release',
                       'Decision_Meeting_Type',    'Realisation_Resp_FullNm',
                       'Verification_Date',        'Verified_Release',
                       'Verification_OK',          'Verification_Resp_FullNm',
                       'Closure_Date',             'Closure_Reason',
                       'Delivered_Release',        'Closure_Auth_Rep_FullNm',
                       'Originator_Acceptance',    '',
                       'ATM_Safety',               'ATM_Safety_Ops',
                       'Parent_Pcr',               'Children_Pcrs',
                       'Continued_Pcr',            'Previous_Pcr',
                       'Identical_Pcrs',           'Processed_Pcr'
                     );						

// These are plain text not inserted into a table.
$arrPlainText = array( 'Description',              'Analysis_Results', 
                       'Decision_Description',     'Realisation_Description', 
                       'Verification_Description', 'Closure_Description',
                       'ATM_Safety_Analysis',      'Children_Pcrs_Realisation_Description', 
                       'Children_Pcrs_Verification_Description'
                     );
?>

<form action="print.php" method="post">

<?php
// Tags handling section.
if ( $_SESSION['username'] == "lbn" || $_SESSION['username'] == "jeo")
{
  echo 'Tags: <input type="text" name="id" value="'.$pcrnum.'" size="12" readonly />';
  echo '<input type="text" name="pcrtags" value="'.$cursor["Tags"].'" size="30" />';
  echo '<input type="submit" name="addtags" value="Submit"/>';
  echo '<br>';
}
?>

<?php
  /*
    Section for PCR Backward, PCR Forward, Add Favorit, Wrap text, Preformatted Text, Field descriptions and Links.
  */
?>

<input type="submit" name="pcrbackward" value="<<<"/>
<input type="submit" name="pcrforward" value=">>>"/>
<input type="submit" name="addfavorite" value="Add Favorite"/>

<font size=-1>
<?php
// Activate highligting if required.
if ( isset( $_SESSION['highlighttags'] ) )
  $highlight = "&highlight=yes";
else
  $highlight = "";
  
echo '<a href="print.php?id='.$pcrnum.'&breaktext=yes'.$highlight.'">Wrap Text</a>&nbsp;';
echo '<a href="print.php?id='.$pcrnum.'&breaktext=no'.$highlight.'">Preformatted Text</a>&nbsp;' ; 
?>

<a href="Exported_fields_description_20131003.html">Field descriptions</a>
</font>

<?php
// Make quicklinks to long texts.
?>

<font size=-2>
<?php
foreach ( $arrPlainText as $i )
{
  echo "<a href=#".$i.">".$i."</a>&nbsp;&nbsp;";
}
?>
</font>

<?php
echo "</form>";

// PCR heading section.
// If detected release contains true release number then take out COOPANS_ of number
// else submit whatever is written.
$DetRel = str_replace( "COOPANS_", "", strtoupper( $cursor['Detected_Release'] ) ); 

echo '<font size=+0.5><b>'.$DetRel.': '.$cursor['id'].' - "'.HighlightTags($cursor['Headline']).'"</b></font>&nbsp;&nbsp;';

// Find out whatever a Safety Assessment exist and mark it
// $RiskCollection = new MongoCollection( $db, 'risk_'.$_SESSION["country"] );
var_dump($db);
var_dump($_SESSION["country"]);
$RiskCollection = $db->'risk_'.$_SESSION["country"];

$query = array( "PCRID" => $cursor['id'] );
$RiskCursor = $RiskCollection->find( $query ); // there can be more variant of same ID

if ( $RiskCursor->count() > 0 )
{
  echo '<font style="background-color:yellow">Safety-Assessment(s):</font>';
  
  foreach( $RiskCursor as $o )
  {
    if ( isset( $o['PCRID'] ) && trim( $o['Mitigation'] ) <> "" )
      echo '<span title="Mitigation:'.$o['Mitigation'].'">'.'<a href="risk_view.php?pcr='.$o['PCRID'].'&Variant='.$o['Variant'].'">M'.$o['Variant'].'</span> ';
    else
      echo '<a href="risk_view.php?pcr='.$o['PCRID'].'&Variant='.$o['Variant'].'">R'.$o['Variant'].'</span> ';
  }   
}

// Indicate if Parent, Child, Continued, Processed, Previous or Identical PCR.
if ( isset( $cursor['Children_Pcrs'] ) && $cursor['Children_Pcrs'] <> "" )
  echo '<font color="green">(Parent)</font>';
  
if ( isset( $cursor['Parent_Pcr'] ) && $cursor['Parent_Pcr'] <> "" )
  echo '<font color="red">(Child)</font>';

if ( isset( $cursor['Continued_Pcr'] ) && $cursor['Continued_Pcr'] <> "" )
  echo '<font color="red">(Continued)</font>';

if ( isset( $cursor['Processed_Pcr'] ) && $cursor['Processed_Pcr'] <> "" )
  echo '<font color="red">(Processed)</font>';

if ( isset( $cursor['Previous_Pcr'] ) && $cursor['Previous_Pcr'] <> "" )
  echo '<font color="red">(Previous)</font>';

if ( isset( $cursor['Identical_Pcrs'] ) && $cursor['Identical_Pcrs'] <> "" )
  echo '<font color="red">(Identical)</font>';

// End of PCR heading section.
?>

<table border="0">

<?php
for ( $i = 0 ; $i < count( $arrTableText ); $i += 2)
{
  echo "<tr>";

  for ( $t = 0; $t <= 1; $t++ )
  {      
    // If record does not exist indicate that is does not.
    $Key = $arrTableText[$i+$t];
    if ( isset( $cursor[$Key] ) )
    {
      $Val = $cursor[$Key];
      if ( $Val == "" )
        $Val = "-";   
    }
    else
    {
      $Val = "No key in DB"; 
    } 
    
    // Remove time from all date keys.
    if ( strpos( $Key, "_Date" ) )
      $Val = substr( $Val, 0, 10 );
    
    // Make link on OldPCRNum.
    if ( $Key == "OldPCRNum" and $Val <> "-" and $Val <> "No key in DB" )
      $Val = '<a href="printGAIA.php?pcr='.$Val.'">'.$Val.'</a>';
      
    // Make link on children and parent pcrs
    if ( strpos( $Key, "_Pcr" ) and $Val <> "-" and $Val <> "No key in DB" )
    {
      $arrIDs = explode( chr(10), $Val ); // Seperated by line feed i.e. 10
      $Val = "";
    
      foreach( $arrIDs as $id )
      {
        $Val.='<a href="print.php?id='.$id.'">'.$id.'</a>&nbsp;';
      }
    }
    
	if ( $Val <> "No key in DB" )
    {
      $SpanKey = $Key;
      if ( $Key == "State" )
      {
        $SpanKey = '<span style="color:#C00000" title="Status explained:
State is imported as (State) from the ClearQuest database.

***State from ClearQuest:
Submitted
Analysed
Accepted
StartedWork
Solved
Verified
Closed">'.$Key.'</span>';
      }
      
      // in case key does not contains <span> then SpanKey=Ley
      echo '<td valign="top"><a href="search_reports.php?key='.$Key.'">'.$SpanKey.':</a></td>';
      echo '<td valign="top"><font size=-1>'.$Val."</font></td>";   
    }
	else
	{
	  $tmp = "";
      
      echo '<td valign="top">'.$tmp.'</td>';
      echo '<td valign="top">'.$tmp."</td>";
	}
  }
  
  echo "</tr>";
}

echo '</table><br>';

// Insert links to attachments.
if ( isset( $cursor["Attachments"] ) and $cursor["Attachments"] <> "" )
{
  echo "<b>Attachments:</b> <font size=-1>";
  
  foreach ( $cursor["Attachments"] as $Filename )
  {
    echo '<a href="attachments/'.$cursor["id"].'/'.$Filename.'">'.$Filename.'</a>&nbsp;&nbsp;&nbsp;';
  }
  
  echo "<br><br></font>";
}

// Make longer texts after table in selected format.
foreach ( $arrPlainText as $i )
{
  // If key is not set, set it
  if ( !isset( $cursor[$i] ) )
    $cursor[$i] = "No key in DB";

   // Make header text with Anchor.
   echo '<font COLOR="0000CC"><a name="'.$i.'">'.$i.':</a></font><br>';

   if ( $breaktext == "yes" )
   {
     $newstr = str_ireplace( "\n", '<br>', $cursor[$i] );
     
     echo '<br><font face="monospace" size=+.5>'.RemoveHTMLTags(HighlightTags($newstr)).'</font><br><br>';
   }
   else
   { 
     echo '<pre>'.RemoveHTMLTags(HighlightTags($cursor[$i])).'</pre>'; 
   }
}
?>

<br><a href="search_input.php">New Search</a><br>

<?php
session_write_close();  // Remember to close session fast to avoid locks in response.
?>

</body>
</html>
