<?php

$host = $_COOKIE['host'];
$port = $_COOKIE['port'];
$passwd = $_COOKIE['passwd'];
$socket = '/tmp/vnc.sock';

?>
<html>
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>

<div id="vnccontainer">
  <img id="vncviewer" src="vncimg.php?socket=<?=urlencode($socket)?>" />
  <!--img id="vncviewer" src="lala.png" /-->
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

	  if (keyCode == 16) {
		// right shift
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
	  $.post("vncevent.php", JSON.stringify({ op: 'rawmsg', socket: '<?=$socket?>', rawdata: bytes }));
	  return(retcode);
	}
	
	$(document).keydown(function(e){
	  return(keyPress(e, 1));
	});

	$(document).keyup(function(e){
	  return(keyPress(e, 0));
	});
	
	/*$(document).keydown(function(e){
		// keydown gets called before keypress
		var keyCode = e.keyCode;
		console.log('keydown(); ' + keyCode);
		// special keys
		if (keyCode === 8) { // backspace
			$.post( "vncevent.php", JSON.stringify({ op: "keypress", pressed: 1, code: keyCode, spkey: 0xff }) );			
			return false;	// prevent back on browser
		} else if (keyCode === 17) { // ctrl
			$.post( "vncevent.php", JSON.stringify({ op: "keypress", pressed: 1, code: 0xe3, spkey: 0xff }) );
			_ctrl = 1;
			return false;
		} else if (keyCode === 16) { // shift
			$.post( "vncevent.php", JSON.stringify({ op: "keypress", pressed: 1, code: 0xe1, spkey: 0xff }) );
			_shift = 1;
			return false;
		}

	});*/

	/*$(document).keyup(function(e){
		var keyCode = e.keyCode;
		console.log('keyup(); ' + keyCode);
		if (keyCode === 17) { // ctrl
			$.post( "vncevent.php", JSON.stringify({ op: "keypress", pressed: 0, code: 0xe3, spkey: 0xff }) );
			_ctrl = 0;
			return false;
		}  else if (keyCode === 16) { // shift
			$.post( "vncevent.php", JSON.stringify({ op: "keypress", pressed: 0, code: 0xe1, spkey: 0xff }) );
			_shift = 1;
			return false;
		}

	});*/

</script>