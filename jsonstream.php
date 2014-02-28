<?php
require('./phpvnc.php');
declare(ticks = 1);
ignore_user_abort(false);
ob_implicit_flush(true);

$imgObj = new stdClass();

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

register_shutdown_function('_cleanup');
session_start();
$host = $_SESSION['host'];
$port = $_SESSION['port'];
$passwd = $_SESSION['passwd'];
$socket = $_SESSION['socket'];

$client = new vncClient();
$auth = $client->auth($host, $port, $passwd);

if ($auth === false) {
	$img = errorImage(640, 480, "\n ERROR:\n Could not connect to remote RFB.\n\n " . $client->errstr);
	header('Content-type: image/jpeg');
	$imgObj->error = 1;
	$imgObj->errstr = $client->errstr;
	$imgObj->errno = $client->errno;
	
	echo "event: frame\n";
	echo "data: ";
	echo json_encode($imgObj);
	echo "\n\n";
	
	die();
}

$init = $client->serverInit();
$stat = $client->streamImage('jpeg', $socket, true);

