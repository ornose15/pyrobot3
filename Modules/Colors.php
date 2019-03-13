<?php

/*
*		Rainbow!
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class Colors_m54 extends PB2 {
	function __construct() {
		parent::PB2();
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'c') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if(count($cmd) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "c [options: -w / -l] [text]");
				return 1;
			}
			if($cmd[1] != '-w' && $cmd[1] != '-l') {
				$args = substr($text, strlen($cmd[0])+1, strlen($text));
				$argsplit = explode(' ', $args);
				$num = 2;
				$new = '';
				foreach($argsplit as $c) {
					$new .= '' . $num . $c . ' ';
					$num++;
					if($num == 16) { $num = 1; }
					if($num == 0 || $num == 1) { $num = 2; }
				}
				$this->send("PRIVMSG $channel :$new");
				return 1;
			}
			if($cmd[1] == '-w') {
				$args = substr($text, strlen($cmd[0])+1, strlen($text));
				$argsplit = explode(' ', $args);
				$num = 2;
				$new = '';
				foreach($argsplit as $c) {
					$new .= '' . $num . $c . ' ';
					$num++;
					if($num == 16) { $num = 1; }
					if($num == 0 || $num == 1) { $num = 2; }
				}
				$new = substr($new, 5);
				$this->send("PRIVMSG $channel :$new");
				return 1;
			}
			if($cmd[1] == '-l') {
				$args = substr($text, strlen($cmd[0])+1, strlen($text));
				$argsplit = str_split($args);
				$num = 2;
				$new = '';
				foreach($argsplit as $c) {
					$new .= '' . $num . $c;
					$num++;
					if($num == 16) { $num = 1; }
					if($num == 0 || $num == 1) { $num = 2; }
				}
				$new = substr($new, 9);
				$this->send("PRIVMSG $channel :$new");
				return 1;
			}
		}
		return 0;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>