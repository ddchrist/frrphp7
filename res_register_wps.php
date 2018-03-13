<?php include("admin_functions.php"); 
include("class_lib.php");
?>
<?php
// NOTE this script has no login requirement in order to facilitate registration

define('_GLOBAL_TABLE', '_Admin_');  // This is used for the Admin collection to store tables that must be global/common for all users. A document is created with key "User" = "_Admin_"

?>


<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<style type="text/css">
a:link{
  color:blue;
}
a:visited{
  color:blue;
}
a:hover{
  color:black; text-decoration: underline;
}
a:focus{
  color:green;
}
a:active{
  color:red;
}

A{text-decoration:none}     <!--  remove underline in links -->
</style>
<title>Register WPs</title>
<?php //include("class_lib.php"); ?>
<script src="scrollfix.js" type="text/javascript"></script>
<script>

function FixWP(wp)
{
   //alert(wp)
   //document.getElementById(wp).innerHTML = "your_text";
   //document.getElementById(wp).style.color = "green";
   document.getElementById(wp).style.backgroundColor="Yellow";
//   document.getElementById(wp).scrollIntoView()
//Element.prototype.documentOffsetTop = function () {
//    return this.offsetTop + ( this.offsetParent ? this.offsetParent.documentOffsetTop() : 0 );
//};
//   top = document.getElementById(wp).documentOffsetTop() - ( window.innerHeight / 2 );
//   window.scrollTo( 0, top );
}
</script>
<script>
// http://stackoverflow.com/questions/2968690/get-windows-username-with-javascript
function GetUser() {
   return Components.classes["@mozilla.org/process/environment;1"].getService(Components.interfaces.nsIEnvironment).get('USERNAME');
}
</script>

<script language="JavaScript">
   // http://cf-click.blogspot.dk/2012/02/get-windows-user-login-account-as.html
function GetUser2() { 
    var objNet = new ActiveXObject("WScript.NetWork");
    var strUserName = objNet.UserName;
    var strDomain = objNet.UserDomain;
    alert(strUserName);
}
</script>

<head>
<body onunload="unloadP('res_register_wbs')" onload="loadP('res_register_wbs')">
</head>
<?php

if (isset($_GET['status'])) {
   // 'status' can be 'accept' or 'reject' or not existing
   // change/set status in database by inserting initials into document
   // "SubjAccepted" which is an array of initials on format e.g. lbn=>1, sol=>1
   $wp=$_GET['wp'];
   $subj=strtoupper(substr($_GET['subject'],0,30));  // max 30 characters
   $subj = preg_replace('/[^(\x20-\x7F)]*/','', $subj); // remove non ascii char
   $tag=$_GET['tag'];
   $_SESSION['username']=$subj;
   $_SESSION["ProjectName"]=$_GET['projectname'];
   $project=$_GET['projectname'];
   $WPViewLevel=$_GET['WPViewLevel'];
   
//   echo $WPViewLevel;
//   exit;
   
   //include("class_lib.php");
   // Get list of all WPs from WPNum and below that must be changed
   $query=unserialize($tag);
   $query2=array('WPNum' => new MongoRegex("/^$wp/"));
   $query=array('$and'=>array($query,$query2));   
   
//   $obj=new db_handling(array('WPNum' => new MongoRegex("/^$wp/")),"");
   $obj=new db_handling($query,"");
   $obj->get_cursor_find_sort();

   $fail=false;
   // Change all found WPs
   foreach($obj->cursor as $o) {
      $wp2=$o['WPNum'];
      $obj2=new db_handling(array('WPNum' => $wp2),"");
      $obj2->get_cursor_findone();
      
      if(SubjectAllocatedAsResource($subj,$obj2)) {
         echo "Work Package ($wp2) <b>failed</b> registration<br>";
         $fail=true;
         continue;
      }
            
      // create $obj2->cursor["SubjAccepted"] if it does not exist
      if(!isset($obj2->cursor["SubjAccepted"])) $obj2->cursor["SubjAccepted"]=array();
      $arrSubjectsWhoAccepted=$obj2->cursor["SubjAccepted"];
      
      // Add subject to list
      if($_GET['status']=="accept") {
         $arrSubjectsWhoAccepted[$subj]="1";   
      }
      else unset($arrSubjectsWhoAccepted[$subj]);
      $obj2->cursor["SubjAccepted"]=$arrSubjectsWhoAccepted;
      $obj2->save_collection();
      echo "Work Package ($wp2) successfully registered<br>";
      //echo "location:res_register_wps.php?wp=$wp&subj=$subj&projectname=$project&tag=$tag";
   }
   if($fail) {
      echo "<b>Warning! Your mission failed or partly failed<br></b>";
      echo "Subject ($subj) is already allocated as a resource. This means that you can no longer change status without contacting an administrator. In principle, you now have to find a replacement.";
      echo "<br>";
      echo '<a href="res_register_wps.php?wp='.$wp.'&subj='.$subj.'&projectname='.$project.'&tag='.htmlentities($tag).'">Back to list</a>';
      exit;   
   }
   header("location:res_register_wps.php?wp=$wp&subj=$subj&projectname=$project&tag=$tag");
   exit;
}

