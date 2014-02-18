<?php
require('./phpvnc.php');

$host = $_COOKIE['host'];
$port = $_COOKIE['port'];
$passwd = $_COOKIE['passwd'];
$socket = $_GET['socket'];

$client = new vncClient();
$auth = $client->auth($host, $port, $passwd);
$init = $client->serverInit();
$client->streamMjpeg($socket);
