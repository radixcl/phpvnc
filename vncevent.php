<?php
$rawdata = file_get_contents('php://input');

require('./phpvnc.php');



//$client->sendKey($key, 0);
//$client->sendKey(64, 0);



$data = json_decode($rawdata, true);

//error_log("data: " . $rawdata);

switch($data['op']) {
	case 'rawmsg':
		$rawdata = $data['rawdata'];
		$sid = $data['session'];
		$socket = '/tmp/vnc_' . $sid;
		$raw = implode($rawdata);
		//error_log("got: $raw");
		$client = new vncClient();
		//$auth = $client->auth($host, $port, $passwd);
		//$init = $client->serverInit();

		$bytes = '';
		foreach($rawdata as $byte) {
			$bytes .= chr(intval($byte));
			//error_log("byte: " . $byte);
		}

		error_log("put socket dump $socket: " . $client->hex_dump($bytes, "\n", 1));
		$client->putSocket($socket, $bytes);
		break;
}
