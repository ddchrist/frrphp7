<?php include("login_headerscript.php"); ?>
<?php include("class_lib.php"); ?>
<?php include("admin_functions.php"); ?>
<?php

$arrData=array();
if ($_GET['format']=="xml") {
   // load library
   require 'php-excel.class.php';   // http://code.google.com/p/php-excel/
   ConvertChecklistIntoArray($arrData);
   $xls = new Excel_XML('UTF-8', false, 'Checklist');
   $xls->addArray($arrData);
   $xls->generateXML('checklist');
   exit;
}
if ($_GET['format']=="xls") {
   // load library
   include 'xls.inc';   // http://code.google.com/p/php-xls/
   ConvertChecklistIntoArray($arrData);
   $header=$arrData[0];
   unset($arrData[0]);  // remove header line
   xlsDoc('checklist',$header,$arrData);
   exit;
}
elseif ($_GET['format']=="csv") {
   header("Content-Type: text/csv; charset=UTF-8");
   header("Content-Disposition: attachment;Filename=checklist.csv");
   $CSVdelimiter=GetAdminData($_SESSION['username']."_csvdelimitor");
   if($CSVdelimiter=="") $CSVdelimiter=";";
   ConvertChecklistIntoArray($arrData);
   foreach($arrData as $arr) {
      $outstr="";
      $outstr.="=";  // trick to avoid Excel converting eventual date to string: ="04.02.12"
      foreach($arr as $val) {
         GetCSV($outstr,$val,$CSVdelimiter);
      }
      $outstr.="\r\n";
      echo iconv("UTF-8", "ISO-8859-1//TRANSLIT",$outstr);
   }
}
else {
   echo 'Given export format is not known. Must be "xls" or "csv"';
}
exit;

function ConvertChecklistIntoArray(&$arrData) {
   // ***********************
   // Generate headings
   // ***********************
   $arrSites=array("EKDK"=>"dk", "LOWW"=>"at", "EIDW"=>"ie", "EISN"=>"ie", "LDZO"=>"hr", "ESMM"=>"se", "ESOS"=>"se");
   $arrKeys=array("TC#","H1","H2","H3","Comments");
   
   // Make unique list of countries
   $arrCountries=array();   
   foreach($arrSites as $Country) $arrCountries[]=$Country;
   $arrCountries=array_unique($arrCountries);

   // Add country specific notes to headings
   foreach($arrCountries as $Country) $arrKeys[]="Note_".$Country;

   // Check whatever a RoleTeam has been selected
   $RoleT=GetAdminOnUser("SelectedRoleTeam","Checklist");

   // Write headers of DB
   $arrTmp=array();
   for($i=0;$i<count($arrKeys);$i++) {
      $arrTmp[]=$arrKeys[$i];
   }

   // Fix suggested SubjectGroup header
   if($RoleT<>"") $arrTmp[]="SubjectGroup";

   // Fix suggested role header
   $arrTmp[]="Roles";

   // Fix site headers
   foreach ($arrSites as $s=>$country) {
      $arrTmp[]=ProcessString($s);
   }

   // Fix headers for selected RoleTeam
   if($RoleT<>"") $arrRoleTeam=FixHeadersOfRoleTeam($RoleT,$arrTmp); 

   $arrData[]=$arrTmp;

   // ***********************
   // Write content of DB
   // ***********************
   $obj=new db_handling(array("Backup" => array('$exists' => false)),"");
   $obj->DataBase="Checklist";

   $obj->get_cursor_find_sort();
   $h1=$h2=$h3="";
   foreach ($obj->cursor as $o) {
      $arrTmp=array();
      if(strlen($o['WPNum'])==2) {  // header L1
         $h1=trim($o['WPName']);
      }
      elseif(strlen($o['WPNum'])==5) {  // header L2
         $h2=trim($o['WPName']);
      }
      elseif(strlen($o['WPNum'])==8) {  // header L3
         $h3=trim($o['WPName']);
         if(isset($o['Note'])) $Note=$o['Note'];
         $WPNum=$o['WPNum'];
      
         $arrTmp[]=ProcessString($WPNum);
         $arrTmp[]=ProcessString($h1);
         $arrTmp[]=ProcessString($h2);
         $arrTmp[]=ProcessString($h3);
         $arrTmp[]=ProcessString($Note);

         // List country specific notes for all countries
         foreach($arrCountries as $Country) {
            if(isset($o["Note_".$Country])) {
               $Note=$o["Note_".$Country];
               $arrTmp[]=ProcessString($Note);
            }
            else $arrTmp[]="";
         }

        // fix SubjectGroups
        if($RoleT<>"") FixSubjectGroup($o,$arrTmp,$RoleT,$arrRoleTeam);

        // fix roles
        if(isset($o["Roles"])) $arrTmp[]=ProcessString($o["Roles"]);
        else $arrTmp[]=ProcessString("");

        // fix sites
        foreach ($arrSites as $s=>$country) {
           if(isset($o[$s])) $arrTmp[]=ProcessString($o[$s]);
           else $arrTmp[]=ProcessString("");
        }

        // Fix selected RoleTeam
        if($RoleT<>"") FixRoleTeam($o,$arrTmp,$RoleT,$arrRoleTeam);

        $arrData[]=$arrTmp;
      }
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
// Fix headers of RoleTeam and return the found RoleTeam (array containing the team)
function FixHeadersOfRoleTeam($RoleT,&$arrTmp) {
   $objRoleTeam=new db_handling(array('User' => "_Country_".$_SESSION["country"]),"Backup");
   $objRoleTeam->DataBase="Checklist";
   $objRoleTeam->CollectionName="Admin";
   $objRoleTeam->get_cursor_findone();
   $RoleTeam=$objRoleTeam->cursor[$RoleT];
   foreach($RoleTeam as $r) {
      $arrTmp[]=ProcessString($r['Role']);
   }
   return $RoleTeam;
}
// Fix the roles (from team) for the actual TCs are exported
function FixRoleTeam(&$o,&$arrTmp,$RoleT,$arrRoleTeam) {
   if(isset($o[$RoleT])) {
      foreach($arrRoleTeam as $r) {  
         if(in_array($r['Role'], $o[$RoleT])) $arrTmp[]="T";
         else $arrTmp[]="F";
      }
   }
   else {  // No RoleTeam in array just make false
      foreach($arrRoleTeam as $r) $arrTmp[]="F";
      return;
   }
}
// Fix the roles (from team) for the actual TCs are exported
function FixSubjectGroup(&$o,&$arrTmp,$RoleT,$arrRoleTeam) {
   $SubjectGroup="";
   if(isset($o[$RoleT])) {
      foreach($arrRoleTeam as $r) {  
         if(in_array($r['Role'], $o[$RoleT])) $SubjectGroup.=$r['Role'].", ";
      }
   }
   else {  // No RoleTeam in array just make false
      $arrTmp[]="";
      return;
   }
   $arrTmp[]=substr($SubjectGroup,0,-2);  // return subject group and remove last character
   
}
function GetCSV(&$outstr,$val,$CSVdelimiter) {
   $val=str_replace('"','""',$val);  // 
   $outstr.='"'.$val.'"'.$CSVdelimiter;
   return;
}

?>
