<?php include("login_headerscript.php"); ?>
<?php include("admin_functions.php"); ?>

<?php
$_SESSION["query"]="";  // coming from csv download and could be large and therefore necessary to delete
// MUST BE REMOVED
//echo session_save_path();
//session_destroy();
//exit(0);

$db_name="testdb"; // Database name
$tbl_name="risk_".GetAdminMemberValue('country'); // Collection name

$m = new MongoClient();
$db = $m->$db_name;
$collection = new MongoCollection($db, $tbl_name);
?>

<?php
// Gets a variable from session if available else it returns an empty variable
// Variables are trimmed, session stored with "" if not exisitng
function GetVarFromSession($var)
{
   if(isset($_SESSION[$var])) $var=trim($_SESSION[$var]);
   else {
      $var="";
      $_SESSION[$var]="";
   }
   return $var;
}
?>


<?php
function GetAdminMemberValue($key) {
   $m = new MongoClient();
   $db = $m->admin;
   $collection = new MongoCollection($db, 'members');
   $query = array('user' => $_SESSION["username"]);
   $cursor = $collection->findone($query);
   return ($cursor[$key]);
}
function WriteDropDownTableOfPCRdb($collection, $id)
{

   // Get all key names from irf table
   $cursor = $collection->findOne();
   reset($cursor);
   while (list($user[], $val)=each($cursor));

   // Take out Mongo _id and replace with nothing i.e. no selection.
   for ($i=0; $i<count($user); $i++) {
      if ($user[$i]=="_id") $user[$i]="";
   }

   // sort array so empty string gets to the top
   sort($user);
      
   $dropdowntext='<select id="' . $id . '" name="' . $id . '">';
   foreach ($user as $i) {
      $dropdowntext=$dropdowntext . '<option value="' . $i . '"' . ">$i</option>";
   }
   $dropdowntext=$dropdowntext . '</select>';
   $_SESSION["risk_dropdownfilterkey_$id"]=$dropdowntext;


   // to make default selection the part of selection is replaced with new part inclufing 'selected' within html
   $key=GetVarFromSession("risk_select_".$id);
   $dropdowntext=str_replace('<option value="'.$key.'">'.$key.'</option>',
                             '<option selected value="'.$key.'">'.$key.'</option>',         
                             $dropdowntext );

   echo $dropdowntext;
}
?>

<?php
function WriteDropDownTableOfLogicalOperator($id)
{
  echo '<select id="' . $id . '" name="' . $id . '">';
  ?>
  <option value="and">and</option>
  <option value="or">or</option>
  </select> 
  <?php
}
?>

<?php
function GenerateSelect($name = '', $options = array(), $nameselection) {
// Generate a table based on the array input. 
// Drop down list gets named: $name
// $options: The key of the array
// $value: The values of the array
// $nameselection: If set it is matched all $value and if it matches, it makes this value the default selection
// http://www.kavoir.com/2009/02/php-drop-down-list.html
// if $value starts with '*' it will be the selected in the drop down
   $html = '<select name="'.$name.'">';
   foreach ($options as $option => $value) {
      if ($value==$nameselection) {
         $html .= '<option selected value='.$value.'>'.$option.'</option>';
      }
      else $html .= '<option value='.$value.'>'.$option.'</option>';
   }
   $html .= '</select>';
   return $html;
}
?>

