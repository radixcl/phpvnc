<?php
require('./phpvnc.php');

$client = new vncClient();
$auth = $client->auth('localhost', 5900, 'kaka80');
$init = $client->serverInit();
//$img = $client->getRectangle();
//var_dump($img);

//imagepng($img, "lala.png");

$client->streamMjpeg();