if (isset($_GET['wp']) && !isset($_GET['subj'])) {
   if(isset($_GET['tag'])) $tag=$_GET['tag'];
   else $tag=""; 

   //echo "User:"."<script language=javascript>GetUser()</script>";
   //echo "User:"."<script language=javascript>GetUser2()</script>";
?>
   <form action="res_register_wps.php" method="get">
   <input type="text" name="wp" size="14" value="<?php echo $_GET['wp'];?>" readonly="readonly"/>
   <input type="text" name="projectname" size="14" value="<?php echo $_GET['projectname'];?>" readonly="readonly"/>
   <input type="text" name="tag" size="14" value="<?php echo htmlentities($tag);?>" readonly="readonly"/>
   <br>
   Your initials (only ascii characters are allowed):
   <input type="text" name="subj" size="14" value=""/>
   <input type="submit" name="RegisterSubj" value="Submit"/>
   </form>
<?php
}
// Calls with parameters: wp, subj, projectname, subj, tag
elseif (isset($_GET['wp']) && isset($_GET['subj'])) {
  // Show initials
   $subj=strtoupper(substr($_GET['subj'],0,30));  // max 30 characters
   $subj = preg_replace('/[^(\x20-\x7F)]*/','', $subj); // remove non ascii char
   $_SESSION['username']=$subj;
   if(trim($subj)=="") {
      echo "Error: No name/initials is given!";
      exit;
   }
   echo '<b>My initials</b>: <b>'.$subj."</b>";
   echo '<br><b>Note!</b> Scroll down to find selected Activity, it will be highlighted - yellow.';   
   echo '<br><b>Status:</b><br>&nbsp;&nbsp;<span style="background-color: #88FF88">Green</span> means it has been accepted.<br>&nbsp;&nbsp;<span style="background-color: #FF8888">Red</span> Rejected or not yet decided.<br>&nbsp;&nbsp;Allocated means it is no longer possible to change.<br><b>Time Registration:</b> <a href="psp_elements.html">PSP-info</a><hr>';
   echo "<b>There are 2 views shown with the same content. First view is sorted by date. Second view is sorted by Work Package number</b><hr>";
   echo "<b>Sorted by date</b>";
   ListWPsWithLinks(true,$subj);
   echo "<br><b>Sorted by Work Package number</b>";
   ListWPsWithLinks(false,$subj);
   exit;
}
exit;
function AcceptedResource(&$obj2,$Subj) 
{
//echo $Subj." ";
//if(isset($obj2->cursor["SubjAccepted"])) print_r( $obj2->cursor["SubjAccepted"]);
//echo "<br>";
   if(isset($obj2->cursor["SubjAccepted"])) {
      $arrSubjectsWhoAccepted=$obj2->cursor["SubjAccepted"];
   }
   else $arrSubjectsWhoAccepted=array();
   
   if(isset($arrSubjectsWhoAccepted[$Subj])) return true;
   else return false;   
}

