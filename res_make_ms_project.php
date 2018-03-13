<?php include("login_headerscript.php"); ?>
<?php include("class_lib.php"); ?>
<?php include("admin_functions.php"); ?>
<?php

$arrData=array();
if ($_GET['format']=="xml") {
   // load library
   require 'php-excel.class.php';   // http://code.google.com/p/php-excel/
   ConvertWPsIntoArray($arrData);
   $xls = new Excel_XML('UTF-8', false, 'COOPANS Common Checklist');
   $xls->addArray($arrData);
   $xls->generateXML('checklist');
   exit;
}
// Other xmlwriters: http://excelwriterxml.sourceforge.net/
//                    http://webdeveloperplus.com/php/5-libraries-to-generate-excel-reports-in-php/

if ($_GET['format']=="xls") {
   // load library
   include 'xls.inc';   // http://code.google.com/p/php-xls/
   ConvertWPsIntoArray($arrData);
   $header=$arrData[0];
   unset($arrData[0]);  // remove header line
   xlsDoc('checklist',$header,$arrData);
   exit;
}
elseif ($_GET['format']=="csv") {
   header("Content-Type: text/csv; charset=UTF-8");
   header("Content-Disposition: attachment;Filename=COOPANS_MS_Project.csv");
   $CSVdelimiter=GetAdminData($_SESSION['username']."_csvdelimitor");
   if($CSVdelimiter=="") $CSVdelimiter=";";
   ConvertWPsIntoArray($arrData);
   $SkipEqualOnHeaderLine=true;
   foreach($arrData as $arr) {
      $outstr="";
      //if(!$SkipEqualOnHeaderLine) $outstr.="=";  // this forces excel not to convert some of the numbers to dates i.e: ="02.04.13"
      foreach($arr as $val) {
         GetCSV($outstr,$val,$CSVdelimiter);
      }
      $outstr=substr($outstr,0,-1);  // remove last ";" from string
      $outstr.="\r\n";
      $SkipEqualOnHeaderLine=false;
      echo iconv("UTF-8", "ISO-8859-1//TRANSLIT",$outstr);
   }
}
else {
   echo 'Given export format is not known. Must be "xls" or "csv"';
}
exit;

function ConvertWPsIntoArray(&$arrData) {
   // ***********************
   // Generate headings
   // ***********************

   // Fix site headers
   $arrTmp=array();
   // $_GET["Level"] = "Outline Level" for UK
   // $_GET["Level"] = "Dispositionsniveau" for DK damn MS translations :-(
   if($_GET["national"]=="uk") $arrTmp=array("WBS", "Name", "Outline Level", "Start", "Finish");
   elseif($_GET["national"]=="dk") $arrTmp=array("WBS", "Navn", "Dispositionsniveau", "Planlagt startdato", "Planlagt slutdato");
   $arrData[]=$arrTmp;

   // ***********************
   // Write content of DB
   // ***********************
   $obj=new db_handling(array("Backup" => array('$exists' => false)),"");
   $obj->get_cursor_find_sort();
   
   foreach ($obj->cursor as $o) {
      $arrTmp=array();
      $arrTmp[]=$o['WPNum'];
      $arrTmp[]=$o['WPName'];
      $arrTmp[]=substr_count($o['WPNum'],".")+1;  // Outline Level
      if(isset($o['StartDate'])) {
         if($o['StartDate']<>"") $arrTmp[]=date("d-m-Y", strtotime($o['StartDate']));
         else $arrTmp[]="";
      }
      else $arrTmp[]="";
      if(isset($o['EndDate'])) {
         if($o['EndDate']<>"") $arrTmp[]=date("d-m-Y", strtotime($o['EndDate']));
         else $arrTmp[]="";
      }
      $arrData[]=$arrTmp;
   }
}

function ProcessString($val) {
   $val=str_replace('“','""',$val);  // tackle special character '“' -> '"'
   $val=str_replace('”','""',$val);  // tackle special character '”' -> '"'
   $val=str_replace('–','-',$val);  // tackle special character '–' -> '-'
   $val=str_replace('•','-',$val);  // tackle special character '•' -> '*'
   $val=str_replace('’',chr(39),$val);  // tackle special character '’' -> '''
   $val=str_replace('‘',chr(39),$val);  // tackle special character '’' -> '''
   $val=str_replace('’',chr(39),$val);  // tackle special character '’' -> '''
   $val=str_replace('≠','<>',$val);  // tackle special character '≠' -> '<>'
   $val=str_replace('…','...',$val);  // tackle special character '…' -> '...'
   $val=str_replace("\r\n","\n",$val); // makes sure that within a cell there are proper newline
   return $val;
}

function GetCSV(&$outstr,$val,$CSVdelimiter) {
   $val=str_replace('"','""',$val);  // 
   $outstr.='"'.$val.'"'.$CSVdelimiter;
   return;
}

?>
