<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<?php
if (isset($_POST['pcrs_to_list'])) {
   $query=array();
   $text=strtoupper($_POST['pcrs_to_list']);
   $text=str_replace("-","_",$text);
   $text=str_replace(".","_",$text);
   $_SESSION['pcrs_to_list']=$text;
   $text=str_replace(" ","\n",$text);  // make to text string seperated by space only
   $text=str_replace("PCR_","",$text);
   $text=str_replace("COOPANS_","\n",$text);
   $text=str_replace("\t","\n",$text);  // make to text string seperated by space only   
   $arraypcrtmp=explode("\n",trim($text));   // put each word into array
   $arraypcr=array();
   foreach ($arraypcrtmp as $i) {   
      $i=trim($i);
      if($i!="") {  // take out empty lines
         if(is_numeric($i)) { 
            $i="COMP".str_pad($i,8,"0",STR_PAD_LEFT); // convert numbers to new CQ ids         
         } 
         // Correct B1, B2 e.g. B2_954 is converted to B2_0954 i.e. adding '0'
         if(substr($i,0,1)=="B") {
            $arr=explode("_",$i);
            $i=$arr[0]."_".str_pad($arr[1],4,"0",STR_PAD_LEFT);
         }
         
         
               
         $arraypcr[]=$i;    // also take out spaces
      }
   }
   
   $m = new Mongo();
   $db = $m->selectDB('testdb');
   $collection = new MongoCollection($db, 'pcr');
   $query=array();
   foreach ($arraypcr as $i) {
      // convert GAIA number to ClearCase number if necessary and possible
      if(substr($i,0,1)=="B") {
         $PCRNum="PCR_COOPANS_".str_replace("B1_","",$i);
         $cursor = $collection->findOne(array('OldPCRNum' => $PCRNum));
         if($cursor<>NULL) {
            if($cursor["id"]<>"") $i=$cursor["id"];
         }
      }

      $query[]=array("id"=>$i);
   }
   $query = array( '$or' => $query);
   $_SESSION["query"]=serialize($query);
   header("location:select2download.php?collection=pcr");
   exit();
}

if(isset($_SESSION['pcrs_to_list'])) $text=$_SESSION['pcrs_to_list'];
else {
   $_SESSION['pcrs_to_list']="";
   $text="";
}
?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title>Search Turning Torso</title>
</head>
<body>
<?php include("headerline.html"); ?>
<font size=+1><b>List PCR numbers with content</b></font><br>

Type PCR numbers into the form and submit.<br>
Use format e.g. COMP00093802 b1_7221 b2_123 PCR_COOPANS_123<br>
PCR_COOPANS_B2_123 or 93802. By not indicating build number,<br>
it is assumed to be ClearQuest numbers and 'COMP' + necessary<br>
'0' are padded in front e.g. '93802' => 'COMP00093802'.<br>
'-', '.' is converted to "_" so you can avoid using the shift key.<br>
If a ClearCase 'id' exist for the a given GAIA number, the<br>
GAIA number is converted into the ClearCase 'id'. Eventually<br>
choose key "OldPCRNum" to see the conversion - later. <br>
After it is submitted, keys from the database<br>
can be selected for import into spreadsheets.<br>
PCRs must be seperated by space or new line.

<form action="admin_print_pcrs.php" method="post">

<textarea name="pcrs_to_list" COLS=80 ROWS=6><?php echo $text; ?></textarea>
<br>
<input type="submit" />
</form>

</body>
</html>

