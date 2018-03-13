<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<html>
<head>
<title>Upload Mermaid dump</title>
</head>
<body>
<?php include("headerline.html"); ?>

<?php

// Paste PCR from PDF into database
if(isset($_GET['InsertPCR'])) {
?>
   <font size=+1><b>Copy PCR from PDF and paste into text area</b></font><br>
   <form action="admin_uploadgaiapcrs.php" method="post">
   <textarea name="TextToPCR" COLS=80 ROWS=6></textarea>
   <br>
   <input type="submit" />
   </form>
   </body>
   </html>
<?php
   exit;
}
elseif(isset($_POST['TextToPCR'])) {
   $arrHeader = array('RegularID',         'ExternalID',
                      'AuthorLogin',       'DateOfCreation',
                      'Version',           'Support', 
                      'Subject',           'Keywords',
                      'Type',              'PCR_AUTHOR',
                      'Severity',          'S/W_cla/cor_date',
                      'S/W_status',        'Rel_PCR/ECR',
                      'Resp',              'COR_VERS',
                      'CSCI_NAME',         'ORIGIN',
                      'S/W_clo/d??_Date',  'DetectionPhase',
                      'PhaseStage',        'DocStatus',
                      'Doc_cla/cor_date',  'Doc_clo/d??_Date',
                      'DocComp',           'TEST_ID',
                      'SystemReq',         'Priority',
                      'Safety',            'FREE_KEYWORDS',
                      'OTHER_BASE_PCR',    'LINK_PCR',
                      'DOCUMENT_LINK',     'ProductName',
                      'BASELINE',          'Site',
                      'Criticality'   );
   $arrKeysNotInGaia = array('PATCH_COR', 'PCRNo', 'Last_Updated', 'MeRMAId_PCR');                         
   // Prefill variables with nothing to avoid errors
   foreach($arrHeader as $k) {
      $doc[$k]="No data";
   }
   foreach($arrKeysNotInGaia as $k) {
      $doc[$k]="No data";
   }

   $PCR=$_POST['TextToPCR'];
   $arrPCR=explode("\n",$PCR);
   $state=0;
   foreach($arrPCR as $line) {
      $line=preg_replace('~[\r\n]+~','',$line);  // remove newlines from string
      //echo $line." ".strpos($line,"PCR_COOPANS")."<br>";
      switch($state) {
         case 0:
            if(strpos($line,"PCR_COOPANS") !==false) {
               //$doc['ExternalID']=$line;
               $arrPCRnum=explode("_",$line);
               $doc['RegularID']=intval($arrPCRnum[count($arrPCRnum)-1]);
               $doc['ProductName']="";
               for($t=1;$t<count($arrPCRnum)-1;$t++) {
                  $doc['ProductName'].=$arrPCRnum[$t]."_";
               }
               $doc['ProductName']=substr($doc['ProductName'],0,-1);  // take out last "_"
               $state++;
            }            
            break;
         case 1:
            $doc['Subject']=$line;
            $state++;
            break;
         case 2:
            GetKeyValueOfKey("External Nb:","ExternalID",$doc,$state,$line);
            break;
         case 3:
            GetKeyValueOfKey("Author:","AuthorLogin",$doc,$state,$line);
            break;
         case 4:
            GetKeyValueOfKey("Date:","DateOfCreation",$doc,$state,$line);
            break;
         case 5:
            GetKeyValueOfKey("Originator:","PCR_AUTHOR",$doc,$state,$line);
            break;
         case 6:
            GetKeyValueOfKey("Database:","ProductName",$doc,$state,$line);
            break;
         case 7:
            GetKeyValueOfKey("Site:","Site",$doc,$state,$line);
            break;
         case 8:
            GetKeyValueOfKey("Safety:","Safety",$doc,$state,$line);
            break;
         case 9: // Description, Report, Answer, PCR_History
            if(trim($line)=="Description") {
               $doc["Description"]="";
               $state++;
            }
            break;
         case 10:
            if(trim($line)=="Report") {
               $doc["Report"]="";
               $state++;
            }
            else $doc["Description"].=$line."\n";
            break;
         case 11:
            if(trim($line)=="Delivered Items") {
               $doc["Answer"]="";
               $state++;
            }
            else $doc["Report"].=$line."\n";
            break;
         case 12:
            if(trim($line)=="History") {
               $doc["PCR_History"]="";
               $state++;
            }
            else $doc["Answer"].=$line."\n";
            break;
         case 13:
            $doc["PCR_History"].=$line."\n";
            // no more lines with content after this
            break;

      }
      
   }
   //print_r($doc);
   foreach($doc as $k=>$v) {
      if($k=="Description" or $k=="Report" or
         $k=="Answer" or $k=="PCR_History") {
         echo $k.":<br>";
      }
      else echo $k.": ".$v."<br>";
   }
   // Store doecument to DB
   if($state==13) {
      $m = new Mongo();
      $db = $m->selectDB('testdb');
      $collection = new MongoCollection($db, 'pcr');
      $document=$collection->findone(array('RegularID'=>$doc['RegularID'],'ProductName'=>$doc['ProductName'])); 
      foreach($doc as $k=>$v) {
         $document[$k]=$v;
      }
      //print_r($document);
      $collection->save($document);
      echo "<b>Updated</b><br>";
      $ExtraPCRs=GetAdminData("ExtraPCRs");
      $ExtraPCRs.=$document['ProductName']."_".$document['RegularID']." + ";
      StoreAdminData("ExtraPCRs",$ExtraPCRs);
   }
   else echo "<b>PCR is NOT stored. Something is wrong with the format. Stoped at state=".$state."</b><br>";
   exit;
}

