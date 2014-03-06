<?php
header('Content-type: text/javascript');
session_start();
?>

// global vars...
var mouseMoved = false;
var mouseX = 0;
var mouseY = 0;
var mouseLeft = 0;
var mouseRight = 0;
var mouseMiddle = 0;
var mouseScrollUp = 0;
var mouseScrollDown = 0;
var image = new Image();
var connected = 1;
var es;

var shid = '<?=$_SESSION['shid']?>';


function main() {
	// initial setup
	
	setInterval(function(){
	  // send mouse coordinates to RFB every 1 sec if the mouse got moved
	  if (mouseMoved == true) {
		sendMouseEvent(mouseX, mouseY, mouseLeft, mouseMiddle, mouseRight, mouseScrollUp, mouseScrollDown);
		mouseMoved = false;
	  }
	}, 1000);
	
	
	$('#vncviewer').bind("contextmenu", function () {
	  console.log('no context menu');
	  return false;
	});  
	
	// drag&drop pls...
	$('#vncviewer').on('dragstart', function(event) { event.preventDefault(); });
	
	$('#vncviewer').mousedown(function(e) {
	  console.log('mousedown: ' + e.which);
	  if (e.which == 1) {
		mouseLeft = 1;
	  } else if (e.which == 2) {
		mouseMiddle = 1;
	  } else if (e.which == 3) {
		mouseRight = 1;
	  }
	  sendMouseEvent(mouseX, mouseY, mouseLeft, mouseMiddle, mouseRight, mouseScrollUp, mouseScrollDown);
	});
	
	$('#vncviewer').mouseup(function(e) {
	  console.log('mouseup: ' + e.which);
	  if (e.which == 1) {
		mouseLeft = 0;
	  } else if (e.which == 2) {
		mouseMiddle = 0;
	  } else if (e.which == 3) {
		mouseRight = 0;
	  }
	  sendMouseEvent(mouseX, mouseY, mouseLeft, mouseMiddle, mouseRight, mouseScrollUp, mouseScrollDown);
	  event.preventDefault();
	});
	
	
	$('#vncviewer').mousemove(function(e) {
	  var offset = $(this).offset();
	  var scrollLeft = $(document).scrollLeft();
	  var scrollTop = $(document).scrollTop();
	  
	  mouseMoved = true;
	  mouseX = (e.clientX - offset.left + scrollLeft);
	  mouseY = (e.clientY - offset.top + scrollTop);
	});
}

// console.log and IE
var alertFallback = false;
 if (typeof console === "undefined" || typeof console.log === "undefined") {
   console = {};
   if (alertFallback) {
	   console.log = function(msg) {
			alert(msg);
	   };
   } else {
	   console.log = function() {};
   }
 }

// "asdf".getBytes();
String.prototype.getBytes = function () {
  var bytes = [];
  for (var i = 0; i < this.length; ++i) {
	bytes.push(this.charCodeAt(i));
  }
  return bytes;
};

function hexToArray(str) {
  var a = [];
  for (var i = 0; i < str.length; i += 2) {
	a.push("0x" + str.substr(i, 2));
  }
  return(a);
}

// Convert value as 8-bit unsigned integer to 2 digit hexadecimal number.
function hex8(val) {
  val &= 0xFF;
  var hex = val.toString(16).toUpperCase();
  return ("00" + hex).slice(-2);
}

// Convert value as 16-bit unsigned integer to 4 digit hexadecimal number.
function hex16(val) {
	val &= 0xFFFF;
	var hex = val.toString(16).toUpperCase();
	return ("0000" + hex).slice(-4);
}

// Convert value as 32-bit unsigned integer to 8 digit hexadecimal number.
function hex32(val) {
	val &= 0xFFFFFFFF;
	var hex = val.toString(16).toUpperCase();
	return ("00000000" + hex).slice(-8);
}

function binaryToHex(s) {
  var i, k, part, accum, ret = '';
  for (i = s.length-1; i >= 3; i -= 4) {
	  // extract out in substrings of 4 and convert to hex
	  part = s.substr(i+1-4, 4);
	  accum = 0;
	  for (k = 0; k < 4; k += 1) {
		  if (part[k] !== '0' && part[k] !== '1') {
			  // invalid character
			  return { valid: false };
		  }
		  // compute the length 4 substring
		  accum = accum * 2 + parseInt(part[k], 10);
	  }
	  if (accum >= 10) {
		  // 'A' to 'F'
		  ret = String.fromCharCode(accum - 10 + 'A'.charCodeAt(0)) + ret;
	  } else {
		  // '0' to '9'
		  ret = String(accum) + ret;
	  }
  }
  // remaining characters, i = 0, 1, or 2
  if (i >= 0) {
	  accum = 0;
	  // convert from front
	  for (k = 0; k <= i; k += 1) {
		  if (s[k] !== '0' && s[k] !== '1') {
			  return { valid: false };
		  }
		  accum = accum * 2 + parseInt(s[k], 10);
	  }
	  // 3 bits, value cannot exceed 2^3 - 1 = 7, just convert
	  ret = String(accum) + ret;
  }
  return { valid: true, result: ret };
}

