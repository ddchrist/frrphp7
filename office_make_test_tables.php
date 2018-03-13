<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<html>
<head>
<title>Create table of testers</title>
</head>
<body>
<?php include("headerline.html"); ?>
<?php

if(isset($_POST["initialstolist"])) {
   $arr_init=GetCleanArrayOfInitials();
   $_SESSION["initials"]=$arr_init;


//print_r($arr_init);
   exit;
}

$text="";
?>
<form action="office_download_initials.php" method="post">

<textarea name="initialstolist" COLS=80 ROWS=10><?php echo $text; ?></textarea>
<br>
<font size=-1>db=admin, collection=InitialMappings</font>
<br>
<input type="submit" name="MapInitials" value="Map Initials" /> Decryption Key:<input type="password" name="key"  id="key">
<br>
<input type="submit" name="ElNina" value="El Nina" />
</form>

</body>
</html>

<?php
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

