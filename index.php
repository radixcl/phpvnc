<?php
require('./phpvnc.php');

$client = new vncClient();
$auth = $client->auth('192.168.1.2', 5901, 'passw0rd');
$init = $client->serverInit();
//$img = $client->getRectangle();
//var_dump($img);

//imagepng($img, "lala.png");

$client->streamMjpeg();
