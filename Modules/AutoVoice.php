<?php

/*
*		Auto voices users
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class AutoVoice_m65 extends PB2 {
	function __construct() {
		parent::PB2();
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		return 1;
	}
	function onUserJoin($nick, $channel, $host) {
		if($channel == '#music') {
			return $this->send("MODE $channel +v $nick");
		}
		if($channel == '#android' && $host == 'jamie@how.2.shot.web') {
			return $this->send("MODE $channel +v $nick");
		}
		return 1;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>