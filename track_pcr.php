<?php
// Accept PCR correction without login (please note it is done later again with login)
session_start();
if(!isset($_SESSION['username'])) {
   if(isset($_POST["AcceptPCRCorr"])) {
      InsertAcceptedToDB($_POST["Id"],$_POST["AcceptCorr"]);
      echo "<b>Thanks - status is updated!</b><br>";
      echo "IP address registered:<br>";
      RegisterIP(get_ip_address(),$_POST["Id"],$_POST["AcceptCorr"]);
      ShowIPRegLog($_POST["Id"]);
      exit;
   }
   elseif(isset($_GET["accept"])) {
      ManageAcceptANSPs($_GET["accept"]);
      exit;
   }
}

?>
<?php include("login_headerscript.php"); ?>
<?php include("class_lib.php"); ?>
<?php include("admin_functions.php"); ?>
<?php
if(isset($_POST["ChangeFramesize"])) {
   $_SESSION["framesize"]=$_POST["framesize"];
   header("location:search_input.php");
   exit;
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

<style type="text/css">

td {

    overflow: hidden;
    text-overflow: ellipsis;
}
table {
    border-collapse: collapse;
}

table, td, th {
    border: 3px solid black;
}




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
<script>
function SelectPCRStatus()
{

   if(document.getElementById('PCRStatus').value == 'RequestedCPPG')
   {
      arrHide=['ReqPCRType','ReqReqType','ReqReqBy','ReqCorrType','ReqComment','ReqWhyImp'];
      var i, len, s;
      len = arrHide.length
      for (i=0; i<len; ++i) {
        if (i in arrHide) {
          s = arrHide[i];
          var result_style = document.getElementById(s).style;
          result_style.display = 'table-row';

        }
      }
   }
   else if(document.getElementById('PCRStatus').value == 'Closed')
   {
      arrHide=['ReqClosureReason'];
      var i, len, s;
      len = arrHide.length
      for (i=0; i<len; ++i) {
        if (i in arrHide) {
          s = arrHide[i];
          var result_style = document.getElementById(s).style;
          result_style.display = 'table-row';

        }
      }
   }
}
</script>
<script src="sorttable.js"></script>
<script>
// table input text filter: http://codepen.io/chriscoyier/pen/tIuBL
(function(document) {
	'use strict';

	var LightTableFilter = (function(Arr) {

		var _input;

		function _onInputEvent(e) {
			_input = e.target;
			var tables = document.getElementsByClassName(_input.getAttribute('data-table'));
			Arr.forEach.call(tables, function(table) {
				Arr.forEach.call(table.tBodies, function(tbody) {
					Arr.forEach.call(tbody.rows, _filter);
				});
			});
		}

		function _filter(row) {
			var text = row.textContent.toLowerCase(), val = _input.value.toLowerCase();
			row.style.display = text.indexOf(val) === -1 ? 'none' : 'table-row';
		}

		return {
			init: function() {
				var inputs = document.getElementsByClassName('light-table-filter');
				Arr.forEach.call(inputs, function(input) {
					input.oninput = _onInputEvent;
				});
			}
		};
	})(Array.prototype);

	document.addEventListener('readystatechange', function() {
		if (document.readyState === 'complete') {
			LightTableFilter.init();
		}
	});

})(document);
</script>


<title>Track PCRs</title>
</head>
<body>
<?php include("headerline.html"); ?>
<?php

$_SESSION["ProjectName"]="COOPANS";  // dummy setting otherwise class_lib.php will not work

//print_r($_GET);
//print_r($_POST);

if(isset($_GET["report"])) {
   if($_GET["report"]=="cppg") CreateReqestedCPPGReport();
   exit;
}
elseif(isset($_GET["IPlog"])) {
   ShowIPRegLog($_GET["IPlog"]);
   exit;
}
elseif(isset($_POST["AcceptPCRCorr"])) {
   InsertAcceptedToDB($_POST["Id"],$_POST["AcceptCorr"]);
   echo "IP address registered: <br>";
   RegisterIP(get_ip_address(),$_POST["Id"],$_POST["AcceptCorr"]);
   header("location:track_pcr.php?report=cppg");
   exit;
}
elseif(isset($_GET["accept"])) {
   ManageAcceptANSPs($_GET["accept"]); // transfer COMP id
   echo '<br><a href="track_pcr.php?IPlog='.$_GET["accept"].'">Log</a>';
   exit;
}
elseif(isset($_POST["AddNewPCR"])) {
   ViewTrackOfPCR("");
   exit;
}
elseif(isset($_POST["Command"])) {
   $db_name="testdb"; // Database name
   $tbl_name="TrackPCR"; // Collection name

//   $m = new MongoClient();
//   $db = $m->selectDB($db_name);
//   $collection = new MongoCollection($db, $tbl_name);
$m = new MongoDB\Client();
$db = $m->$db_name;
$collection = $db->$tbl_name;
   $Id=trim($_POST["Id"]);
   if(strlen($Id)<>12) {
      echo "<b>ERROR!</b> - PCR number / Id format is incorrect. Must be 12 characters!";
      echo "<br>";
      foreach($_POST as $k=>$v) {
         echo "<br><b>".$k.":</b> ".$v;
      }
      exit;
   }

   $Log="";
   //$cursor=$collection->findone(array("id"=>$Id, "Country"=>$_SESSION["country"]));
   $_id=$_POST["_id"];  // mongo ID
   if($_id<>"") $cursor=$collection->findone(array("_id"=> new MongoID($_id)));
   else {
      $cursor=$collection->findone(array("id"=>$Id, "Country"=>$_SESSION["country"]));
      if($cursor<>NULL) {
         echo "Track of PCR $Id already exist";
         exit;
      }
      else $cursor=array();
   }
   if(isset($cursor["id"]) && $cursor["id"]<>$Id) {
      $cursor["id"]=$Id;
      $Log.="Changed id from ".$cursor["id"]." to ".$Id."\n";
   }
   if($cursor==NULL) {
      $cursor["id"]=$Id;
      $Log.="Set id to $Id\n";
   }

   $Headline=trim(GetHeadlineFromCQ($Id,$_POST["Headline"]));

   // Add last changed track pcr to array of last activities
   $arrLastVisitedTracks=GetAdminOnUser("TrackPCRs_LastVisited");
   $arrLastVisitedTrackHeadings=GetAdminOnUser("TrackPCRs_LastVisitedHeadings");
   if($arrLastVisitedTracks=="") {
      $arrLastVisitedTracks=array();
      $arrLastVisitedTrackHeadings=array();
   }

   // remove $Id if already in array
   $PosArray=array_search($Id, $arrLastVisitedTracks);
   if($PosArray) unset($arrLastVisitedTracks[$PosArray]);
      array_unshift($arrLastVisitedTracks, $Id); // add element to top of array
   array_unshift($arrLastVisitedTrackHeadings, $Headline); // add element to top of array
   $arrLastVisitedTracks=array_slice($arrLastVisitedTracks, 0, 5); // take out top five of array
   $arrLastVisitedTrackHeadings=array_slice($arrLastVisitedTrackHeadings, 0, 5); // take out top five of array
   StoreAdminOnUser("TrackPCRs_LastVisited",$arrLastVisitedTracks);
   StoreAdminOnUser("TrackPCRs_LastVisitedHeadings",$arrLastVisitedTrackHeadings);

   if(!isset($cursor["ClosureReason"])) $cursor["ClosureReason"]="";
   AddToLog("ClosureReason",$cursor["ClosureReason"],trim($_POST["ClosureReason"]),$Log);
   $cursor["ClosureReason"]=$_POST["ClosureReason"];
   if(!isset($cursor["Headline"])) $cursor["Headline"]="";
   AddToLog("Headline",$cursor["Headline"],trim($_POST["Headline"]),$Log);
   $cursor["Headline"]=$Headline;
   if(!isset($cursor["Conclusion"])) $cursor["Conclusion"]="";
   AddToLog("Conclusion",$cursor["Conclusion"],trim($_POST["Conclusion"]),$Log);
   $cursor["Conclusion"]=trim($_POST["Conclusion"]);
   if(!isset($cursor["Discussion"])) $cursor["Discussion"]="";
   AddToLog("Discussion",$cursor["Discussion"],trim($_POST["Discussion"]),$Log);
   $cursor["Discussion"]=trim($_POST["Discussion"]);
   if(!isset($cursor["Status"])) $cursor["Status"]="";
   AddToLog("Status",$cursor["Status"],trim($_POST["Status"]),$Log);
   $cursor["Status"]=$_POST["Status"];
   if(!isset($cursor["ReqRel"])) $cursor["ReqRel"]="";
   AddToLog("ReqRel",$cursor["ReqRel"],trim($_POST["ReqRel"]),$Log);
   $cursor["ReqRel"]=strtoupper(trim($_POST["ReqRel"]));
   if(!isset($cursor["IRFNum"])) $cursor["IRFNum"]="";
   AddToLog("IRFNum",$cursor["IRFNum"],trim($_POST["IRFNum"]),$Log);
   $cursor["IRFNum"]=strtoupper(trim($_POST["IRFNum"]));

   if(!isset($cursor["ReqPCRType"])) $cursor["ReqPCRType"]="";
   AddToLog("PCR type",$cursor["ReqPCRType"],trim($_POST["ReqPCRType"]),$Log);
   $cursor["ReqPCRType"]=trim($_POST["ReqPCRType"]);

   if(!isset($cursor["ReqReqType"])) $cursor["ReqReqType"]="";
   AddToLog("PCR Requested type",$cursor["ReqReqType"],trim($_POST["ReqReqType"]),$Log);
   $cursor["ReqReqType"]=trim($_POST["ReqReqType"]);

   if(!isset($cursor["ReqReqBy"])) $cursor["ReqReqBy"]="";
   AddToLog("Requested by",$cursor["ReqReqBy"],trim($_POST["ReqReqBy"]),$Log);
   $cursor["ReqReqBy"]=trim($_POST["ReqReqBy"]);

   if(!isset($cursor["ReqCorrType"])) $cursor["ReqCorrType"]="";
   AddToLog("PCR Correction type",$cursor["ReqCorrType"],trim($_POST["ReqCorrType"]),$Log);
   $cursor["ReqCorrType"]=trim($_POST["ReqCorrType"]);

   if(!isset($cursor["ReqComment"])) $cursor["ReqComment"]="";
   AddToLog("Comment Correction type",$cursor["ReqComment"],trim($_POST["ReqComment"]),$Log);
   $cursor["ReqComment"]=trim($_POST["ReqComment"]);

   if(!isset($cursor["ReqWhyImp"])) $cursor["ReqWhyImp"]="";
   AddToLog("Why Corrected",$cursor["ReqWhyImp"],trim($_POST["ReqWhyImp"]),$Log);
   $cursor["ReqWhyImp"]=trim($_POST["ReqWhyImp"]);

   $cursor["Country"]=$_SESSION["country"];
   if($Log<>"") {
      $Log=date("Y-m-d").' '.date("H:i:s").' *** <b>'.$_SESSION['username']."</b>\n".$Log;
      if(isset($cursor["Log"])) $cursor["Log"]=$Log."\n".$cursor["Log"];
      else $cursor["Log"]=$Log;
      $cursor["LastUpdate"]=date("Y-m-d").' '.date("H:i:s")." ".$_SESSION['username'];
   }
   $collection->save($cursor);

}
elseif(isset($_GET["id"])) {
   ViewTrackOfPCR($_GET["id"]);
   exit;
}
elseif(isset($_GET["StatusFilterSubmit"]) || isset($_GET["ClosureReason"]) ||
       isset($_GET["UnderTest"]) || isset($_GET["UnderInvestigation"]) ||
       isset($_GET["UnderDiscussion"]) || isset($_GET["RequestedCPPG"]) ||
       isset($_GET["RequestedThales"]) || isset($_GET["Closed"])  )
   {
   if(isset($_GET["check_status"]) && $_GET["check_status"]<>NULL) {
      foreach($_GET["check_status"] as $Status) {
         //$query[]=array('Status'=>$Status);
         $arrCheckStatus[$Status]=true; // prepare to store filter settings
      }
      StoreAdminOnUser("TrackPCRs_CheckStatus",$arrCheckStatus);
      StoreAdminOnUser("TrackPCRs_ClosureReason",$_GET["ClosureReason"]);
      //$query=array('$or'=>$query);
   }
   StoreAdminOnUser("TrackPCRs_FreeText",trim($_GET["FreeText"]));
}
elseif(isset($_GET["LogId"])) {
   echo "<b>Change log:</b><pre>".GetKeyFromTrackPCR(array('_id'=> new MongoId($_GET["LogId"])),"Log")."</pre>";
   exit;
}
elseif(isset($_GET["sort"])) {
   $_SESSION['TrackSort']=$_GET["sort"];
}

ShowListOfPCRs();
exit;

function ShowListOfPCRs()
{
   // ***************************************
   // List Tracked PCRs
   // ***************************************

   // the following line must be aligned with option later
   $options=array('UnderTest','UnderInvestigation','UnderDiscussion','RequestedCPPG','RequestedThales','Closed');
   $optioninfo=array('UnderTest: Problem is detected for discussion during V&V',
                     'UnderInvestigation: Problem needs further investigation after V&V',
                     'UnderDiscussion: Problem is operationally discussed for finding conclusions',
                     'RequestedCPPG: A solution of the problem shall be requested at CPPG',
                     'RequestedThales: A solution of the problem is requested to Thales',
                     'Closed: Problem is no longer a problem, corrected/implemented in code or rejected at CPPG/Thales (will still be an open PCR in ClearQuest)'
                    );

   echo '<form action="" method="get">';
   echo '<input type="submit" name="StatusFilterSubmit" value="Filter"/>';


   $query=array();
   $arrCheckStatus=GetAdminOnUser("TrackPCRs_CheckStatus"); // get filter settings

   // make deafult array if none yest (first user)
   if($arrCheckStatus==NULL || $arrCheckStatus=="") {
      foreach($options as $opt)
      {
         $arrCheckStatus[$opt]=1;
      //$arrCheckStatus=array('UnderTest'=>1,'UnderInvestigation'=>1,'UnderDiscussion'=>1,'RequestedCPPG'=>1,'RequestedThales'=>1);
      }
   }

   $t=0;
   foreach ($options as $opt) {
      if(isset($arrCheckStatus[$opt])) {
         $checked="checked";
         $query[]=array('Status'=>$opt);
      }
      else $checked="";
      $link= '<input type="checkbox" name="check_status[]" value="'.$opt.'" '.$checked.' onchange="this.form.submit()"/>'.$opt;
      echo '<span style="color:#C00000;" title="'.$optioninfo[$t++].'">'.$link."(".CountItems($opt).')</span>';
   }

   // ClosureReason handling
   $options=array(''=>'','Zombie'=>'Zombie','Duplication'=>'Duplication',
                  'ProbDisap'=>'ProbDisap', 'SafetyAnalysed'=>'SafetyAnalysed',
                  'NO_REASON' => 'NO_REASON');

   if(in_array(array('Status'=>'Closed'),$query)) {
      $StatClo=true;
      $ClosureReason=GetAdminOnUser("TrackPCRs_ClosureReason");
   }
   else {
      $StatClo=false;
      $ClosureReason=""; // make sure drop down is reset
   }

   echo generateSelect2('ClosureReason', $options, $ClosureReason,"this.form.submit()");
   echo '<span style="color:#C00000;" title="Closure Reason:
   Corrected: PCR corrected and no problem found.
   Work-a-round: PCR exist but another solution found.
   Zombie: The closed PCR might live up again if considered solved at a later stage.
   Duplicate: The same as another track.
   ProbDisap: Problem disappered.
   SafetyAnalysed: Problem is Safety Analysed and no longer considered an issue.
   NO_REASON: Closure Reason is not indicated or record does not exist.">ClosureReason</span>';

   $query=array('$or'=>$query);

   // ClosureReason handling
   if($ClosureReason=="NO_REASON" && $StatClo) {
      $query2=array('$or'=>array(
                              array('ClosureReason'=> array('$exists'=>false)),
                              array('ClosureReason'=>'')
                              ));
      $query=array('$and'=>array($query,$query2));
   }
   elseif($ClosureReason<>"" && $StatClo) {
      $query=array('$and'=>array($query,array("ClosureReason"=>$ClosureReason)));
   }

   $FreeText=GetAdminOnUser("TrackPCRs_FreeText");
   if($FreeText<>"") {
      $FreeText=str_replace(" ","|",$FreeText);
      $query2=array();
      $query2[]=array("Headline"=>new MongoRegex("/$FreeText/i"));
      $query2[]=array("Conclusion"=>new MongoRegex("/$FreeText/i"));
      $query2[]=array("Discussion"=>new MongoRegex("/$FreeText/i"));
      $query2=array('$or'=>$query2);
      $query=array('$and'=>array($query,$query2));
   }

   $db_name="testdb"; // Database name
   $tbl_name="TrackPCR"; // Collection name
//   $m = new MongoClient();
//   $db = $m->selectDB($db_name);
//   $collection = new MongoCollection($db, $tbl_name);
$m = new MongoDB\Client();
$db = $m->$db_name;
$collection = $db->$tbl_name;
   if($query==NULL) $query=array("Country"=>$_SESSION["country"]);
   else {
      $arr=array("Country"=>$_SESSION["country"]);
      $query=array('$and'=>array($query,$arr));
   }

   if(isset($_SESSION['TrackSort'])) $SortArr=array($_SESSION['TrackSort'] => -1, "id" => -1);
   else $SortArr=array("id" => -1);
   $options = array("sort" => $SortArr,);
   $cursor=$collection->find($query, $options);
//   $Hits=$cursor->count();
   $Hits=$collection->count($query);
   $HitsMax=CountItems("All");

?>
<br><b><span style="color:#C00000;" title="Enter one or several words to search in any of the keys 'Headline', 'Conclusion' and 'Discussion'. Regular expressions can also be used. Leave empty to search for anything. Note that this filter depends on the checkmark setting for PCR status as well - so make sure the right checks are enabled.
Regular Expression:
Or: Word1|Word2|Word3
And: ^(?=.*Word1)(?=.*Word2)(?=.*Word3)
Or and whole words (no fragtion of words): \Word1\b|\Word2\b
And and whole words (no fragtion of words): ^(?=.*\Word1\b)(?=.*\Word2\b)">Free Text to filter:</span></b><input type="text" name="FreeText" size="20" value="<?php echo $FreeText;?>" />
<?php
echo '<b> Found: ('.$Hits." of ".$HitsMax.')</b>';
echo '</form>';
echo "<hr>";
echo '<form action="track_pcr.php" method="post">';
echo '<input type="search" class="light-table-filter" data-table="order-table" placeholder="Table Filter">';
echo '<input type="submit" name="AddNewPCR" value="Add New PCR"/>';


// Show last track numbers of last modified tracks
echo '<font size="-1">';
echo "My Last updated tracks: ";
$arrLastVisitedTracks=GetAdminOnUser("TrackPCRs_LastVisited");
$arrLastVisitedTrackHeadings=GetAdminOnUser("TrackPCRs_LastVisitedHeadings");

if($arrLastVisitedTracks<>"") {
   for($t=0;$t<sizeof($arrLastVisitedTracks);$t++) {
      echo '<span title="'.$arrLastVisitedTrackHeadings[$t].'"><a style="color:#C00000;" href="track_pcr.php?id='.$arrLastVisitedTracks[$t].'">'.$arrLastVisitedTracks[$t].' </a></span>';
   }
}

echo '</font>';
echo '</form>';


?>



<!-- more classes are added by seperating classes with white spaces   -->
<table class="order-table table sortable">
<tr>
<!-- <th><a href="track_pcr.php?sort=id">id</a></th> -->
<th>id</th>
<th>SA</th>
<th>IRF#</th>
<!-- <th><a href="track_pcr.php?sort=ReqRel">ReqRel</a></th> -->
<th>ReqRel</th>
<th style="color:#666666;">RealRel<br><font size="-1">CQ-import</font></a></th>
<th style="color:#666666;">DetectedRel<br><font size="-1">CQ-import</font></a></th>
<th style="color:#666666;">State<br><font size="-1">CQ-import</font></a></th>
<!-- <th><a href="track_pcr.php?sort=Status">Status</a></th> -->
<th>Status</th>
<!-- <th><a href="track_pcr.php?sort=LastUpdate">Last Updated</a></th> -->
<th>Last Updated</th>
<th>Headline</th>
<?php
   foreach($cursor as $o) {
      $Id='<a href="track_pcr.php?id='.$o['id'].'">'.$o['id'].'</a>';
      $Headline=GetHeadlineFromCQ($o['id'],$o["Headline"]);
      if($Headline=="") $Headline="-";
      echo '<tr>';
      echo '<td>'.$Id.'</td>';
      echo '<td style="text-align: center";>';
      InsertSafetyAssessmentLink($o['id']);
      echo '</td>';


      $IRFNum=$o['IRFNum'];
      if(substr($IRFNum,0,2)=="DK" && substr($IRFNum,6,1)=="_") {
         $IRFID='<a href="http://10.17.43.200/view/'.$IRFNum.'">'.$IRFNum.'</a>';
      }
      else $IRFID=$IRFNum;
      if($IRFID=="") $IRFID="-";
      echo '<td>'.$IRFID.'</td>';

      $ReqRel=$o["ReqRel"];
      if($ReqRel=="") $ReqRel="-";
      echo '<td style="background-color:'.GetSWReleaseColour($ReqRel,$o).'">'.$ReqRel.'</td>';

      $RealRel=GetKeyFromCQ(array("id"=>$o['id']),"Realisation_Release");
      if($RealRel=="") $RealRel="-";
      else $RealRel=substr($RealRel,8);  // strip off COOPANS_ from number
      echo '<td style="background-color:'.GetSWReleaseColour($RealRel,$o).'">'.$RealRel.'</td>';

      $DetRel=GetKeyFromCQ(array("id"=>$o['id']),"Detected_Release");
      if($DetRel=="") $DetRel="-";
      else $DetRel=substr($DetRel,8);  // strip off COOPANS_ from number
      echo '<td>'.$DetRel.'</td>';

      $CQStatus=GetKeyFromCQ(array("id"=>$o['id']),"State");
      $ClosureReason=GetKeyFromCQ(array("id"=>$o['id']),"Closure_Reason");
      if($CQStatus=="Solved" || $CQStatus=="Verified" || $CQStatus=="Closed") $style='style="background-color:#FF5050;"';
      else $style="";
      if($CQStatus=="") $CQStatus="-";
      //else $CQStatus=substr($CQStatus,0,6);  // only show first letters of state
      if($ClosureReason<>"") $CQStatus="<i>$CQStatus</i>";
      echo "<td $style>".'<span title="'.$ClosureReason.'">'.$CQStatus.'</td>';

      if(!isset($o['ClosureReason'])) $o['ClosureReason']="";
      if($o['ClosureReason']<>"") $TrackCloReason="Closure Reason: ".$o['ClosureReason']."\n";
      else $TrackCloReason="";
      if(trim($o['Conclusion'])<>"") {
         echo '<td><span style="color:#C00000;" title="'.$TrackCloReason."Conclusion:\n".$o['Conclusion'].'">'.$o['Status'].'</span></td>';
      }
      elseif(trim($o['Discussion'])<>"") {
         echo '<td><span style="color:#C00000;" title="'."Conclusion: Not Indicated!\nDiscussion:\n".$o['Discussion'].'">'.$o['Status'].'</span></td>';
      }
      else {
         echo '<td><span style="background-color: #FFFF80; color:#C00000;" title="'."Conclusion: Not Indicated!\nDiscussion: Not Indicated!\nGet something done here to remove yellow highlight.".$o['Discussion'].'">'.$o['Status'].'</span></td>';
      }

      if(isset($o["LastUpdate"])) $LastUpdate=$o["LastUpdate"];
      else $LastUpdate="-";
      //echo '<td style="max-width: 100px;" nowrap><span style="color:#C00000;" title="'.$LastUpdate.'">'.$LastUpdate.'</span></td>';
      echo '<td style="max-width: 75px; text-overflow: clip; white-space: nowrap;"><span style="color:#C00000;" title="'.$LastUpdate.'">'.$LastUpdate.'</span></td>';

      echo '<td>'.$Headline.'</td>';
      echo '</tr>';
   }
   echo "</table>";
   echo '<form action="" method="post">';
   echo '<input type="submit" name="AddNewPCR" value="Add New PCR"/>';
   echo '<a href="track_pcr.php?report=cppg">Get CPPG report</a>';
   echo '</form>';

   ?>
   Note 'ReqRel' and 'RealRel' turns <span style="background-color: #FFFF80">yellow</span> if software release has been received within the last 14 days. If the release has been received and is more than 14 days old, they turn <span style="background-color: #FF5050">red</span>. In case the issue is closed, no colours will be used.<br>
   "State" is state from ClearQuest database. It will turn <span style="background-color: #FF5050">red</span> if PCR is either "Solved", "Verified" or "Closed". Hoover state to see "Closure Reason". "Closure Reason" is available if "State" is in italics.
   </body>
   </html>
   <?php
}

function InsertSafetyAssessmentLink($Id)
{
   $db_name="testdb"; // Database name
   $tbl_name='risk_'.$_SESSION["country"]; // Collection name
   $m = new MongoClient();
   $db = $m->$db_name;
   $collection = new MongoCollection($db, $tbl_name);
   $query = array("PCRID" => $Id);
   $cursor = $collection->find($query); // there can be more variant of same ID

   if($cursor->count()==0) echo '<a href="risk_view.php?pcr='.$Id.'">-';
   else {
      foreach($cursor as $o) {
        if(isset($o['PCRID']) && trim($o['Mitigation'])<>"") echo '<span title="Mitigation:
'.$o['Mitigation'].'">'.'<a href="risk_view.php?pcr='.$Id.'&Variant='.$o['Variant'].'">M'.$o['Variant'].'</span><br>';
        else echo '<a href="risk_view.php?pcr='.$Id.'&Variant='.$o['Variant'].'">R'.$o['Variant'].'</span><br>';
      }
   }
   return;
}

function GetSWReleaseColour($SWRel,&$o)
// Looks up the SW release in #release dates in COOPANS
// Return "yellow" if release is released and not 14 days old
// Return "red" if release is released but more than 14 days old
// Otherwise return white background
// If a "ReqRel" or "RealRel" (here just $SWRel) numbers contains 4th level,
//   it is taken out e.g: B2_5_16_3_P1 is converted to B2_5_16_P1

{
   if($o["Status"]=="Closed") return "#FFFFFF";  // white - already closed do not colour
   $SWRel=trim(strtoupper($SWRel)); // convert e.g. B2_5_16_p1 to B2_5_16_P1
   $SWRel=str_replace("_",".",$SWRel); // convert B2_5_15 to B2.5.15 format
   $OldSWSRel=$SWRel;
   $SWRel=explode(" ",$SWRel,2);  // e.g. 'B2.5.15 STEP1' into 'B2.5.15'
   $SWRel=$SWRel[0];
   $arrSWRel=explode(".",$SWRel,6);  // e.g. B2.5.15.1.P1 into B2 5 15 1 P1
//echo $SWRel."#";
   switch (substr_count($SWRel,".")) {
      case 0:   // e.g. '' or 'B2'
//echo "<br>";
         return "#FFFFFF";  // white - it is not a release number - eventually empty
         break;
      case 1:   // e.g. 'B2.5'
         $SWRel=$arrSWRel[0].".".$arrSWRel[1];  // Retrieve e.g. B2.5 as B2.5
         break;
      case 2:   // e.g. 'B2.5.2'
         $SWRel=$arrSWRel[0].".".$arrSWRel[1].".".$arrSWRel[2];  // Retrieve e.g. B2.5.2 as B2.5.2
         break;
      case 3:   // e.g. 'B2.5.2.1' or 'B2.5.2.P1'
         if(substr($arrSWRel[3],0,1) == "P")   // if e.g. B2.5.2.P1
         {
            $SWRel=$arrSWRel[0].".".$arrSWRel[1].".".$arrSWRel[2].".".$arrSWRel[3];  // Retrieve e.g. B2.5.2.P1 as B2.5.2.P1
         }
         else $SWRel=$arrSWRel[0].".".$arrSWRel[1].".".$arrSWRel[2];  // Retrieve e.g. B2.5.2.1 as B2.5.2
         break;
      case 4:   // e.g. 'B2.5.2.1.P1'
         if(substr($arrSWRel[4],0,1) == "P")   // if e.g. B2.5.2.1.P1
         {
            $SWRel=$arrSWRel[0].".".$arrSWRel[1].".".$arrSWRel[2].".".$arrSWRel[4];  // Retrieve e.g. B2.5.2.1.P1 as B2.5.2.P1
         }
         else $SWRel=$arrSWRel[0].".".$arrSWRel[1].".".$arrSWRel[2];  // Retrieve e.g. B2.5.2.1 as B2.5.2
         break;
   }

//echo $SWRel."<br>";

   $db_name="Projects"; // Database name
   $tbl_name="COOPANS"; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $query=array();
   //$query[]=array("WPName"=>new MongoRegex("/^$SWRel( |$)/")); // match release number as the number starting at beginning of string until spave or end of line.
   $query[]=array("WPName"=>new MongoRegex("/^$SWRel/")); // match release number as the number starting at beginning of string
   $query[]=array("Tags"=>new MongoRegex("/release/"));
   $query=array('$and'=>$query);
   $cursor=$collection->findone($query);

   //echo $cursor["WPNum"]." ".$SWRel." ".$cursor["WPName"]."<br>";

   if($cursor==NULL) return "#FFFFFF";  // white

   $SWReceivedDate=$cursor["StartDate"];
   $SWReceivedDatePlus2Week=date('Y-m-d',strtotime($SWReceivedDate . "+2 weeks"));
   $Today=date('Y-m-d');

   //echo "#$SWRel#$SWReceivedDate#$SWReceivedDatePlus2Week#$Today#<br>";
   if($Today >= $SWReceivedDate && $Today <= $SWReceivedDatePlus2Week) return "#FFFF80"; // Green
   elseif($Today > $SWReceivedDatePlus2Week) return "#FF5050";  // red
   else return "#FFFFFF";  // white
}

function CountItems($val)
{
   $db_name="testdb"; // Database name
   $tbl_name="TrackPCR"; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   if($val=="All") $query=array("Country"=>$_SESSION["country"]);
   else {
      $query=array("Status"=>$val , "Country"=>$_SESSION["country"]);
   }
   //$query=array("Country"=>$_SESSION["country"] , "Status"=>$val);
   $cursor=$collection->find($query);
   return($cursor->count());
}


function AddToLog($Text,$Before,$Now,&$Log)
{
   if($Before<>$Now) {
      $Log.="<b>$Text changed:</b>\n$Now\n";
   }

}
function ViewTrackOfPCR($Id)
{
   $db_name="testdb"; // Database name
   $tbl_name="TrackPCR"; // Collection name
   $m = new MongoClient();
   $db = $m->$db_name;
   $collection = new MongoCollection($db, $tbl_name);
   $cursor=$collection->findone(array("id"=>$Id, "Country"=>$_SESSION["country"]));
   $Headline=GetHeadlineFromCQ($cursor['id'],$cursor["Headline"]);
   $SizeHeadline=strlen($Headline)+5;
   if(isset($cursor['Conclusion'])) $Conclusion=$cursor['Conclusion'];
   else $Conclusion="";
   if(isset($cursor['Discussion'])) $Discussion=$cursor['Discussion'];
   else $Discussion="";
   if(isset($cursor['Status'])) $Status=$cursor['Status'];
   else $Status="";
   if(isset($cursor['ReqRel'])) $ReqRel=$cursor['ReqRel'];
   else $ReqRel="";
   if(isset($cursor['IRFNum'])) $IRFNum=$cursor['IRFNum'];
   else $IRFNum="";
   if(isset($cursor['_id'])) $_id=$cursor['_id'];
   else $_id="";

   ?>
   <form action="track_pcr.php" method="post">
   <table>
   <tr>
   <td><b>PCR number:</b></td>
   <td><input type="text" name="Id" size="14" value="<?php echo $Id;?>" />
   <a href="print.php?id=<?php echo $Id;?>" target="_blank"><?php echo "Open-PCR-in-new-tab";?></a>
   </td>
   </tr>
   <tr>
   <td><b>Headline:</b></td>
   <td><input type="text" name="Headline" size="<?php echo $SizeHeadline;?>" value="<?php echo $Headline;?>" /></td>
   </tr>
   <tr>
   <td><b>IRF number:</b></td>
   <td><input type="text" name="IRFNum" size="12" value="<?php echo $IRFNum;?>"/>
   <?php
   if(substr($IRFNum,0,2)=="DK" && substr($IRFNum,6,1)=="_") {
      echo '<a href="http://10.17.43.200/view/'.$IRFNum.'">'.$IRFNum.'</a>';
   }

   ?>
   e.g. 'DK1439_001'</td>
   </tr>
   <tr>
   <td><b>Requested Release:</b></td>
   <td><input type="text" name="ReqRel" size="12" value="<?php echo $ReqRel;?>" /> E.g. 'B2_2_1'</td>
   </tr>
   <tr>
   <td><b>PCR Status:</b></td>
   <?php
   $options=array('UnderTest'=>'UnderTest','UnderInvestigation'=>'UnderInvestigation','UnderDiscussion'=>'UnderDiscussion','RequestedCPPG'=>'RequestedCPPG','RequestedThales'=>'RequestedThales','Closed'=>'Closed');
   $selected=$Status;
   echo "<td>".generateSelect2('Status', $options, $selected,"SelectPCRStatus()")."</td>";


   // Closure Reason handling
   echo '<tr id="ReqClosureReason" style="display: none;">';
   $options=array(''=>'','Corrected'=>'Corrected','Work-a-round'=>'Work-a-round','Zombie'=>'Zombie','Duplication'=>'Duplication',
                  'ProbDisap'=>'ProbDisap', 'SafetyAnalysed'=>'SafetyAnalysed');
   if(isset($cursor['ClosureReason'])) $ClosureReason=$cursor['ClosureReason'];
   else $ClosureReason="";
   echo "<td><b>Closure Reason:</b></td>";
   echo "<td>".generateSelect2('ClosureReason', $options, $ClosureReason,"")."</td>";
   echo "</tr>";


   if(isset($cursor['ReqPCRType'])) $ReqPCRType=$cursor['ReqPCRType'];
   else $ReqPCRType="";
   if(isset($cursor['ReqReqType'])) $ReqReqType=$cursor['ReqReqType'];
   else $ReqReqType="";
   if(isset($cursor['ReqReqBy'])) $ReqReqBy=$cursor['ReqReqBy'];
   else {
      if($_SESSION["country"]=="dk") $ReqReqBy="Naviair";
   }
   if(isset($cursor['ReqCorrType'])) $ReqCorrType=$cursor['ReqCorrType'];
   else $ReqCorrType="";
   if(isset($cursor['ReqComment'])) $ReqComment=$cursor['ReqComment'];
   else $ReqComment="";
   if(isset($cursor['ReqWhyImp'])) $ReqWhyImp=$cursor['ReqWhyImp'];
   else $ReqWhyImp="";

   ?>

   </tr>
   <tr id="ReqPCRType" style="display: none;">
   <td><b><span style="color:#C00000;" title="BCT, SAR, JMMO, other">PCR type:</span></b></td>
   <td><input type="text" name="ReqPCRType" size="20" value="<?php echo $ReqPCRType;?>" /></td>
   </tr>

   </tr>
   <tr id="ReqReqType" style="display: none;">
   <td><b><span style="color:#C00000;" title="Blocking / Oppertunity">PCR Request type:</span></b></td>
   <td><input type="text" name="ReqReqType" size="20" value="<?php echo $ReqReqType;?>" /></td>
   </tr>

   </tr>
   <tr id="ReqReqBy" style="display: none;">
   <td><b>PCR Requested by:</b></td>
   <td><input type="text" name="ReqReqBy" size="10" value="<?php echo $ReqReqBy;?>" /></td>
   </tr>

   </tr>
   <tr id="ReqCorrType" style="display: none;">
   <td><b><span style="color:#C00000;" title="Normal, Regression, Reopen 'atd2', Reopen New PCR">PCR Correction type:</span></b></td>
   <td><input type="text" name="ReqCorrType" size="20" value="<?php echo $ReqCorrType;?>" /></td>
   </tr>

   </tr>
   <tr id="ReqComment" style="display: none;">
   <td><b><span style="color:#C00000;" title="Comment to the PCR Correction type">Comment Correction type:</span></b></td>
   <!-- <td><input type="text" name="ReqComment" size="50" value="<?php echo $ReqComment;?>" /></td> -->
   <td><textarea rows="2" cols="65" name="ReqComment"><?php echo $ReqComment;?></textarea></td>
   </tr>

   </tr>
   <tr id="ReqWhyImp" style="display: none;">
   <td><b><span style="color:#C00000;" title="Comment to why this PCR is correct to be corrected">Why Corrected:</span></b></td>

   <td><textarea rows="2" cols="65" name="ReqWhyImp"><?php echo $ReqWhyImp;?></textarea></td>
   </tr>

   </table>
   <b>Conclusion:</b><br>
   <textarea rows="10" cols="80" name="Conclusion"><?php echo $Conclusion;?></textarea>
   <br>
   <b>Discussion:</b><br>
   <textarea rows="10" cols="80" name="Discussion"><?php echo $Discussion;?></textarea>
   <br>
   <input type="submit" name="Command" value="Submit"/>
   <input type="text" name="_id" size="20" value="<?php echo $_id;?>" readonly />
   </form>
   <b>Note!</b> Only the administrator/responsible should make changes to the 'Conclusion' and 'PCR Status' fields.
   <hr>

   <script type="text/javascript">
   SelectPCRStatus();
   </script>


   <?php
   if(isset($cursor['Log'])) echo '<a href="track_pcr.php?LogId='.$cursor["_id"].'">Change Log</a><br>';

   if(isset($cursor["_id"]))  // only add to log if PCR already exist
   {
      $ViewLogarr=StoreWhoViewedIntoViewLog($cursor["_id"],$_SESSION["username"]);
      echo "<b>View Log:</b><pre>";
      foreach($ViewLogarr as $Stamp)
      {
         echo "$Stamp<br>";
      }
      echo "</b><pre>";
   }
   exit;
}

function StoreWhoViewedIntoViewLog($MongId,$user)
// Lookup in TrackPCR the selestec mongo id and add a log of time and user to the beginning
// of key "ViewLog". The ViewLog is returned as string.
{
   $db_name="testdb"; // Database name
   $tbl_name="TrackPCR"; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $cursor=$collection->findone(array('_id'=>new mongoid($MongId)));

   if(!isset($cursor["ViewLog"])) $cursor["ViewLog"]=array();
   $Stamp=array(date("Y-m-d").' '.date("H:i:s").' '.$user);
   $cursor["ViewLog"]=array_merge($Stamp,$cursor["ViewLog"]);
   $collection->save($cursor);
   return $cursor["ViewLog"];
}

function GetHeadlineFromCQ($Id, &$OldHeadline)
// returns headline from pcr base. If $Id does not exist, headline in $OldHeadline is returned
{
   $Val=GetKeyFromCQ(array("id"=>$Id),"Headline");
   if($Val=="") return $OldHeadline;
   else return $Val;
}
function GetKeyFromCQ($query,$Key)
{
   $db_name="testdb"; // Database name
   $tbl_name="pcr"; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $cursor=$collection->findone($query);
   if($cursor==NULL) return "";
   else return $cursor[$Key];
}
function GetKeyFromTrackPCR($query,$Key)
{
   $db_name="testdb"; // Database name
   $tbl_name="TrackPCR"; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $cursor=$collection->findone($query);
   if($cursor==NULL) return "";
   else return $cursor[$Key];
}


function GenerateSelect2($name = '', $options = array(), $nameselection, $onchange) {
// Generate a table based on the array input.
// Drop down list gets named: $name
// $options: The key of the array
// $value: The values of the array
// $nameselection: If set it is matched all $value and if it matches, it makes this value the default selection
// $onchange: javascript call if onchange. Leave empty "" if not used. Could be "this.form.submit()" or "SelectPCRStatus()"
// http://www.kavoir.com/2009/02/php-drop-down-list.html
// if $value starts with '*' it will be the selected in the drop down
   $onchange='onchange="'.$onchange; // activate autosubmit by javascript
   $html = '<select name="'.$name.'" id="PCRStatus" '.$onchange.'">';
   foreach ($options as $option => $value) {
      if ($value==$nameselection) {
         $html .= '<option selected value='.$value.'>'.$option.'</option>';
      }
      else $html .= '<option value='.$value.'>'.$option.'</option>';
   }
   $html .= '</select>';
   return $html;
}

function CreateReqestedCPPGReport()
{
   $db_name="testdb"; // Database name
   $tbl_name="TrackPCR"; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $query=array("Status"=>"RequestedCPPG" , "Country"=>$_SESSION["country"]);
   $SortArr=array("LastUpdate" => -1);
   $cursor=$collection->find($query)->sort($SortArr);
   echo '<input type="search" class="light-table-filter" data-table="order-table" placeholder="Table Filter">';
   echo '<table class="order-table table sortable">'; // more classes are added by seperating classes with white spaces
   echo "<tr>";
   echo "<th>PCR Number</th><th>Headline</th><th>Created</th><th>Detected Release</th><th>Status</th><th>S/W Component</th><th>PCR type</th><th>PCR Request Type</th><th>Requested by</th><th>Correction requested in release</th><th>Correction type</th><th>Comment to correction</th><th>Comment to why is important</th><th>Accepted ANSPs</th>";
   echo "</tr>";

   foreach($cursor as $obj) {
      $Id=$obj['id'];
      echo "<tr>";
      echo "<td>".'<a href="track_pcr.php?id='.$Id.'">'.$Id.'</a>'."</td>";
      echo "<td>".GetKeyFromCQ(array("id"=>$Id),"Headline")."</td>";
      echo "<td nowrap>".substr(GetKeyFromCQ(array("id"=>$Id),"Creation_Date"),0,10)."</td>";
      $DetRel=trim(GetKeyFromCQ(array("id"=>$Id),"Detected_Release"));
      if($DetRel=="") $DetRel="-";
      $DetRel=str_replace("COOPANS_","",$DetRel);  // get rid of COOPANS_
      echo "<td>".$DetRel."</td>";
      echo "<td>".GetKeyFromCQ(array("id"=>$Id),"State")."</td>";
      echo "<td>".GetKeyFromCQ(array("id"=>$Id),"ATM_Component_List")."</td>";

      echo "<td>".GetObjectIfExist($obj,"ReqPCRType")."</td>";
      echo "<td>".GetObjectIfExist($obj,"ReqReqType")."</td>";
      echo "<td>".GetObjectIfExist($obj,"ReqReqBy")."</td>";
      echo "<td>".GetObjectIfExist($obj,"ReqRel")."</td>";
      echo "<td>".GetObjectIfExist($obj,"ReqCorrType")."</td>";
      echo "<td>".GetObjectIfExist($obj,"ReqComment")."</td>";
      echo "<td>".GetObjectIfExist($obj,"ReqWhyImp")."</td>";
      $NumNotAccepted=CorrectionNotAccepted($obj);
      if($NumNotAccepted==0) echo '<td style="background-color: #00C000;"><a href="track_pcr.php?accept='.$Id.'">Yes</a></td>';
      else echo '<td style="background-color: #FF8080;"><a href="track_pcr.php?accept='.$Id.'">No('.$NumNotAccepted.')</a></td>';

      echo "</tr>";
   }
   echo "</table>";
}
function GetObjectIfExist(&$obj,$key)
{
   if(isset($obj[$key]))
   {
     $val=trim($obj[$key]);
     if($val=="") return "-";
     else return $obj[$key];
   }
   else return "-";
}
function CorrectionNotAccepted(&$obj)
{
   // Count and return the number of NOT accepted corrections (not accepted by countries)
   // If all countries has accepted, it will return 0
   $arrAcceptPossibleNames=array('Naviair','LVF','CCL','ACG','IAA');
   if(isset($obj["Accepted"])) {
      $arrAccept=$obj["Accepted"];
      $NumOfNo=0;
      foreach($arrAcceptPossibleNames as $name)
      {
        if(!in_array($name,$arrAccept)) $NumOfNo++;
      }
      return $NumOfNo;
   }
   return sizeof($arrAcceptPossibleNames);  // all have not accepted
}
function ManageAcceptANSPs($Id)
{
   $db_name="testdb"; // Database name
   $tbl_name="TrackPCR"; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $cursor=$collection->findone(array('id'=>$Id));
   if(isset($cursor['Accepted'])) $arrAccept=$cursor['Accepted'];
   else $arrAccept=array();
   $arrAcceptPossibleNames=array('Naviair','LVF','CCL','ACG','IAA');
   echo '<form action="track_pcr.php" method="post">';
   echo 'Accept correction to PCR: <input type="text" name="Id" size="14" value="'.$Id.'" readonly/>';
   echo '<a href="print.php?id='.$Id.'">'.$Id.'</a><br>';


   foreach($arrAcceptPossibleNames as $name)
   {
      if(in_array($name,$arrAccept))
      {
         echo '<input type="checkbox" name="AcceptCorr[]" value="'.$name.'" checked>'.$name.'<br>';
      }
      else
      {
         echo '<input type="checkbox" name="AcceptCorr[]" value="'.$name.'">'.$name.'<br>';
      }
   }
   echo '<input type="submit" name="AcceptPCRCorr" value="Submit">';
   echo '</form>';
}
function InsertAcceptedToDB($Id,$arrAccept)
{
   if($arrAccept==NULL) $arrAccept=array();
   // $arrAccept is an array containing selected accepted ANSPs
   $db_name="testdb"; // Database name
   $tbl_name="TrackPCR"; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $cursor=$collection->findone(array('id'=>$Id));

   $ListAccept=array();
   $arrAcceptPossibleNames=array('Naviair','LVF','CCL','ACG','IAA');
   foreach($arrAcceptPossibleNames as $name)
   {
      if(in_array($name,$arrAccept)) $ListAccept[]=$name;
   }
   $cursor['Accepted']=$ListAccept;
   $collection->save($cursor);
}

function RegisterIP($ip,$Id,$AcceptCorr)
{

   if(isset($_SESSION['username'])) $ip.="-".$_SESSION['username'];
   $db_name="testdb"; // Database name
   $tbl_name="TrackPCR"; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $cursor=$collection->findone(array('id'=>$Id));
   if(!isset($cursor['IPlog'])) $cursor['IPlog']="";
   if($AcceptCorr==NULL) {
      $AcceptCorr=array();
      $tmp="All deselected";
   }
   else $tmp="";
   foreach($AcceptCorr as $name)
   {
     $tmp.=$name.",";
   }
   $cursor['IPlog']="<b>".$ip.":</b> ".$tmp."<br>".$cursor['IPlog'];
   //echo $cursor['IPlog'];
   $collection->save($cursor);
}
function ShowIPRegLog($Id)
{
   $db_name="testdb"; // Database name
   $tbl_name="TrackPCR"; // Collection name
   $m = new MongoClient();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);
   $cursor=$collection->findone(array('id'=>$Id));
   echo '<pre><font size="-1">';
   if(isset($cursor['IPlog'])) echo $cursor['IPlog'];
   else echo "Log empty!";
   echo '</font></pre>';
}
function get_ip_address() {
    // check for shared internet/ISP IP
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    // check for IPs passing through proxies
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // check if multiple ips exist in var
        if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
            $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($iplist as $ip) {
                if (validate_ip($ip))
                    return $ip;
            }
        } else {
            if (validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED']) && validate_ip($_SERVER['HTTP_X_FORWARDED']))
        return $_SERVER['HTTP_X_FORWARDED'];
    if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
        return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && validate_ip($_SERVER['HTTP_FORWARDED_FOR']))
        return $_SERVER['HTTP_FORWARDED_FOR'];
    if (!empty($_SERVER['HTTP_FORWARDED']) && validate_ip($_SERVER['HTTP_FORWARDED']))
        return $_SERVER['HTTP_FORWARDED'];

    // return unreliable ip since all else failed
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Ensures an ip address is both a valid IP and does not fall within
 * a private network range.
 */
function validate_ip($ip) {
    if (strtolower($ip) === 'unknown')
        return false;

    // generate ipv4 network address
    $ip = ip2long($ip);

    // if the ip is set and not equivalent to 255.255.255.255
    if ($ip !== false && $ip !== -1) {
        // make sure to get unsigned long representation of ip
        // due to discrepancies between 32 and 64 bit OSes and
        // signed numbers (ints default to signed in PHP)
        $ip = sprintf('%u', $ip);
        // do private network range checking
        if ($ip >= 0 && $ip <= 50331647) return false;
        if ($ip >= 167772160 && $ip <= 184549375) return false;
        if ($ip >= 2130706432 && $ip <= 2147483647) return false;
        if ($ip >= 2851995648 && $ip <= 2852061183) return false;
        if ($ip >= 2886729728 && $ip <= 2887778303) return false;
        if ($ip >= 3221225984 && $ip <= 3221226239) return false;
        if ($ip >= 3232235520 && $ip <= 3232301055) return false;
        if ($ip >= 4294967040) return false;
    }
    return true;
}
