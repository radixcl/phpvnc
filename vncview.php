<?php
session_start();

$host = $_SESSION['host'];
$port = $_SESSION['port'];
$passwd = $_SESSION['passwd'];
$socket = $_SESSION['socket'];
$sesid = $_COOKIE['PHPSESSID'];

?>
<html>
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>

<div id="vnccontainer">
  <img id="vncviewer" src="vncimg.php" />
</div>
</html>

<script type="text/javascript">
		
	function keyPress(e, upDown) {
	  var keyCode = e.keyCode;
	  var spKey = 0;
	  var retcode = true;
	  
	  console.log('keyPress(' + upDown + '); keycode ' + keyCode);
	  
	  // specials
	  if (keyCode == 8) { // backspace
		spKey = 0xff;
		retcode = false;
	  }
	  
	  if (keyCode == 13) { // enter
		spKey = 0xff;
		retcode = false;
	  }
	  	  
	  if (keyCode == 16) {
		// left shift
		spKey = 0xff;
		keyCode = 0xe1;
		retcode = false;
	  }
	  
	  if (keyCode == 17) {
		//control
		spKey = 0xff;
		keyCode = 0xe3;
		retcode = false;
	  }
	  
	  var bytes;
	  bytes = [0x04, upDown, 0x00, 0x00, 0x00, 0x00, spKey, keyCode];
	  $.post("vncevent.php", JSON.stringify({ session: '<?=$sesid?>', op: 'rawmsg', rawdata: bytes }));
	  return(retcode);
	}
	
	$(document).keydown(function(e){
	  return(keyPress(e, 1));
	});

	$(document).keyup(function(e){
	  return(keyPress(e, 0));
	});
	
</script>