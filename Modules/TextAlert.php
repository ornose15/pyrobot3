<?php

/*
*		Text nickalerts to my phone.
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class TextAlert_m72 extends PB2 {
	var $toggle;
	function __construct() {
		parent::PB2();
		$this->toggle = false;
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'tatog') {
			if($this->toggle == true) {
				$this->toggle = false;
				$this->send("PRIVMSG $channel :TextAlert off");
			} else if($this->toggle == false) {
				$this->toggle = true;
				$this->send("PRIVMSG $channel :TextAlert on");
			}
			return 1;
		}
		if($this->toggle == true) {
			$arr = array_keys(PB2::$aStatic['aHosts'], "pyrokid@pyrokid.com");
			if(stripos($text, $arr[0]) !== false) {
				mail('0000000000@txt.att.net',
				'Nickalert at ' . $channel,
				'(' . $nick . ') ' . $text,
				'From: p@b2.com' . "\r\n" . 'Reply-To: p@b2.com' . "\r\n" . 'X-Mailer: PHP/' . phpversion());
			}
			return 1;
		}
		return 0;
	}
	function onUserJoin($nick, $channel, $host) {
		return 1;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>