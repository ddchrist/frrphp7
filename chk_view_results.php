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

<!-- Script is used to insert dropdown values into a html input form or other id.. -->
<script>
function InsertDropDown(fetchID,insertID) {
    //alert(fetchID)
    //alert(document.getElementById("leave").value);
    document.getElementById(insertID).value = document.getElementById(fetchID).value 

}
</script>


</head>
<body onunload="unloadP('chk_manage_wbs')" onload="loadP('chk_manage_wbs')">
<?php include("chk_headerline.html"); ?>

<?php
// ******************************************************
// *** POST/GET Control logic
// ******************************************************

if(isset($_GET['ImportTestChecks'])) {
   GetNameOfZippedTestChecksFile();
}
elseif (isset($_POST['Upload'])) {
   $FileName=UploadZippedTestChecksFileToServer();
   echo "Importing zip'ed file: ".$FileName;
   ImportZippedTestCheckFile($FileName);
   echo "Imported zip'ed file: ".$FileName;
   ?>
   <br><b><a href="chk_view_results.php?ImportTestChecks">Upload another zip'ed TestCheck file</a></b>
   <?PHP
   exit;
}
elseif (isset($_POST['ViewStatus'])) 
{
   $arrInputFields=array("Release","Site","ANSP","TC","NameRole","Date","TestedBy");
   if(isset($_POST["Mismatch"])) StoreAdminOnUser("ChkViewResults_Mismatch","checked");
   else StoreAdminOnUser("ChkViewResults_Mismatch","");
   StoreAdminOnUser("ChkViewResults_MasterCLFilter",trim($_POST["MasterCLFilter"]));
   
   $query=array();
   foreach($arrInputFields as $InputField)
   {
      $search=trim($_POST[$InputField]);
      StoreAdminOnUser("ChkViewResults_".$InputField,$_POST[$InputField]);
      
      //echo "<br>"."ChkViewResults_".$InputField."####".$_POST[$InputField];
      
      if($search<>"") {
         $search=$_POST[$InputField];
         // make query matching from beginning of string when "TC"
         if($InputField=="TC") $query[]=array($InputField => new MongoRegex("/^$search/i"));
         else $query[]=array($InputField => new MongoRegex("/$search/i"));      
      }   
   }
   if($query<>NULL) $query=array('$and' => $query); // make sure search is global if nothing is selected
   
   //print_r($query);

   ShowHtmlInputForms();
   $_SESSION["query"]=serialize($query);
   ViewStat($query,$_POST['Site']);
   exit;

}
else ShowHtmlInputForms();
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
function ShowHtmlInputForms()
{
   ?>

   <!-- ************* Form Input ************** -->
   <?php
   //$Release=$Site=$Country=$TestCase=$Role=$Date=$Tester="";
   $Release=GetAdminOnUser("ChkViewResults_"."Release");
   $Site=GetAdminOnUser("ChkViewResults_"."Site");
   $Country=GetAdminOnUser("ChkViewResults_"."ANSP");
   $TestCase=GetAdminOnUser("ChkViewResults_"."TC");
   $Role=GetAdminOnUser("ChkViewResults_"."NameRole");
   $Date=GetAdminOnUser("ChkViewResults_"."Date");
   $Tester=GetAdminOnUser("ChkViewResults_"."TestedBy");
   $Mismatch=GetAdminOnUser("ChkViewResults_"."Mismatch");
   $MasterCLFilter=GetAdminOnUser("ChkViewResults_"."MasterCLFilter");
   $arrAllKeysInTestCheck=GetAdminOnUser("ChkViewResults_AllKeysInTestCheck");
   ?>
   <form action="chk_view_results.php" method="post">
   <table border="0">
   <tr>

   <td style="color:#C00000">
   <span title="Release number or part of it e.g. 'B2_4_14' or 'B2_5'. Will show from release number and below.">Release:</span>
   </td>
   
   <td><input type="text" name="Release" value="<?php echo $Release;?>" /></td>
   <td>Site:</td>
   <td>
   <input id="InsertSite" type="text" name="Site" value="<?php echo $Site;?>"/><-<select id="FetchSite" onchange="InsertDropDown('FetchSite','InsertSite')">
   <?php 
   $arr=array("","LOWW", "EKDK", "EIDW", "EISN", "LDZO", "ESMM", "ESOS");
   foreach ($arr as $a) echo '<option value="'.$a.'">'.$a.'</option>';
   ?>
   </select>
   If site is selected, only TCs with site selected in MASTER is shown! 
   </td>

   </tr>
   <tr>
   <td>TestCase#:</td>
   <td><input type="text" name="TC" value="<?php echo $TestCase;?>" /></td>
   <td>ANSP:</td>
   <td>
   <input id="InsertANSP" type="text" name="ANSP" value="<?php echo $Country;?>"/><-<select id="FetchANSP" onchange="InsertDropDown('FetchANSP','InsertANSP')">
   <?php 
   $arr=array("","NAVIAIR", "LFV", "ACG", "CCL", "LDZO", "IAA");
   foreach ($arr as $a) echo '<option value="'.$a.'">'.$a.'</option>';
   ?>
   </select>  
   </td>

   </tr>
   <tr>
   <td style="color:#C00000">
   <span title="Role of tester doing the TestChecks e.g. 'TWR', 'ACC' or 'APP'. For more roles simultaniously, use regular expressions e.g. 'ACC|APP'">Role:</span>
   </td>
   <td><input type="text" name="NameRole" value="<?php echo $Role;?>" /></td>
   <td>Date:</td>
   <td><input type="text" name="Date" value="<?php echo $Date;?>" /></td>
   </tr>
   <tr>
   <td style="color:#C00000">
   <span title="Initials of tester doing the TestChecks e.g. 'STE' or 'SME'. For more testers simultaniously, use regular expressions e.g. 'STE|CME'. As regular expressions match anything containing, it can be a great idea to lock match to beginning of the string by setting '^' in front e.g. '^ste'.">Tester:</span>
   
   </td>
   <td><input type="text" name="TestedBy" value="<?php echo $Tester;?>" /></td>
   <td>
   </td>
   <td style="color:#C00000">
   <a href="select2download.php?db=Checklist&collection=TestChecks">Download Test Checks as CSV for Excel</a>
   <span title="If downloaded, the whole database of test checks is submitted. If you only one parts of it, make a query first and then Download.">info</span>
   </td>
   </tr>
   </table>
   <hr>
   <table border="0"><tr>
   <td style="color:#C00000">
   <span title="MasterCL Filter: (Master CheckList Filter) selects the part of the MASTER checklist that must be shown after query is submitted i.e. 'Search'. Regular expressions can be used so e.g. '^01|^02' will show all test cases from the master checklist starting with numbers '01' or '02'. Left empty everything is listed. Limiting the search will significantly speedup the server processing.">MasterCL Filter:</span>
   <input type="text" name="MasterCLFilter" value="<?php echo $MasterCLFilter;?>"/>
   </td>

   <td style="color:#C00000"><input type="checkbox" name="Mismatch" value="checked" <?php echo $Mismatch;?>/>
   <span title="In the days/months after a checklist is executed, there is a risk that the Master Checklist would have changed i.e. new numbering, deleted test cases etc. If that is the case, the numbering could fail to match between the Master Checklist and the registered test checks. Activating this checkmark, will list all such mismatch, before the usual table of status is shown. A method to avoid this is in the planning, but still not implemented">Show mismatch between MasterCheckList and TestChecks.</span>
   </td>
   </tr>
   </table>
   
   <input type="submit" name="ViewStatus" value="Search" />
   <font size="-1">If non of the above criterias are used, the whole database is analysed.</font>
   <?PHP
/*   $AllKeysInTestCheck=GetKeysFromFirstTestCheckInDB();
   echo "<br>Show keys: ";
   foreach($AllKeysInTestCheck as $Key) {
      // Take out keys that should NOT be selected i.e. always shown keys
      if($Key=="_id" or $Key=="TC" or $Key=="Status" or $Key=="H1" or $Key=="H2") continue;
      echo '<input type="checkbox" name="KeysToShow[]" value="'.$Key.'" />'.$Key;  
   } */
   ?>
   </form>
   <hr>
   <?PHP
   $_SESSION["query"]=serialize(array());
}

