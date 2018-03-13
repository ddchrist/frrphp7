<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<html>
<head>
<title>Upload ClearQuest dump</title>
</head>
<body>
<?php include("headerline.html"); ?>
<?php

$Key[]=NULL;
$Records[]=NULL;
// ***************************************
// Step 1 - Script is called and no file is selected (set) yes

if(!isset($_FILES['userfile']['tmp_name'])) {
   ?>
   <!-- The data encoding type, enctype, MUST be specified as below -->
   <form enctype="multipart/form-data" action="admin_uploadclearquestpcrs.php" method="POST">
       <!-- MAX_FILE_SIZE must precede the file input field -->
       <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
       <!-- Name of input element determines name in $_FILES array -->
       Upload Thales ClearQuest file (e.g. 'PCR_COOPANS_Database_140711.zip'):<br><input name="userfile" type="file" />
       <br><input type="submit" name="Upload" value="UploadCQDump" />
   </form>
   <?php
   exit;
}
// Step 2
elseif(isset($_POST['Upload'])) {
   // In PHP versions earlier than 4.1.0, $HTTP_POST_FILES should be used instead
   // of $_FILES.
   // http://www.php.net/manual/en/features.file-upload.post-method.php
   // Choose whatever to upload file to gaia (Build 1) or gaia2 (Build 2)
   $uploaddir = '/var/www/';
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
   $FileName=basename($_FILES['userfile']['name']);
   echo "Filename: ".$FileName;
   //header("location:admin_uploadgaiapcrs.php");
   UnzipCQFiles($uploaddir,$FileName);
   // Handling of CQ import
   ImportXMLToKeyAndRecords($Key,$Records,"PCR_COOPANS_Export.xml");
   echo "<b>Keys imported:<br></b>";
   foreach ($Key as $k) echo $k."<br>";  // list all keys
   InsertRecordsIntoDB($Key,$Records);
   // Insert old PCRs from B1/B2 into new Clear Quest based DB
   InsertOldGAIADBIntoPCR();
   DropCQpcrCollection();  // drop collection of manually delta import files from Vincent (text imports)
   StoreAdminData("LastCQPCRInDB",GetLastPCRNumFromDB());
   StoreAdminData("ExtraPCRs",""); // erase manually added pcrs
   StoreAdminData("DateOfCQTextImport","none"); // erase manually added pcrs from text files
   
   RemoveCQDumpFiles($uploaddir,$FileName);
   exit;
}
exit;

function DropCQpcrCollection()
{
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'CQpcr');
   $response=$collection->drop();
}

function UnzipCQFiles($Path,$Filename)
{
   $zip = new ZipArchive;
   if ($zip->open($Path.$Filename) === TRUE) {
      $zip->extractTo($Path);
      $zip->close();
      echo ' unzipped successfully<br><br>';
   } else {
    echo 'failed to unzip file';
   }
}
function RemoveCQDumpFiles($Path,$Filename)
{
   unlink($Path.$Filename); // .zip file with CQ dump
   unlink('ClearquestResult.xsd');  // inside .zip
   unlink('PCR_COOPANS_Export.xml'); // inside .zip - file containing xml dump
   unlink('PCR_COOPANS_Summary.xml'); // inside .zip - file with xml for Excel
   rmdir('resources');  // inside .zip - directory that is normally empty
}

function ImportXMLToKeyAndRecords(&$Key,&$Records,$FileName)
{
   // $Key will return an array with all the Keys used in the ClearQuest DB
   // $Records will return arrays of all PCRs in CQ format

   $xml=simplexml_load_file($FileName);
   // Get ClearQuest version - Not used for anything
   print_r($xml->providerInfo);
   echo "<br>";
   // Get queryInfo - Not used for anything
   print_r($xml->queryInfo);
   echo "<br>";
   // Get Time of Creation of XML
   print_r($xml->attributes()->creationTime);
   StoreAdminData("ClearQuestVersion",(string) $xml->attributes()->creationTime);
   echo "<br>";
   // Get Column Names
   //print_r($xml->columnNames);
   echo "<br><br>";
   $Key=array(); // used to store name of all keys in ClearQuest DB
   foreach ($xml->columnNames->children() as $k=>$v) {
      $v=preg_replace('/[. ]/','_',$v); // Change space and punctuation to "_"
      $Key[]=(string) $v;  // Cast simpleXMLObject to string
   }
      
   StoreAdminData("ClearQuestKeys",$Key);
   // Get all records
   //print_r($xml->records);
   $Records=$xml->records;
}

