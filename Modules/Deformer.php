<?php

/*
*		Deforms strings into gibberish
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class Deformer_m34 extends PB2 {
	function __construct() {
		parent::PB2();
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'deform') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if(count($cmd) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "deform [options: -w / -l / -f] [text]");
				return 1;
			}
			if($cmd[1] != '-w' && $cmd[1] != '-l' && $cmd[1] != '-f') {
				$args = substr($text, strlen($cmd[0])+1, strlen($text));
				$args = explode(' ', $args);
				shuffle($args);
				$this->send("PRIVMSG $channel :$author 2[Deformer] " . implode(' ', $args));
				return 1;
			}
			if($cmd[1] == '-w') {
				$args = substr($text, strlen($cmd[0])+strlen($cmd[1])+2, strlen($text));
				$args = explode(' ', $args);
				shuffle($args);
				$this->send("PRIVMSG $channel :$author 2[Deformer] " . implode(' ', $args));
				return 1;
			}
			if($cmd[1] == '-l') {
				$args = substr($text, strlen($cmd[0])+strlen($cmd[1])+2, strlen($text));
				$args = explode(' ', $args);
				//shuffle($args);
				$res = '';
				foreach($args as $key) {
					$key = str_split($key);
					shuffle($key);
					$res .= implode('', $key) . ' ';
				}
				echo $res;
				$this->send("PRIVMSG $channel :$author 2[Deformer] " . $res);
				return 1;
			}
			if($cmd[1] == '-f') {
				$args = substr($text, strlen($cmd[0])+strlen($cmd[1])+2, strlen($text));
				$args = str_split($args);
				shuffle($args);
				$fin = '';
				foreach($args as $res) {
					$fin .= $res;
				}
				$this->send("PRIVMSG $channel :$author 2[Deformer] " . $fin);
				return 1;
			}
		}
		return 0;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>