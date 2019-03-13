<?php

/*
*		Gets the last twitter entry of a specified user.
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class TwitterFeed_m61 extends PB2 {
	function __construct() {
		parent::PB2();
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'twitter') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			@trigger_error("");
			if(count($cmd) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "twitter [profile]");
				return 1;
			}
			$doc = new DOMDocument();
			$doc->load('http://twitter.com/statuses/user_timeline/' . $cmd[1] . '.rss');
			$le = error_get_last();
			if($le['type'] != 1024) {
				$this->send("PRIVMSG $channel :7[Twitter] Could not retrieve Twitter feed from '" . $cmd[1] . "'");
				return 1;
			}
			$i = 0;
			foreach($doc->getElementsByTagName('item') as $node) {
				if(++$i == 2) { break; }
				$user = explode(': ', $node->getElementsByTagName('description')->item(0)->nodeValue);
				$desc = strip_tags(str_replace(array("\r\n", "\r", "\n"), ' ', $node->getElementsByTagName('description')->item(0)->nodeValue));
				$desc = preg_replace("@mave@i", "ma".chr(2).chr(2)."ve", $desc);
				$this->send("PRIVMSG $channel :7[Twitter] " . $desc);
				$this->send("PRIVMSG $channel :" . str_replace(' +0000', '', $node->getElementsByTagName('pubDate')->item(0)->nodeValue));
				$this->send("PRIVMSG $channel :http://twitter.com/" . $user[0] . "/");
			}
			return 1;
		}
		return 0;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>