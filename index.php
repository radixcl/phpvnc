<!DOCTYPE html>
<html>
	<head>
		<title>PHPVNC client</title>
	</head>
	<body>
		<h1>PHPvnc client</h1>
		<form method="post" action="setsession.php">
			Host: <input name="host" type="text" value="localhost"><br>
			Port: <input name="port" type="text" value="5900"><br>
			Username: <input name="username" type="text"> (if needed by RFB)<br>
			Password: <input name="passwd" type="password"><br>
			<input type="submit">
		</form>
	</body>
</html>
