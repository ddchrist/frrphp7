<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<style type="text/css">
a:link{
  color:black;
}
a:visited{
  color:black;
}
a:hover{
  color:orange; text-decoration: underline;
}
a:focus{
  color:green;
}
a:active{
  color:red;
}

A{text-decoration:none}     <!--  remove underline in links -->
</style>


<title>Status of Test Checks</title>
<?php include("class_lib.php"); ?>

<script src="scrollfix.js" type="text/javascript"></script>


</head>
<body onunload="unloadP('chk_manage_wbs')" onload="loadP('chk_manage_wbs')">
<?php include("headerline.html"); ?>

<?php
// ******************************************************
// *** POST/GET Control logic
// ******************************************************
if(isset($_GET['ImportTextDump'])) {
   GetNameOfZippedTestChecksFile();
}
elseif (isset($_POST['Upload'])) {
   $FileName=UploadZippedTestChecksFileToServer();
   ImportZippedTestCheckFile($FileName);
   echo "<br>Imported ClearQuest text file: ".$FileName;
   StoreAdminData("DateOfCQTextImport",date("Y-m-d"));
   echo "<br>Date: ".GetAdminData("DateOfCQTextImport");
   exit;
}

?>
</body>
</html>
<?PHP
exit;

// ******************************************************
// ******************************************************
// *** MAIN PHP - Functions
// ******************************************************
// ******************************************************

// ******************************************************
// *** HTML Input forms
// ******************************************************


// ******************************************************
// *** Import of test check files
// ******************************************************
function GetNameOfZippedTestChecksFile()
{

   if(isset($_GET['TextDump'])) $TextDump="TextDump";
   else $TextDump="";
   // if not started then select file name to be uploaded
   if(isset($_POST['userfile'])) echo "userfile"."<br>";
   if(!isset($_FILES['userfile']['tmp_name'])) {
      ?>
      <!-- The data encoding type, enctype, MUST be specified as below -->
      <form enctype="multipart/form-data" action="search_ImportClearQuestTextDump.php" method="POST">
          <input id="InsertSite" type="text" name="TextDump" value="<?php echo $TextDump;?>" readonly/>
          <br>
          <!-- MAX_FILE_SIZE must precede the file input field -->
          <input type="hidden" name="MAX_FILE_SIZE" value="60000000" />
          <!-- Name of input element determines name in $_FILES array -->
          Send this file: <input name="userfile" type="file" />
          <br>
          <input type="checkbox" name="DelTextFiles" value="yes">Delete already uploaded text files/dumps<br>
          <input type="submit" name="Upload" value="Upload Thales ClearQuest text dump" />
      </form>
      <?php
   }
}

function UploadZippedTestChecksFileToServer()
// Returns the file name
{
   // http://www.php.net/manual/en/features.file-upload.post-method.php
      
   $uploaddir = ''; //make sure to chmod a=rwx for directory "testchecks";
   $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

   echo '<pre>';
   if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
       echo "File is valid, and was successfully uploaded.\n";
   } else {
       echo "Possible file upload attack!\n";
   }

   echo 'Here is some more debugging info:';
   print_r($_FILES);
   echo "</pre>";
   
   
   
   
   return ($_FILES['userfile']['name']);
}

function ImportZippedTestCheckFile($FileName)
{
   //DropTestCheckCollection(); // should be removed later

   
   $FilePath = "";

   ImportCQTextFile($FilePath,$FileName);
   CopyCollectionCQpcrTopcr();
}
function CopyCollectionCQpcrTopcr()
{
   echo "<hr>";
   echo "<b>Copy collection 'CQpcr' on top of collection 'pcr'.</b><br>";
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'CQpcr');
   $collection2 = new MongoCollection($db, 'pcr');
   $cursor=$collection->find();
   $ManuallyAdded="";
   foreach($cursor as $obj) {
      $id=$obj['id'];
      $cursor2=$collection2->findone(array('id'=>$id));
//      echo "##".$id;
//      print_r($cursor2);
      foreach($obj as $k=>$v) {
         if($k=="_id") continue;  // do not change DB key so new records are avoided. 
         $cursor2[$k]=$v;
         if($k=="id") {
            if(substr($v,0,4)<>"COMP" || strlen($v)<>12) {
               echo '<br><b>Error in import - "Id" is not correct and nothing is updated:</b><br>';
               echo "$k = $v";
               exit;
            }
         }
      }
      //print_r($cursor2);
      $collection2->save($cursor2);
      $ManuallyAdded.=$id." ";
   }
   if(strlen($ManuallyAdded)>4000) $ManuallyAdded="Too many new PCRs to list";
   StoreAdminData("ExtraPCRs",$ManuallyAdded);
   echo "<b>Manualle added pcrs:</b> ".$ManuallyAdded."<br>"; 
}



