<!DOCTYPE html>
<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title><Report></title>
<!--   https://github.com/Regaddi/Chart.js/blob/9fe98b61c5c059bcf347508ac724d38f6eb83764/Chart.min.js  http://opensource.org/licenses/MIT -->
<script src="Chart.min.js"></script>
<script>


// ****** PIE ******
function DrawPie(TableName) {
//alert("haha");
   arrHeading=GetDataFromTableIDs(0,0,TableName);
   arrValue=GetDataFromTableIDs(1,0,TableName);

var arrColor=["#DDDDDD", "#DDCCDD", "#DDBBDD", "#DDAADD", "#DD99DD", "#DD88DD",
              "#DD77DD", "#DD66DD", "#DD55DD", "#DD44DD", "#DD33DD", "#DD22DD",
              "#DD11DD", "#DD00DD", "#DDDDCC", "#FFFFFF", "#FFFFFF", "#FFFFFF", 
              "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF" ];
var pieData=[];   // array of objects
   for(t=0; t<arrHeading.length; ++t) {
      arrHeading[t]=arrHeading[t].replace("&amp;","&");
      obj={        
         value: parseFloat(arrValue[t]),
         color: arrColor[t],
         label: arrHeading[t],
         labelColor: 'black',
         labelFontSize: '16'
      };
      pieData[t]=obj;
   }


//alert(pieData[0].value); 
//alert(pieData[1].label);  

   var myPie = new Chart(document.getElementById("canvas"+TableName).getContext("2d")).Pie(pieData, {
     labelAlign: 'center'
   });
}



function createChart()
        {
           var i,t;
           var arrData0=GetDataFromTableIDs(0,0);
           var arrData1=GetDataFromTableIDs(1,1);
           var arrData2=GetDataFromTableIDs(2,1);
           var arrData3=GetDataFromTableIDs(3,1);
           
           //EndIndex=FindEndIndexInSeria(arrData1, arrData2, arrData3);
           
           arrData0=ReduceDataSerieTo(30,arrData0);
           arrData1=ReduceDataSerieTo(30,arrData1);
           arrData2=ReduceDataSerieTo(30,arrData2);
           arrData3=ReduceDataSerieTo(30,arrData3);
           
           

            //Get the context of the canvas element we want to select
            var ctx = document.getElementById("myChart").getContext("2d");
 
            //Create the data object to pass to the chart
            var data = {
                labels : arrData0,
                datasets : [
                            {
                                fillColor : "rgba(180,180,180,0.5)",
                                strokeColor : "rgba(180,180,180,1)",
                                data : arrData1
                            },
                            {
                                fillColor : "rgba(151,187,205,0.5)",
                                strokeColor : "rgba(151,187,205,1)",
                                data : arrData2
                            },
                            {
                                fillColor : "rgba(141,167,185,0.5)",
                                strokeColor : "rgba(120,167,190,1)",
                                data : arrData3
                            }                           
                           ]
                      };
                      
 
            //The options we are going to pass to the chart
            options = {
               scaleFontColor: "#000",
           //  datasetStrokeWidth: 5,
                bezierCurve : false,
                pointDot : false,
                barDatasetSpacing : 15,
                barValueSpacing: 10,
                commonAxisSettings: {
                   label: {
                     overlappingBehaviour: { mode: 'rotate', rotationAngle: 80}
                   }
                }
            };
 
            //Create the chart
            new Chart(ctx).Line(data, options);
        }

function GetDataFromTableIDs(Col,Add,TableName) {
// Col = column of table to be returned
try {  // if code below fails, it will trigger catch
   var Row;
   var OldData;
   var arrData=new Array();
   Row=0;
   OldData=0; 
   while (1)
   {
     
      Id=TableName+'_'+Row+'_'+Col;
      if(Add) {  // Add previous data to coming (summation)
         intData=parseInt(document.getElementById(Id).innerHTML,10); // convert to integer
         arrData[Row]=OldData + intData; 
         OldData=arrData[Row];
              
      }
      else {  // Just get element as is ans store
         arrData[Row] = document.getElementById(Id).innerHTML; // will generate err if no more IDs      
      }
      //alert(arrData[Row]+" "+Row);
      
      Row++;
   }
}  
   catch(err) {
      // No more IDs in table      
   }
   return arrData;
   
}



</script>

</head>
<!--  <body onload="DrawPie();">   -->
<body>

<?php include("headerline.html"); ?>


<?php
if(isset($_POST["ReportPhases"])) {
   MakeReport();
   exit; 
}

function ListArray(&$arrToBeListed,$arrHeaders,$TableName) {
// &$arrToBeListed = array to be listed
// $arrHeaders = headers of list
// $TableName = name of ID tags e.g. 'Phase' and id could be Phase_0_1  (row=0,column=1)

   $Sum=0;
   foreach ($arrToBeListed as $k=>$v) {
      $Sum+=$v;
   }
   echo '<table border="0">';
   echo "<tr>";
   foreach ($arrHeaders as $Header) {
      echo '<th align="left">'.$Header."&nbsp;&nbsp;</th>";
   }
   echo "</tr>";
   $Row=0;
   foreach ($arrToBeListed as $k=>$v) {
      if($v==0) continue;  // take values of zero out
      echo '<tr>';
      if($v/$Sum*100 >=10) $Percentage=substr($v/$Sum*100,0,4);
      else $Percentage=substr($v/$Sum*100,0,3);
      $Percentage=str_pad($Percentage,3,"0",STR_PAD_LEFT);
      //echo "<td>".$k.'</td><td align="right">'.$v.'</td><td align="right">'.$Percentage."%</td>";
      $Col0='<td id="'.$TableName.'_'.$Row.'_0"'.">$k</td>";
      $Col1='<td align="right" id="'.$TableName.'_'.$Row.'_1"'.">$v</td>";
      $Col2='<td align="right" id="'.$TableName.'_'.$Row.'_2"'.">$Percentage</td>";
      echo "$Col0 $Col1 $Col2";
      $Row++;
      echo '</tr>';
   }
    
   echo '<tr><td><b>Total</b></td><td align="right"><b>'.$Sum.'</b></td><td align="right"><b>100.0%</b></td></tr>';
   echo '</table>';
   echo "<br><br>";
}

