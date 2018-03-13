<?php include("login_headerscript.php"); ?>
<?php include("class_lib.php"); ?>
<?php include("admin_functions.php"); ?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Admin Project</title>
</head>
<body>
<?php

$debug=false;
// Get connection to all WPs
$obj=new db_handling(array("Backup" => array('$exists' => false)),"");
$obj->get_cursor_find_sort();

//$obj2=new db_handling(array('WPNum' => $o['WPNum']),"Backup");
//      $obj2->DataBase="Checklist";
//      $obj2->get_cursor_findone();

//$oList=new db_handling(array('WPNum' => new MongoRegex("/^$WPNumFrom/")),"Backup");
//      $oList->get_cursor_find();

// Drop content of DB
$m = new Mongo();
$db = $m->selectDB('Projects');
$collection = new MongoCollection($db, 'COOPANS_Cockpit');
$response=$collection->drop();

//$objDate=new db_handling(array(),"Backup");
//$objDate->CollectionName="COOPANS_Cockpit";
//$objDate->get_cursor_find();
//$arrDates=$objDate->cursor;


$Header=array();
// Count over all WPs
foreach ($obj->cursor as $oList) {
   // Store super header names
   $HeaderLevel=substr_count($oList["WPNum"], ".");
   $Header[$HeaderLevel]=$oList["WPName"];
   // clear the following headers
   for($t=$HeaderLevel+1;$t<5;$t++) {
      $Header[$t]=""; 
   }

   if(isset($oList["Ressources"])) {
      // if dates are not given or incorrect then exit
      if(isset($oList["StartDate"]) and isset($oList["EndDate"]) and $oList["StartDate"]<>"" and $oList["EndDate"]<>"") {
       
         $StartDate=$oList["StartDate"];
         $EndDate=$oList["EndDate"];
         // http://stackoverflow.com/questions/4233605/elegant-way-to-get-the-count-of-months-between-two-dates
         $date1 = new DateTime($StartDate);
         $date2 = new DateTime($EndDate);
         $interval = date_diff($date1, $date2);
         $NumOfMonths = ($interval->format('%m'))+1;  // number of months between date1 and date2
         //$ManDaysPerMonth=$ManDays/$DurationMonths;

//         print_r($oList["WPNum"]);
//         echo " -- ".$StartDate." ".$EndDate." ".$NumOfMonths;
//         echo "<br>";
         // list all ressources in WP
         foreach($oList["Ressources"] as $oSub) {
            if(trim($oSub["AllRes"])<>"") $Subj=trim($oSub["AllRes"]); // Subj. = Allocated Ressource
            elseif(trim($oSub["ReqRes"])<>"") $Subj=trim($oSub["ReqRes"]); // Subj. = Requested Ressource
            else $Subj=trim($oSub["Roles"]); // Subj. = Requested Ressource               

            $objDate=new db_handling(array('Subj' => $Subj),"Backup");
            $objDate->CollectionName="COOPANS_Cockpit";
            $objDate->get_cursor_findone();
            $objDate->cursor['Subj']=$Subj;   // overwrite if exist otherwise creates a new


            // Store all dates
            $StartMonth=intval(substr($StartDate,5,2));
            //$StartMonth=date("m",strtotime($StartDate));
            $StartYear=intval(substr($EndDate,0,4));
//            echo $StartMonth." ".$StartYear." ";

            // Build headers into text
            $HeaderText="";
            for($u=1;$u<4;$u++) {
               if(isset($Header[$u])) {
                  $HeaderText.=$Header[$u]." : ";
               }
            }

            // Each ressource entry is aligned within a start date and a end date for the WP
            // The following counts over this period in order to create an entry for each month.
            for($t=0;$t<$NumOfMonths;$t++) {
               
               $ManDays=$oSub["ManDays"]/$NumOfMonths;
               $ManDaysFormatted=number_format($ManDays, 1, ',', ' ');
               $text=$ManDaysFormatted." : ".$oList["WPNum"]." : ".$HeaderText;
               $FormattedMonth=str_pad($StartMonth, 2, '0', STR_PAD_LEFT); // add leading zero to months 1 to 9
//               $data=array("$StartYear-$FormattedMonth" => array("Subj" => $Subj,
//                                                             "Text" => $text));
//               print_r($data);
               if(isset($objDate->cursor["$StartYear-$FormattedMonth"])) {
                  $arrDateExisting=$objDate->cursor["$StartYear-$FormattedMonth"];
                  if($arrDateExisting["Subj"] == $Subj) {
                     $objOld=$objDate->cursor["$StartYear-$FormattedMonth"];
                     $objOld['ManDays']+=$ManDays;
                     //$objOld['Tasks'][]= array('Text'=>$text);
                     print_r($objOld['Tasks']);
                     echo "<br>";
                     print_r(array('Text'=>$text));
                     echo "<br>";
                     print_r(array_merge($objOld['Tasks'], array('Text'=>$text)));
                     exit;

                  }
                  //echo "###".$arrDateExisting["Subj"]."###";

               }
               else {
                  $YearMonth=date("F",mktime(0,0,0,$StartMonth,10))." ".$StartYear;
                  $objDate->cursor["$StartYear-$FormattedMonth"] = array("Subj" => $Subj,
                                                                 "ManDays" => $ManDays, "Tasks" => array(
                                                                 "Text" => $YearMonth."\n".$text));
               }
//               echo "<br>";
               $StartMonth++;
               // Count up year if months exceeds 12
               if($StartMonth==13) {
                  $StartMonth=0;
                  $StartYear++;
               }  
            }
            $objDate->save_collection($objDate->cursor);
//            echo $Subj;
//            echo "<br><br>";
         }
      }
      else {
         // The following could be activated to track forgotten dates
         //echo "<br>Ressource without start and end dates - WP#: ".$oList["WPNum"]."<br>";
      }
   }
}
WriteHTMLofCockpit();
exit;


