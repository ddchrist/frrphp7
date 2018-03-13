<?php include("login_headerscript.php"); ?>
<?php
// Handles attachments of files for a given IRF
// Is accesed through method 'GET'. Does internally use method POST
// If called with '?irf=' some number then this number will be inserted into collection
//   fs.files (GridFS). 
// If called without an irf number, then collection fs.files will be given the temporary
//   number "NoNumberYet" and the script it returns to must call this script again with
//   ?irf='irf_number'&updatenumber=1. The this script will attach the right irf to fs.files 

// *******************************************************
// ******************** Definitions **********************
// *******************************************************
define("FILE_DB",             "test");      // DB where Mongo GridFS will be working
define("FILE_COLLECTION",     "fs.files");  // fs.files is set by GridFS and can not be changed
                                            // fs.files contain information of file name length
                                            // and how it is 'chunked' into subfiles 
define("NO_NUMBER_YET",    "No_IRF_Number_yet");  // Parts of tag to identify that irf is not in
                                                  // fs.files. It is a unique tag and should not
                                                  // be changed
define("DBKEY_IRFNUM",     "irfnum");             // IRF number key in DB for collection fs.files


?>

<?php
// *******************************************************
// ********* GET / POST Control logic ********************
// *******************************************************

if (isset($_GET["irf"])) {
   $irfnum=$_GET["irf"];                    // if IRF exist get number
   if (isset($_GET["updatenumber"])) {
      UpdateIRFNumberInGridFS($irfnum);
      header("location:irf_view.php?irf=$irfnum");   // *****not quite sure where it has to return yet******
      exit;  // make sure code below is not executed after execution of header
   }
}
else $irfnum=$_SESSION['username']."_".NO_NUMBER_YET;  // if IRF is not yet submitted,
                                            // number has not been allocated - so fix temp num.

// if form is submitted go back to IRF input sheet
if(isset($_POST["submit"])) header("location:irf_input.php");
elseif(isset($_POST["addupload"])) {
   $irfnum=$_POST['irfnum'];
   UploadFileToDB($irfnum);
}
elseif(isset($_GET["removeid"])) {
   $irfnum=$_GET['irf'];
   RemoveFileFromUploadList();
}

ShowUploadView($irfnum);
session_write_close();  // remember to close session fast to avoid locks in response
exit(0);
?>

<?php
// ******************************************************
// ***************** FUNCTIONS START ********************
// ******************************************************
function UpdateIRFNumberInGridFS($irfnum) {
   // Will look up all files in collection fs.files where no irf number has been attached.
   // These records are identified by an key irfnum containing userid + the text NoNumberYet
   // e.g. value lbn_No_Num_ber_Yet
   // This value is replaced by the proper irf number
   $m = new Mongo();
   $db = $m->selectDB(FILE_DB);
   $collection = new MongoCollection($db, FILE_COLLECTION);
   $irfid=$_SESSION['username']."_".NO_NUMBER_YET;  // user id + NoNumberYet
   $cursor = $collection->find(array(DBKEY_IRFNUM => $irfid));

   foreach($cursor as $i) {
      $doc = $collection->findone(array("_id" => $i['_id']));
      $doc[DBKEY_IRFNUM]=$irfnum;
      $collection->save($doc);
   }
}

// ***********************************************************
function GetFileNameAndIDFromDB($irfnum) {
   $m = new Mongo();
   $db = $m->selectDB(FILE_DB);
   $collection = new MongoCollection($db, FILE_COLLECTION);
   $cursor = $collection->find(array(DBKEY_IRFNUM => $irfnum));
   $array=array();
   foreach($cursor as $id => $value) {   // http://php.net/manual/en/mongo.tutorial.php
      $array[]=array($id=>$value['filename']);
   }
   return $array;
}

// ***********************************************************
function ShowUploadView($irfnum) {
?>
   <html>
   <head>
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
   <title>Select IRF attachments</title>
   </head>
   <body>
   <font size=+1><b>Select IRF attachments</b></font>
   <form action="irf_select_upload.php" method="post" enctype="multipart/form-data">
   <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
   <input type="text" name="irfnum" value="<?php echo $irfnum?>" readonly />
   <br>
   <?php
   $arrFileNameID=GetFileNameAndIDFromDB($irfnum);

   ?><table border="0"><?php
   foreach($arrFileNameID as $c) {
         list($id,$filename)=each($c);  // se PHP bog
         echo "<tr>";
         echo "<td>$filename </td>";
         echo '<td><a href="irf_select_upload.php?removeid='.$id."&irf=$irfnum".'">Remove</a></td>';
         echo '<td><a href="irf_download.php?id='.$id."&filename=".$filename.'">Download</a></td>';
         echo "</tr>";
   }
   ?></table><?php
?>
   <table border="0">
   <!-- row 1 -->
   <tr>
   <td>Upload:</td>
   <td><input type="file" name="f" id="file" /></td>
   <td><input type="submit" name="addupload" value="Add upload"/></td>
   </tr>
   </table>
   <input type="submit" name="submit" value="Back to IRF"/>
   </form>
   </body>
   </html>   
<?php
}

// ***********************************************************
// Upload selected file to DB and stores the IRF number on key 'attachment'
function UploadFileToDB($irfnum) {
   if($_FILES['f']['tmp_name'] != "") {
      // Upload file into MongoDB
      // http://devzone.zend.com/1730/getting-started-with-mongodb-and-php/ 
      // http://www.cyberciti.biz/faq/linux-unix-apache-increase-php-upload-limit/
      try {
        $m = new Mongo();
        $db = $m->selectDB(FILE_DB);
        $conn = new MongoCollection($db, FILE_COLLECTION);
        $gridfs = $db->getGridFS();  // collection
        // check uploaded file
        // store uploaded file in collection and display ID
        if (is_uploaded_file($_FILES['f']['tmp_name'])) {
          $id = $gridfs->storeUpload('f'); // id to find file in fs.files
          echo 'Saved file with ID: ' . $id;
        } else {
          throw new Exception('Invalid file upload');
        }
        
        // Store a key called attachment that points to IRF number if any
        $cursor = $conn->findOne(array("_id" => $id));
        //$file=array_merge($file, array('attachments'=>$irfnum));
        $cursor[DBKEY_IRFNUM]=$irfnum;
        $gridfs->save($cursor);
       
      } catch (MongoConnectionException $e) {
        die('Error connecting to MongoDB server');
      } catch (Exception $e) {
        die('Error: ' . $e->getMessage());
      }
      header("location:irf_select_upload.php");
      exit;  // make sure code below is not executed after execution of header
   }
   else {
      echo "Something is wrong with uploading files damn!";
      exit(0);
   }
   return $id;
}

// ***********************************************************
// Removes a file upload from GridFS based in mongo ID that must
// be in $_GET[removeid]
function RemoveFileFromUploadList() {
   $m = new Mongo();
   $db = $m->selectDB(FILE_DB);
   $conn = new MongoCollection($db, FILE_COLLECTION);
   $gridfs = $db->getGridFS();  // collection
   $gridfs->delete(new MongoId($_GET['removeid']));
//   $file = $gridfs->findOne(array('_id' => new MongoId($_GET['removeid'])));   
//   $id = $file->file['_id'];                    // Get the files ID
   //$gridfs->delete($id);  

}


// ******************************************************
// ****************** FUNCTIONS END**********************
// ******************************************************


