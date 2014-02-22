<?php

require_once('./config.php');

define('_DEBUG', true);

function debug($str) {
	if (_DEBUG == true)
		error_log($str);
}

function debug_dump($data) {
	if (_DEBUG == true)
		error_log(vncClient::hex_dump($data, "\n", true));
}

class vncClient {
	private $host;
	private $port;
	private $passwd;
	private $fp;
	private $sdata;
	
	public $errno;
	public $errstr;
	public $hostname;
		
	private function dwrite($fp, $data) {
		$i = @fwrite($fp, $data, strlen($data));
		fflush($fp);
		/*if (_DEBUG == true) {
			error_log(sprintf("--- WRITE(%d)\t", $i));
			error_log($this->hex_dump($data, "\n", true));		
		}*/
		return($i);
	}

	private function dread_nonblocking($fp, $len) {
		stream_set_blocking($fp, 0);
		$data = @fread($fp, $len);
		stream_set_blocking($fp, 1);
		/*if (_DEBUG == true) {		
			error_log(sprintf("--- READ(%d)\t", strlen($data)));
			error_log($this->hex_dump($data, "\n", true));
		}*/
		return($data);		
	}
	
	private function dread($fp, $len) {
		$data = @fread($fp, $len);
		/*if (_DEBUG == true) {		
			error_log(sprintf("--- READ(%d)\t", strlen($data)));
			error_log($this->hex_dump($data, "\n", true));
		}*/
		return($data);
	}

	private function fullread($sd, $len) {
		$ret = '';
		$read = 0;
		
		while ($read < $len && ($buf = @fread($sd, $len - $read))) {
		  $read += strlen($buf);
		  $ret .= $buf;
		}
		
		return $ret;
	}

	static public function hex_dump($data, $newline="\n", $buffer=false) {
		
		if ($buffer == true)
			ob_start();
		static $from = '';
		static $to = '';
		
		static $width = 16; // number of bytes per line
		
		static $pad = '.'; // padding for non-visible characters
		
		if ($from==='')
		{
		  for ($i=0; $i<=0xFF; $i++)
		  {
			$from .= chr($i);
			$to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
		  }
		}
		
		$hex = str_split(bin2hex($data), $width*2);
		$chars = str_split(strtr($data, $from, $to), $width);
		
		$offset = 0;
		foreach ($hex as $i => $line)
		{
		  echo sprintf('%6X',$offset).' : '.implode(' ', str_split($line,2)) . '[' . $chars[$i] . ']' . $newline;
		  $offset += $width;
		}
		
		if ($buffer == true) {
			$buf = ob_get_clean();
			return($buf);
		}
		
	}

	
	private function mirrorBits($k) {
		$arr = unpack('c*', $k);
		$ret = '';
		$cnt = count($arr);
		if($cnt > 8){
			$cnt = 8;
		}
	
		for($i=1; $i<=$cnt; $i++){
			$s = $arr[$i];
			$s = (($s >> 1) & 0x55) | (($s << 1) & 0xaa);
			$s = (($s >> 2) & 0x33) | (($s << 2) & 0xcc);
			$s = (($s >> 4) & 0x0f) | (($s << 4) & 0xf0);
			$ret = $ret . chr($s);
		}
		return $ret;
	}

	
	public function auth($host, $port, $passwd) {
		$this->host = $host;
		$this->port = $port;
		$this->passwd = $passwd;

		$this->fp = fsockopen($this->host, $this->port, $this->errno, $this->errstr, 30);
		
		if (!$this->fp) {
			return false;
		}

		// init and version
		$data = $this->dread($this->fp, 12);
		
		$version = substr($data, 4, 7);
		$this->dwrite($this->fp, "RFB 003.003\n");
		
		// auth
		$data = $this->dread($this->fp, 4);
		
		if ($data !== "\00\00\00\02") {
			$this->errstr = 'Unknown RFB auth type';
			return false;
		}
		
		// get auth challenge
		$data = $this->dread($this->fp, 16);
		// send auth pass
		$iv = mcrypt_create_iv(mcrypt_get_iv_size (MCRYPT_DES, MCRYPT_MODE_ECB), MCRYPT_RAND);
		//echo "CHALLENGE!!\n";
		$crypted = mcrypt_encrypt(MCRYPT_DES, $this->mirrorBits($passwd), $data, MCRYPT_MODE_ECB, $iv);
		$this->dwrite($this->fp, $crypted);
		
		// auth result
		$data = $this->dread($this->fp, 4);
		if ($data != "\00\00\00\00") {
			$this->errstr = 'RFB auth error';
			return false;
		}
		// auth OK
		debug("Auth OK");
		return true;
	}
	
