<?php
// Check if session is not registered , redirect back to main page.
// Put this code in first line of web page.
session_start();
if(!session_is_registered('myusername')){
   echo "Access denied";
   exit();
   header("location:checklogin.php");
   
}
?>
<?php include("admin_functions.php"); ?>

<html>
<head>
<title>Upload Mermaid dump</title>
</head>
<body>
<?php include("headerline.html"); ?>

<?php
// if not started then select file name to be uploaded
//echo "File Error code: ".$_FILES['userfile']['error'];
if(isset($_POST['userfile'])) echo "userfile"."<br>";
if(!isset($_FILES['userfile']['tmp_name'])) {
   ?>
   <!-- The data encoding type, enctype, MUST be specified as below -->
   <form enctype="multipart/form-data" action="admin_uploadpcrs.php" method="POST">
       <!-- MAX_FILE_SIZE must precede the file input field -->
       <input type="hidden" name="MAX_FILE_SIZE" value="60000000" />
       <!-- Name of input element determines name in $_FILES array -->
       Send this file: <input name="userfile" type="file" />
       <input type="submit" value="Upload Mermaid dump" />
   </form>
<pre>Path: F:\operations\O F&aelig;lles\COOPANS O\COOPANS JCCB\COOPANS OPEN PCR\</pre>
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
   } else {
       echo "Possible file upload attack!\n";
   }

   echo 'Here is some more debugging info:';
   print_r($_FILES);

   print "</pre>";

   // ************************************   
   // Get file into DB
   // ***********************************

   InsertFileIntoMongoDB($_FILES['userfile']['name']);
   // Store filename into DB so version can be shown in admin_main.php
   StoreAdminData('MermaidFileName',$_FILES['userfile']['name']);
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
   $arrHeader =    array('RegularID',         'ExternalID',
                      'AuthorLogin',       'DateOfCreation',
                      'ProductName',       'Version',
                      'Support',           'Site',
                      'Subject',           'Keywords',
                      'Type',              'Criticality',
                      'PCR_AUTHOR',        'TEST_ID',
                      'CSCI_NAME',         'ORIGIN',
                      'COR_VERS',          'BASELINE',
                      'OTHER_BASE_PCR',    'LINK_PCR',
                      'FREE_KEYWORDS',     'PATCH_COR',
                      'Description',       'PCR_History',
                      'Report',            'Answer',
                      'PCRNo',             'Last_Updated',
                      'DOCUMENT_LINK',     'Severity',
                      'S/W_cla/cor_date',  'S/W_status',
                      'Rel_PCR/ECR',       'Resp',
                      'S/W_clo/d??_Date',  'DetectionPhase',
                      'PhaseStage',        'DocStatus',
                      'Doc_cla/cor_date',  'Doc_clo/d??_Date',
                      'DocComp',           'SystemReq', 
                      'Priority',          'Safety', 
                      'MeRMAId_PCR' );

   // $cursor = $collection->findone(array('sessionname' => $_SESSION['irftestsession']));
//   $_SESSION['irfswversion']=$cursor['swversion'];
//   $_SESSION['irfdataset']=$cursor['dataset'];
//   $_SESSION['irfrigid']=$cursor['rigid'];
   //foreach($array as $i) {
   //   $doc=array_merge((array)$doc, (array)array($i=>$_SESSION[$i]));
   //}

   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'pcr');
   $response=$collection->drop();
   //echo "Drop response=";
   //print_r($response);
   //echo "   ";

   $doc=array();

   // doublet handling
   $pcrnumintold=0;
   $productnameold="";
   $numofdoublets=0;

   for($row=2;$row<=$xls->rowcount();$row++) {  // skip header row so start by 2

      // remove potential doublets of pcrs
      $pcrnumint=intval($xls->val($row,1));  // col 1 is regular id
      $productname=$xls->val($row,5);  // col 5 is product name / build
      if($pcrnumint==$pcrnumintold && $productnameold==$productname) {
         $row++;
         $numofdoublets++;
         echo "Doublet was detected: Removed one of PCRs:".$productname." ".$pcrnumintold."<br>";
      }
      else {
         $pcrnumintold = $pcrnumint;
         $productnameold= $productname;
      }

    
      for($col=0;$col<45;$col++) {
         if($arrHeader[$col] == 'RegularID' || $arrHeader[$col] == 'MeRMAId_PCR') {
            $doc[$arrHeader[$col]]= intval($xls->val($row,$col+1));  // integer
         }
         else {
            $doc[$arrHeader[$col]]= iconv("UTF-8","UTF-8//IGNORE",$xls->val($row,$col+1));  // text
         }
      }
      $collection->insert($doc);
      $doc=array();
   }
   $realrow=$row-2;
   echo "<br><b>Number of records: $realrow records</b><br>";
   $imported=$realrow-$numofdoublets;
   echo "<b>Imported: $imported records</b><br>";
}

//for($row=1;$row<=$xls->rowcount();$row++) {
//   echo "$row ";
//   for($col=1;$col<=$xls->colcount();$col++) {
//      echo " ".$xls->val($row,$col);
//   }
//}


