<?php include("login_headerscript.php"); ?>


<?php include("admin_functions.php"); ?>

<html>
<head>
<title>Upload Mermaid dump</title>
<style>
table {
    border-collapse: collapse;
}

table, td, th {
    border: 1px solid black;
    text-align: left;
}
</style>
</head>
<body>
<?php include("headerline.html"); ?>
<?php
// **************************

if(isset($_GET['CopyPCRToOldPCR'])) {
   // Copy Collection testdb->pcr to testdb->oldpcr
   if($_SESSION['username']=="lbn") {
      $m = new Mongo();
      $db = $m->selectDB("testdb");
      $collection = new MongoCollection($db, "pcr");
      $collection2 = new MongoCollection($db, "oldpcr");
      $cursor=$collection->find();
      // List WP numbers and relevant Keys
      foreach($cursor as $o) {
         $collection2->insert($o);
      }
   }
   echo "<br>Copied 'testdb->pcr' to 'testdb->oldpcr'. As mongoDB _Id are copied as well, DB does not build up used multiple times";
   exit;
}

if(isset($_GET['InsertCQIdIntoOldGAIAPCR'])) {
   // Go through all oldpcrs (GAIA) and insert mapped ClearCase IDs in key CQId
   if($_SESSION['username']=="lbn") {
      $m = new Mongo();
      $db = $m->selectDB("testdb");
      $collection = new MongoCollection($db, "oldpcr");
      $collection2 = new MongoCollection($db, "oldpcr");
      $cursor=$collection->find();
      // List WP numbers and relevant Keys
      foreach($cursor as $o) {
         $o["CQId"]=GetClearQuestID($o["ProductName"], $o["RegularID"]);
         echo "PCR_".$o["ProductName"]."_".$o["RegularID"]."=".$o["CQId"]."<br>";
         $collection2->save($o);
      }
   }
   echo "Inserted mapped ClearQuest IDs into oldpcrs (GAIA)";
   exit;
}
if(isset($_GET['DeleteFields'])) {
   ?>
   <form action="admin_main.php" method="post">
   DataBase:<input type="text" name="DataBase" size="16" value=""/>
   Collection:<input type="text" name="Collection" size="16" value=""/>
   Key:<input type="text" name="key" size="16" value=""/><br>
   <input type="submit" name="CheckKeys" value="Check Keys" />
   </form>
<pre>Type in first letter of "Key" to be deleted. All Keys containing
the first letters will be deleted in the Database/Collection. Note
it works by matching 'WPNum' which must be available in each
document/record</pre>
   <?php
   exit;
}
if(isset($_POST['CheckKeys'])) {
?>
      <form action="admin_main.php" method="post">
      DataBase:<input type="text" name="DataBase" size="16" readonly value="<?php echo $_POST['DataBase']; ?>"/>
      Collection:<input type="text" name="Collection" size="16" readonly value="<?php echo $_POST['Collection']; ?>"/>
      Key:<input type="text" name="key" size="16" readonly value="<?php echo $_POST['key']; ?>"/><br>
      Password:<input type="password" name="mypassword"  id="mypassword">
      <input type="submit" name="DeleteKeys" value="Delete Keys" />
      </form>
<?php
      $m = new Mongo();
      $db = $m->selectDB($_POST['DataBase']);
      $collection = new MongoCollection($db, $_POST['Collection']);
      $Key=$_POST['key'];
      $cursor=$collection->find();
      // List WP numbers and relevant Keys
      foreach($cursor as $o) {
         foreach ($o as $k=>$v) {
            if(substr($k,0,strlen($Key))==$Key) {
                echo $o["WPNum"]." ".$k."<br>";
            }
         }
      }
      exit;

}
if(isset($_POST['DeleteKeys'])) {
   if($_POST["mypassword"]=="vise10MUN22") {
      $m = new Mongo();
      $db = $m->selectDB($_POST['DataBase']);
      $collection = new MongoCollection($db, $_POST['Collection']);
      $collection2 = new MongoCollection($db, $_POST['Collection']);
      $Key=$_POST['key'];
      //$query=array('WPNum' => new MongoRegex("/^$Key/"));  // search anything starting with $Key
      $cursor=$collection->find();   // "Backup" => array('$exists' => false)

      foreach($cursor as $o) {
         foreach ($o as $k=>$v) {
            if(substr($k,0,strlen($Key))==$Key) {
                echo $o["WPNum"]." ".$k." <b>Deleted</b><br>";
                $Filter=array('WPNum' => $o["WPNum"]);
                $Update=array('$unset'=>array($k=>1));
                $collection2->update($Filter,$Update);
            }
         }
      }
     exit;
   }
   else {
      echo "You are not authorised!";
      exit;
   }
}
elseif(isset($_GET['GetAttachmentsFileNames']))
{
   GetContentOfAttachmentsDirectoryToDB();
   exit;
}
//*****************************
?>
<table>
<tr>
<th>Users</th><th>Activity</th>
</tr>
<tr>
<td>All</td><td><a href="admin_adduser.php">Add New User</a></td>
</tr>
<tr>
<td>All</td><td><a href="admin_print_pcrs.php">Type in PCR numbers and get a list with selected content</a></td>
</tr>
<tr><td> </td><td> </td>
</tr>
<tr>
<th>Admin</th><th>Activity</th>
</tr>
<tr>
<td>ARH/JEO: bi-weekly</td><td><a href="admin_uploadclearquestpcrs.php">Upload The ClearQuest Database</a></td>
</tr>
<tr>
<td>ARH/JEO: bi-weekly</td><td><a href="admin_main.php?GetAttachmentsFileNames=1">Get file names of Clear Case PCR attachments into DB</a></td>
</tr>
<tr>
<td>ARH/JEO: weekly</td><td><a href="search_ImportClearQuestTextDump.php?ImportTextDump&TextDump=TextDump">Upload ClearQuest text files to Database with text dump of PCRs</a></td>
</tr>
<tr>
<td>JEO: when updated by JEO</td><td><a href="risk_view.php?insertJEO">Insert Safety Mitigations from JEO's Excel sheet (PCR_Safety_...xls)</a></td>
</tr>
</table>