function MakeReport() {  
   $DetectedRelease=trim($_POST["DetectedRelease"]);
   $DetectedRelease=str_replace(".","_",$DetectedRelease);
   $DetectedRelease=str_replace("b","B",$DetectedRelease);
   $DetectedRelease=preg_replace("/[^0-9_B]/","",$DetectedRelease);   
   $_SESSION["search_DetectedRelease"]=$DetectedRelease;
   if($DetectedRelease<>"") $ShownReleases=$DetectedRelease;
   else $ShownReleases="All releases";
   
   
//   $m = new Mongo();
//   $db = $m->selectDB('testdb');
//   $collection = new MongoCollection($db, 'pcr');  // New version
   //$collection = new MongoCollection($db, 'oldpcr');  // online version
$m = new MongoDB\Client();
$db = $m->testdb;
$collection = $db->pcr;
   // *********************************   
   // Find distribution of ORIGIN
   ?>
   <canvas id="canvasOrigin" height="450" width="450"></canvas>
   <?PHP
   $arrKeyNum=array();
   echo "<br><b>Distribution of ORIGIN (DetectedRelease=$ShownReleases):</b>";
   ?>
   <a href="search_info_keys.html#origin" target="_blank">info</a> 
   <br>
   <?PHP
   $r=$collection->distinct("ORIGIN");
   foreach($r as $value) {
      $cursor=$collection->find(array("ORIGIN"=>$value, 'Version' => new MongoRegex("/$DetectedRelease/")));
      if(trim($value)=="") $value="NO_INFO";  
      $arrKeyNum[$value]=$cursor->count();
   }
   arsort($arrKeyNum);
   ListArray($arrKeyNum,array("ORIGIN","Count","Percentage"),"Origin");
   ?><script>DrawPie('Origin');</script><?PHP

   // **********************************
   // Find distribution of DetectionPhase
   ?>
   <hr><canvas id="canvasDetPhaseAll" height="450" width="450"></canvas>
   <?PHP
   echo "<br><b>Distribution of DetectionPhase (DetectedRelease=$ShownReleases):</b>";
   ?>
   <a href="search_info_keys.html#detectionphase" target="_blank">info</a> 
   <br>
   <?PHP
   $r=$collection->distinct("DetectionPhase");
   // $r is a mesh e.g. 'V&V V&V V&V' and another with just 'V&V'
   // So $r is made to a new list with all individual taken out (seperated by spaces)
   $arr=array();
   foreach($r as $value) {
      $e=explode(" ",trim($value));
      foreach($e as $ee) {
        if(trim($ee)=="") $ee="NO_INFO";  
        $arr[]=$ee;
      }      
   }
   $r=array_unique($arr);
   

   $arrKeyNum=array();
   foreach($r as $value) {
      $cursor=$collection->find(array("DetectionPhase"=>$value, 'Version' => new MongoRegex("/$DetectedRelease/")));
      $arrKeyNum[$value]=$cursor->count();
      //echo $value." : ".$cursor->count()."<br>";
   }
   arsort($arrKeyNum);
   ListArray($arrKeyNum,array("Phase","Count","Percentage"),"DetPhaseAll");
   ?><script>DrawPie('DetPhaseAll');</script><?PHP
   
   // *****************************
   // List DetectionPhase sorted on SAT and BCT
   ?>
   <hr><canvas id="canvasDetPhaseRed" height="450" width="450"></canvas>
   <?PHP
   echo "<br><b>Distribution of DetectionPhase with merged SATs and BCTs (DetectedRelease=$ShownReleases):</b>";
   ?>
   <a href="search_info_keys.html#detectionphase" target="_blank">info</a> 
   <br>
   <?PHP
   $arrSATBCT=array();
   $arrSATBCT["SAT"]=0;
   $arrSATBCT["BCT"]=0;
   foreach($arrKeyNum as $k=>$v) {
      if($v==0) continue; // skip all with no contribution
      elseif(substr($k,0,3)=="SAT") $arrSATBCT["SAT"]+=$v;
      elseif(substr($k,0,3)=="BCT") $arrSATBCT["BCT"]+=$v;
      else $arrSATBCT[$k]=$v;
   }
   arsort($arrSATBCT);
   ListArray($arrSATBCT,array("Phase","Count","Percentage"),"DetPhaseRed");
   ?><script>DrawPie('DetPhaseRed');</script><?PHP
  
   exit;
}

if(isset($_SESSION["search_DetectedRelease"])) $DetectedRelease=$_SESSION["search_DetectedRelease"];
else {
   $DetectedRelease="";
}
?>
<form action="search_report_phases.php" method="post">
The following creates a report of the distribution of PCRs based on ORIGIN and DetectionPhase.
<br><br>
Detected Release e.g. 'B2_4' for all Build 2.4 or empty for all builds<input type="text" name="DetectedRelease" value="<?php echo $DetectedRelease;?>" size="10"/>
<br>
<input type="submit" name="ReportPhases" value="Report"/>
</form>

</body>
</html> 