// ******************************************************
// *** Import of test check files
// ******************************************************
function GetNameOfZippedTestChecksFile()
{

   switch($_SESSION["country"]) {
      case "dk":
         $DefaultSite="EKDK"; 
         $DefaultANSP="NAVIAIR";
         break;
      case "se":
         $DefaultSite="ESMM"; 
         $DefaultANSP="LFV";
         break;
      case "ie":
         $DefaultSite="EISN"; 
         $DefaultANSP="IAA";
         break;
      case "hr":
         $DefaultSite="LDZO"; 
         $DefaultANSP="CCL";
         break;
      case "at":
         $DefaultSite="LOWW"; 
         $DefaultANSP="ACG";
         break;                       
   }

   // if not started then select file name to be uploaded
   if(isset($_POST['userfile'])) echo "userfile"."<br>";
   if(!isset($_FILES['userfile']['tmp_name'])) {
      ?>
      <!-- The data encoding type, enctype, MUST be specified as below -->
      <form enctype="multipart/form-data" action="chk_view_results.php" method="POST">
          <!-- MAX_FILE_SIZE must precede the file input field -->
          <input type="hidden" name="MAX_FILE_SIZE" value="60000000" />
          <!-- Name of input element determines name in $_FILES array -->
          Send this file: <input name="userfile" type="file" />
          <br>
          Truncate 'Release' Name e.g. 'B2_4_14_P3' (if empty name is taken from TestCheck files)<input type="text" name="TestCheckRelease" value="" />
          <br>
          Truncate 'Site' name:
          <?php echo GenerateSelect("TestCheckSite",array(""=>"","LOWW"=>"LOWW","EKDK"=>"EKDK","EIDW"=>"EIDW","EISN"=>"EISN","LDZO"=>"LDZO","ESMM"=>"ESMM","ESOS"=>"ESOS"),$DefaultSite);?>
          <br>
          Truncate 'ANSP' name:
          <?php echo GenerateSelect("TestCheckANSP",array(""=>"","NAVIAIR"=>"NAVIAIR","LFV"=>"LFV","ACG"=>"ACG","CCL"=>"CCL","IAA"=>"IAA"),$DefaultANSP);?>
          <br>
          <input type="submit" name="Upload" value="Upload Zip'ed TestChecks" />
      </form>
      <?php
   }
}