	public function serverInit() {
		// ServerInitialistion
		// initial config for client
		$this->dwrite($this->fp, "\01");
		
		$data = $this->dread($this->fp, 24);
		$this->sdata = unpack('n2size/C4flag/n3max/C3shift/x3skip/Nslen', $data);
		//print_r($sdata);
		
		// RAW mode
		$REQ = pack('C2n1N2', 2, 0, 2, 0, 0);
		$this->dwrite($this->fp, $REQ);
		
		// get host name
		$hostname = $this->dread($this->fp, $this->sdata['slen']);
		
		$toReturn = new stdClass();
		$toReturn->hostname = $hostname;
		$toReturn->sdata = $this->sdata;
		debug(__FUNCTION__ . '(); ' . print_r($this->sdata, 1));
		return($toReturn);
	}
	
	public function getRectangle($incremental=0, $oldimg=NULL) {
		//debug(__FUNCTION__ . '(); start');
		// server data
		$width = $this->sdata['size1']; 	// remote screen widht
		$height = $this->sdata['size2'];	// remote screen height
		$BitsPerPixel = $this->sdata['flag1']; 	// bpp
		$Profundidad = $this->sdata['flag2'];	// color depth
		$bigEndianFlag = $this->sdata['flag3'];
		$trueColorFlag = $this->sdata['flag4'];
		$RedMAX = $this->sdata['max1'];
		$GreenMax = $this->sdata['max2'];
		$BlueMAX = $this->sdata['max3'];
		$redshift = $this->sdata['shift1'];
		$greenshift = $this->sdata['shift2'];
		$blueshift = $this->sdata['shift3'];
		$SLEN = $this->sdata['slen'];		
	
		// send FramebufferUpdateRequest
		$REQ = pack('C2n4', 3, $incremental, 0, 0, $width, $height);
		if ($this->dwrite($this->fp, $REQ) === false) return false;
			
		// read first byte, we only proceed if data got is really a rfb update
		if ($incremental == 0)
			$r = $this->dread($this->fp, 1);	// RFB update is 4 bytes long, so we read 1 byte first
		else
			$r = $this->dread_nonblocking($this->fp, 1);

		if ($r === false) return false;	

		// manage non RFB messages
		if ($r == "\01") {	// SetColourMapEntries
			debug(__FUNCTION__ . '(); got SetColourMapEntries');
			// need to read 6 more bytes
			$r .= $this->dread($this->fp, 6);
			// FIXME: we need to do something here to manage this message
			return($oldimg);
		}

		if ($r == "\02") {	// Beep
			debug(__FUNCTION__ . '(); got Beep');
			debug_dump($r);
			return($oldimg);
		}

		if ($r == "\03") {	// Got server cut text
			debug(__FUNCTION__ . '(); got server cut text');
			return($oldimg);
			$r = $this->dread($this->fp, 4);	// text lenght (4 bytes)
			$txtLenght = unpack('l', $r);
			debug(__FUNCTION__ . '(); text lenght: ' . $txtLenght[0]);
			// read text
			$r = $this->dread($this->fp, $txtLenght[0]);
			return($oldimg);
		}
		
		if ($r == "\255") {
			// what the fuck does this message means?
			return($oldimg);
		}

		// check if received data is really a framebuffer update
		if (ord($r) != 0) {
			// not a rfb update (or rfb communication out of sync (?))
			debug(__FUNCTION__ . '(); NOT a rfbupdate (' . ord($r) . ')!!');
			debug_dump($r);
			//die();
			return($oldimg);
		}
		
		
		// read the rest and get FramebufferUpdate
		if ($incremental == 0)
			$r .= $this->dread($this->fp, 3);	// RFB update is 4 bytes long, so we read the rest 3 bytes left
		else
			$r .= $this->dread_nonblocking($this->fp, 3);

		
		if ($r === false) return false;			// WAT
		
		if (strlen($r) == 0 && $oldimg != NULL && $incremental == 1) {
			// no changes on image
			//debug(__FUNCTION__ . '(); no changes. end');
			return($oldimg);
		}

		
		$data = unpack('Cflag/x/ncount', $r);
			
		if ($oldimg == NULL)
			$img = imagecreatetruecolor($width, $height);
		else
			$img = $oldimg;
		
		debug(__FUNCTION__ . '(); total rectangles: ' . $data['count']);
		for ($rects=0; $rects < $data['count']; $rects++) {
			//Obtener la informaci—n rect‡ngulo
			$r = $this->dread($this->fp, 12);
			if ($r === false) return false;
			if (strlen($r) == 0) {
				debug(__FUNCTION__ . '(); rect ' . $rects+1 .  ' got no data!');
				break;
			}
			$rect = unpack('nx/ny/nwidth/nheight/Ntype', $r);
			debug(sprintf('%s(); rect %d-%d info: %dx%dx%d x:%d y:%d', __FUNCTION__, (int)$rects+1, $data['count'], $rect['width'], $rect['height'], $BitsPerPixel, $rect['x'], $rect['y']));
			
			if ($rect['width'] > $width) {
				debug(sprintf('%s(); rect %d width: %d > %d !! ERROR', __FUNCTION__, $rects+1, $rect['width'], $width));
				break;
			}
			if ($rect['height'] > $height) {
				debug(sprintf('%s(); rect %d height: %d > %d !! ERROR', __FUNCTION__, $rects+1, $rect['height'], $height));
				break;
			}
		
			$divisor = 8;
			$readmax = $rect['width'] * $BitsPerPixel / $divisor;
			
			debug(sprintf('%s(); rect %d start drawing', __FUNCTION__, $rects+1));
			for ($i = 0; $i < $rect['height']; $i++) {
				$r = $this->fullread($this->fp, $readmax);
				if ($r === false) return false;	
				$rarr = unpack('C*', $r);
				if (count($rarr) < $readmax) {
					debug("Raw data is not correct. $i\n");
					break;
				}
			 
				time_nanosleep(0, 1000);
				for ($j = 0; $j < $rect['width']; $j++) {
					$offset = $j*4+1;
					//echo "offset: $offset\n";
					$roja = $rarr[$offset + $redshift / $divisor];
					$verde = $rarr[$offset + $greenshift / $divisor];
					$azul = $rarr[$offset + $blueshift / $divisor];
					// fuck! this is _really_ slow!!!!
					$color = imagecolorallocate($img, $roja, $verde, $azul);
					imagesetpixel($img, $rect['x'] + $j, $rect['y'] + $i, $color);
					imagecolordeallocate($img, $color);
				}
			}
			debug(sprintf('%s(); rect %d end drawing', __FUNCTION__, $rects+1));

			
		}
		return($img);	
	}