// Step 1 - Script is called and no file is selected (set) yes
elseif(!isset($_FILES['userfile']['tmp_name'])) {
   ?>
   <!-- The data encoding type, enctype, MUST be specified as below -->
   <form enctype="multipart/form-data" action="admin_uploadgaiapcrs.php" method="POST">
       <!-- MAX_FILE_SIZE must precede the file input field -->
       <input type="hidden" name="MAX_FILE_SIZE" value="60000000" />
       <!-- Name of input element determines name in $_FILES array -->
       Send this file: <input name="userfile" type="file" />
       <?php
          // When 3 files have been uploaded then change radio button default seeting from B1 to B2
          if(isset($_SESSION["GaiaFiles"])) $GaiaFiles=$_SESSION["GaiaFiles"];
          else $GaiaFiles="";
          if(substr_count($GaiaFiles,"vmsplit")>=3) {
             echo '<input type="radio" name="Build" value="B1">B1';
             echo '<input type="radio" name="Build" value="B2" checked="checked">B2';
          
          }
          else {
            echo '<input type="radio" name="Build" value="B1" checked="checked">B1';
            echo '<input type="radio" name="Build" value="B2">B2';
          }
       ?>
       <input type="submit" name="Upload" value="Upload Gaia dump" />
       <input type="submit" name="Finished" value="Finished" />
       <br>
   </form>
   <?php
   if(isset($_SESSION["GaiaFiles"])) echo "Uploaded files:<br>". $_SESSION["GaiaFiles"];
   echo '<br><br><b>How it works:</b><br>Start by selecting the first build 1 one called "vmsplit3_aa.tar" and then "Upload".<br>Now select the next file "vmsplit3_ab.tar" and "Upload"<br>Finally the last file "vmsplit3_ac.tar" and upload.<br>Note that the order of file "..a.", "..b." and "..c." does not matter.<br>Now the radio button should automatically change to build 2 (or do it yourself).<br>Select the file "vmsplit3_aa.tar" - then "Upload"<br>Now hit "Finished" and it will create the new PCR database. (Will take some time!)';
   exit;
}
else if(isset($_POST['Upload'])) {
   // In PHP versions earlier than 4.1.0, $HTTP_POST_FILES should be used instead
   // of $_FILES.
   // http://www.php.net/manual/en/features.file-upload.post-method.php
   // Choose whatever to upload file to gaia (Build 1) or gaia2 (Build 2)
   if($_POST['Build']=="B1") $uploaddir = '/var/www/gaia/';
   else $uploaddir = '/var/www/gaia2/';
echo "###Path=".$uploaddir."<br>";

   $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

   echo '<pre>';
   if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
       echo "File is valid, and was successfully uploaded.\n";
   } 
   else {
       echo "Possible file upload attack!\n";
   }

   echo 'Here is some more debugging info:';
   print_r($_FILES);

   echo "</pre>";
   $FileName=$_POST['Build'].": ".basename($_FILES['userfile']['name']);
   if(isset($_SESSION["GaiaFiles"])) $_SESSION["GaiaFiles"].="<br>".$FileName;
   else $_SESSION["GaiaFiles"]=$FileName;
   header("location:admin_uploadgaiapcrs.php");
   exit;
}
else {   // Now convert/unpack files and store into DB
   $_SESSION["GaiaFiles"]="";
   $arrHeader =    array('RegularID',         'ExternalID',
                      'AuthorLogin',       'DateOfCreation',
                      'Version',           'Support', 
                      'Subject',           'Keywords',
                      'Type',              'PCR_AUTHOR',
                      'Severity',          'S/W_cla/cor_date',
                      'S/W_status',        'Rel_PCR/ECR',
                      'Resp',              'COR_VERS',
                      'CSCI_NAME',         'ORIGIN',
                      'S/W_clo/d??_Date',  'DetectionPhase',
                      'PhaseStage',        'DocStatus',
                      'Doc_cla/cor_date',  'Doc_clo/d??_Date',
                      'DocComp',           'TEST_ID',
                      'SystemReq',         'Priority',
                      'Safety',            'FREE_KEYWORDS',
                      'OTHER_BASE_PCR',    'LINK_PCR',
                      'DOCUMENT_LINK',     'ProductName',
                      'BASELINE',          'Site',
                      'Criticality'   );

   // Following are keys found in Mermaid but not found in Gaia dump
   $arrKeysNotInGaia = array('PATCH_COR', 'PCRNo', 'Last_Updated', 'MeRMAId_PCR');

   // Mappings Marmaid / Gaia
   // 'CSCI_NAME'  = 'S/W Comp'
   // ????         = 'Operation Context'     'Operation Context****'
   // 'TEST_ID'    = 'Test Ref'
   // 'COR_VERS'   = 'V/C'

   // Well 'Description', 'PCR_History', 'Report', 'Answer' are stored in seperate files

   // The following keys are in Marmaid database but not in COOPANS_gisel.txt from gaia DB
   // 'TEST_ID', 'CSCI_NAME', 'COR_VERS', 'BASELINE', 'PATCH_COR', 'Description',
   // 'PCR_History', 'Report', 'Answer', 'PCRNo', 'Last_Updated', 'MeRMAId_PCR'

   // Keys from gaia:
   // Array ( [0] => Regular Id [1] => External Id [2] => Author Login [3] => Date Of Creation [4] => Version [5] => Support [6] => Subject [7] => Keywords [8] => Type [9] => Originator [10] => Severity [11] => S/W cla/cor date [12] => S/W status [13] => Rel PCR/ECR [14] => Resp [15] => V/C [16] => S/W Comp [17] => Origin [18] => S/W clo/d?? Date [19] => Detection Phase [20] => Phase Stage [21] => Doc Status [22] => Doc cla/cor date [23] => Doc clo/d?? Date [24] => Doc Comp [25] => Test Ref [26] => System Req [27] => Priority [28] => Safety [29] => FREE_KEYWORD [30] => OTHER_BASE_PCR [31] => LINK_PCR [32] => DOCUMENT_LINK [33] => Product Name [34] => Operation Context [35] => Site [36] => Criticality ) 

   // Drop content of DB
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'pcr');
   $response=$collection->drop();


   PrepareGaiaFilesForImport("B1");
   $NumPCRsB1=ImportGaiaDB($collection,"gaia","",$arrHeader,$arrKeysNotInGaia);
   PrepareGaiaFilesForImport("B2");
   $NumPCRsB2=ImportGaiaDB($collection,"gaia2","B2_",$arrHeader,$arrKeysNotInGaia);  
   echo "<pre>";
   echo "Number of PCRs imported B1___: ".$NumPCRsB1;
   echo "<br>Number of PCRs imported B2___: ".$NumPCRsB2;
   echo "<br><b>Number of PCRs imported B1+B2: ".($NumPCRsB1+$NumPCRsB2);
   echo "<br>Extra manually added PCRs deleted i.e.: ".GetAdminData("ExtraPCRs");
   StoreAdminData("ExtraPCRs","");
   echo "</b></pre><br>";
   
   exit;
}


