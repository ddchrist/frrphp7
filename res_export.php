<?php include("login_headerscript.php"); ?>
<?php include("class_lib.php"); ?>
<?php include("admin_functions.php"); ?>
<?php  // class_lib.php cause some line feed outputs that comes in to the beginning of the download ... do not know how to remove those...????

$debug=false;

if(!$debug) {
   header("Content-Type: text/csv; charset=UTF-8");
   header("Content-Disposition: attachment;Filename=dboutput.csv");
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
//   $date1 = new DateTime($StartDate);
//   $date2 = new DateTime($EndDate);
   $DurationMonths=(substr($EndDate,0,4)-substr($StartDate,0,4))*12+
                          substr($EndDate,5,2)-substr($StartDate,5,2)+1;
//   $interval = date_diff($date1, $date2);
//   $DurationMonths = ($interval->format('%m'))+1;
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