	public function putSocket($path, $data) {
		$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
		if (!@socket_connect($socket, $path)) {
			debug("socket_connect error");
			return(false);
		}
		$ret = socket_write($socket, $data, strlen($data));
		socket_close($socket);
		return($ret);
		
	}
	
	private function getImage($format, $img) {
		switch($format) {
			case 'png':
				imagepng($img);
				break;
			case 'jpeg':
				imagejpeg($img);
				break;
			case 'gif':
				imagegif($img);
				break;
			default:
				return(false);
		}
		
	}
	
	public function streamImage($format, $shid) {
		global $config;

		ob_implicit_flush(true);
		ob_end_flush();

		//setup shared memory segment
		$segment = shm_attach($config->shm->key, $config->shm->size, $config->shm->permissions);
		debug("SHM: " . $config->shm->key . " " . $config->shm->size . " " . $config->shm->permissions);
		debug("Starting mjpeg stream to " . $this->host);

		set_time_limit(0);
		header("Cache-Control: no-cache");
		header("Cache-Control: private");
		header("Pragma: no-cache");
		header('content-type: multipart/x-mixed-replace; boundary=--phpvncbound');
		echo "Content-type: image/$format\n\n";
		
		$img = $this->getRectangle(0, NULL);	// initial screen
		$this->getImage($format, $img);
		
		echo "--phpvncbound\n";
		echo "Content-type: image/$format\n\n";
		for(;;) {
			$img = $this->getRectangle(1, $img);
			if ($img === false) {
				debug("stream terminated");
				return(false);
			}
			$this->getImage($format, $img);

			echo "--phpvncbound\n";
			echo "Content-type: image/$format\n\n";
			
			// read data from shared memory and send it to RFB
			$shm_data = @shm_get_var($segment, $_SESSION['shid']);
			if (trim($shm_data) != '') {
				debug("shm got: " . $this->hex_dump($shm_data, "\n", 1));
				$this->dwrite($this->fp, $shm_data);
				shm_put_var($segment, $_SESSION['shid'], '');
			}

			time_nanosleep(0, 125000000);
		}
	}
	
	
	public function sendKey($pressed, $key, $spkey) {
		// http://tools.ietf.org/html/rfc6143#section-7.5.4
		
		$REQ = pack('CCScccc', 4, $pressed, 0, 0,0, $spkey, $key);
		debug($this->hex_dump($REQ, "\n", true));
		$this->dwrite($this->fp, $REQ);
	}
}

