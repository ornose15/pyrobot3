<?php

/*
*		Real time printer for WHOIS and WHO data.
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class RTWhois_m50 extends PB2 {
	function __construct() {
		parent::PB2();
		return 1;
	}
	function parseWho($channel, $aWho) {
		if(isset($aWho['401'])) {
			$this->send('PRIVMSG ' . $channel . ' :' . $aWho['401']);
			return 1;
		}
		$aSplit = explode(' ', $aWho['311']);
		$sNickname = $aSplit[3];
		$this->send('PRIVMSG ' . $channel . ' :' . '[WHOIS] ' . $sNickname);
		$sString = '';
		if(isset($aWho['307'])) { $sString = $sNickname . ' is registered | '; } $sString .= 'Real name: ' . substr($aSplit[7], 1);
		if(isset($aWho['313'])) { $sString .= ' | ' . substr($aWho['313'], strripos($aWho['313'], ':')+5, -2); }
		$this->send('PRIVMSG ' . $channel . ' :' . $sString);
		$this->send('PRIVMSG ' . $channel . ' :Hostname: ' . $aSplit[4] . '@' . $aSplit[5]);
		$aSplit = explode(' ', $aWho['312']);
		$this->send('PRIVMSG ' . $channel . ' :' . $sNickname . ' is on ' . substr($aSplit[5], 1, -2) . ' ' . $aSplit[4]);
		$this->send('PRIVMSG ' . $channel . ' :Channels: ' . str_replace(' ', ', ', substr($aWho['319'], strripos($aWho['319'], ':')+1, -3)));
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		return 0;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>