<br><br><b>Admin advanced:</b> (Some functions are outdated)<br>
(WARNING! Do not mesh with the following unless you know what you are doing!!)<br>
<br><a href="search_ImportClearQuestTextDump.php?ImportTextDump">Upload ClearQuest text files to Database</a>
<br><a href="search_ImportClearQuestTextDump.php?ImportTextDump&TextDump=TextDump">Upload ClearQuest text files to Database with text dump of PCRs</a>
<br><a href="search_ImportClearQuestTextDump.php?ImportTextDump">(quiet) Upload ClearQuest text files to Database WITHOUT text dump of PCRs (to be used for large number of (bulk) PCRs)</a>
<br><a href="admin_uploadclearquestpcrs.php">Upload The ClearQuest Database</a>
<br><a href="admin_main.php?GetAttachmentsFileNames=1">Get file names of Clear Case PCR attachments into DB</a>
<br><a href="admin_uploadgaiapcrs.php">Upload The Gaia Database '.tar' files</a> Present versions:
<font size=-1  face="verdana" color="blue">
<?php echo "B1=".GetAdminData("GaiaDBVersion_B1");?>
<?php echo " B2=".GetAdminData("GaiaDBVersion_B2");?>
</font>
<br>
<a href="admin_uploadpcrs.php">Upload Mermaid '.xls' dump</a> Present file:
<font size=-1  face="verdana" color="blue"> 
<?php echo GetAdminData("MermaidFileName");?>
</font>
<br>
<a href="admin_excel_import.php">Import Excel file to DB</a>
<br>
<a href="admin_main.php?DeleteFields=1">Delete fields in DB</a>
<br>
<a href="admin_main.php?CopyPCRToOldPCR=1">Copy pcr collection to oldpcr collection. Oldpcr is not dropped before</a>
<br>
<a href="admin_main.php?InsertCQIdIntoOldGAIAPCR=1">Update oldpcr collection (GAIA) with ClearCase IDs</a>
<br>
<a href="admin_uploadgaiapcrs.php?InsertPCR=1">Copy-Paste PCR from .PDF into DB</a>
<br>
<a href="risk_view.php?insert">Insert Safety Assessments from Word table to DB (For Allan)</a>
<br>
<a href="risk_view.php?insertJEO">Insert Safety Mitigations from JEO's Excel sheet (PCR_Safety_...xls)</a>
</body>
</html>


<?php

function GetClearQuestID($ProductName,$RegId)
{
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $col = new MongoCollection($db, 'pcr');
      
   $cursor=$col->findone(array("OldPCRNum"=>"PCR_".$ProductName."_".$RegId));
   return $cursor["id"];
}

// *********************************************************************************
//   Make Attachment key in pcr DB to point to list of file names of attachments
//   Attachments are places in localhost/attachments/id/filename
// *********************************************************************************

function GetContentOfDirectory($path,&$arrFileNames)
{
   if ($handle = opendir($path)) {
       while (false !== ($entry = readdir($handle))) {
           if ($entry != "." && $entry != "..") {
               // echo "$entry<br>";
               $arrFileNames[]=$entry;
           }
       }
       closedir($handle);
   }
}
function GetContentOfAttachmentsDirectoryToDB()
{
   $arrFileNames=array();
   GetContentOfDirectory("attachments",$arrFileNames);
   echo "<br>";
   foreach($arrFileNames as $Dir)  // $Dir is directory name which is the same as CQ id.
   {
      //echo $Dir."<br>";
      $arrFileNames=array();
      GetContentOfDirectory("attachments/$Dir",$arrFileNames);
      StoreAttachmentsInDB($Dir,$arrFileNames);
      //print_r($arrFileNames);
      //echo "<br>***************************<br>";
   }
   
}
function StoreAttachmentsInDB($id,&$arrFileNames)
{
   //$m = new Mongo();
   //$db = $m->selectDB('testdb');
   //$collection = new MongoCollection($db, 'pcr');
   $m = new MongoDB\Client();
   $db = $m->testdb;
   $collection = $db->pcr;
   var_dump($collection);
   $NewData=array('$set' => array("Attachments" => $arrFileNames));
   var_dump($NewData);
   $collection->update(array("id"=>$id), $NewData); 
   echo $id." ";
}