function sendMouseEvent(x, y, lbutton, rbutton, mbutton, sup, sdown) {
  var bytes = [];
  var buttonMask;
  var X;
  var Y;
  buttonMask = parseInt(lbutton + (rbutton*2) + (mbutton*4) + (sup*8) + (sdown*16));
  //buttonMask = parseInt("0x" + binaryToHex( lbutton.toString() + (rbutton*2).toString() + (mbutton*4).toString() + (sup*8).toString() + (sdown*16).toString() + '000').result);
  console.log('buttonMask: 0x' + hex8(buttonMask));
  //bytes = [0x05, buttonMask, 0x00, X, 0x00, Y];
  bytes.push(0x05);
  bytes.push(buttonMask);
  X = hexToArray(hex16(x));
  //console.log("X: " + x);
  bytes.push(parseInt(X[0]));
  bytes.push(parseInt(X[1]));
  Y = hexToArray(hex16(y));
  //console.log("Y: " + y);
  bytes.push(parseInt(Y[0]));
  bytes.push(parseInt(Y[1]));
  //console.log(bytes);
  
  $.post("vncevent.php", JSON.stringify({ shid: shid, op: 'rawmsg', rawdata: bytes }));
}



function keyPress(e, upDown) {
  var keyCode = e.keyCode;
  var spKey = 0;
  var retcode = true;
  var char;
  
  //console.log(e);
  if($.inArray(e.keyCode,[8, 9, 13,16,17,18,19,20,27,35,36,37,38,39,40,91,93,224]) == -1 && e.ctrlKey == false && e.altKey == false) {
	console.log('not a special key');
	return true;
  }
  console.log('keyPress(' + upDown + '); keycode ' + keyCode + ' ('  + hex8(keyCode) + ') ' + e.charCode);
  
  if (keyCode >= 112 && keyCode <= 123) { // F1-F12
	var fBase = 0xbe-1;
	var cBase = 112-1;
	var fCode = cBase - keyCode;
	fCode = fCode * -1;
	console.log('fCode: F' + fCode);
	spKey = 0xff;
	keyCode = fBase + fCode;
	retcode = false;
  }
  // process keymap
  else if (typeof(keyMap[keyCode]) != 'undefined') {
	spKey = keyMap[keyCode][0];
	keyCode = keyMap[keyCode][1];
	retcode = false;
  }
  sendKeyEvent(upDown, spKey, keyCode);
  return retcode;
}
	
function sendKeyEvent(upDown, spKey, keyCode) {
  var bytes;
  bytes = [0x04, upDown, 0x00, 0x00, 0x00, 0x00, spKey, keyCode];
  $.post("vncevent.php", JSON.stringify({ shid: shid, op: 'rawmsg', rawdata: bytes }));	  
}

$(document).keydown(function(e){
  return(keyPress(e, 1));
});

$(document).keyup(function(e){
  return(keyPress(e, 0));
});

$(document).keypress(function(e){
  console.log("keypress: " + e.charCode);
  var char = e.charCode;
  sendKeyEvent(1, 0, char);
  e.preventDefault();
});
	
function ctrlAltDel() {
  var bytes = [0x04, 0x01, 0x00, 0x00, 0x00, 0x00, 0xff, 0xe3,	// ctrl alt del sequence
			   0x04, 0x01, 0x00, 0x00, 0x00, 0x00, 0xff, 0xe9,
			   0x04, 0x01, 0x00, 0x00, 0x00, 0x00, 0xff, 0xff,
			   0x04, 0x00, 0x00, 0x00, 0x00, 0x00, 0xff, 0xff,
			   0x04, 0x00, 0x00, 0x00, 0x00, 0x00, 0xff, 0xe9,
			   0x04, 0x00, 0x00, 0x00, 0x00, 0x00, 0xff, 0xe3];
  $.post("vncevent.php", JSON.stringify({ shid: shid, op: 'rawmsg', rawdata: bytes }));
}

function setupEventSource() {
  es = new EventSource('jsonstream.php');  
  es.addEventListener("frame", function(e) {
	var canvas = $('#vncviewer');
	var ctx = canvas[0].getContext("2d");
	//console.log("got frame!");
	var obj = JSON.parse(e.data);
	//$('#vncviewer').attr('src', 'data:image/jpeg;base64,' + obj.image);
	if (obj.width != canvas.width()) {
	  //canvas.width(obj.width);
	  canvas[0].width = obj.width;
	}
	if (obj.height != canvas.height()) {
	  //canvas.height(obj.height);
	  canvas[0].height = obj.height;
	}
	image.src = "data:image/jpeg;base64," + obj.image;
	image.onload = function() {
	  ctx.drawImage(image, 0, 0, obj.width, obj.height);
	};
  });

  es.addEventListener("error", function(e) {
	var obj = JSON.parse(e.data);
	if (obj.error == 'errauth') {
	  es.close();
	  connected = 0;
	}
	if (obj.error == 'disconnected') {
	  reconnect();
	  return;
	}
	alert("Error: " + obj.errstr);
  });
}

function reconnect() {
  es.close();
  es = null;
  setupEventSource();
}

$(document).ready(function(){
  setupEventSource();
  main();
});