function UploadZippedTestChecksFileToServer()
// Returns the file name
{
   // http://www.php.net/manual/en/features.file-upload.post-method.php
   
   $uploaddir = 'testchecks/'; //make sure to chmod a=rwx for directory "testchecks";
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

   
   $PathZipFile = "testchecks/$FileName";
   $PathUnzippedFiles = 'testchecks/tmp/';

   $OnlyOnce=true;
   $zip = new ZipArchive;
   if ($zip->open($PathZipFile) === true) {
      // extracts one file at a time
      $FoundReleases=array(); // array containing release numbers found in files as key
      for($i = 0; $i < $zip->numFiles; $i++) {                        
         $zip->extractTo($PathUnzippedFiles, array($zip->getNameIndex($i)));
         // remember to set directory tmp with chmod a=rwx tmp
                          
           // here you can run a custom function for the particular extracted file
         ImportTestCheck($PathUnzippedFiles,$zip->getNameIndex($i),$FoundReleases);
         unlink($PathUnzippedFiles.$zip->getNameIndex($i)); // delete unzipped file
      }
      
      
      $zip->close();
        
      // Handle information on found releases 
      echo "<br>Releases found in the TestCheck files: <b>";
      foreach ($FoundReleases as $Release=>$v) {
        echo $Release.", ";
      }
      ?></b><br>
      Use the following link to correct release number if it is not in format e.g. 'B2_4_14_P1': 
      <a href="chk_admin.php?CorrReleaseNo">LINK Correct Release number</a><br>
      <?PHP                  
   }
   else 
   {
      echo "File not found";
      exit;
   }
}
function GetKeysFromFirstTestCheckInDB()
// Currently NOT used
// return the keys of one testcheck e.g. TC, H1, H2, H3 .... in an array
{
   $m = new MongoClient();
   $db = $m->selectDB('Checklist');
   $collection = new MongoCollection($db, 'TestChecks');
   $doc=$collection->find();
   foreach($doc as $o)
   {
      foreach($o as $k=>$v) $AllKeysInTestCheck[]=$k;
      break;
   }
   return $AllKeysInTestCheck;      
}