function ImportCQTextFile($FilePath,$FileName)
// Import the keys and values from an individual test check file.
// Return / build up the FoundReleases array where key is containing the release number
{
   if($_POST['TextDump']=="TextDump") {
     ?>
     <b><a href="select2download.php?db=testdb&collection=CQpcr">*** Download ClearQuest dump as CSV for Excel</a></b><br><br>
     <?PHP
   }

   // Drop already uploaded dump files if requested
   if(isset($_POST["DelTextFiles"])) {
      DropCQpcrCellection();
      echo "<br><b>CQpcr collection is dropped</b><br>";
   }
   
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'CQpcr');
   
   $File=file_get_contents($FilePath.$FileName);

   // convert all "\r\n" to "\n" as Thales files mix up usage
   str_replace("\r\n","\n", $File);
   
   // Get List of All IDs
   // Note that child and parent IDs are equal therefor, COMP numbers
   // must be found as those where two are following eac other i.e. id|id
   $arrList=getocurence($File,"|COMP");
   $arrListOfIDs=array();
   foreach($arrList as $id) {
      $arrListOfIDs[]=substr($File,$id+1,12);
   }
   $arrListOfIDs=array_unique($arrListOfIDs);
//   print_r($arrListOfIDs);
//   echo "<br><br>";
   // remove COMP numbers where they do not exist as double ids i.e. "id|id"
   $arrUniqueIDs=array();
   $arrUniqueIDs[0]="id";
   foreach($arrListOfIDs as $id) {
      if(strpos($File,"$id|$id|") !==false) $arrUniqueIDs[]=$id;
      else continue;
   }
//   print_r($arrUniqueIDs);
//   echo "<br><br>";
     
   $arrFile=array();
   $NumOfIDs=getocurence($File,"|id|");
   $NumOfIDs=sizeof($NumOfIDs)-1;
   
   foreach($arrUniqueIDs as $id) {
      // Find first occurence of ID
      $first=strpos($File,$id."|".$id);
      // Find last occurence of ID
      $arrOccurenceIDs=(getocurence($File,"|$id|"));
      $last=$arrOccurenceIDs[$NumOfIDs]; // note if more fields are imported this must be adjusted
      if($id=="id") $arrFile[]=substr($File,$first,$last-$first)."\r\n";
      else $arrFile[]=substr($File,$first,$last-$first+14)."\r\n";
      
//      echo "<b>".$id."# ".$first.", ".$last." : </b>".substr($File,$first,$last-$first+14)."**##**<br><br>";
   }
   
   //print_r($arrFile);   
   //exit;
   
   // A text file from excel is line seperated by "\r\n" and text on multiple lines with "\n".