function WriteHTMLofCockpit() {

//   $objDate=new db_handling("","Backup");
//   $objDate->CollectionName="COOPANS_Cockpit";
//   $objDate->get_cursor_find();

   $m = new Mongo();
   $db = $m->selectDB('Projects');
   $collection = new MongoCollection($db, 'COOPANS_Cockpit');
//   $cursor=$collection->find();
   $cursor=$collection->find()->sort(array("Subj" => 1));
   echo '<font size="+3">Ressource Cockpit</font>';
   echo '<table border="1">';
   echo '<tr>';
   
   // Make table headers
   $Year="2013";
   $Month=1;
   echo "<th>"."Subject"."</th>";
   for($t=0;$t<24;$t++) {
      if($Month==13) {
         $Month=1;
         $Year++;
      }
      $MonthName=date("M",mktime(0,0,0,$Month,10));
      echo "<th>".$MonthName."<br>".$Year."</th>";
      $Month++;
   }
   echo '</tr>';


   foreach($cursor as $obj) {
      echo '<tr>';
      $Subj=$obj['Subj'];
      $Year="2013";
      $Month=1;
      echo "<td>".$obj['Subj']."</td>";
      for($t=0;$t<24;$t++) {
         if($Month==13) {
            $Month=1;
            $Year++;
         }
         $YearMonth=$Year."-".str_pad($Month, 2, '0', STR_PAD_LEFT);
         //echo $YearMonth;
         echo '<td align="right">';
         if(isset($obj[$YearMonth])) {
             $text=$obj[$YearMonth]["Text"];
             $ManDays=number_format($obj[$YearMonth]["ManDays"], 1, ',', ' ');
             $ManDaysLink='<a href="res_cockpit.php?WP=22">'.$ManDays.'</a>';
             echo '<div title="'.$text.'">'.$ManDaysLink."</div>";
             //echo $ManDaysLink;
         }
         else {
             echo "-";
         }
         echo '</td>';
         $Month++;
      }
//      echo "<br>";
//      $Text=$obj['Subj'];
//      print_r($obj);
      echo '</tr>';
   }
   echo '</table>';
}

   // Generate csv headings
   $arrKeys=array("Category","Unit","Subj.","WP","WP name",
                   "Sub1","Sub2","Start","End","Status");
   $Months=array("Jan 13","Feb 13","Mar 13","Apr 13","May 13","Jun 13",
                 "Jul 13","Aug 13","Sep 13","Oct 13","Nov 13","Dec 13",
                 "Jan 14","Feb 14","Mar 14","Apr 14","May 14","Jun 14",
                 "Jul 14","Aug 14","Sep 14","Oct 14","Nov 14","Dec 14",
                 "Jan 15","Feb 15","Mar 15","Apr 15","May 15","Jun 15",
                 "Jul 15","Aug 15","Sep 15","Oct 15","Nov 15","Dec 15" );
   // Write content of DB
   $obj=new db_handling(array("Backup" => array('$exists' => false)),"");
   $obj->get_cursor_find_sort();

   $outstr="";
   $CSVdelimiter=GetAdminData($_SESSION['username']."_csvdelimitor");
   if($CSVdelimiter=="") $CSVdelimiter=",";

   // Write headers of DB
   for($i=0;$i<count($arrKeys);$i++) {
      GetCSV($outstr,"##".$arrKeys[$i],$obj->cursor,$CSVdelimiter);
   }
   foreach($Months as $t) {
      GetCSV($outstr,"##".$t,$obj->cursor,$CSVdelimiter); 
   }
   GetCSV($outstr,"##"."\r\n",$obj->cursor,$CSVdelimiter);  // add new line

   $Header=array();
   foreach ($obj->cursor as $oList) {          
      $HeaderLevel=substr_count($oList["WPNum"], ".");
      $Header[$HeaderLevel]=$oList["WPName"];
      
      // clear the following headers
      for($t=$HeaderLevel+1;$t<5;$t++) {
         $Header[$t]=""; 
      }

      
      if(isset($oList["Ressources"])) {
         if($oList["Ressources"] <> array()) {
            $Ressources=$oList["Ressources"];
            foreach($Ressources as $ot) {
               GetCSV($outstr,"Roles",$ot,$CSVdelimiter); // Category = Roles
               GetCSV($outstr,"",$ot,$CSVdelimiter); // Unit = empty
               // Export allocated ressource is existing otherwise export Requested Ressource if any
               if(trim($ot["AllRes"])<>"") GetCSV($outstr,"AllRes",$ot,$CSVdelimiter); // Subj. = Allocated Ressource
               elseif(trim($ot["ReqRes"])<>"") GetCSV($outstr,"ReqRes",$ot,$CSVdelimiter); // Subj. = Requested Ressource
               else GetCSV($outstr,"Roles",$ot,$CSVdelimiter); // Subj. = Requested Ressource               

               GetCSV($outstr,"WPNum",$oList,$CSVdelimiter); // WP = WPNum

               // list headers: H0, H1, H2, H4 exist but only list H1, H2, H3
               for($u=1;$u<4;$u++) {
                  if(isset($Header[$u])) {
                     GetCSV($outstr,"##".$Header[$u],$ot,$CSVdelimiter); // WP = WPNum
                  }
               }
               ListManDaysInMonths($oList,$ot["ManDays"],$CSVdelimiter,$outstr);
               GetCSV($outstr,"##"."\r\n",$obj->cursor,$CSVdelimiter);  // add new line
            }
            
         }
      }
   }

   if(!$debug) echo iconv("UTF-8", "ISO-8859-1//TRANSLIT",$outstr);
