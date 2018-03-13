<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title>Good Morning Europe</title>
</head>
<body>

<?PHP
if(isset($_POST["submit"])) {
  ?>
  Thanks for your contribution.<br>Sorry to say but nobody really knows as it really depends on when the picture is taken :-) <br>
  Have a nice weekend.<br>
  Lasse
   
  <?PHP
  exit;
}
?>
<b>Goooooood Mooooooorning Europe!</b><br><br>

Due to the new ClearQuest database, but also as a tribute to the European Song Contest (ESC), you now have the posibility to participate in a little quiz. The price might be high, but who knows :-)<br><br>

You just have to answer who the following beautiful legs (from birded ladies) belongs to:<br>
(<b>Hint!</b> 'Roland Schmidt', 'Jørgen Hjorth Pedersen', 'Conchita Wurst'<br><br>

<form action="quiz.php" method="post">
<table border="0">
<tr>
<td><IMG SRC="leg1.jpeg" ALT="some text" ></td>

<td><IMG SRC="leg2.jpeg" ALT="some text" ></td>

<td><IMG SRC="leg3.jpeg" ALT="some text" ></td>
</tr>
<tr>
<td><input type="input" name="leg1" value="Write a name"/></td>
<td><input type="input" name="leg2" value="Write a name"/></td>
<td><input type="input" name="leg3" value="Write a name"/></td>
</tr>
</table><br>

<input type="submit" name="submit" value="Submit"/>
</form>

</body>
</html> 
