<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<?php
// Include routines to handle selection of priority and severity for all countries
include 'pcr_severity_priority.php';
?>

<?php
if(isset($_SESSION["framesize"])) $framesize=$_SESSION["framesize"];
else $framesize=600;


// In case Preselected Filters must be save, get, delete divert from this script
if(isset($_POST["FilterCommand"])) {
   // as going to another script, all filters selections must be stored in session
   for($i=0;$i<4;$i++) {
      $_SESSION["select_key".$i] = $_POST["key$i"];
      if($i<3) $_SESSION["select_log".$i] = $_POST["log$i"];
      $_SESSION["select_op".$i] = $_POST["op$i"];
      $_SESSION["select_val".$i] = $_POST["val$i"];
   }
   header("location:search_preselected_filter.php?FilterCommand=". $_POST["FilterCommand"] . "&FilterType=PCRSearch&FilterName=" . $_POST["FilterName"] . "&SelectedFilter=". $_POST["SelectedFilter"]);
   exit;  // make sure code below is not executed after execution of header
}
elseif(isset($_POST["PCRSearch"])) {
   UpdateSearchStat($_SESSION['username'],"SearchPCR");
   // Get PCR Search inputs
   $id=strtoupper(trim($_POST["id"]));
   
   // Convert e.g. B2_123 to B2_0123 so it will match DB
   if(substr($id,0,1)=="B") {
      $arr=explode("_",$id);
      $id=$arr[0]."_".str_pad($arr[1],4,"0",STR_PAD_LEFT);
   }
   
   $Headline=trim($_POST["Headline"]);
   $ATMFreeKeywords=trim($_POST["ATMFreeKeywords"]);
   $TotalSearch=trim($_POST["TotalSearch"]);
   
   // $SearchCategory to contain word(s) to look for so it can highlight in listing
   if($Headline<>"") $SearchCategory=$Headline;
   elseif($ATMFreeKeywords<>"") $SearchCategory=$ATMFreeKeywords;
   elseif($TotalSearch<>"") $SearchCategory=$TotalSearch;
   elseif($id<>"") $SearchCategory=$id;
   else {
      echo "An unidentified key is used or all search keys are empty";
      exit;
   }

   if(isset($_POST["breaktext"])) $breaktext=$_POST["breaktext"];
   else $breaktext="";
   
   // Store PCR Search inputs to be recalled in submit form
   $_SESSION["id"]=$id;
   $_SESSION["Headline"]=$Headline;
   $_SESSION["ATM_Free_Keywords"]=$ATMFreeKeywords;
   $_SESSION["TotalSearch"]=$TotalSearch;
   
   $_SESSION["breaktext"]=$breaktext;

   // build query
   if ($id <> "") $query = array('id' => new MongoRegex("/$id/i"));
   else if ($ATMFreeKeywords <> "") $query = array('ATM_Free_Keywords' => new MongoRegex("/$ATMFreeKeywords/i"));
   else if ($Headline <> "") $query = array('Headline' => new MongoRegex("/$Headline/i"));
   else if ($TotalSearch <> "") {
      $query = array('$or' => array( array('TotalSearch' => new MongoRegex("/$TotalSearch/i")),
                                     array('Tags' => new MongoRegex("/$TotalSearch/i"))   ) );
   }
   // else if ($key0 <> "") $query=GetAdvancedQuery(); 
   else {
      echo 'No search criteria given';
      exit(0);
   }
   var_dump($query);
   BuildQueryToIncludeDetectedRelease($query);
}
elseif(isset($_POST["CombinedPCRSearch"])) {
   UpdateSearchStat($_SESSION['username'],"SearchCombined");
   $key0=$_POST["key0"];
   if ($key0 <> "") $query=GetAdvancedQuery();
   else {
      echo 'No search criteria given';
      exit;
   }
   
   BuildQueryToIncludeDetectedRelease($query);
   $TotalSearch="";
   $SearchCategory="";
   
}
?>
<!--<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
     "http://www.w3.org/TR/html4/strict.dtd">  -->
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 

<title>Results of search</title>
</head>
<body>
<?php include("headerline.html"); ?>
<?php

$time1=microtime(true);

//$m = new MongoClient();
//$db = $m->selectDB('testdb');
//$collection = new MongoCollection($db, 'pcr');
   $m = new MongoDB\Client();
   $db = $m->testdb;
   $collection = $db->pcr;

