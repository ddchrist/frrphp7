<?php include("login_headerscript.php");
require 'vendor/autoload.php';//composer require "mongodb/mongodb=^1.0.0.0"
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<title>Add New User</title>
</head>
<body>
<?php include("headerline.html"); ?>

<?php
$myusername=GetVarFromSession('username');
$db_name="admin"; // Database name
$tbl_name="members"; // Collection name
// Connect to server and select databse.
$m = new MongoDB\Client();
$db = $m->$db_name;
$collection = $db->$tbl_name;

//$m = new Mongo();
//$db = $m->selectDB($db_name);
//$collection = new MongoCollection($db, $tbl_name);
$query=array('user' => $myusername);
$cursor = $collection->findone($query);
$country=$cursor['country'];
?>

<?php
$errorflag=0;
if (isset($_POST['AdminInsertUser'])) {
   // Sanity check if user name - no check for special characters
   if($_POST['AdminCountry']=="dk") {
      if(strlen($_POST['AdminUser'])!=3) {
         $errorflag=ErrorMessage('Users from dk must be 3 letter initials e.g. "ste"');
      }
   }
   elseif(strlen($_POST['AdminUser'])==3) {
      $errorflag=ErrorMessage('Users can not be 3 letters - reserved Naviair!');
   }
   if($_POST['AdminUser']=="") {
      $errorflag=ErrorMessage('Username missing!');
   }
   if($_POST['AdminPassword']=="") {
      $errorflag=ErrorMessage('Password missing!');
   }
   if(strlen($_POST['AdminPassword'])<=8) {
      $errorflag=ErrorMessage('Password must at be at least 9 characters. Use lower and upper case characters as well as numbers. Please combine to make it more safe!');
   }
   if($_POST['AdminFirstName']=="") {
      $errorflag=ErrorMessage('First Name missing!');
   }
   if($_POST['AdminLastName']=="") {
      $errorflag=ErrorMessage('Last Name missing!');
   }
   // Check if username already exist 
   $query=array('user' => strtolower($_POST['AdminUser']));
   $cursor = $collection->findone($query);
   if($cursor['user']) {
      if($myusername!=$cursor['user']) {  // if login user is same as form user then overwrite user data with new information
         $errorflag=ErrorMessage('Username already exist. Find another username!');
      }
   }

   // Store Record in DB
   if($errorflag==0) {
      $cursor['user']=strtolower($_POST['AdminUser']);
      $cursor['password']=$_POST['AdminPassword'];
      $cursor['firstname']=$_POST['AdminFirstName'];
      $cursor['lastname']=$_POST['AdminLastName'];
      $cursor['country']=$_POST['AdminCountry'];
      $cursor['role_group']=$_POST['AdminRole'];
      //$collection->save($cursor);
	  $inResult = $collection->insertOne(
        ['user' => $cursor['user'],
		'password' => $cursor['password'],
		'firstname' => $cursor['firstname'],
		'lastname' => $cursor['lastname'],
		'country' => $cursor['country'],
		'role_group' => $cursor['role_group']]
      );
      echo '<font color="green">User updated!</font><br>';
   }
}

?>
<font size=+1><b>Add New User</b></font>
<form action="admin_adduser.php" method="post">
<table border="0">
<!-- row 1 -->
<tr>
<td>Username:</td>
<td><input type="text" name="AdminUser" size="16" value=""/></td>
</tr>
<tr>
<td>Password:</td>
<td><input type="text" name="AdminPassword" size="16" value=""/></td>
</tr>
<tr>
<td>First Name:</td>
<td><input type="text" name="AdminFirstName" size="16" value=""/></td>
</tr>
<tr>
<td>Last Name:</td>
<td><input type="text" name="AdminLastName" size="16" value=""/></td>
</tr>
<tr>
<td>Country:</td>
<td><?php echo GenerateSelect("AdminCountry",array("dk","hr","ie","at","se"),$country); ?></td>
</tr>
<tr>
<td>Role:</td>
<td><?php echo GenerateSelect("AdminRole",array("user","admin","hazard","hazardapprove"),$country); ?></td>
</tr>
</table>
<input type="submit" name="AdminInsertUser" value="Add User"/>
<br>
WARNING! Password must be minimum 9 characters.<br>Administrator can get and view any password!

</form>
</body>
</html>

<?php
function GenerateSelect($name = '', $options = array(), $nameselection) {
// Generate a table based on the array input. 
// Drop down list gets named: $name
// $options: The key of the array
// $value: The values of the array
// $nameselection: If set it is matched all $value and if it matches, it makes this value the default selection
// http://www.kavoir.com/2009/02/php-drop-down-list.html
   $html = '<select name="'.$name.'">';
   foreach ($options as $option => $value) {
      if ($value==$nameselection) {
         $html .= '<option selected value='.$value.'>'.$value.'</option>';
      }
      else $html .= '<option value='.$value.'>'.$value.'</option>';
   }
   $html .= '</select>';
   return $html;
}
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
// Gets a variable from session if available else it returns an empty variable
// Variables are trimmed, session stored with "" if not exisitng
function ErrorMessage($text)
{
   echo '<font color="red">Error: '.$text.' - Record not stored!</font><br>';
   return 1;
}
?>
