<!DOCTYPE html>
<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title><Report></title>
<script src="Chart.js"></script>
<script>
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

function GetDataFromTableIDs(Col,Add) {

try {  // if code below fails, it will trigger catch
   var Row;
   var OldData;
   var arrData=new Array();
   Row=0;
   OldData=0; 
   while (1)
   {
     
      Id=Row+'_'+Col;
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

function ReduceDataSerieTo(ReqNumOfData,arrData) {
   var arrReducedData=new Array();
   LengthData=arrData.length;
   j=0;
   for(i=0;i<=LengthData;i+=LengthData/ReqNumOfData) {
      arrReducedData[j++]=arrData[Math.floor(i)];
      //alert(arrReducedData[j-1]+" "+arrData[i]+" "+Math.floor(i));
   }
   return arrReducedData;
}
function FindEndIndexInSeria(arrData1, arrData2, arrData3) {
   LengthData=arrData1.length;
   for(i=LengthData-1;i>0;i--) {
      alert(i+" "+arrData1[i]+" "+arrData2[i]+" "+arrData3[i]);
      if(arrData1[i]!=0 || arrData2[i]!=0 || arrData3[i]!=0) break;
      
   }
   alert(i);
   return i;
}

</script>

</head>
<!-- <body onload="createChart();">   -->
<body>

<?php include("headerline.html"); ?>


<?php

if(isset($_POST["ReportDates"])) {
   $_SESSION["search_StartDate"]=$_POST["StartDate"];   
   $DetectedRelease=trim($_POST["DetectedRelease"]);
   $DetectedRelease=str_replace(".","_",$DetectedRelease);
   $DetectedRelease=str_replace("b","B",$DetectedRelease);
   $DetectedRelease=preg_replace("/[^0-9_B]/","",$DetectedRelease);   
   $_SESSION["search_DetectedRelease"]=$DetectedRelease;
   if($DetectedRelease<>"") $ShownReleases=$DetectedRelease;
   else $ShownReleases="All releases";
?>
   <table border="0">
   <tr>
   <td align="center"><b>Accumulated PCRs over time - Mind the Gap<br>DetectedRelease=<?php echo $ShownReleases; ?></b></td>
   <td></td>
   </tr>
   <tr>
   <td>No. of PCRs<br>
   <canvas id="myChart" width="700" height="400"></canvas>
   </td>
   <td>
   <span style="color:#C00000" title="When a PCR is corrected by the software team, it is set to cor and a time stamp is added for tracking => S/W_cla/cor_date
Same when the software team refuse to correct it and put it to clarification.

Later on the process, when the validation team validate a PCR, it set it to clo if it is ok and a time stamp is added for tracking => S/W_clo/d??_Date
Same when the PCR is agreed to be a dup or a dnr.

So 
-	Date: is when the client create a PCR.
-	S/W_cla/cor_date is when software has finished correcting and give back a PCR to validation team
-	S/W_clo/d??_Date is when validation team has finished validation and give back PCR to the client
">Info:<br></span>
   <p style="color:rgb(180,180,180);">Top: Created</p>
   <p style="color:rgb(151,187,205);">Mid: Closed/Verified/Solved</p>
   <p style="color:rgb(120,167,190);">Bot: Closed</p>
   </td>
   <td>
    
   </td>
   </tr>
   </table>
<?php
   MakeReport();
   
   
   ?><script>createChart();</script><?PHP
   echo "</body></html>";
   exit; 
}

function MakeReport() { 
   $DetectedRelease=$_SESSION["search_DetectedRelease"];     
   $arrHeaders=array("Creation_Date", "Realisation_Date", "Closure_Date");
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'pcr');
   
   if($DetectedRelease<>"") {
         // 'Version' to be changed to 'Detected_Release' when clearQuest
      $query = array('Detected_Release' => new MongoRegex("/$DetectedRelease/i"), "id" => new MongoRegex("/^COMP/"));
   }
   else {     
      //$query = array('Detected_Release' => new MongoRegex("/B/"), "id" => new MongoRegex("/^COMP/"));  // which is any PCR or all - trick!
      $query = array("id" => new MongoRegex("/^COMP/"));
   }
?> 
<br>
<?php

   echo '<table border="0">';
   // Make Headers
   echo "<tr>";
   echo "<th>Period</th>";
   foreach($arrHeaders as $Header) {
      echo "<th>";
      echo "&nbsp;&nbsp;".$Header;
      echo "</th>";
   }
   echo "</tr>";
   $StartDate=trim($_POST["StartDate"]);
   if($StartDate=="") {
      $StartDate=date('Y-m-d',strtotime(date("Y-m-d") . " -2 years"));
      $_SESSION["search_StartDate"]=$StartDate;
   }
   if($_POST["period"]=="Months") ReportMonths($StartDate,$collection,$arrHeaders,$query);
   elseif($_POST["period"]=="Weeks") ReportWeeks($StartDate,$collection,$arrHeaders,$query);
   elseif($_POST["period"]=="Days") ReportDays($StartDate,$collection,$arrHeaders,$query);
   echo '</table>';
}


function ReportWeeks($YearMonthDay,$collection,&$arrHeaders,$queryRel) {
   $_SESSION["search_period"]="W";
   $YearMonthDay=date('Y-m-d',strtotime($YearMonthDay . " -1 weeks"));
   $Col=0;
   $Row=0;
   do {    
      echo "<tr>";
      $YearMonthDay=date('Y-m-d',strtotime($YearMonthDay . "+1 weeks"));
      $custom_date = strtotime( date('Y-m-d', strtotime($YearMonthDay)) ); 
      $week_start = date('Y-m-d', strtotime('this week last monday', $custom_date));
      $week_end = date('Y-m-d', strtotime('this week next sunday', $custom_date));

      echo '<td id="'.$Row."_".$Col++.'"'.">$week_start</td>";
      foreach($arrHeaders as $Header) {
         $query=array($Header => array(  '$gte' => $week_start,
                                         '$lte' => $week_end)    );
         $query=array('$and'=>array($queryRel,$query));
         $cursor = $collection->find($query);
         $Num=$cursor->count();
         echo '<td align="right" '.'id="'.$Row."_".$Col++.'"'.'>'.$Num."</td>";
      } 
      echo "</tr>";
      $Row++;
      $Col=0;
   } while ($YearMonthDay < date("Y-m-d"));

}
function ReportDays($YearMonthDay,$collection,&$arrHeaders,$queryRel) {
   $_SESSION["search_period"]="D";
   $YearMonthDay=date('Y-m-d',strtotime($YearMonthDay . " -1 days"));
   $Col=0;
   $Row=0;
   do {    
      echo "<tr>";
      $YearMonthDay=date('Y-m-d',strtotime($YearMonthDay . "+1 days"));

      echo '<td id="'.$Row."_".$Col++.'"'.">$YearMonthDay</td>";     
      foreach($arrHeaders as $Header) {
         $query = array("$Header" => new MongoRegex("/$YearMonthDay/"));
         $query=array('$and'=>array($queryRel,$query));
         $cursor = $collection->find($query);
         $Num=$cursor->count();
         echo '<td align="right" '.'id="'.$Row."_".$Col++.'"'.'>'.$Num."</td>";
      } 
      echo "</tr>";
      $Row++;
      $Col=0;
   } while ($YearMonthDay < date("Y-m-d"));
}


function ReportMonths($YearMonth,$collection,&$arrHeaders,$queryRel) {
   $_SESSION["search_period"]="M";
   $YearMonth=date('Y-m',strtotime($YearMonth . " -1 months"));
   $Col=0;
   $Row=0;
   do {    
      echo "<tr>";
      $YearMonth=date('Y-m',strtotime($YearMonth . "+1 months"));

      echo '<td id="'.$Row."_".$Col++.'"'.">$YearMonth</td>";
      foreach($arrHeaders as $Header) {
         $query = array("$Header" => new MongoRegex("/$YearMonth/"));
         $query=array('$and'=>array($queryRel,$query));
         $cursor = $collection->find($query);
         $Num=$cursor->count();
         echo '<td align="right" '.'id="'.$Row."_".$Col++.'"'.'>'.$Num."</td>";
      } 
      echo "</tr>";
      $Row++;
      $Col=0;
   } while ($YearMonth < date("Y-m"));
}

// Set default selected periods
if(isset($_SESSION["search_StartDate"])) $Start=$_SESSION["search_StartDate"];
else {
   $Start=date('Y-m-d',strtotime(date("Y-m-d") . " -2 years"));

}

if(isset($_SESSION["search_DetectedRelease"])) $DetectedRelease=$_SESSION["search_DetectedRelease"];
else {
   $DetectedRelease="";
}

if(isset($_SESSION["search_period"])) {
   if($_SESSION["search_period"]=="M") {
      $Month="checked";
      $Week="";
      $Day="";
   }
   elseif($_SESSION["search_period"]=="W") {
      $Month="";
      $Week="checked";
      $Day="";
   }
   else {
      $Month="";
      $Week="";
      $Day="checked";
   }
}
else {
      $Month="checked";
      $Week="";
      $Day="";
}
?>
<form action="search_report_accumulated_comp.php" method="post">
The following creates a report of number of PCRs over a period. The period is from a set beginning and until today. Be aware, that the PCR database is only updated from time to time and this means that the last data might not be correct i.e. PCRs missing. You will get an accumulated graph and a table with the data (actual registered - not accumulated). The table can be copy-pasted into Excel for further processing.
<br><br>Start date e.g. 2013-05 or 2013-05-01<input type="text" name="StartDate" value="<?php echo $Start;?>" size="10"/>
<br>
Detected Release e.g. 'B2_4' for all Build 2.4 or empty for all builds<input type="text" name="DetectedRelease" value="<?php echo $DetectedRelease;?>" size="10"/>
<br>
<input type="radio" name="period" value="Months" <?php echo $Month;?> />Months
<br>
<input type="radio" name="period" value="Weeks" <?php echo $Week;?> />Weeks
<br>
<input type="radio" name="period" value="Days" <?php echo $Day;?> />Days
<br>
<input type="submit" name="ReportDates" value="Report"/>
</form>

</body>
</html> 







