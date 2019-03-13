<?php

/*
*		Logs chan conversations
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class Logger_m7 extends PB2 {
	function __construct() {
		parent::PB2();
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		file_put_contents('Logs/AndroidIRC/' . $channel . '.log', '(' . date('h:i:s') . ') (' . $nick . ') ' . $text . "\r\n", FILE_APPEND);
		return 0;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>