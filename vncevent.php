<?php
$rawdata = file_get_contents('php://input');

require('./phpvnc.php');

$host = $_COOKIE['host'];
$port = $_COOKIE['port'];
$passwd = $_COOKIE['passwd'];

$client = new vncClient();
$auth = $client->auth($host, $port, $passwd);
$init = $client->serverInit();

//$client->sendKey($key, 0);
//$client->sendKey(64, 0);



$data = json_decode($rawdata, true);

//error_log("data: " . $rawdata);

switch($data['op']) {
	case 'keypress':
		$client->sendKey($data['pressed'], $data['code'], $data['spkey']);
		break;
	case 'ctrlkey':
		$client->sendKey(1, 0xe3, 0xff); // control
		$client->sendKey(1, $data['code'], $data['spkey']); // +key
		$client->sendKey(0, $data['code'], $data['spkey']); // -key
		$client->sendKey(0, 0xe3, 0xff); // -control
		break;
	case 'rawmsg':
		$socket = $data['socket'];
		$rawdata = $data['rawdata'];
		$raw = implode($rawdata);
		//error_log("got: $raw");

		$bytes = '';
		foreach($rawdata as $byte) {
			$bytes .= chr(intval($byte));
			//error_log("byte: " . $byte);
		}

		error_log("put socket dump: " . $client->hex_dump($bytes, "\n", 1));
		$client->putSocket($socket, $bytes);
		break;
}
