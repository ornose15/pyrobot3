<?php

/*
*		Translates languages
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class Trans_m48 extends PB2 {
	function __construct() {
		parent::PB2();
		PB2::registerFunction("trans");
		return 1;
	}
 
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		$args = substr($text, strlen($cmd[0])+strlen($cmd[1])+strlen($cmd[2])+3, strlen($text));
		
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'trans' || $cmd[0] == PB2::$aStatic['sTrigger'] . 't') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			@trigger_error("");
			if(count($cmd) < 3) {
				$this->send("NOTICE ".$nick." :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "trans [fromLang] [toLang] [text]");
				return 1;
			}
			if(strlen($cmd[1]) != 2 || strlen($cmd[2]) != 2) {
				$this->send("PRIVMSG $channel :4[Translate] Could not successfully translate your request");
				return 1;
			}
			/*$t = file_get_contents("http://translate.google.com/translate_a/t?client=j&sl=" . urlencode($cmd[1]) . "&tl=" . urlencode($cmd[2]) . "&text=" . urlencode($args));
			$t = json_decode(utf8_encode($t), true);
			$glue = '';
			foreach($t['sentences'] as $t) {
				$glue .= $t['trans'];
			}
			$this->send("PRIVMSG $channel :3[" . $cmd[1] . " -> " . $cmd[2] . "] " . $glue);*/
			$cmd[1] = preg_replace("/[^a-zA-Z0-9\s]/", "", $cmd[1]);
			$cmd[2] = preg_replace("/[^a-zA-Z0-9\s]/", "", $cmd[2]);
			if(strlen($cmd[1]) != 2 || strlen($cmd[2]) != 2) {
				$this->send("PRIVMSG $channel :4[Translate] Could not successfully translate your request");
				return 1;
			}
			$APPID = "2DB703290E722BFD7056587C085DB1DBD9CCBBDE"; // your app ID
			$trans = file_get_contents('http://api.microsofttranslator.com/V2/Ajax.svc/Translate?appId='.$APPID.'&from='.$cmd[1].'&to='.$cmd[2].'&text='.urlencode($args));
			$trans = substr($trans, 4, -1);
			if((strpos($trans, 'ArgumentOutOfRangeException') === false) && (trim($trans) != '')) {
				$this->send("PRIVMSG $channel :3[" . $cmd[1] . " -> " . $cmd[2] . "] " . $trans);
			} else {
				$this->send("PRIVMSG $channel :4[Translate] Could not successfully translate your request");
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