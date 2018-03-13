<?php include("login_headerscript.php"); ?>
<?php include("class_lib.php"); ?>
<?php
// Convert the MasterChecklist to something usefull

// input
$obj=new db_handling(array('WPNum' => new MongoRegex("/^./")),"Backup");
$obj->DataBase="Checklist";
$obj->CollectionName="MASTER2";

// drop collection
$obj2=new db_handling(array('WPNum' => "any"),"Backup");
$obj2->DataBase="Checklist";
$obj2->CollectionName="MASTER";
$obj2->drop_collection();

$obj->get_cursor_find();
$oldh1="";
$oldh2="";
$oldh3="";

foreach($obj->cursor as $o) {
   $TCNum=trim($o["WPNum"]);
   $h1=substr($TCNum,0,2);  // xx.yy.zz
   $h2=substr($TCNum,3,2);  // xx.yy.zz
   $h3=substr($TCNum,6,2);  // xx.yy.zz
   $He1=$o["H1"];
   $He2=$o["H2"];
   $He3=$o["H3"];
   $Note=$o["Note"];

   // output
   $obj2=new db_handling(array('WPNum' => $TCNum),"Backup");
   $obj2->DataBase="Checklist";
   $obj2->CollectionName="MASTER";
//echo $h1." #".$oldh1."<br>";
   if($h1<>$oldh1) {
      $obj2->get_cursor_findone();
      $obj2->cursor["WPNum"]=$h1;
      $obj2->cursor["WPName"]=trim($He1);
      $obj2->cursor["Show"]="1";
      $obj2->cursor["Initiator"]="NoName";
      $obj2->insert($obj2->cursor);
      $oldh1=$h1;
   }
   if($h2<>$oldh2) {
      $obj2->get_cursor_findone();
      $obj2->cursor["WPNum"]=$h1.".".$h2;
      $obj2->cursor["WPName"]=trim($He2);
      $obj2->cursor["Show"]="1";
      $obj2->cursor["Initiator"]="NoName";
      $obj2->insert($obj2->cursor);
      $oldh2=$h2;
   }
   if($h3<>$oldh3) {
      $obj2->get_cursor_findone();
      $obj2->cursor["WPNum"]=$h1.".".$h2.".".$h3;
      $obj2->cursor["WPName"]=trim($He3);
      $obj2->cursor["Description"]="";
      $obj2->cursor["Note"]=trim($Note);
      $obj2->cursor["Show"]="1";
      $obj2->cursor["Initiator"]="NoName";
      if(strtoupper($o["Naviair"])=="YES") $obj2->cursor["EKDK"]="T";  // Naviair
      else $obj2->cursor["EKDK"]="F";
      if(strtoupper($o["ACG"])=="YES") $obj2->cursor["LOWW"]="T";  // ACG
      else $obj2->cursor["LOWW"]="F";
      if(strtoupper($o["IAA"])=="YES") $obj2->cursor["EIDW"]="T";  // Dublin
      else $obj2->cursor["EIDW"]="F";
      if(strtoupper($o["IAA"])=="YES") $obj2->cursor["EISN"]="T";  // Shannon
      else $obj2->cursor["EISN"]="F";
      $obj2->cursor["LDZO"]="T";  // Croatia
      if(strtoupper($o["LFV MM"])=="YES") $obj2->cursor["ESMM"]="T";  // Malmö
      else $obj2->cursor["ESMM"]="F";
      if(strtoupper($o["LFV MM"])=="YES") $obj2->cursor["ESOS"]="T";  // Stockholm
      else $obj2->cursor["ESOS"]="F";
      $obj2->cursor["Roles"]=$o["Role"];

      $obj2->insert($obj2->cursor);
      $oldh3=$h3;
   }

//echo $TCNum." ".$He1." ".$He2." ".$He3."<br>";
}
?>