function SubjectAllocatedAsResource($subj,&$obj2)
// Check in Ressource list if Resource is already there and if is it returns true
// otherwise false.
{
   if(isset($obj2->cursor["Ressources"])) {
      foreach($obj2->cursor["Ressources"] as $arrRes) {
         if(isset($arrRes["AllRes"])) {
            if($arrRes["AllRes"]==$subj) return true;
         }
      }
   }
   return false;
}
function ListWPsWithLinks($sorted,$subj)
{
   $_SESSION["ProjectName"]=$_GET['projectname'];
   $project=$_GET['projectname'];
   $wp=$_GET['wp'];
   if($_GET['tag']<>"") $tag=$_GET['tag'];
   else $tag="";

   $query=unserialize($tag);

   // filter out WPs in the past (history)
   $Today=date("Y-m-d");
   $arr=array('EndDate' => array('$gte' => $Today));
   $query=array('$and'=>array($query,$arr));

   $html="";
   $obj=new db_handling($query,"");
   
   if($sorted) $obj->get_cursor_find_sort_array(array("StartDate"=>1,"WPNum"=>1));  // sort by StartDate
   else $obj->get_cursor_find_sort(1,"WPNum");  // sort by WPNum

   $html.='<table border="0">';
   $html.='<tr align="left"><th>WP#</th>';
   if($sorted) $html.='<th>Header</th>';
   $html.='<th>Activity</th><th>WeekDay</th><th>StartDate</th><th>EndDate</th><th>Status</th></tr>';
   
   foreach($obj->cursor as $o) {
      $obj2=new db_handling(array('WPNum' => $o['WPNum']),"");
      $obj2->get_cursor_findone();
            
      $wp2=$obj2->cursor["WPNum"];
      if($sorted) {
         if(WPContainsSubWPs($wp2)) continue;
      }
      $level=substr_count($wp2,".");

      // Style header levels size
      if($sorted) $id=$wp2."sorted";
      else $id=$wp2;
      if($level<=2) $html.='<tr id="'.$id.'" style="font-weight: bold">';
      elseif($level==3) $html.='<tr id="'.$id.'">';
      else $html.='<tr id="'.$id.'" style="font-size:80%">';
       
      // Insert week number in tag
      $Name=$obj2->cursor["WPName"];
      $Name=str_replace("#W#","W".date('W', strtotime($obj2->cursor['StartDate'])),$Name);
      
      // make indent string to align WP numbers
      $IndentString="";
      for($t=0;$t<$level*2;$t++) $IndentString.="&nbsp;";
      
      // Prepare printing format in table row
      $html.='<td>'.$obj2->cursor["WPNum"]."</td>";
            
      if($sorted) {
         $SuperValue=GetSuperHeader($wp2);
         $html.="<td>&nbsp;".$SuperValue."</td>";
         $IndentString="&nbsp;";
         //$IndentString=$SuperValue.":::";
      }
      $html.="<td>".$IndentString.$Name."</td>";
      
      // Show week stats
      $DayOfWeek=date('D', strtotime($obj2->cursor['StartDate']));
      $WeekNum=date('W', strtotime($obj2->cursor['StartDate']));
      $html.="<td>W$WeekNum $DayOfWeek</td>";
      
      $html.="<td>".$obj2->cursor["StartDate"]."</td>";
      $html.="<td>".$obj2->cursor["EndDate"]."</td>";
            
      // List whatever it is allocated, accepted or rejected by subject
      // Check if already allocated
      if(SubjectAllocatedAsResource($subj,$obj2)) $html.='<td style="background-color:#FFFFFF">Allocated</td>';
      else {
         // Check whatever it is on resource (Accepted) list or not (Rejected)
         if(AcceptedResource($obj2, $subj)) $html.='<td style="background-color:#88FF88"><a href="res_register_wps.php?status=reject&wp='.$wp2.'&subject='.$subj.'&projectname='.$project.'&tag='.htmlentities($tag).'&WPViewLevel='.$wp.'">Reject</a></td>';
         else $html.='<td style="background-color:#FF8888"><a href="res_register_wps.php?status=accept&wp='.$wp2.'&subject='.$subj.'&projectname='.$project.'&tag='.htmlentities($tag).'&WPViewLevel='.$wp.'">Accept</a></td>';
      }
      $html.="</tr>";
   }
   $html.="</table>";
   echo $html;

   // scroll to relevant wp
   if($sorted) $id=$wp."sorted";
   else $id=$wp;
   echo "<script language=javascript>FixWP('".$id."')</script>";
}

function WPContainsSubWPs($WPNum) {
   $query=array('WPNum' => new MongoRegex("/^$WPNum/"));  // anything that begins with
   $obj=new db_handling($query,"");
   $obj->get_cursor_find();
   if($obj->cursor->count()>1) return true;
   else return false;
}

function GetSuperHeader($WPNum) {
   $WPNum=GetNextSuperLevel($WPNum);
   $obj=new db_handling(array('WPNum' => $WPNum),"Backup");
   $obj->DataBase="Projects";
   $value=$obj->get_value("WPName");   // return "" if key does not exist in DB
   if(trim($value)!="") return $value;
   return "";    // super level does not exist - return empty
}

?>