//   header("location:res_admin.php");
   exit;

function ListManDaysInMonths(&$obj,$ManDays,$CSVdelimiter,&$outstr) {
   global $debug;
   if(!isset($obj["StartDate"])) return;
   GetCSV($outstr,"StartDate",$obj,$CSVdelimiter); // Start = StartDate
   GetCSV($outstr,"EndDate",$obj,$CSVdelimiter); // End = EndDate
   GetCSV($outstr,"",$obj,$CSVdelimiter); // Status = empty
   $StartDate=$obj["StartDate"];
   $EndDate=$obj["EndDate"];
// echo " ".$StartDate." ".$EndDate." ".$ManDays." ";
   if($StartDate=="") return;

   // http://stackoverflow.com/questions/4233605/elegant-way-to-get-the-count-of-months-between-two-dates
   $date1 = new DateTime($StartDate);
   $date2 = new DateTime($EndDate);
   $interval = date_diff($date1, $date2);
   $DurationMonths = ($interval->format('%m'))+1;
   $ManDaysPerMonth=$ManDays/$DurationMonths;

   // list months 3 years starting from Jan 13

   $DiffYear=intval(substr($StartDate,0,4))-2013;
   $DiffMonth=intval(substr($StartDate,5,2))-1;
//echo "XX".$DiffYear." ".$DiffMonth."XX";
   $NumberOfMonthsToStartDate=12*$DiffYear+$DiffMonth;
//echo "#".$NumberOfMonthsToStartDate."### ";
   if($NumberOfMonthsToStartDate<0) {
      for($t=0;$t<36;$t++) {
         GetCSV($outstr,"",$obj,$CSVdelimiter); // Date is before what is requested so insert nothing
      }
   }
   else {
      for($t=0;$t<$NumberOfMonthsToStartDate;$t++) {
         GetCSV($outstr,"",$obj,$CSVdelimiter);
      }
      for($t=0;$t<$DurationMonths;$t++) {
         GetCSV($outstr,"##".number_format($ManDaysPerMonth,4,',',''),$obj,$CSVdelimiter);
         // GetCSV($outstr,"##".$ManDaysPerMonth,$obj,$CSVdelimiter);
      }
      for($t=0;$t<36-$DurationMonths-$NumberOfMonthsToStartDate;$t++) {
         GetCSV($outstr,"",$obj,$CSVdelimiter);
      }
   }
}
function GetCSV(&$outstr,$key,&$obj,$CSVdelimiter) {
   global $debug;
   if(substr($key,0,2)=="##") {  // If start of key is ## then just put key to string not using DB
      $String=substr($key,2);   // Take out ##
   }
   elseif(!isset($obj[$key])) {
      $StringToAdd='""'.$CSVdelimiter;  // return empty string if no key present
      goto EarlyExit;
   }
   else $String=$obj[$key];

   $val=str_replace('"','""',$String);
   $StringToAdd='"'.$val.'"'.$CSVdelimiter;

   if($key=="##\r\n") {
      $StringToAdd="\r\n";
   }

EarlyExit:
   $outstr.=$StringToAdd;
   
   if($debug) {
      if($StringToAdd=="\r\n") echo "<br>";
      else echo $StringToAdd;
   }
   return;
}
?>
</body>
</html>