$collection->createIndex(array('id' => 1));
//mongo 2.6 and forward: $collection->createIndex(array('id' => 1));  // need to index id otherwise sort on id runs into internal memory limit of 32 MB, see http://docs.mongodb.org/manual/reference/limits/#Sorted-Documents


$_SESSION["query"]=serialize($query);
var_dump($query);
//$cursor = $collection->find($query)->sort(array('id'=>1));
$cursor = $collection->find(
[$query],
['sort' =>[ 'id'=>1]]
);

var_dump($cursor->count());

// if only one PCR is found go directly to show it using print.php
if($cursor->count()==1) {
   foreach ($cursor as $obj) {  // well there is only one object as count=1 ... so !
      $id=$obj['id'];
      if(substr($id,0,1)=="B") header("location:printGAIA.php?pcr=$id");
      else header("location:print.php?id=$id");
      exit;
   }
}
?>
  <a href="search_input.php">New Search</a>
  <a href="select2download.php?collection=pcr">Download</a>
  <?php
  $time2=microtime(true);

  echo ' ' . $cursor->count() . ' <b>document(s) found in </b><font size=-1>(' . number_format(($time2-$time1),4) . ' s).</font><br>'
  ?>
  <font size=-1>Use ctrl-w to close browser tabs/windows. <mark>ctrl-link to open new tabs.</mark> shift-link to open new window</font>

  <?php
  $arrOfIDs=array();  // used to store in session so queried PCRs can be shown in other scripts
  ?>
  <table border="0">
  <tr>
  <td valign="top"><font size=-1 face="verdana" color="blue"><u>id</u> (CQ or GAIA)</font></td><td><font size=-1  face="verdana"><b>Headline</b> {</font><font color="red">(Child), (Continued), (Processed), (Previous), (Identical), </font><font color="green">(Parent)</font><font color="black">} </font><font color="blue">Delivered_Release</font></td>
  </tr>
  <tr>
  <td valign="top"><font size=-1 face="verdana" color="blue">OldPCRNum<br>Detected_Release<br>External_id</font></td><td valign="top"><font size=-1 face="verdana">Part of Description. Size can be set by changing 'Framesize'</font></td>
  </tr>
  <?php
  foreach ($cursor as $obj) {
    $arrOfIDs[]=$obj['id'];

    ?>
    <tr>
    <td><font size=-1 face="verdana"> 
        <?php
        //echo '<a href="print.php?id='.str_pad($obj['id'],4,"0",STR_PAD_LEFT). '"target="_blank">'.str_pad($obj['id'],4,"0",STR_PAD_LEFT).'</a>'; 
        
        if(substr($obj['id'],0,1)=="B") echo '<a href="printGAIA.php?pcr='.$obj['id'].'">'.$obj['id'].'</a>&nbsp;&nbsp;&nbsp;';
        else echo '<a href="print.php?id='.$obj['id'].'">'.$obj['id'].'</a>&nbsp;&nbsp;&nbsp;';              
        ?>
        </font>
    </td>
    <td>
        <font size=-1><b><?php
          echo HighlightTextInString($SearchCategory,$obj['Headline']); 
          ?>  </b>
        <?php
        // indicate if mother or child PCR
        if(isset($obj['Children_Pcrs']) and $obj['Children_Pcrs']<>"") echo '<font color="green">(Parent)</font>';
        if(isset($obj['Parent_Pcr']) and $obj['Parent_Pcr']<>"") echo '<font color="red">(Child)</font>';
        if(isset($obj['Continued_Pcr']) and $obj['Continued_Pcr']<>"") echo '<font color="red">(Continued)</font>';
        if(isset($obj['Processed_Pcr']) and $obj['Processed_Pcr']<>"") echo '<font color="red">(Processed)</font>';
        if(isset($obj['Previous_Pcr']) and $obj['Previous_Pcr']<>"") echo '<font color="red">(Previous)</font>';
        if(isset($obj['Identical_Pcrs']) and $obj['Identical_Pcrs']<>"") echo '<font color="red">(Identical)</font>';
        
        ?>
        </font>
        <font size=-2  face="verdana" color="blue">
        <?php echo $obj['Delivered_Release']; //echo str_replace('COOPANS','',$obj['COR_VERS']);
         //  echo str_replace('_',' ',$tmpstring).'<br>';
        ?>  </font>
    </td>
    </tr>
    <tr>
    <td valign="top"><font size=-2  face="verdana" color="blue"> 
        <?php
        if($framesize>=300) {
           if(isset($obj["OldPCRNum"])) echo $obj['OldPCRNum'].'<br>';
           echo $obj['Detected_Release'].'<br>';
           echo $obj['External_id'].'<br>';
           
           //echo $obj['DocStatus']; // echo $obj['SWStatus']
        }
        ?>
        </font></td>
    <?php   // Takes out string area e.g. 300 characters before and after search criteria
    if ($SearchCategory <> "") {
       $i=stripos($obj['Description'],$SearchCategory);
       $endstring=$i+$framesize/2;
       $startstring=$i-$framesize/2;
       if ($startstring<=1) {
          $endstring=$endstring-$startstring;  // as startstring is negative it will increase endstring
          $startstring=0;
          
       }
       
       $ReducedDescription=substr($obj['Description'],$startstring,$endstring-$startstring);      
       $stringtoprint=HighlightTextInString($SearchCategory,$ReducedDescription);
    }
    else {
       $endstring=$framesize;
       $startstring=0;
       $stringtoprint=$obj['Description'];
       $stringtoprint=substr($stringtoprint,$startstring,$endstring-$startstring);
    }
    ?>
    <td><font size=-1>  
        <?php // strip_tags() can also be used but seems to perform difficult
              echo RemoveHTMLTags($stringtoprint); ?>
        </font></td>
    </tr>    
    <?php 
  }
  echo '</table>';
  ?><br><a href="search_input.php">New Search</a><br><?php
  //session_start();
  $_SESSION["arrOfIDs"]=$arrOfIDs;

  // Store results in $outstr and pass it on for a .csv file download
