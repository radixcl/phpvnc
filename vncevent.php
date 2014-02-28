<?php
$rawdata = file_get_contents('php://input');

require('./phpvnc.php');

$data = json_decode($rawdata, true);

//error_log("data: " . $rawdata);

switch($data['op']) {
	case 'rawmsg':
		$rawdata = $data['rawdata'];
		$shid = $data['shid'];
		$raw = implode($rawdata);
		//error_log("got: $raw");
		$client = new vncClient();
		//$auth = $client->auth($host, $port, $passwd);
		//$init = $client->serverInit();

		// check for existing shared memory segment
		@$shid_test = shmop_open($config->shm->key, "a", 0666, 0);
		if (empty($shid_test)) {
			// shm does not exists
			debug("main(); shared memory segment does not exists, stream disconnected?");
			break;
		}
		unset($shid_test);

		$bytes = '';
		foreach($rawdata as $byte) {
			$bytes .= chr(intval($byte));
			//error_log("byte: " . $byte);
		}

		debug("put shmem dump: " . $client->hex_dump($bytes, "\n", 1));
		//$client->putSocket($socket, $bytes);
		

		$segment = shm_attach($config->shm->key, $config->shm->size, $config->shm->permissions);
		$shm_data = @shm_get_var($segment, $shid);
		if (shm_put_var($segment, $shid, $shm_data . $bytes) === false) {
			debug('main(); Could not write to shared memory.');
		}
		break;
}