function ImportTestCheck($FilePath,$FileName,&$FoundReleases)
// Import the keys and values from an individual test check file.
// Return / build up the FoundReleases array where key is containing the release number
{
   $m = new MongoClient();
   $db = $m->selectDB('Checklist');
   $collection = new MongoCollection($db, 'TestChecks');
   
   
   $File=file_get_contents($FilePath.$FileName);
   // A text file from excel is line seperated by "\r\n" and text on multiple lines with "\n".
   $arrFile=explode("\r\n",$File);
   $doc=array();
   foreach($arrFile as $FileLine)
   {
      if(trim($FileLine)=="") continue;  // skip empty lines
      $arrKeyVal=explode(":",$FileLine,2); // important to limit to 2 otherwise more than 2 arrays could be returned if more ':' present.
      // remove "#" from keys to assure compatibility with MongoDB
      $Key=str_replace("#","",$arrKeyVal[0]);

      $Val=iconv("ISO-8859-1","UTF-8//IGNORE",$arrKeyVal[1]);
      $Val=str_replace("\n","<br>",$Val); // convert to html line breaks
      
      // Change name of key SoftwareVersion to Release
      if($Key=="SoftwareVersion") $Key="Release";  // change name of key software version to release
      // Correct some SW versions which was incorrectly type in and also remove COOPANS from SW version name
      if($Key=="Release") {
         if(trim($_POST["TestCheckRelease"])=="") {
            $Val=str_replace("COOPANS_P","COOPANS_B",$Val); 
            $Val=str_replace("COOPANS_","",$Val);
            $Val=str_replace("NAVTEST_","",$Val);
         }
         else $Val=trim(strtoupper($_POST["TestCheckRelease"]));  // truncate release if requested 
         $FoundReleases[$Val]=""; 
      }
      
      // Correct / reverse date format
      if($Key=="Date") {
         $Val=explode("-",$Val);
         $Val=array_reverse($Val);
         $Val=implode($Val,"-");
      }
      
      $doc[$Key]=$Val;
      //echo $arrKeyVal[0]." ".$arrKeyVal[1]."<br>";
   }
   //add additional data
   if(!isset($doc["Site"])) {
      if($_POST["TestCheckSite"]=="") {
         echo "<br><b>Warning:</b> No 'Site' name is indicated or found in file. Please redo!";
         exit;
      }
      else $doc["Site"]=$_POST["TestCheckSite"];  
   }
   if(!isset($doc["ANSP"])) {
      if($_POST["TestCheckANSP"]=="") {
         echo "<br><b>Warning:</b> No 'ANSP' name is indicated or found in file. Please redo!";
         exit;
      }
      else $doc["ANSP"]=$_POST["TestCheckANSP"];  
   }
   $doc["Uploaded"]=date("Y-m-d H:i:s");
   $collection->insert($doc);

   return $arrKeyVal;
}