function ImportGaiaDB($collection,$GaiaPath,$Build,&$arrHeader,&$arrKeysNotInGaia) {
   $doc=array();
   //chdir($GaiaPath);
   //echo getcwd()."<br>";
   $FileName="$GaiaPath/COOPANS_".$Build."gisel.txt";  // COOPANS_B2_gisel.txt or COOPANS_gisel.txt
   $file = fopen($FileName, "r") or exit("Unable to open file!");
   //Output a line of the file until the end is reached

   // Remove invalid characters: http://www.zeitoun.net/articles/clear-invalid-utf8/start
   $data=fgets($file);   // get rid of first header row
   $NumOfPCRs=0;  // count number of PCRs
   while(!feof($file)) {
     $arrGaia=explode(":",fgets($file));
     if(count($arrGaia) < 37) goto exitloop; // exit if number of columns mismatch
     $LastPCRNum=$doc[$arrHeader[0]]=intval(substr($arrGaia[0],5));   // intval($arrGaia[0]);
     for($t=1;$t<37;$t++) {
        $data=iconv("UTF-8","UTF-8//IGNORE",$arrGaia[$t]);
        $doc[$arrHeader[$t]]=$data;
//*************
//****************
        if($arrHeader[$t]=="DateOfCreation" or
           $arrHeader[$t]=="S/W_clo/d??_Date" or
           $arrHeader[$t]=="Doc_cla/cor_date" or
           $arrHeader[$t]=="Doc_clo/d??_Date" or
           $arrHeader[$t]=="S/W_cla/cor_date") {
              //$data=str_replace('/',"-",$data);
              //$arrHeader[$t]=date('Y-m-d',strtotime($date));
              //$date=DateTime::CreateFromFormat('d/m/y',$data);
              //$doc[$arrHeader[$t]]=$date->format('Y-m-d');
              $dato=date_create_from_format('d/m/y',$data);
              if($dato) $doc[$arrHeader[$t]]=date_format($dato,'Y-m-d');
              else $doc[$arrHeader[$t]]=""; // false date format - truncate!          
           }
        // Fix incorrect ProductNames
        if($arrHeader[$t]=="ProductName") {
           if($Build=="" and $data<>"COOPANS") {
              echo '<br><font color="red">Incorrect productname in B1_PCR_'.$doc[$arrHeader[0]].". Now corrected to COOPANS</font>";
              $doc[$arrHeader[$t]]="COOPANS";  // correct product name
           }
           elseif($Build=="B2_" and $data<>"COOPANS_B2") {
              echo '<br><font color="red">Incorrect productname in B2_PCR_'.$doc[$arrHeader[0]].". Now corrected to COOPANS_B2</font>";
              $doc[$arrHeader[$t]]="COOPANS_B2";  // correct product name
           }

        }
     }

     // reset keys in Marmaid but not in gaia DB
     for($t=0;$t<count($arrKeysNotInGaia);$t++) {
        $doc[$arrKeysNotInGaia[$t]]="No Data";
     }
     GetDescHistRepoAnsw($GaiaPath,$doc['ExternalID'], $doc);  // $doc passed by reference
     $collection->insert($doc);
     $NumOfPCRs++;
     $doc=array();
   }
   exitloop:
   // Get last PCR number from DB
   if($Build=="") $Build="B1_";
   StoreAdminData($Build,$LastPCRNum);  // Stored in session "B1_" or "B2_"
   echo "<br>Last PCR number build $Build: ".$LastPCRNum."<br>";

   fclose($file);
   return($NumOfPCRs);  // number of PCRs stored in DB
}

