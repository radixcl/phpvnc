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
	var _ctrl = 0;
	var _shift = 0;
	
	/*$(document).keypress(function(e) {
		var keyCode = e.charCode;
		var charTyped = String.fromCharCode(keyCode);
		console.log('keypress(); ' + keyCode + " : " + charTyped);
		
		if (keyCode === 3) {
			// ctrl C
			$.post( "vncevent.php", JSON.stringify({ op: "ctrlkey", code: 0x63, spkey: 0x00 }) );
			return false;
		}
		
		$.post( "vncevent.php", JSON.stringify({ op: "keypress", pressed: 1, code: keyCode, spkey: 0x00 }) );
		$.post( "vncevent.php", JSON.stringify({ op: "keypress", pressed: 0, code: keyCode, spkey: 0x00 }) );
	});*/
	
	$(document).keydown(function(e){
		var keyCode = e.keyCode;
		console.log('keydown(); keycode ' + keyCode);
		var bytes;
		bytes = [0x04, 0x01, 0x00, 0x00, 0x00, 0x00, 0x00, keyCode];
		$.post("vncevent.php", JSON.stringify({ op: 'rawmsg', socket: '<?=$socket?>', rawdata: bytes }));
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