<?php
function WriteDropDownTableOfMatching($id)
{
   
//   $val[$i]=GetVarFromSession("select_".$id);  // selected="selected"

  echo '<select id="' . $id . '" name="' . $id . '">';
  ?>
  <option value="contains">contains</option>
  <option value="equals">=</option>
  <option value="notequal">&lt;&gt;</option>
  <option selected value="gre">&gt;</option>
  <option value="greequ">&gt;=</option>
  <option value="les">&lt;</option>
  <option value="lesequ">&lt;=</option>
  
  </select> 
  <?php
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title>Search Risks</title>
</head>
<body>
<?php include("headerline.html"); ?>
<font size=-2>
<?php
echo "PCR_Safety...version=".GetAdminData("PCR_Safety");
?>
</font>
<hr>
<font size=+1><b>Risk Search form</b></font>
<?php
// If GET method returns 'yes' then clear all forms
if(isset($_GET["clearforms"])) {
   $_SESSION["risksearchnum"]="";
   for($i=0;$i<4;$i++) {
      $_SESSION["risk_select_key".$i]="";
      $_SESSION["risk_select_log".$i]="";
      $_SESSION["risk_select_op".$i]="";
      $_SESSION["risk_select_val".$i]="";
   }
}

$risksearchnum=GetVarFromSession("risksearchnum");
if($risksearchnum=='0') $risksearchnum="";  // necessary as it returns as integer = 0


?>
<form action="risk_list.php" method="post">
<table border="0">
<!-- row 1 -->
<tr>
<td>PCR# (e.g. 7221):</td>
<td><input type="text" name="risksearchnum" value="<?php echo $risksearchnum;?>"/></td>
<td> </td>
<td> </td>
</tr>

</table> 
<br>
<!-- Combined search form -->
<?php
for($i=0;$i<4;$i++) {
   //$key[$i]=GetVarFromSession("select_key".$i);
   $log[$i]=GetVarFromSession("risk_select_log".$i);
   $op[$i]=GetVarFromSession("risk_select_op".$i); 
   $val[$i]=GetVarFromSession("risk_select_val".$i);
}

// $html = GenerateSelect('company', $companies);


// Make table of 4 rows for advanced search query
$operators = array(
//      option => value
	'='        => 'equals',          //   =
	'&lt;&gt;' => 'notequal',        //   <>
	'&gt;'     => 'gre',             //   >
	'&gt;='    => 'greequ',          //   >=
	'&lt;'     => 'les',             //   <
	'&lt;='    => 'lesequ',          //   <=
        'contains'        => 'contains',          //   contains

);

?>

<?php
// Rows of logical drop down
$logic = array(
//      option => value
	'and' => 'and', 
	'or'  => 'or',  
);


echo '<table border="0">';
for($i=0;$i<4;$i++) {
  ?><tr><td><?php  // next row , column 1
  echo WriteDropDownTableOfPCRdb($collection, "key".$i);
  ?></td><td><?php  // column 2 
  $nameselection="";
  foreach ($operators as $option => $value) {
      if ($value == $op[$i]) {
         $nameselection=$value;   // make it default selected in drop down
         break;
      }
  }
  echo GenerateSelect("op".$i, $operators, $nameselection);
  ?></td><td><?php  // column 3 
  echo '<input type="text" name="val'.$i.'" value="'.$val[$i].'"/>';
  ?></td><td><?php  // column 4
  if($i<3) {   // there are only 3 logical operators so avoid the last   
     $nameselection="";
     foreach ($logic as $option => $value) {
         if ($value == $log[$i]) {
            $nameselection=$value;   // make it default selected in drop down
            break;
         }
     }
     echo GenerateSelect("log".$i, $logic, $nameselection);
  }
  ?></td><td><?php  // column 5 
  ?></tr><?php  // end of row
}
echo '</table><br>';
?>
</table> 
<br>


<input type="submit" />
<a href="risk_search.php?clearforms=yes">Clear forms</a>
</form>
<?php
session_write_close();  // remember to close session fast to avoid locks in response
?>
<font size=-1  face="verdana" color="blue">
<pre>
There are two ways to do search. Using the first input fields or the lower 4 fields.
It is not possible to combine those two parts !!!

Top 1: If something is written in a field above, it will take action and any other
fields below will be ignored.

Low 4: If you want to do a combined search then use the lower 4 fields and make
sure nothing is written into the top 5 fields.
</pre>
</font>
</body>
</html> 


