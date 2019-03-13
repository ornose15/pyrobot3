<?php

/*
*		Gets the last 3 songs from a Last.fm user
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class Lastfm_m70 extends PB2 {
	function __construct() {
		parent::PB2();
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'lastfm') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			@trigger_error("");
			if(count($cmd) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "lastfm [profile]");
				return 1;
			}
			$doc = new DOMDocument();
			$doc->load('http://ws.audioscrobbler.com/1.0/user/' . $cmd[1] . '/recenttracks.rss');
			$le = error_get_last();
			if($le['type'] != 1024) {
				$this->send("PRIVMSG $channel :4[Last.fm] Could not retrieve Last.fm track list from '" . $cmd[1] . "'");
				return 1;
			}
			$user = $cmd[1];
			$nuser = substr($user, 1, strlen($user));
			$user = substr($user, 0, 1);
			$user .= chr(2) . chr(2);
			$user .= $nuser;
			$this->send("PRIVMSG $channel :4[Last.fm] " . $user);
			$i = 0;
			$l = 1;
			foreach($doc->getElementsByTagName('item') as $node) {
				if(++$i == 4) { break; }
				$this->send("PRIVMSG $channel :$l) " . $node->getElementsByTagName('title')->item(0)->nodeValue);
				$l++;
			}
			$this->send("PRIVMSG $channel :http://last.fm/user/" . $user . "/");
			return 1;
		}
		return 0;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>