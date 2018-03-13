<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<html>
<head>
<title>Upload Mermaid dump</title>
<?php include("class_lib.php"); ?>
</head>
<body>
<?php include("chk_headerline.html"); ?>

<?php
//if(!$_SESSION["username"]=="lbn") {
//   echo "No user rights to do this!";
//   exit;
//}

// Global variables
$arrAllowedKeys=array("EIDW","EISN","EKDK","ESMM","ESOS","LOWW","LDZO",
                   "Note","Note_dk","Note_ie","Note_hr","Note_se","Note_at",
                   "WPName","WPNum","SuggestedChange","Roles",
                   "Initiator","Modified", "ModifiedBy",
                   "H1","H2","H3","Comments","TCNum","TC#","References");
                   

// if not started then select file name to be uploaded
//echo "File Error code: ".$_FILES['userfile']['error'];
if(isset($_POST['userfile'])) echo "userfile"."<br>";
if(!isset($_FILES['userfile']['tmp_name'])) {
   ?>
   <b>WARNING - READ!</b><br>Before doing any upload make sure to backup the database/collection <b>TestChecks/MASTER</b>.<br><br>
   <!-- The data encoding type, enctype, MUST be specified as below -->
   <form enctype="multipart/form-data" action="chk_import_testchecks_excel.php" method="POST">
       <!-- MAX_FILE_SIZE must precede the file input field -->
       <input type="hidden" name="MAX_FILE_SIZE" value="60000000" />
       <!-- Name of input element determines name in $_FILES array -->
       TestCheks File to upload: <input name="userfile" type="file" /><br>
       
       <input type="submit" value="Upload TestCheck file" />
       Password:<input type="password" name="mypassword"  id="mypassword">
   </form>
<pre>Choose a '.xls' (Excel 97-2003 format) file to be uploaded (not '.xlsx').

If a test case exist, it will be overwritten by the headers/keys in the import file.
Headers/keys not in the import file will be left untouched. So to make sure all
information in a test case is deleted before import, it is suggested to delete it
beforehand or make sure all keys are present in the import format.
Headers/Keys are case sensitive.

<b>Example format #1:</b>
<b>TCNum    H1                 H2                          H3                  Comments .. .. ..</b>
64.01.01 ADSB, ModeS & WAM  Position Symbol and...      Task 1 of 28....    #DPR#TIME...
64.01.02 ADSB, ModeS & WAM  Position Symbol and...      Task 2 of 28....

Internally in the DB 'H1', 'H2' and 'H3' does not exist. They are all called 'WPName' and is
differentiated by the 'WPNum' (TestCase number). So when trying to store e.g. 64.01.01
and the super headers (H1, H2) do not exist, they are created.

'Comments' does not exist in the DB and is converted to 'Note'.
'TCNum' (TestCase Number) does not exist in the DB and is converted to 'WPNum'.
'TC#' (TestCase Number) does not exist in the DB and is converted to 'WPNum'.

<b>Example format #2:</b>
Another way to make a format is (and this is how the DB matches without conversion):
<b>WPNum        WPName                   Note .. .. ..</b>
64           ADSB, ModeS & WAM   
64.01        Position Symbol and...  
64.01.01     Task 1 of 28....         #DPR#TIME...
64.01.02     Task 2 of 28....         
</pre>
<?php
echo "Allowed headers/keys in format:<br>";
foreach($arrAllowedKeys as $key) echo "<b>$key</b>, ";

}
else {
   if($_POST["mypassword"]=="vise10MUN22") {
      // In PHP versions earlier than 4.1.0, $HTTP_POST_FILES should be used instead
      // of $_FILES.
      // http://www.php.net/manual/en/features.file-upload.post-method.php
      // make sure uploaddir has chmod a=rwx
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
   else echo "Incorrect password";
}

?>

</body>
</html>



<?php
function InsertFileIntoMongoDB($filename)
{
   error_reporting(E_ALL ^ E_NOTICE);
   require_once 'excel_reader2.php';
   // http://stackoverflow.com/questions/3666412/php-excel-reader-problem-with-utf-8

   // $filename="COOPANS_MERMAID_22032012.xls";
   $xls=new Spreadsheet_Excel_Reader($filename,false,"UTF-8");  // "false" makes it drop formatting and makes the file much smaller
   //$xls->setUTFEncoder('UTF-8');
   //$xls->setUTFEncoder('iconv');
   //$xls->setOutputEncoding('UTF-8');
   //$xls->read("testmermaid.xls");

   $m = new Mongo();
   $db = $m->selectDB("Checklist");
   $collection = new MongoCollection($db, "MASTER");

   $doc=array();
   $arrHeaderKey=array();

   // Get Header names from first row in Excel file
   $row=1;  // first row of excel table
   $NumOfCol=$xls->colcount();
   $NumOfRow=$xls->rowcount();
   for($col=1;$col<=$NumOfCol;$col++) {
      $arrHeaderKey[$col]= trim(iconv("UTF-8","UTF-8//IGNORE",$xls->val($row,$col)));  // text
   }
   VerifyHeaderKeys($arrHeaderKey);

   // Import cells - foreach row get all columns
   for($row=2;$row<=$NumOfRow;$row++) {  // skip header row so start by 2
      for($col=1;$col<=$NumOfCol;$col++) {
         if($arrHeaderKey[$col]=="H3") $arrHeaderKey[$col]="WPName";   // convert name to DB name
         if($arrHeaderKey[$col]=="Comments") $arrHeaderKey[$col]="Note";  // convert name to DB name
         if($arrHeaderKey[$col]=="TCNum" or $arrHeaderKey[$col]=="TC#") $arrHeaderKey[$col]="WPNum";  // convert name to DB name
         
         $cell=iconv("UTF-8","UTF-8//IGNORE",$xls->val($row,$col));  // text
         $doc[$arrHeaderKey[$col]] = $cell;
      }
      $WPNum=$doc["WPNum"];
      if(!ValidFullWPNum($WPNum)) {
         echo "<br><b>Import failed!</b> WPNum: <b>$WPNum</b> is invalid in row number <b>$row</b>.";
         exit;
      }
      $document=$collection->findone(array("WPNum"=>$WPNum));

      if($document==NULL)
      {
         echo "<br>Added new TestCase: <b>$WPNum</b>.";
         CreateSuperLevelHeadersOfTestCase($WPNum,$doc);  // if H1 or H2 of WPnumber doeas not exist create them
      }
      else {
         echo "<br>WPNum <b>$WPNum</b> exist and is overwritten: ";
      }
      
      foreach($doc as $k=>$v) {
         if($k=="H1" or $k=="H2") continue; // do not store H1 and H2 
         $document[$k]=$v;  // copy file row into existing TestCase
         echo "<br>&nbsp;&nbsp;&nbsp;$k=$v";
      }

      $collection->save($document);
      $doc=array();
      AddHTMLofWPToDB($WPNum,"Checklist","chk");
      UpdateHTMLColoursofSubWPs($WPNum,"Checklist","chk");
      
   }

   echo "<br><b>Number of rows imported: </b>$NumOfRow<br>";
   echo "<b>Number of columns imported: </b>$NumOfCol<br>";
   echo "<b>Headers processed:</b>";
   for($col=1;$col<=$NumOfCol;$col++) {
      echo '"'.$arrHeaderKey[$col].'"'.", ";
   }

}
function CreateSuperLevelHeadersOfTestCase($WPNum,&$doc)
// In case TestCase=$WPNum does not have super headers i.e. H1 and H2
// they must be created
{
   $WPNumH2=GetNextSuperLevel($WPNum);
   UpdateSuperLevel($WPNumH2,$doc["H2"]);
   $WPNumH1=GetNextSuperLevel($WPNumH2);
   UpdateSuperLevel($WPNumH1,$doc["H1"]);
}

function UpdateSuperLevel($WPNum,$Header)
// $WPNum = SuperLevel to be updated e.e. "01.01" as super level to "01.01.01"
// $Header = Header to be inserted into super level
// If super level exist, it is not updated
{
   $m = new Mongo();
   $db = $m->selectDB("Checklist");
   $collection = new MongoCollection($db, "MASTER");
   
   $obj=$collection->findone(array("WPNum"=>$WPNum));
   if($obj==NULL) {
      $obj["WPName"]=$Header;
      $obj["WPNum"]=$WPNum;
      $collection->save($obj);
      AddHTMLofWPToDB($WPNum,"Checklist","chk");
      UpdateHTMLColoursofSubWPs($WPNum,"Checklist","chk");
      echo "<br>Superlevel <b>$WPNum</b> header <b>$Header</b> created.";
   }
}

function VerifyHeaderKeys($arrHeaderKey)
// make sure header keys are correct in relation to what is used in DB
{
   global $arrAllowedKeys;
                   
   foreach($arrHeaderKey as $key) {
      if(!in_array($key,$arrAllowedKeys)) {
         echo "<br>Column header name '".$key."' is not valid. Import halted / no changes done.";
         echo "<br>Allowed header keys:<br>";
         foreach($arrAllowedKeys as $key) echo "<b>$key</b>, ";
         exit;
      }
   }

   return true;
}

