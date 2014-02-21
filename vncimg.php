<?php
require('./phpvnc.php');
declare(ticks = 1);
ignore_user_abort(false);

pcntl_signal(SIGTERM, "sig_handler");
function sig_handler($signo) {
	if ($signo == SIGTERM)
		_cleanup();
		die();
}

register_shutdown_function('_cleanup');
session_start();
$host = $_SESSION['host'];
$port = $_SESSION['port'];
$passwd = $_SESSION['passwd'];
$socket = $_SESSION['socket'];

$client = new vncClient();
$auth = $client->auth($host, $port, $passwd);
$init = $client->serverInit();
$stat = $client->streamMjpeg($socket);

function _cleanup() {
	global $config;
	$host = $_SESSION['host'];
	debug("mjpeg stream to $host terminated, cleaning up.");
	$segment = shm_attach($config->shm->key, $config->shm->size, $config->shm->permissions);
	shm_remove($segment);
}
