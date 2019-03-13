<?php

/*
*		Gives title of a youtube link
*
*		@author 	MaVe
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class Youtube_m8 extends PB2 {
	function __construct() {
		parent::PB2();
		return 1;
	}
	function strbet($inputStr, $delimeterLeft, $delimeterRight) { 
		$posLeft=strpos($inputStr, $delimeterLeft); 
		$posLeft+=strlen($delimeterLeft); 
		$posRight=strpos($inputStr, $delimeterRight, $posLeft); 
		return substr($inputStr, $posLeft, $posRight-$posLeft); 
	}
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		if(in_array($channel, array('#android'))) { return 1; }
		foreach ($cmd as $text) {
			$sRegex = '@http://www.youtube.com/watch\?v=(.*)@';
			preg_match($sRegex, $text, $aMatches);
			if (!empty($aMatches)) {
				$author = $this->safeNick($nick);
				$sWatchcode = trim($aMatches[1]);
				$nPos = strpos($sWatchcode, "&");
				if ($nPos !== FALSE)
					$sWatchcode = substr($sWatchcode, 0, $nPos);
				$sWebsite = file_get_contents('http://www.youtube.com/watch?v=' . $sWatchcode);

				$sTitle = $this->strbet($sWebsite, '<title>', '</title>');
				$sTitle = str_replace(array("\r\n", "\r", "\n", " - YouTube"), '', $sTitle);
				$sTitle = trim(substr($sTitle, 21, -2));
				$sTitle = str_replace(array('&#x202a;', '&#x202c;&rlm;'), '', $sTitle);
				$this->send("PRIVMSG " . $channel . " :[" . $author . "] 1,0You0,4Tube Video \"" . htmlspecialchars_decode($sTitle, ENT_QUOTES) . "\"");
				break;
			}
		}
		return 0;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>