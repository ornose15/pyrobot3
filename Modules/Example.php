<?php

/*
*		Default example module for PB2.
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class Example_m84 extends PB2 {
	function __construct() {
		parent::PB2();
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		if(strtolower($cmd[0] . ' ' . $cmd[1]) == 'hi pyrobot2') {
			$this->send("PRIVMSG $channel :oh nah nah, whats ma name?");
			return 1;
		}
		return 0;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>