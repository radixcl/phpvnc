<?php
require('./phpvnc.php');

session_start();
$host = $_SESSION['host'];
$port = $_SESSION['port'];
$passwd = $_SESSION['passwd'];
$socket = $_SESSION['socket'];

$client = new vncClient();
$auth = $client->auth($host, $port, $passwd);
$init = $client->serverInit();
$client->streamMjpeg($socket);
