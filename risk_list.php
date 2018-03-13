<?php include("login_headerscript.php"); ?>

<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title>Search IRF</title>
</head>
<body>
<?php include("headerline.html"); ?>

<?php


if(isset($_POST["risksearchnum"])) {
   $pcrnum=$_POST["risksearchnum"];
   $_SESSION["risksearchnum"]=$pcrnum;
   $query = array('PCRID' => new MongoRegex("/$pcrnum/i"));
//   $pcrnum=$_POST["risksearchnum"];
//   if($pcrnum != "") {
//      header("location:risk_view.php?pcr=$pcrnum&resetregularid=1");
   
}
elseif(isset($_POST["key0"])) {
   $key0=$_POST["key0"];  // set if multiple search query
   $query=GetAdvancedQuery();
}
else {
   echo 'No search criteria given';
   exit(0);
}


$time1=microtime(true);

$m = new MongoClient();
$db = $m->testdb;
$collection = new MongoCollection($db, 'risk_'.GetAdminMemberValue('country'));

$_SESSION["query"]=serialize($query);
$cursor = $collection->find($query);
?>

<?php
$arrItems = array(                        0 => 'PCRID', 
                                          1 => 'FailureMode',
                                          2 => 'Hazard',
                                          3 => 'Barriers',
                                          4 => 'EffectPre',
                                          5 => 'SeverityPre',
                                          6 => 'ProbabilityPre',
                                          7 => 'RiskPre',
                                          8 => 'Mitigation',
                                          9 => 'EffectPost',
                                          10 => 'SeverityPost',
                                          11 => 'ProbabilityPost',
                                          12 => 'RiskPost',
                                          13 => 'Approved');

?>
  <a href="risk_search.php">New Search</a>
  <?php
  echo '<a href="select2download.php?collection=risk_'.GetAdminMemberValue('country').'">Download</a>';
  
  $time2=microtime(true);

  echo ' ' . $cursor->count() . ' <b>document(s) found in </b><font size=-1>' . number_format(($time2-$time1),4) . ' s.</font><br>'
  ?>
  <font size=-1>Use ctrl-w to close browser tabs/windows. ctrl-link to avoid window opens</font>
  <?php // Insert form to open all PCR numbers in browser tabs ?>
  <hr>
  <?php

  echo '<table border="0">';
  $regularid=array();  // used to store in session so tab'ed windows can be opened
  foreach ($cursor as $obj) {
    $regularid[]=$obj['PCRID'];
    ?>
    <tr>
    <td><font size=-1  face="verdana"> 
        <?php
        echo '<a href="risk_view.php?pcr=' . $obj['PCRID'] . '&Variant='.$obj['Variant']. '"target="_blank">' . $obj['PCRID'].$obj['Variant'] . '</a>';
        ?>
        </font>
    </td>
    <td>
        <font size=-1><b>  <?php echo $obj['FailureMode']; ?>  </b></font>
        <font size=-2  face="verdana" color="blue">
        <?php
        ?>  </font>
    </td>
    <?php 
  }
  echo '</table>';
  ?><br><a href="risk_search.php">New Search</a><br><?php
  //session_start();
  $_SESSION["risk_regularid"]=$regularid;

  // Store results in $outstr and pass it on for a .csv fil download
  $outstr="";
  foreach ($cursor as $obj) {
    $outstr.= join(';', $obj)."\n";
  }
  
  echo '<a href="select2download.php?collection=risk_'.GetAdminMemberValue('country').'">Download as csv</a>'

?>

</body>
</html> 
<?php
session_write_close();  // remember to close session fast to avoid locks in response
?>
<?php
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
      $_SESSION["risk_select_key".$i]=$key[$i];
      $_SESSION["risk_select_log".$i]=$log[$i];
      $_SESSION["risk_select_op".$i]=$op[$i];
      $_SESSION["risk_select_val".$i]=$val[$i];
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
           $op[$i]='';
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
   // $cursor = $collection->find(array('Description' => new MongoRegex("/$irfnum/i")));

   // First html drop down selection / filter selection
   if ($key[0] <> "") {
      if ($op[0] == "=") $query = array($key[0] => $val[0]);
      elseif ($op[0] == "contains") $query = array($key[0] => new MongoRegex("/".$val[0]."/i"));
      else $query = array( $key[0] => array( $op[0] => $val[0] ) );
   }   
   else return($query);

   // Second, third and fourth html drop down selection / filter selection
   for ($i=1;$i<=3;$i++) {
      if ($key[$i] <> "") {
         if ($op[$i] == "=")  $query = array( $log[$i-1] => array( array($key[$i] => $val[$i]), $query));
         elseif ($op[$i] == "contains") $query = array( $log[$i-1] => array( array($key[$i] => new MongoRegex("/".$val[$i]."/i")), $query));
         else $query = array( $log[$i-1] => array( array( $key[$i] => array( $op[$i] => $val[$i] ) ), $query) );
      }   
      else return($query);
   }
   return($query);

}

function GetAdminMemberValue($key) {
   $m = new MongoCLient();
   $db = $m->admin;
   $collection = new MongoCollection($db, 'members');
   $query = array('user' => $_SESSION["username"]);
   $cursor = $collection->findone($query);
   return ($cursor[$key]);
}

?>
