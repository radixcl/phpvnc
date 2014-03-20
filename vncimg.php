<?php
require('./phpvnc.php');
declare(ticks = 1);
ignore_user_abort(false);
ob_implicit_flush(true);

pcntl_signal(SIGTERM, "sig_handler");
function sig_handler($signo) {
	if ($signo == SIGTERM)
		_cleanup();
		die();
}

function _cleanup() {
	global $config;
	$host = $_SESSION['host'];
	debug("mjpeg stream to $host terminated, cleaning up.");
	//$segment = shm_attach($config->shm->key, $config->shm->size, $config->shm->permissions);
	//shm_remove($segment);
}

function errorImage($width, $height, $text) {
	// taken from http://cl1.php.net/manual/es/function.imagestring.php#90481
	// Set font size
	$font_size = 5;
	
	$ts=explode("\n",$text);
	/*$width=0;
	foreach ($ts as $k=>$string) { //compute width
		$width=max($width,strlen($string));
	}
	
	// Create image width dependant on width of the string
	$width  = imagefontwidth($font_size)*$width;
	// Set height to that of the font
	$height = imagefontheight($font_size)*count($ts);*/
	$el=imagefontheight($font_size);
	$em=imagefontwidth($font_size);
	// Create the image pallette
	$img = imagecreatetruecolor($width,$height);
	// Dark red background
	$bg = imagecolorallocate($img, 0xAA, 0x00, 0x00);
	imagefilledrectangle($img, 0, 0,$width ,$height , $bg);
	// White font color
	$color = imagecolorallocate($img, 255, 255, 255);
	
	foreach ($ts as $k=>$string) {
		// Length of the string
		$len = strlen($string);
		// Y-coordinate of character, X changes, Y is static
		$ypos = 0;
		// Loop through the string
		for($i=0;$i<$len;$i++){
			// Position of the character horizontally
			$xpos = $i * $em;
			$ypos = $k * $el;
			// Draw character
			imagechar($img, $font_size, $xpos, $ypos, $string, $color);
			// Remove character from string
			$string = substr($string, 1);      
		}
	}
	return($img);
}

register_shutdown_function('_cleanup');
session_start();
$host = $_SESSION['host'];
$port = $_SESSION['port'];
$passwd = $_SESSION['passwd'];
$socket = $_SESSION['socket'];
$tls = $_SESSION['tls'];

$client = new vncClient();
$auth = $client->auth($host, $port, $passwd, $tls);

if ($auth === false) {
	$img = errorImage(640, 480, "\n ERROR:\n Could not connect to remote RFB.\n\n " . $client->errstr);
	header('Content-type: image/jpeg');
	imagejpeg($img);
	die();
}

$init = $client->serverInit();
$stat = $client->streamImage('jpeg', $socket);

