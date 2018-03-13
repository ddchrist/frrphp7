<?php include("login_headerscript.php"); ?>
<?php
// This script id called my method "get" and will download stored
// attachments. The ID of the file must be given e.g.:
// ifr_download.php?id=4beaa34f00f4784c0a300000

//http://blog.hardkap.com/index.php/posts/00069/MongoDB---GridFS

if(isset($_GET['id'])) $id=$_GET['id'];
else {
   echo "Error: id of file to be downloaded must be given";
   exit(0);
}

try {
  // open connection to MongoDB server
  $conn = new Mongo('localhost');

  // access database
  $db = $conn->test;

  // get GridFS files collection
  $grid = $db->getGridFS();

  // retrieve file from collection
  //$file = $grid->findOne(array('_id' => new MongoId('4beaa34f00f4784c0a300000')));
  $file = $grid->findOne(array('_id' => new MongoId($id)));

  // send headers and file data
  // Content-Type:    can be image/jpeg, image/gif, image/png, text/plain
  // application/plain, application/x-zip, application/octet-stream
  header('Content-Type: application/octet-stream');
  header("Content-Disposition: attachment;Filename=".$_GET['filename']);
  header('Content-Transfer-Encoding: binary');
  echo $file->getBytes();
  exit;  

  // disconnect from server
  $conn->close();
} catch (MongoConnectionException $e) {
  die('Error connecting to MongoDB server');
} catch (MongoException $e) {
  die('Error: ' . $e->getMessage());
}
?>
