<?php

define('_DEBUG', false);

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
		$i = fwrite($fp, $data, strlen($data));
		fflush($fp);
		if (_DEBUG == true) {
			printf("--- WRITE(%d)\t", $i);
			$this->hex_dump($data);		
		}
		return($i);
	}

	private function dread($fp, $len) {
		$data = fread($fp, $len);
		if (_DEBUG == true) {		
			printf("--- READ(%d)\t", strlen($data));
			$this->hex_dump($data);
		}
		return($data);
	}

	private function fullread($sd, $len) {
		// hay q mejorar esta mierda... consume mucha cpu
		$ret = '';
		$read = 0;
		
		while ($read < $len && ($buf = fread($sd, $len - $read))) {
		  $read += strlen($buf);
		  $ret .= $buf;
		}
		
		return $ret;
	}

	public function hex_dump($data, $newline="\n", $buffer=false) {
		
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
		return($toReturn);
	}
	
	public function getRectangle() {
		// server data
		$width = $this->sdata['size1']; 
		$height = $this->sdata['size2'];
		$BitsPerPixel = $this->sdata['flag1']; 
		$Profundidad = $this->sdata['flag2'];
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
		$REQ = pack('C2n4', 3, 0, 0, 0, $width, $height);
		$this->dwrite($this->fp, $REQ);
		
		// get FramebufferUpdate
		$r = $this->dread($this->fp, 4);		
		$data = unpack('Cflag/x/ncount', $r);
	
		//echo "flag: $data[flag]\n";
		//echo "rectangles: $data[count]\n";
		
		$img = imagecreatetruecolor($width, $height);
		
		for ($rects=0; $rects < $data['count']; $rects++) {
			//Obtener la informaci—n rect‡ngulo
			$r = $this->dread($this->fp, 12);
			$rect = unpack('nx/ny/nwidth/nheight/Ntype', $r);
			//echo "RECT $rects:\n";
			//print_r($rect);
		
			$divisor = 8;
			$readmax = $rect['width'] * $BitsPerPixel / $divisor;
			//echo "BPP: $BitsPerPixel\n";
			//echo "readmax: $readmax\n";
		
			for ($i = 0; $i < $rect['height']; $i++) {
				$r = $this->fullread($this->fp, $readmax);
				$rarr = unpack('C*', $r);
				//echo "count rarr: " . count($rarr) . ":$readmax\n";
				//echo "$i:$j\n";
				if (count($rarr) < $readmax) {
					//echo("Raw data is not correct. $i\n");
					break;
				}
			 
				for ($j = 0; $j < $rect['width']; $j++) {
					$offset = $j*4+1;
					//echo "offset: $offset\n";
					$roja = $rarr[$offset + $redshift / $divisor];
					$verde = $rarr[$offset + $greenshift / $divisor];
					$azul = $rarr[$offset + $blueshift / $divisor];
					$color = imagecolorallocate($img, $roja, $verde, $azul);
					//echo "draw " . ($rect['x'] + $J) . "x" . ($rect['y'] + $i) . " ";
					//echo ".";
					if (imagesetpixel($img, $rect['x'] + $j, $rect['y'] + $i, $color) == false) {
						die("Draw color failed.");
					}			 
				}
			}
			
		}
		return($img);	
	}

	public function putSocket($path, $data) {
		$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
		if (!@socket_connect($socket, $path)) {
			echo "socket_connect error";
			return(false);
		}
		$ret = socket_write($socket, $data, strlen($data));
		socket_close($socket);
		return($ret);
		
	}
	public function streamMjpeg($socknam) {
		// setup socket for receiving commands
		@unlink($socknam);	// delete if already exists

		$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
		if (!socket_bind($socket, $socknam)) {
			echo 'error socket_bind';
			@unlink($socknam);
			die();
		}
		
		if (!socket_listen($socket)) {
			echo 'error socket_listen';
			@unlink($socknam);
			die();
		}
		
		if (!socket_set_nonblock($socket)) {
			echo 'error stream_set_blocking';
			@unlink($socknam);
			die();
		}


		set_time_limit(0);
		header("Cache-Control: no-cache");
		header("Cache-Control: private");
		header("Pragma: no-cache");
		header('content-type: multipart/x-mixed-replace; boundary=--phpvncbound');
		for(;;) {
			$__then = microtime();
			$img = $this->getRectangle();
			ob_start();
			imagejpeg($img);
			imagedestroy($img);
			echo ob_get_clean(); 
			$__now = microtime();
			error_log(sprintf("Elapsed:  %f", $__now-$__then));

			//time_nanosleep(0, 250000000);
			echo "--phpvncbound\n";
			echo "Content-type: image/jpeg\n\n";
			
			// check ipc socket for incomming commands
			if(($newc = @socket_accept($socket)) !== false) {	// got new cmd
				$ipc_in_buf = @socket_read($newc, 1024, PHP_BINARY_READ);
				error_log("Got socket cmd");
				error_log($this->hex_dump($ipc_in_buf, "\n", 1));
				// write incoming command to rfb
				$this->dwrite($this->fp, $ipc_in_buf);
				socket_close($newc);
				unset($newc);
			}
		}
	}
	
	public function sendKey($pressed, $key, $spkey) {
		// http://tools.ietf.org/html/rfc6143#section-7.5.4
		
		$REQ = pack('CCScccc', 4, $pressed, 0, 0,0, $spkey, $key);
		error_log($this->hex_dump($REQ, "\n", true));
		$this->dwrite($this->fp, $REQ);
	}
}