// ******************************************************
// *** View search in DB as HTML / View Status of tests
// ******************************************************
function ViewStat($query,$Site)
{

   $arrListOfSWVersions=GetListOfSWVersions($query);
   if($arrListOfSWVersions==NULL) {
      echo "<br><b>Warning:</b> No records found by given search query";
      return;
   }

   $arrStatus=array();
   $arrTCNumbers=array();
   
   GetListOfAllTCNumsFromMasterCheckList($arrTCNumbers);
   GetStatus($arrStatus,$arrTCNumbers,$query);
   
   echo '<br><b><font size="+2">TestChecks matched to the Master CheckList:</b>';

   echo '<table border="1">';
   // Table Headers start ***
   echo "<tr><th>TC#</th>";
   echo "<th>Activity</th>";
   foreach ($arrListOfSWVersions as $t) echo "<th>".$t."</th>";
   echo "<th>Acc Status</th>";
   echo "<th>Roles</th>";
   // Insert checkbox selected keys as headers
   $arrKeysToShow=array();
   if(isset($_POST['KeysToShow'])) {
      $arrKeysToShow=$_POST['KeysToShow'];
   }
   echo "</tr>";
   // Table headers end ***
   
   $m = new MongoClient();
   $db = $m->selectDB('Checklist');
   $collection = new MongoCollection($db, 'MASTER');
      
   foreach ($arrTCNumbers as $TC)
   {  
      // if not site is selected then only list headings
      if(!SiteSelected($TC,$Site)) {  
        if(substr_count($TC,".")==2) {
           continue;
        }
      }
      
      echo "<tr>";
      echo "<td>";
      echo $TC;
      echo "</td>";
      InsertHeaders($TC);
      $TotalStatusOfAllBuilds="";
      foreach ($arrListOfSWVersions as $t)
      {
         echo "<td>";
         if(isset($arrStatus[$t][$TC])) {
            echo $arrStatus[$t][$TC];
            $TotalStatusOfAllBuilds.=" ".$arrStatus[$t][$TC];
         }
         // insert checkbox selected keys after status         
         else echo "-";
         echo "</td>";
      }
      if(strpos($TotalStatusOfAllBuilds,"FA")) echo '<td style="background-color:#FF0000">';
      elseif($TotalStatusOfAllBuilds<>"") echo '<td style="background-color:#00FF00">';
      else echo "<td>"; 
      if(trim($TotalStatusOfAllBuilds)<>"") echo trim($TotalStatusOfAllBuilds);
      else echo "-";     
      echo "</td>";
      
      $doc=$collection->findone(array("WPNum"=>$TC));
      if(isset($doc["Roles"]) && trim($doc["Roles"])<>"") echo "<td>".trim($doc["Roles"])."</td>";
      else echo "<td>-</td>";
      echo "</tr>";
   }
   echo "</table>";
}

function GetListOfSWVersions($query)
// returns a list of found SW versions in all collections
{
   $m = new MongoClient();
   $db = $m->selectDB('Checklist');
   $collection = new MongoCollection($db, 'TestChecks');
   $count=$collection->count($query);
   if($count==0) return NULL; // return on empty query
   $doc=$collection->find($query);   
   
   
   foreach($doc as $o) {
      $arrSWVersions[$o["Release"]]="";
   }
   $arrListOfSWVersions=array();
   foreach($arrSWVersions as $k=>$v)
   {
      $arrListOfSWVersions[]=$k;
   
   }
   arsort($arrListOfSWVersions);
   echo "Found <b>".count($arrListOfSWVersions)."</b> releases, ";
   return $arrListOfSWVersions;
}

