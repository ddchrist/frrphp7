<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<html>
<head>
<title>Upload Mermaid dump</title>
</head>
<body>
<?php include("headerline.html"); ?>

<?php
if(!$_SESSION["username"]=="lbn") {
   echo "No user rights to do this!";
   exit;
}

// if not started then select file name to be uploaded
//echo "File Error code: ".$_FILES['userfile']['error'];
if(isset($_POST['userfile'])) echo "userfile"."<br>";
if(!isset($_FILES['userfile']['tmp_name'])) {
   ?>
   <!-- The data encoding type, enctype, MUST be specified as below -->
   <form enctype="multipart/form-data" action="admin_excel_import.php" method="POST">
       <!-- MAX_FILE_SIZE must precede the file input field -->
       <input type="hidden" name="MAX_FILE_SIZE" value="60000000" />
       <!-- Name of input element determines name in $_FILES array -->
       File to upload to DataBase: <input name="userfile" type="file" /><br>
       DataBase:<input type="text" name="DataBase" size="16" value=""/>
       Collection:<input type="text" name="Collection" size="16" value=""/>
       Key:<input type="text" name="key" size="16" value=""/><br>

       <input type="submit" value="Upload Mermaid dump" />
       Password:<input type="password" name="mypassword"  id="mypassword">
   </form>
<pre>Choose a .xls file to be uploaded to the DataBase (not .xlsx).
Insert a destination DataBase name and a destination collection name.
If a "Key" is given data will be encrypted. Only column names (in
.xls file) starting with "*" is encrypted</pre>
   <?php
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
   $db = $m->selectDB($_POST['DataBase']);
   $collection = new MongoCollection($db, $_POST['Collection']);
   // Get key (if any) for encryption
   if(isset($_POST['key'])) $key=$_POST['key'];
   else $key="";
   $response=$collection->drop();

   $doc=array();
   $arrHeaderKey=array();

   // Get Header names from first row in Excel file
   $row=1;  // first row of excel table
   $NumOfCol=$xls->colcount();
   $NumOfRow=$xls->rowcount();
   for($col=1;$col<=$NumOfCol;$col++) {
      $arrHeaderKey[$col]= iconv("UTF-8","UTF-8//IGNORE",$xls->val($row,$col));  // text
   }

   for($row=2;$row<=$NumOfRow;$row++) {  // skip header row so start by 2
      for($col=1;$col<=$NumOfCol;$col++) {
      
         $cell=iconv("UTF-8","UTF-8//IGNORE",$xls->val($row,$col));  // text
//         $cell=$xls->val($row,$col);  // text
         // If first character in excel column heading is "*" then encrypt content.
         if(substr($arrHeaderKey[$col],0,1)=="*") {
            if($key=="") {
               echo "ERROR: Column header '".$arrHeaderKey[$col]."' must be encrypted using a key, but no key was given!";
               exit;
            }
            $cell=encrypt($cell, $key);
         }
         $doc[$arrHeaderKey[$col]] = $cell;
      }
      $collection->insert($doc);
      $doc=array();
   }

   echo "<br><b>Number of rows imported: </b>$NumOfRow<br>";
   echo "<b>Number of columns imported: </b>$NumOfCol<br>";
   echo "<b>Headers created:</b>";
   for($col=1;$col<=$NumOfCol;$col++) {
      echo '"'.$arrHeaderKey[$col].'"'.", ";
   }

}

function encrypt($str, $key)
{
    $result=mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB);   
    return base64_encode($result);
}

function decrypt($str, $key)
{   
    $str=base64_decode($str);
    $str = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB);
    return $str;
}
// http://tech.chitgoks.com/2008/03/24/php-encrypt-decrypt-using-base64/
function eeencrypt($string, $key) {
  $result = '';
  for($i=0; $i<strlen($string); $i++) {
    $char = substr($string, $i, 1);
    $keychar = substr($key, ($i % strlen($key))-1, 1);
    $char = chr(ord($char)+ord($keychar));
    $result.=$char;
  }
  return base64_encode($result);
}
function eedecrypt($string, $key) {
  $result = '';
  $string = base64_decode($string);
  for($i=0; $i<strlen($string); $i++) {
    $char = substr($string, $i, 1);
    $keychar = substr($key, ($i % strlen($key))-1, 1);
    $char = chr(ord($char)-ord($keychar));
    $result.=$char;
  }
  return $result;
}
