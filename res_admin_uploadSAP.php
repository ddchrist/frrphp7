<?php include("login_headerscript.php"); ?>
<?php include("class_lib.php"); ?>
<?php include("admin_functions.php"); ?>

<html>
<head>
<title>Upload SAP Resources</title>
</head>
<body>
<?php include("res_headerline.html"); ?>

<?php
if(isset($_GET["UpdateCockpitOnly"])) goto SkipUploadingSAPData;

// if not started then select file name to be uploaded
//echo "File Error code: ".$_FILES['userfile']['error'];
if(isset($_POST['userfile'])) echo "userfile"."<br>";
if(!isset($_FILES['userfile']['tmp_name'])) {
   ?>
   <!-- The data encoding type, enctype, MUST be specified as below -->
   <form enctype="multipart/form-data" action="res_admin_uploadSAP.php" method="POST">
       <!-- MAX_FILE_SIZE must precede the file input field -->
       <input type="hidden" name="MAX_FILE_SIZE" value="4000000" />
       <!-- Name of input element determines name in $_FILES array -->
       Upload SAP Resource file: <input name="userfile" type="file" />
       <input type="submit" value="Upload SAP Resources" />
   </form>
   <?php
}
else {
   // In PHP versions earlier than 4.1.0, $HTTP_POST_FILES should be used instead
   // of $_FILES.
   // http://www.php.net/manual/en/features.file-upload.post-method.php

   $uploaddir = '/var/www/';
   $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

   echo '<pre>';
   if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
       echo "File is valid, and was successfully uploaded.\n";
       print "</pre>";
   } 
   else {
       echo "Possible file upload attack!\n";
       echo 'Here is some more debugging info:';
       print_r($_FILES);
       print "</pre>";
       exit;
   }
   
   // ************************************   
   // Get file into DB
   // ***********************************

   InsertFileIntoMongoDB($_FILES['userfile']['name']);
SkipUploadingSAPData:
   echo "Updating Cockpit with resources";
   UpdateCockpitWithRegisteredResources();
   echo "<br>Done!";
}

?>

</body>
</html>



<?php
function InsertFileIntoMongoDB($filename)
{
   error_reporting(E_ALL ^ E_NOTICE);
   require_once 'excel_reader2.php';
   // $filename="COOPANS_MERMAID_22032012.xls";
   $xls=new Spreadsheet_Excel_Reader($filename,false,"UTF-8");  // "false" makes it drop formatting and makes the file much smaller
   //$xls->setUTFEncoder('UTF-8');
   //$xls->setUTFEncoder('iconv');
   //$xls->setOutputEncoding('UTF-8');
   //$xls->read("testmermaid.xls");

   // These are the text that must be used inside MongoDB. Some differ from the imported headers
   $arrHeader =    array('Subject',          'OmkArt',
                         'PSP',           'VrdCO',
                         'COomr',         'Hours',
                         'RealDate',      'BookDate',
                         'Year',          'From',
                         'To',            'RegDate',
                         'OmkArtBet',     'Reference',
                         );


   $m = new Mongo();
   $db = $m->selectDB('Projects');
   $collection = new MongoCollection($db, 'SAPResources');
   $response=$collection->drop();

   $doc=array();
   $InsertRecord=true;
   for($row=2;$row<=$xls->rowcount();$row++) {  // skip header row so start by 2
      for($col=0;$col<count($arrHeader);$col++) {
         $v=$xls->val($row,$col+1);
         if($col==0) {            
            if($v==0 or $v=="") { // skip empty rows and and MA-nr=0
              $InsertRecord=false;
              break;
            }
            else {
               // Substitute MAnr with 3 letter subject e.g. 406122->CST
               $doc[$arrHeader[0]]=GetSubjectFromMAnr($v);
               if($doc[$arrHeader[0]]=="###") echo "<br><b>Warning:</b> During import from .xls there were no match for empl. number: $v. It is converted to subject '###'";
               $InsertRecord=true;
            }
         }
         elseif($col==3) {
            $v=str_replace(",","",$v);  // remove "," as it will be interpretated as comma and not thousinds
            $doc[$arrHeader[$col]]=(float) $v;  // Money in DKK
         }
         elseif($col==5) {
            $v=str_replace(",","",$v);  // remove "," as it will be interpretated as comma and not thousinds
            $doc[$arrHeader[$col]]=(float) $v;  // mængde / hours
         }
         elseif($col==6) $doc[$arrHeader[$col]]=ReverseDate($v);  // Real Date of actual work
         elseif($col==7) $doc[$arrHeader[$col]]=ReverseDate($v);  // Book Date of actual work
         elseif($col==11) $doc[$arrHeader[$col]]=ReverseDate($v); // Registration date of work
         else $doc[$arrHeader[$col]]= $v;
      }
      if($InsertRecord) {
         $collection->insert($doc);  // this insert takes very long time to execute
      }
      $doc=array();
   }
   $realrow=$row-2;
   echo "<br><b>Number of imported records: $realrow records</b><br>";
}