function InsertRecordsIntoDB(&$Key,&$Records)
{
   // Drop content of DB
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'pcr');
   $response=$collection->drop();
   
   $collection2 = new MongoCollection($db, 'RecordsForPCRs');
  
   foreach($Records->children() as $child)
   {
      $k=0;
      $TotalSearch="";  // contains all values of document so they can be searched easily by using only one key i.e. "TotalSearch"
      $doc=array();
      foreach($child->children() as $v) {
        //echo $v;
        //echo "<br>";
        $v=trim((string) $v);
        // Take out PCR number of heading if available and store PCR number seperately
        if($Key[$k]=="Headline") $doc["OldPCRNum"]=TakeOutPCRNumOfHeadline($v);
        // Moved truncated headlines from Description first line to Headline
        if($Key[$k]=="Description") {
            $strFirstLine=strstr($v,"\n",true);
            $arrHeadline=explode("Truncated Headline:",$strFirstLine,2);
            if(sizeof($arrHeadline)==2) {
               $doc["OldPCRNum"]=TakeOutPCRNumOfHeadline($arrHeadline[1]);
               $doc["Headline"]=$arrHeadline[1];
            }
        }
        $doc[$Key[$k]]=$v;  // note that TakeOutPCRNumOfHeadline() can change $v
        // Remove not relevant charaters and words not worth searching on
        $v=preg_replace('/[.\n\r\t]/i',' ',$v);        
        $v=preg_replace('/[ ]is[ ]|[ ]are[ ]|[ ]of[ ]|[ ]in[ ]|[ ]to[ ]|[ ]be[ ]|[ ]at[ ]|[ ]up[ ]|[ ]does[ ]|[ ]do[ ]|[ ]the[ ]|[ ]by[ ]|[ ]has[ ]|[ ]have[ ]|[ ]as[ ]|[ ]an[ ]|[ ]a[ ]/i','',$v);
        $v=preg_replace('/\s+/',' ',$v);  // replace multiple spaces with single space
        $TotalSearch.=" ".$v;
        $k++;

      }
      
      // add extra keys not in import
      $cursor2=$collection2->findone(array("id" => $doc["id"])); // get and InsertRecordsForPCRsIntoPCR

      //if($doc["id"]=="COMP00093802") {
      //   echo "jaha".$cursor2["Tags"];
      //   exit;
      //}




      if(isset($cursor2)) $doc["Tags"]=$cursor2["Tags"];
      $doc['TotalSearch']=$TotalSearch;
      $doc["Attachments"]="";
      $doc["CQId"]=$doc["id"];
      $collection->save($doc);
   }
}
function TakeOutPCRNumOfHeadline(&$v)
{
   // Strip out PCR number from headerline and store it as OldPCRNum in DB
   // PCR num format in header line: PCR_COOPANS_B2_2596 ... or PCR_COOPANS_2596 ..
   $arrE=explode(" ",$v,2);
   if(substr($arrE[0],0,11)=="PCR_COOPANS") {
      // Remove PCR number from headerline
      //$v=trim(str_replace($arrE[0],"",$v));
      $v=$arrE[1];  // rest of string as explode limit is 2
      return $arrE[0]; // return PCR Number
   }
   echo "<br>Headerline has strange PCR number: ".$arrE[0]." ".$v;
   return "";
}

function InsertOldGAIADBIntoPCR() {
   // Will insert old type PCRs from testdb->oldpcr into new ClearQuest based DB i.e. testdb->pcr
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'oldpcr');
   $cursor=$collection->find();
   $collection2 = new MongoCollection($db, 'pcr');
   
   // Map old GAIA DB to new ClearQuest based DB
   foreach($cursor as $o) 
   {
   
      $o2=array();
      if($o['ProductName']=="COOPANS") $o2['OldPCRNum']="PCR_COOPANS_B1_".str_pad($o['RegularID'],4,"0",STR_PAD_LEFT);
      else $o2['OldPCRNum']="PCR_COOPANS_B2_".str_pad($o['RegularID'],4,"0",STR_PAD_LEFT);

      if($o['ProductName']=="COOPANS") $o2['id']="B1_".str_pad($o['RegularID'],4,"0",STR_PAD_LEFT);
      elseif($o['ProductName']=="COOPANS_B2") $o2['id']="B2_".str_pad($o['RegularID'],4,"0",STR_PAD_LEFT);
      else 
      {
         echo "Incorrect ProductName for PCR: ".$o['RegularID'];
         exit;
      }      
      
      $o2['Headline']=$o['Subject'];
      $o2['Detected_Release']=$o['Version'];
      $o2['External_id']=$o['ExternalID'];
      $o2['Delivered_Release']=$o['COR_VERS'];
      $o2['Description']=$o['Description'];
      $o2['State']=$o['S/W_status'];
      $o2['CQId']=$o['CQId'];
      $collection2->insert($o2);
   }
}

function GetClearQuestID($OldPCRNum)
{
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $col = new MongoCollection($db, 'pcr');
   
   $arrPCRNum=explode("_",$OldPCRNum);
   $num=(integer) $arrPCRNum[3];
   if($arrPCRNum[2]=="B1") $OldPCRNum=$arrPCRNum[0]."_".$arrPCRNum[1]."_".$num;
   else $OldPCRNum=$arrPCRNum[0]."_".$arrPCRNum[1]."_".$arrPCRNum[2]."_".$num;
   
   $cursor=$col->findone(array("OldPCRNum"=>$OldPCRNum));
   //echo "#$OldPCRNum...".$cursor["id"]."<br>";
   return $cursor["id"];
}

function GetLastPCRNumFromDB()
{
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'pcr');
   $doc=$collection->find()->sort(array('id'=>-1))->limit(1)->getNext();
   return $doc['id'];
}
?> 

</body>
</html>