function GetDescHistRepoAnsw($GaiaPath,$ExternalID, &$doc) {  // pass by reference
// Now get the individual text files by linking key 'ExternalID'
//<#<Description>#>   <#<PCR History>#>   <#<Report>#>   <#<Answer>#>   
   $FileName=$ExternalID.".txt";
//echo $FileName."<br>";
   $file = fopen("$GaiaPath/$FileName", "r") or exit("Unable to open file!");
   // Create keys
   $doc['Description']=$doc['PCR_History']=$doc['Report']=$doc['Answer']="";
   while(!feof($file)) {
      $data=iconv("UTF-8","UTF-8//IGNORE",fgets($file));
      if(trim($data)=="<#<Description>#>") $key='Description';
      else if(trim($data)=="<#<PCR History>#>") $key='PCR_History';
      else if(trim($data)=="<#<Report>#>") $key='Report';
      else if(trim($data)=="<#<Answer>#>") $key='Answer';
      else $doc[$key].=$data;
   }
   fclose($file);
   return;
}

function PrepareGaiaFilesForImport($build) {
   echo getcwd()."<br>";
   // echo chdir("gaia2");
   echo "Prepare Gaia import files... please wait<br>";
   $output= shell_exec("./PrepareGaiaData$build.sh");
   echo "<br>Finished data processing<br>";
   // Store version / filename in db
   echo $output;
   preg_match("/COOPANS(.*)/",$output,$matches);
   StoreAdminData("GaiaDBVersion_$build",$matches[1]);
}

function GetKeyValueOfKey($key,$dbKey,&$doc,&$state,&$line) {
   if(strpos($line,$key) !== false) {
      $pos=strpos($line,": ");
      $doc[$dbKey]=substr($line,$pos+2);
      $state++;         
   }
}


?> 

</body>
</html>