function ReverseDate($dato) {
   $m=substr($dato,0,2);
   $d=substr($dato,3,2);
   $y=substr($dato,6,4);
   return("$y-$m-$d");
}
function GetSubjectFromMAnr($MAnr) {
   // Get Subject initials from Medarbejder nummer
   // Add zero in front of MAnr so in total 8 digits
   //$MAnr=str_pad($MAnr,8,'0', STR_PAD_LEFT);
   $obj=new db_handling(array('MAnr' => $MAnr),"");
   $obj->DataBase="admin";
   $obj->CollectionName="InitialMappings";
   $obj->get_cursor_findone();
   if($obj->cursor==NULL) {
      echo "<b>Warning:</b>No match for resource no: $MAnr.<br>";
      return "###";
   }
   else return($obj->cursor["Subject"]);
}
function UpdateCockpitWithRegisteredResources() {
   RemoveRealDaysFromCockpit();
   $arrSubj=array();
   GetKeyFromSAPResourceList($arrSubj,"Subject");  // $arrSubj is a unique liste of all subjects in SAP import
   echo "<br><br><b>".count($arrSubj)."</b> Subjects found in SAP Import:<br>";
   foreach($arrSubj as $Subj) echo $Subj." ";
   $arrPSP=array();
   GetKeyFromSAPResourceList($arrPSP,"PSP");  // $arrPSP is a unique liste of all subjects in SAP import
   echo "<br><br><b>".count($arrPSP)."</b> PSP elements found in SAP Import:<br>";
   foreach($arrPSP as $PSP) echo $PSP."<br>";
   echo "<br>";

   // go through all subjects found in SAP imported list
   foreach($arrSubj as $Subj) {  // get next subject from SAPResourceList
      $obj=new db_handling(array("Subject"=>$Subj),"");
      $obj->DataBase="Projects";
      $obj->CollectionName="SAPResources";
      $obj->get_cursor_find();
   
      // Lookup subject in Cockpit
      $obj2=new db_handling(array("Subj"=>$Subj),"");
      $obj2->DataBase="Projects";
      $ColName=GetAdminOnUser("CockpitCollection","Projects");
      if($ColName=="") $ColName="COOPANS_Cockpit"; // case it is not set in DB
      $obj2->CollectionName=$ColName;
      $obj2->get_cursor_findone();
      
      // *** Subject Does not exist BEGIN ***
      if($obj2->cursor==NULL) {  // Subject does not exist in Cockpit
         echo "<b>Warning: </b>No match for Subject '<b>".$Subj."</b>' in Cockpit.<br>";
         // Sum SAPResources on a monthly basis
         $arrRegHours=array();
         foreach($obj->cursor as $o) { 
            $YearMonth=substr($o["RealDate"],0,7);
            if(isset($arrRegHours[$YearMonth])) $arrRegHours[$YearMonth]+=$o["Hours"]; // sum month for existing subject
            else $arrRegHours[$YearMonth]=$o["Hours"];  // create new months if subject does not exist
         }

         foreach($arrRegHours as $d=>$Hours) {
            echo "&nbsp;&nbsp;&nbsp;Month: ".$d." Days/Hours: ".number_format($Hours/7.4, 2, '.', ' ')." / ".$Hours."<br>";
         }
                  
         $obj2->cursor["Subj"]=$Subj;
         $obj2->cursor["Unit"]=GetUnitFromSubject($Subj);
         if(!$obj2->cursor["Unit"]) {
            echo "&nbsp;&nbsp;&nbsp;<b>Could not get unit of subject</b><br>";
            continue;
         }
         else {
            echo "&nbsp;&nbsp;&nbsp;Found subject in mapping and updated cockpit<br>";
            $obj2->cursor["AktArt"]="Dummy";
            $obj2->cursor["Cost#"]="Dummy";
         }
      }
      // *** Subject Does not exist END ***
      
      // loop through all objects (for given subject) in
      // SAPResources and insert Actual days in proper
      // Cockpit months
      foreach($obj->cursor as $o) {
         $RealDate=substr($o["RealDate"],0,7);  // "Year-Month" date of work actual carried out
         if(isset($obj2->cursor[$RealDate])) {
            $arrMonth=$obj2->cursor[$RealDate];
            if(!isset($arrMonth["RealDays"])) $arrMonth["RealDays"]=0;
            $arrMonth["RealDays"]+=$o["Hours"]/7.4;
            $obj2->cursor[$RealDate]=$arrMonth;
         }
         else {
            //echo "Warning: ".$RealDate." does not exist for subject '".$Subj."' in Cockpit<br>";
            $obj2->cursor[$RealDate]=array("Subj"=>$Subj,"RealDays"=>$o["Hours"]/7.4);   
         }
      }
      $obj2->collection->save($obj2->cursor);   
   }
}

function RemoveRealDaysFromCockpit() {
   // remove all "RealDays" from cockpit
   $obj2=new db_handling(array(),"");
   $obj2->DataBase="Projects";
   $ColName=GetAdminOnUser("CockpitCollection","Projects");
   if($ColName=="") $ColName="COOPANS_Cockpit"; // case it is not set in DB
   $obj2->CollectionName=$ColName;
   $obj2->get_cursor_find();
   foreach($obj2->cursor as $o) {
      foreach($o as $k=>$v) {
         if(is_array($o[$k])) {
            $d=$o[$k];
            unset($d["RealDays"]);
            $o[$k]=$d;
         }
      }
      $obj2->collection->save($o);
   }
}

function GetKeyFromSAPResourceList(&$arrVal,$key) {
   // go thrugh DB projects, collection SAPResources
   // and subtracts e.g. a unique list of subjects represented 
   $obj=new db_handling(array(),"");
   $obj->DataBase="Projects";
   $obj->CollectionName="SAPResources";
   // $obj->get_cursor_find_sort(1,"Subject",false);
   $obj->get_cursor_find();
   $arrVal=array();
   foreach($obj->cursor as $o) $arrVal[]=$o[$key];
   $arrVal=array_unique($arrVal);
}

function GetUnitFromSubject($Subj) {
   $obj=new db_handling(array("User"=>"_Admin_"),"");
   $obj->DataBase="Projects";
   $obj->CollectionName="Admin";
   $obj->get_cursor_findone();
   if(isset($obj->cursor["Subjects"])) {
      $arrSubjects=$obj->cursor["Subjects"];
      foreach($arrSubjects as $s) {
         if($s["Subj"]=="$Subj") return $s["Unit"];
      }
   }
   return false;
}