//   $arrFile=explode("\r\n",$File);
   
   
   
   $OnlyOnce=true;
   $DocCount=0;  // count number of documents stored into DB
   echo "<hr><b>Keys found in text file:</b><br>";
   foreach($arrFile as $FileLine)
   {
      $id=explode("|",$FileLine,2);  // the first line in file must contain 'id' as first key/valu
      $id=$id[0];  // take out rest after '|'
      
      if(trim($FileLine=="")) continue;
      $doc=array();  // document to be store on mongodb
      // If first row then get Keys (column names) from text file into $arrKeys
      if($OnlyOnce) {
         $arrKeys=array();
         $arrFileLine=explode("|id|",$FileLine);  // |$id| is used as seperator in format
         foreach($arrFileLine as $field) {   
            $field=str_replace(".id","",$field);  // replace e.g. "Children_Pcrs.id" to "Children_Pcrs"
            $field=str_replace(".","_",$field);
            $arrKeys[]=trim($field);  // take out eventual incorrect CR/LF
            echo $field."<br>";
         }
         $arrKeys=array_unique($arrKeys);
         
//         $NumOfKeys=count($arrKeys)-1; // Number of Keys in $arrKeys (last Key is empty)
//         unset($arrKeys[$NumOfKeys]); // remove last key which is empty
         $NumOfKeys=count($arrKeys); // Number of Keys in $arrKeys (last Key is empty)
//         unset($arrKeys[$NumOfKeys]); // remove last key which is empty

         $OnlyOnce=false;
         echo "<hr>";     
      }
      else {         
         $arrFileLine=explode("|$id|",$FileLine); // |$id| is used as seperator in format
         for($t=0;$t<$NumOfKeys;$t++) {
            if($_POST['TextDump']=="TextDump") echo "<br><b>".$arrKeys[$t]."</b>: ".$arrFileLine[$t];
            // Convert french date format
            //echo "####".$arrKeys[$t]."#1$#";
            //echo "####".$arrFileLine[$t]."#2$#";
            if(trim($arrFileLine[$t])<>"" and strpos($arrKeys[$t],"_Date")) {
               $arrFileLine[$t]=ConvertFrenchDateFormatToNormalisedFormat($arrFileLine[$t]);
               if($_POST['TextDump']=="TextDump") echo " => Note! <b>Date converted</b> to: ".$arrFileLine[$t];
            }
            // Store values in proper keys
            $doc[$arrKeys[$t]]=$arrFileLine[$t];
         }
         $collection->insert($doc);
         $DocCount++;
         if($_POST['TextDump']=="TextDump") echo "<br>********************************<br>";
         if($_POST['TextDump']=="TextDump") $_SESSION["query"]=serialize(array());         
      }
   }
   echo "<hr><b>".$DocCount."</b> documents have been inserted into database";
   ?>
   <br>
   <?PHP
   if($_POST['TextDump']=="TextDump") {
     ?>
     <b><a href="select2download.php?db=testdb&collection=CQpcr">*** Download ClearQuest dump as CSV for Excel</a></b>
     <?PHP
   }
}

function getocurence($chaine,$rechercher)
        {
            $lastPos = 0;
            $positions = array();
            while (($lastPos = strpos($chaine, $rechercher, $lastPos))!== false)
            {
                $positions[] = $lastPos;
                $lastPos = $lastPos + strlen($rechercher);
            }
            return $positions;
        }


function ConvertFrenchDateFormatToNormalisedFormat($strDate)
// Convert e.g. '5 avril 2014 13:49:22 GMT+02:00' to '2014-04-05'
{
   $arrDate=explode(" ",$strDate,4);
   $Day=str_pad($arrDate[0],2,"0",STR_PAD_LEFT);
   $Year=$arrDate[2];
   $Month=strtolower($arrDate[1]);
   switch($Month) {
      case "janvier":
         $Month="01";
         break;
      case "février":
         $Month="02";
         break;
      case "mars":
         $Month="03";
         break;
      case "avril":
         $Month="04";
         break;
      case "mai":
         $Month="05";
         break;
      case "juin":
         $Month="06";
         break;
      case "juillet":
         $Month="07";
         break;
      case "août":
         $Month="08";
         break;
      case "septembre":
         $Month="09";
         break;
      case "octobre":
         $Month="10";
         break;
      case "novembre":
         $Month="11";
         break;
      case "décembre":
         $Month="12";
         break;
      }
      return ($Year."-".$Month."-".$Day);
}
// ******************************************************
// *** Database administration - DROP
// ******************************************************
function DropCQpcrCellection()
{
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'CQpcr');
   $response=$collection->drop();
}
?>
