<?php

/*
*		Converts normal english to Speakr
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class Speakr_m71 extends PB2 {
	function __construct() {
		parent::PB2();
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'speakr') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if(count($cmd) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "speakr [text]");
				return 1;
			}
			$args = substr($text, strlen($cmd[0])+1, strlen($text));
			$args = explode(' ', $args);
			$author = $this->safeNick($nick);
			foreach($args as $lm) {
				if(substr($lm, strlen($lm)-1, strlen($lm)-1) != 'r') {
					$nm = $lm . 'r';
					$finish .= $nm . ' ';
				} else {
					$nm = $lm;
					$finish .= $nm . ' ';
				}
			}
			$this->send("PRIVMSG $channel :$author 7[Speakr] " . $finish);
			return 1;
		}
		return 0;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>