function GetStatus(&$arrStatus,&$arrTCNumbers,$query)
// returns:
// $arrStatus: double array containing status of TCs
// $arrTCNumbers: array containing all possible TCs numbers from master checklist
//                if found TC number from TestChecks does not exist in master checklist
//                a warning is given
{
   $m = new MongoClient();
   $db = $m->selectDB('Checklist');
   $collection = new MongoCollection($db, 'TestChecks');
   
   $count=$collection->count($query);
   if($count==0) return false; // return on empty query
   else echo "<b>$count</b> TestChecks.";
   
   if($collection->count($query)==0) return false; // return on empty query
   $doc=$collection->find($query)->sort(array("TC"=>1)); 
   $Mismatch=GetAdminOnUser("ChkViewResults_"."Mismatch");

   if($Mismatch<>"") {  // only show mismatch between Master and Testchecks if selected 
      echo '<table border="1">';
      echo "<br><b>Warning:</b> The following test cases are not found in the master checklist. This is likely due to renumbering or other structureing of the checklist";
   }
   foreach($doc as $o) {
      if(isset($arrStatus[$o["Release"]][$o["TC"]])) {
         $arrStatus[$o["Release"]][$o["TC"]].=" ".$o["Status"];
      }
      else {
         $arrStatus[$o["Release"]][$o["TC"]]=$o["Status"];
      }
      if($Mismatch<>"") {
         if(!in_array($o["TC"],$arrTCNumbers)) {
            echo '<tr>';
            echo "<td>".$o["TC"]."</td>";
            echo '<td><b><font size="+1">'.$o["H1"]."</b></font>";
            echo ' - <b>'.$o["H2"]."</b></td>";
            echo '<td>'.$o["H3"]."</td>";
            echo "</tr>";
         }
      }
   }
   if($Mismatch<>"") echo "</table>"; 

}
function GetListOfAllTCNumsFromMasterCheckList(&$arrAllTCNumbers)
// Gets all TC numbers from Checklist->MASTER (The master checklist)
{
   $m = new MongoClient();
   $db = $m->selectDB('Checklist');
   $collection = new MongoCollection($db, 'MASTER');
   $MasterCLFilter=GetAdminOnUser("ChkViewResults_"."MasterCLFilter");
   if($MasterCLFilter=="") $query=array();
   else $query=array("WPNum" => new MongoRegex("/^$MasterCLFilter/"));   
   $doc=$collection->find($query)->sort(array("WPNum"=>1));
   foreach($doc as $o) {      
      $arrAllTCNumbers[]=$o["WPNum"];
   }
   echo "<b>".count($arrAllTCNumbers)."</b> Master Test Cases and ";
}

function SiteSelected($TC,$Site)
{
   if(trim($Site)=="") return true;
   $m = new Mongoclient();
   $db = $m->selectDB('Checklist');
   $collection = new MongoCollection($db, 'MASTER');
   $doc=$collection->findone(array("WPNum"=>$TC));
   if(isset($doc[$Site]) && $doc[$Site]=="T") return true;
   else return false;
}


function InsertHeaders($TC)
{
   static $collection = NULL;
   if($collection==NULL) {  // only initialise mongo at first call
      $m = new MongoClient();
      $db = $m->selectDB('Checklist');
      $collection = new MongoCollection($db, 'MASTER');
   }
   
   $o=$collection->findone(array("WPNum"=>$TC));
   $HeaderLevel=substr_count($TC,".")+1;  // 1 = H1, 2=H2, 3=H3
   
   //echo $TC." ".$HeaderLevel."###";
   if($HeaderLevel==1) echo '<td><b><font size="+1">'.$o["WPName"]."</b></font></td>";
   if($HeaderLevel==2) echo '<td style="padding-left: 20"><b>'.$o["WPName"]."</b></td>";
   if($HeaderLevel==3) echo '<td style="padding-left: 40">'.$o["WPName"]."</td>";

}
// ******************************************************
// *** Database administration - DROP
// ******************************************************
function DropTestCheckCollection()
// Currently NOT used
{
   $m = new MongoClient();
   $db = $m->selectDB('Checklist');
   $collection = new MongoCollection($db, 'TestChecks');
   $response=$collection->drop();
}
?>