//  $outstr="";
//  foreach ($cursor as $obj) {
//    $outstr.= join(';', $obj)."\n";
//  }
  //$_SESSION["query"]=$outstr;  // used in downloadcsv.php
  
  ?><a href="select2download.php?collection=pcr">Download as csv</a><?php

?>

</body>
</html> 
<?php
session_write_close();  // remember to close session fast to avoid locks in response
?>
<?php
function HighlightTextInString($TextToHighlight,$StringToBeAnalysed)
{
   $ReplaceString='#£#'.$TextToHighlight.'#@#';       
   $StringToBeAnalysed=str_ireplace($TextToHighlight,$ReplaceString,$StringToBeAnalysed);
   $StringToBeAnalysed=str_replace('#£#','<span style="background-color: #FFFF00;">',$StringToBeAnalysed);
   $StringToBeAnalysed=str_replace('#@#','</span>',$StringToBeAnalysed);
   return $StringToBeAnalysed;
}

function GetAdvancedQuery()
{
// This function will return a combined query of up to four search
// criteria as specified in search....php file

   // This is selected keys in the database e.g. 'RegularID'
   $key[0]=$_POST["key0"];
   $key[1]=$_POST["key1"];
   $key[2]=$_POST["key2"];
   $key[3]=$_POST["key3"];

   // get 'and' or 'or'
   $log[0]=$_POST["log0"];
   $log[1]=$_POST["log1"];
   $log[2]=$_POST["log2"];
   $log[3]="";  // not used but kept as dummy - must be initialised

   // This is value / string to search for and can be anything
   $val[0]=$_POST["val0"];
   $val[1]=$_POST["val1"];
   $val[2]=$_POST["val2"];
   $val[3]=$_POST["val3"];

   // This is the logical operator and must translated into 'mongo'
   $op[0]=$_POST["op0"]; 
   $op[1]=$_POST["op1"];
   $op[2]=$_POST["op2"];
   $op[3]=$_POST["op3"];

   // Store selection so they will be default going back to selection
   for($i=0;$i<4;$i++) {
      $_SESSION["select_key".$i]=$key[$i];
      $_SESSION["select_log".$i]=$log[$i];
      $_SESSION["select_op".$i]=$op[$i];
      $_SESSION["select_val".$i]=$val[$i];
   }

   // make format '$and' or '$or'
   $log[0]='$' . $log[0];
   $log[1]='$' . $log[1];
   $log[2]='$' . $log[2]; 

   // For 'RegularID' and 'MeRMAId_PCR' they must be converted to integer
   for($i=0; $i<4; $i++) {
      if($key[$i]=="RegularID" || $key[$i]=="MeRMAId_PCR") $val[$i]=intval($val[$i]);
   }
   // Translation to mongo
   for($i=0; $i<4; $i++) {
      switch ($op[$i]) {
       case "equals":
           $op[$i]='=';
           break;
       case "notequal":
           $op[$i]='$ne';
           break;
       case "gre":
           $op[$i]='$gt';
           break;
       case "greequ":
           $op[$i]='$gte';
           break;
       case "les":
           $op[$i]='$lt';
           break;
       case "lesequ":
           $op[$i]='$lte';
           break;
       case "begin":   // do not know how to implement
           $op[$i]='';
           break;
       case "notbegin":  // do not know how to implement
           $val[$i]='';
           break;
       case "endwith":  // do not know how to implement
           $op[$i]='';
           break;
       case "notendwith":  // do not know how to implement
           $op[$i]='';
           break;
       case "contains":  // do not know how to implement
           $op[$i]='contains';
           break;
       case "notcontains":  // do not know how to implement
           $op[$i]='notcontains';
           break;
      }
   }

//   echo "  $key[0] $op[0] $val[0] $log[0]  ";

   // This is the combined search including ALL four critria.
   //$query = array( $log[2] => array(
   //             array( $key[3] => array( $op[3] => $val[3] ) ),
   //                                  array( $log[1] => array( 
   //                                                         array( $key[2] => array( $op[2] => $val[2] ) )   ,
   //                                                         array( $log[0] => array(
   //                                                              array( $key[1] => array( $op[1] => $val[1] ) )   ,
   //                                                              array( $key[0] => array( $op[0] => $val[0] ) )
   //                                                                                )
   //                                                              )
   //                                                         )
   //                                       )
   //                                )
   //              ) ;

   $query="";

   // $collection->find(array('Name' => new MongoRegex('/John/i')); matches John excatly  
   // $collection->find(array('Name' => new MongoRegex("/.*John.*/i")); also matches sub strings for John
   // $cursor = $collection->find(array('Description' => new MongoRegex("/$description/i")));

   // First html drop down selection / filter selection
   if ($key[0] <> "") {
      if ($op[0] == "=") $query = array($key[0] => $val[0]);
      elseif ($op[0] == "contains") $query = array($key[0] => new MongoRegex("/".$val[0]."/i"));
      elseif ($op[0] == "notcontains") $query = array($key[0] => new MongoRegex("/^((?!".$val[0].").)*$/i"));
      else $query = array( $key[0] => array( $op[0] => $val[0] ) );
   }   
   else return($query);

   // Second, third and fourth html drop down selection / filter selection
   for ($i=1;$i<=3;$i++) {
      if ($key[$i] <> "") {
         if ($op[$i] == "=")  $query = array( $log[$i-1] => array( array($key[$i] => $val[$i]), $query));
         elseif ($op[$i] == "contains") $query = array( $log[$i-1] => array( array($key[$i] => new MongoRegex("/".$val[$i]."/i")), $query));
         elseif ($op[$i] == "notcontains") $query = array( $log[$i-1] => array( array($key[$i] => new MongoRegex("/^((?!".$val[$i].").)*$/i")), $query)); 
         else $query = array( $log[$i-1] => array( array( $key[$i] => array( $op[$i] => $val[$i] ) ), $query) );
      }   
      else return($query);
   }
   return($query);

}
function RemoveHTMLTags($string) {
   // this is due to Thales database sometimes contains tagging that can
   // influence the output as HTML causing text not to be shown as otherwise
   // it would be interpretaed as a remark
   $string=str_replace("<!--", "----",$string);
   $string=str_replace("-->", "----",$string);
   return $string;
}


function BuildQueryToIncludeDetectedRelease(&$query)
{
   $username=$_SESSION['username'];
   $DetectedRelease=trim(GetAdminData($username."_DetectedRelease"));
   // Clean string for typical typos and store
   $DetectedRelease=str_replace(".","_",$DetectedRelease);
   $DetectedRelease=str_replace("b","B",$DetectedRelease);
   $DetectedRelease=str_replace(" ","",$DetectedRelease);
   //$DetectedRelease=preg_replace("/[^0-9_B]/","",$DetectedRelease);
   StoreAdminData($username."_DetectedRelease",$DetectedRelease);
   // Only change query if DetectionPhase is set
   if($DetectedRelease<>"") $query = array( '$and' => array( array('Detected_Release' => new MongoRegex("/".$DetectedRelease."/i")), $query));
   return;
}


?>
