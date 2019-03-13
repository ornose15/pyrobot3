<?php

/*
*		Gives title of a IV:MP forum link
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class IVMPTopics_m81 extends PB2 {
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

		foreach ($cmd as $text) {
			$sRegex = '@forum.iv-multiplayer.com/index.php/topic,(.*)@';
			preg_match($sRegex, $text, $aMatches);
			if (!empty($aMatches)) {
				$author = $this->safeNick($nick);
				$sWatchcode = trim($aMatches[1]);
				$nPos = strpos($sWatchcode, "&");
				if ($nPos !== FALSE)
					$sWatchcode = substr($sWatchcode, 0, $nPos);
				$sWebsite = file_get_contents('http://forum.iv-multiplayer.com/index.php/topic,' . $sWatchcode);

				$sTitle = $this->strbet($sWebsite, '<title>', '</title>');
				$sTitle = str_replace(array("\r\n", "\r", "\n"), '', $sTitle);
				$this->send("PRIVMSG " . $channel . " :[" . $author . "] 7[IV:MP Forums Thread7] \"" . htmlspecialchars_decode($sTitle) . "\"");
				break;
			}
		}
		return 0;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>