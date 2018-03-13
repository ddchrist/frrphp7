<?php include("login_headerscript.php"); ?>

<?php // http://devzone.zend.com/1730/getting-started-with-mongodb-and-php/ 
         http://www.cyberciti.biz/faq/linux-unix-apache-increase-php-upload-limit/  ?>
<html>
  <head></head>
  <body>
    <form method="post" enctype="multipart/form-data">
      Select file for upload:
      <input type="file" name="f" />
      <input type="submit" name="submit" />
    </form>
    <?php
    if (isset($_POST['submit'])) {
      try {
        // open connection to MongoDB server
        $conn = new Mongo('localhost');

        // access database
        $db = $conn->test;

        // get GridFS files collection
        $gridfs = $db->getGridFS();

        // check uploaded file
        // store uploaded file in collection and display ID
        if (is_uploaded_file($_FILES['f']['tmp_name'])) {
          $id = $gridfs->storeUpload('f');
          echo 'Saved file with ID: ' . $id;
        } else {
          throw new Exception('Invalid file upload');
        }

        // disconnect from server
        $conn->close();
      } catch (MongoConnectionException $e) {
        die('Error connecting to MongoDB server');
      } catch (Exception $e) {
        die('Error: ' . $e->getMessage());
      }
    }
    ?>
  </body>
</html>
