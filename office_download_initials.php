<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>
<?php


if(isset($_POST["MapInitials"])) {
   MapInitials();
   exit;
}
elseif(isset($_POST["ElNina"])) {
   ElNina();
   exit;
}
else {
  echo "something is wrong. Illegal entry to script!";
  exit;
}


function ElNina() {
   $arr_initials=GetCleanArrayOfInitials();

   $key=$_POST["key"];  // decryption key

   $db_name="admin"; // Database name
   $tbl_name="InitialMappings"; // Collection name

   $username=$_SESSION['username'];
   $CSVdelimiter=GetAdminData($username."_csvdelimitor");
   if($CSVdelimiter=="") $CSVdelimiter=",";

   header("Content-Type: text/csv; charset=UTF-8");
   header("Content-Disposition: attachment;Filename=ElNina.csv");

   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);

   $outstr="";
   // headerline: Navn E-mail-adresse Firma Mobiltelefon
   $outstr=$outstr . '"'."Navn".'"'.$CSVdelimiter;
   $outstr=$outstr . '"'."E-mail-adresse".'"'.$CSVdelimiter;
   $outstr=$outstr . '"'."Firma".'"'.$CSVdelimiter;
   $outstr=$outstr . '"'."Mobiltelefon".'"'.$CSVdelimiter;
   $outstr=substr($outstr,0,-strlen($CSVdelimiter))."\r\n";   // take out last comma in string and add newline. \n must be within double quotes

   foreach($arr_initials as $o) {
      //$query = array('Subject' => $o);
      $query=array('Subject' => new MongoRegex("/$o/i"));
      $cursor = $collection->findOne($query);
      if($cursor <> null) {
         $firstname=decrypt($cursor["*Fornavn"], $key);
         $familyname=decrypt($cursor["*Efternavn"], $key);
         $email=$cursor["Subject"]."@naviair.dk";
         $mobil="22222222";
         $company="Naviair";
         $name=$firstname." ".$familyname;
      }
      else {
         $email=$o."@naviair.dk";
         $mobil="22222222";
         $company="Naviair";
         $name="*";   
      }
   
      $outstr=$outstr . '"'.$name.'"'.$CSVdelimiter;
      $outstr=$outstr . '"'.$email.'"'.$CSVdelimiter;
      $outstr=$outstr . '"'.$company.'"'.$CSVdelimiter;
      $outstr=$outstr . '"'.$mobil.'"'.$CSVdelimiter;
      $outstr=substr($outstr,0,-strlen($CSVdelimiter))."\r\n";   // take out last comma in string and add newline. \n must be within double quotes
   }
   // convert to encoding Excel can handle
   echo iconv("UTF-8", "ISO-8859-1//TRANSLIT",$outstr);
}

function MapInitials() {
   $arr_initials=GetCleanArrayOfInitials();

   $key=$_POST["key"];  // decryption key

   $db_name="admin"; // Database name
   $tbl_name="InitialMappings"; // Collection name

   $username=$_SESSION['username'];
   $CSVdelimiter=GetAdminData($username."_csvdelimitor");
   if($CSVdelimiter=="") $CSVdelimiter=",";

   header("Content-Type: text/csv; charset=UTF-8");
   header("Content-Disposition: attachment;Filename=InitialMappings.csv");

   $m = new Mongo();
   $db = $m->selectDB($db_name);
   $collection = new MongoCollection($db, $tbl_name);

   $outstr="";

   foreach($arr_initials as $o) {
      //$query = array('Subject' => $o);
      $query=array('Subject' => new MongoRegex("/$o/i"));
      $cursor = $collection->findOne($query);
      if($cursor <> null) {
         $firstname=decrypt($cursor["*Fornavn"], $key);
         $familyname=decrypt($cursor["*Efternavn"], $key);
         $subject=$cursor["Subject"];
         $role=decrypt($cursor["*Omkostningssted"], $key);
         $company="Naviair";
         $name=$firstname." ".$familyname;
      }
      else {
         $subject=$o;
         $role="*";
         $company="Naviair";
         $name="*";  
      }
      $outstr=$outstr . '"'.$name.'"'.$CSVdelimiter;
      $outstr=$outstr . '"'.$subject.'"'.$CSVdelimiter;
      $outstr=$outstr . '"'.$role.'"'.$CSVdelimiter;
      $outstr=$outstr . '"'.$company.'"'.$CSVdelimiter;
      $outstr=substr($outstr,0,-strlen($CSVdelimiter))."\r\n";   // take out last comma in string and add newline. \n must be within double quotes
   }
   // convert to encoding Excel can handle
   echo iconv("UTF-8", "ISO-8859-1//TRANSLIT",$outstr);
}

function decrypt($str, $key)
{   
    $str=base64_decode($str);
    $str = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_ECB);
    // For some reson mcrypt adds a lot of char(0) to the string which must be trimmed
    return trim($str);
}

function GetCleanArrayOfInitials() {
// returns an array of initials filtered for exccess characters
// Also doublicates are removed

//   $tmp=str_split($_POST["initialstolist"]);
//   foreach ($tmp as $o) {
//     echo(ord($o))."<br>";
//   }

   // The following convert the initials into an ordered array
   // by removing special characters and additional spaces

   $str=$_POST["initialstolist"];
   // remove tabs i.e. 09 to space " "
   $str=str_replace(chr(9)," ",$str);
   // remove codes due to window (\r\n)
   $str=str_replace(chr(13),"",$str);
   // convert codes due to linux (\n) and window
   $str=str_replace(chr(10)," ",$str);
   // convert semi colon to space
   $str=str_replace(";"," ",$str);
   // convert comma to space
   $str=str_replace(","," ",$str);  
   // convert punctuation to space
   $str=str_replace("."," ",$str);

//   echo "<br>";
//   $tmp=str_split($str);
//   foreach ($tmp as $o) {
//     echo(ord($o))."<br>";
//   }
   
   // remove extra spaces if available
   $str=explode(" ",$str);
   $result=array();
   foreach($str as $o) {
      if($o<>"") $result[]=$o;
   }
//   print_r($result);
   return array_unique($result);
}

?>




