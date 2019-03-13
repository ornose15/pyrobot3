<?php

class PB2 {
	var $aConf;
	var $aModule;
	var $aOther;
	var $users;
	var $chans;
	public static $aStatic;
	public static $aIRC;
	public static $aWhois;
	
	function __construct() {
		//Configure the bot
		$this->aConf['sServer'] = 'irc.androidirc.org';
		$this->aConf['iPort'] = 6667;
		$this->aConf['sNick'] = '_Pyrobot';
		$this->aConf['sTrigger'] = '^';
		
		//Define essentials
		$this->aOther['autoRejoin'] = true;
		$this->aOther['autoInvite'] = false;
		$this->aOther['evalSnooper'] = false;
		$this->aOther['waitTimeNotice'] = false;
		$this->aOther['lastCMDTime'] = 0;
		$this->aOther['waitTime'] = 5;
		self::$aIRC['iUptime'] = time();
		self::$aStatic['bSilent'] = 0;
		self::$aStatic['sTrigger'] = $this->aConf['sTrigger'];
		self::$aStatic['sNick'] = $this->aConf['sNick'];
		
		//Set the user levels
		self::$aStatic['iLevel'] = array(
			'Pyrokid@pyrokid.com' => 1337,
			'pyrokid@pyrokid.com' => 1337
		);
		$this->aChannels = array();
		
		//Prevent disconnection and turn error reporting off
		set_time_limit(0);
		error_reporting(0);

		//Truncate registered commands
		$sql = mysql_connect("localhost", "root", "") or die ("cannot connect");
		mysql_select_db("pyrobot") or die ("cannot select DB");
		mysql_query("TRUNCATE TABLE regfuncs");
		mysql_close($sql);
		
		//Load modules
		$this->loadModules();
		
		//Connect
		self::$aStatic['iSocket'] = fsockopen($this->aConf['sServer'], $this->aConf['iPort']);
		$this->aOther['iReconnect'] = 1;
		if(!self::$aStatic['iSocket']) {
			return $this->reconnect();
		}
		$this->send("USER " . $this->aConf['sNick'] . " " . $this->aConf['sNick'] . " " . $this->aConf['sNick'] . " :" . $this->aConf['sNick']);
		$this->send("NICK " . $this->aConf['sNick']);

		//Move on...
		$this->receive();
	}
	function autoJoin() {
		//$autojoin = array('#android', '#php', '#pyrokid', '#music');
		$autojoin = array('#pksef');
		for($i=0; $i<=count($autojoin)-1; $i++) {
			$this->joinChan($autojoin[$i]);
		}
	}
	function joinChan($chan) {
		return $this->send("JOIN $chan");
	}
	function loadModules() {
		$mods = array('RandomReply', 'IVMPQuery', 'Trans', 'AutoVoice', 'TextAlert', 'DynCMD', 'Commands', 'IMDb');
		for($i=0; $i<=count($mods)-1; $i++) {
			$this->loadModule($mods[$i]);
		}
		return 1;
	}
	function reconnect() {
		sleep(10);
		echo "ATTEMPTING TO RECONNECT (#{$this->aOther['iReconnect']})\r\n";
		$this->aOther['iReconnect']++;
		self::$aStatic['iSocket'] = fsockopen($this->aConf['sServer'], $this->aConf['iPort']);
		if(!self::$aStatic['iSocket']) {
			return $this->reconnect();
		}
		echo "RECONNECTED ON ATTEMPT #{$this->aOther['iReconnect']}\r\n";
		$this->aOther['iReconnect'] = 1;
		$this->send("USER " . $this->aConf['sNick'] . " " . $this->aConf['sNick'] . " " . $this->aConf['sNick'] . " :" . $this->aConf['sNick']);
		$this->send("NICK " . $this->aConf['sNick']);
		$this->send("PRIVMSG NICKSERV IDENTIFY 123456789");
		$this->autoJoin();
		$this->receive();
		return 1;
	}
	function receive() {
		while(1) {
			$sData = fgets(self::$aStatic['iSocket']);
			$this->translateData($sData);
			if(feof(self::$aStatic['iSocket'])) {
				$this->reconnect();
			}
		}
	}
	function translateData($sData) {
		//Get the data
		echo $sData;
		self::$aIRC['sData'] = $sData;
		
		flush();
		$sMsgs = explode(' ', $sData, 5);

		//Get useful variables
		$sTarget = $sMsgs[2];
	
		$sNickname = strstr($sMsgs[0], '!', true);
		$sNickname = substr($sNickname, 1);
		
		$sHost = explode('@', $sMsgs[0]);
		$sHost = $sHost[0] . '@' . $sHost[1];
		$sHost = explode('!', $sHost);
		$sHost = strtolower($sHost[1]);
		
		$sParams = $sMsgs[3] . ' ' . $sMsgs[4];
		$sParams = substr($sParams, 1, -2);
		$sParams = str_replace(array("\r\n", "\r", "\n"), '', $sParams);
		
		if($sTarget == $this->aConf['sNick']) {
			$sTarget = $sNickname;
		}
		
		//Ping and pong so we don't disconnect
		if($sMsgs[0] == 'PING') {
			$this->send('PONG '. $sMsgs[1]);
			foreach($this->aModule['Module'] as $key => $value) {
				if(strpos($key, 'DynCMD') !== false) {
					$this->aModule['Module'][$key]->checkMysql();
				}
			}
		}
		
		/*======================================================================================
										* BEGIN Important User info *
		======================================================================================*/
		//Get results of WHOs sent to every chan	
		/*
		*	Saving
		*/
		switch($sMsgs[1]) {
			//Get user infos
			case '352': {
				$cParams = explode(' ', $sData);
				$this->users->$cParams[7] = new userClass($cParams[7]);
				$sChan = substr($cParams[3], 1);
				self::$aStatic['aHosts'][$cParams[7]] = strtolower($cParams[4] . '@' . $cParams[5]);
				break;
			}
			//Get channel users
			case '353': {
				$cParams = explode(' ', $sData);
				$sChan = substr($cParams[4], 1);
				$this->chans->$sChan->cWho['sUsersList'] = str_replace(' ', ', ', substr($sData, strripos($sData, ':')+1, -3));
				break;
			}
			//Get channel topics
			case '332': {
				$cParams = explode(' ', $sData);
				$sChan = substr($cParams[3], 1);
				$this->chans->$sChan->cWho['sTopic'] = substr($sData, stripos($sData, ':', 1)+1);
				break;
			}
			case '324': {
				$cParams = explode(' ', $sData);
				$sChan = substr($cParams[3], 1);
				if(strlen($sChan) < 1) { break; }
				$this->chans->$sChan->cWho['sMode'] = substr($sData, stripos($sData, '+'));
				break;
			}
			case '333': {
				$cParams = explode(' ', $sData);
				$sChan = substr($cParams[3], 1);
				$this->chans->$sChan->cWho['sTopicDate'] = 'Set by ' . $cParams[4] . ' on ' . date('F j, Y - g:i:s a', $cParams[5]);
				break;
			}
			case '329': {
				$cParams = explode(' ', $sData);
				$sChan = substr($cParams[3], 1);
				if(strlen($sChan) < 1) { break; }
				$this->chans->$sChan->cWho['sChanDate'] = date('F j, Y - g:i:s a', $cParams[4]);
				break;
			}
		}
		/*
		*	Real Time
		*/
		switch($sMsgs[1]) {
			case '311': {
				self::$aWhois['311'] = $sData;
				$aSplit = explode(' ', $sData);
				self::$aWhois['sNick'] = $aSplit[3];
				break;
			}
			case '307': {
				self::$aWhois['307'] = $sData;
				break;
			}
			case '319': {
				self::$aWhois['319'] = $sData;
				break;
			}
			case '312': {
				self::$aWhois['312'] = $sData;
				break;
			}
			case '313': {
				self::$aWhois['313'] = $sData;
				break;
			}
			case '317': {
				self::$aWhois['317'] = $sData;
				break;
			}
			case '401': {
				self::$aWhois['401'] = 'No such nick/channel';
				break;
			}
			case '266': {
				$this->send("JOIN");
				break;
			}
			case '318': {
				$this->users->{self::$aWhois['sNick']}->cParseWhois(self::$aWhois);
				self::$aWhois = array();
				/*$this->parseWhois(self::$aWhois);
				foreach($this->aModule['Module'] as $key => $value) {
					if(strpos($key, 'RTWhois') !== false) {
						$this->aModule['Module'][$key]->parseWho(self::$aIRC['sChan'], self::$aWhois);
						self::$aWhois = array();
					}
				}*/
				break;
			}
		}
		/*======================================================================================
										* END Important User info *
		======================================================================================*/
		
		//Reply to CTCP requests
		switch(trim($sParams)) {
			case $this->ctcpParse('VERSION'):
				$this->ctcpSend($sNickname, 'VERSION Pyrobot Version 2.3 R3 | Written by Carter/Pyrokid | http://pyrokid.com');
				break;
			case  $this->ctcpParse('TIME'):
				$this->ctcpSend($sNickname, 'TIME ' . date("M d, Y g:i:s A", time()));
				break;
			case  $this->ctcpParse('FINGER'):
				$this->ctcpSend($sNickname, 'FINGER That\'s a very nasty thing for you to do!');
				break;
		}
		
		/*
		*	Set up our callbacks
		*	onConnect
		*	onUserChat
		*	onUserJoin
		*	onUserPart
		*	onInvite
		*	onNotice
		*	onNickChange
		*	onModeChange
		*	onUserKick
		*	onUserQuit
		*	onTopicChange
		*/
		switch($sMsgs[1]) {
			case 'PRIVMSG':  {
				if(strpos($sMsgs[0], '@') != '') {
					$this->onUserChat($sNickname, $sTarget, $sParams, $sHost);
				}
				break;
			}
			case 'JOIN': {
				$sTarget = substr($sTarget, 1);
				$sTarget = str_replace(array("\r\n", "\r", "\n"), '', $sTarget);
				$this->onUserJoin($sNickname, $sTarget, $sHost);
				break;
			}
			case 'PART': {
				$sTarget = '#' . substr($sTarget, 1);
				$sTarget = str_replace(array("\r\n", "\r", "\n"), '', $sTarget);
				$this->onUserPart($sNickname, $sTarget, $sHost);
				break;
			}
			case 'INVITE': {
				$this->onInvite($sNickname, $sHost, $sParams);
				break;
			}
			case 'NOTICE': {
				if($sNickname != 'NickServ' && $sNickname != '') {
					$this->onNotice($sNickname, $sHost, $sParams);
				}
				break;
			}
			case 'NICK': {
				$sTarget = substr($sTarget, 1);
				$sTarget = str_replace(array("\r\n", "\r", "\n"), '', $sTarget);
				$this->onNickChange($sNickname, $sTarget, $sHost);
				break;
			}
			case 'MODE': {
				$cParams = explode(' ', $sData);
				if(strlen(trim($cParams[4])) != 0) {
					$this->onModeChange($sNickname, $cParams[2], $sHost, trim($cParams[3]), $cParams[4]);
				} else {
					$this->onModeChange($sNickname, $cParams[2], $sHost, trim($cParams[3]), $cParams[2]);
				}
				break;
			}
			case 'KICK': {
				$cParams = explode(' ', $sData);
				$this->onUserKick($cParams[3], $sNickname, $cParams[2], $sHost);
				break;
			}
			case 'QUIT': {
				$cParams = explode(' ', $sData);
				if(isset($cParams[3])) {
					$this->onUserQuit($sNickname, substr($cParams[3], strripos($cParams[3], ':'), -2));
				} else {
					$this->onUserQuit($sNickname, false);
				}
				break;
			}
			case 'TOPIC': {
				$cParams = explode(' ', $sData);
				$this->onTopicChange($sTarget, substr($cParams[3], strripos($cParams[3], ':')+1, -2), $sNickname, $sHost);
				break;
			}
			case '376': {
				$this->onConnect();
				break;
			}
		}
	}
	/*
		CALLBACKS
	*/
	function onConnect() {
		$this->send("PRIVMSG NICKSERV IDENTIFY 123456789");
		$this->autoJoin();
		return 1;
	}
	function onTopicChange($channel, $topic, $nick, $host) {
		$this->send("TOPIC $channel");
		if(strtolower($channel) != strtolower($this->aConf['sNick'])) {
			$eventGet = file_get_contents('Events/Events.txt');
			$eventGetL = explode("\n", $eventGet);
			foreach($eventGetL as $events) {
				$eventGet = explode(' ', $events);
				if($eventGet[1] == 'TOPIC') {
					$_ENV["nick"] = $nick;
					$_ENV["channel"] = $channel;
					$_ENV["host"] = $host;
					$_ENV["topic"] = $topic;
					$this->send("PRIVMSG $channel :" . $this->evalBuffer('$topic = $_ENV["topic"]; $nick = $_ENV["nick"]; $channel = $_ENV["channel"]; $host = $_ENV["host"]; ' . substr($events, strlen($eventGet[0])+strlen($eventGet[1])+2, strlen($events))));
				}
			}
		}
		return 1;
	}
	function onUserQuit($nick, $reason) {
		unset($this->users->$nick);
			
		$eventGet = file_get_contents('Events/Events.txt');
		$eventGetL = explode("\n", $eventGet);
		foreach($eventGetL as $events) {
			$eventGet = explode(' ', $events);
			if($eventGet[1] == 'QUIT') {
				$_ENV["nick"] = $nick;
				$_ENV["reason"] = $reason;
				$this->send("PRIVMSG $channel :" . $this->evalBuffer('$nick = $_ENV["nick"]; $channel = $_ENV["reason"]; ' . substr($events, strlen($eventGet[0])+strlen($eventGet[1])+2, strlen($events))));
			}
		}
		return 1;
	}
	function onUserKick($knick, $nick, $channel, $host) {
		if($knick == $this->aConf['sNick']) {
			if($this->aOther['autoRejoin'] == true) {
				$this->joinChan("$channel");
			}
		}
		if(strtolower($channel) != strtolower($this->aConf['sNick'])) {
			$eventGet = file_get_contents('Events/Events.txt');
			$eventGetL = explode("\n", $eventGet);
			foreach($eventGetL as $events) {
				$eventGet = explode(' ', $events);
				if($eventGet[1] == 'KICK') {
					$_ENV["nick"] = $nick;
					$_ENV["channel"] = $channel;
					$_ENV["host"] = $host;
					$_ENV["knick"] = $knick;
					$this->send("PRIVMSG $channel :" . $this->evalBuffer('$knick = $_ENV["knick"]; $nick = $_ENV["nick"]; $channel = $_ENV["channel"]; $host = $_ENV["host"]; ' . substr($events, strlen($eventGet[0])+strlen($eventGet[1])+2, strlen($events))));
				}
			}
		}
		return 1;
	}
	function onModeChange($nick, $channel, $host, $mode, $moded) {
		$this->send("MODE $channel");
		if(strtolower($channel) != strtolower($this->aConf['sNick'])) {
			$eventGet = file_get_contents('Events/Events.txt');
			$eventGetL = explode("\n", $eventGet);
			foreach($eventGetL as $events) {
				$eventGet = explode(' ', $events);
				if($eventGet[1] == 'MODE') {
					$_ENV["nick"] = $nick;
					$_ENV["channel"] = $channel;
					$_ENV["host"] = $host;
					$_ENV["mode"] = $mode;
					$_ENV["moded"] = $moded;
					$this->send("PRIVMSG $channel :" . $this->evalBuffer('$mode = $_ENV["mode"]; $moded = $_ENV["moded"]; $nick = $_ENV["nick"]; $channel = $_ENV["channel"]; $host = $_ENV["host"]; ' . substr($events, strlen($eventGet[0])+strlen($eventGet[1])+2, strlen($events))));
				}
			}
		}
		return 1;
	}
	function onNickChange($nick, $newnick, $host) {
		self::$aStatic['aHosts'][$newnick] = $host;
		unset(self::$aStatic['aHosts'][$nick]);
		if($nick == $this->aConf['sNick']) {
			$this->aConf['sNick'] = $newnick;
			self::$aStatic['sNick'] = $newnick;
		}
		unset($this->users->$nick);
		$this->users->$newnick = new userClass($newnick);
		
		$eventGet = file_get_contents('Events/Events.txt');
		$eventGetL = explode("\n", $eventGet);
		foreach($eventGetL as $events) {
			$eventGet = explode(' ', $events);
			if($eventGet[1] == 'NICK') {
				$_ENV["nick"] = $nick;
				$_ENV["newnick"] = $newnick;
				$_ENV["host"] = $host;
				$_ENV["channel"] = $channel;
				$this->send("PRIVMSG $channel :" . $this->evalBuffer('$newnick = $_ENV["newnick"]; $nick = $_ENV["nick"]; $host = $_ENV["host"]; ' . substr($events, strlen($eventGet[0])+strlen($eventGet[1])+2, strlen($events))));
			}
		}
		return 1;
	}
	function onNotice($nick, $host, $text) {
		if(trim($text[0]) == chr(1) && $text[strlen(trim($text))-1] == chr(1)) {
			$text = explode(' ', $text);
			unset($text[0]);
			$text = implode(' ', $text);
			$text = substr($text, 0, -1);
			$this->send("PRIVMSG " . PB2::$aIRC['sChan'] . " :$text");
		}
		$eventGet = file_get_contents('Events/Events.txt');
		$eventGetL = explode("\n", $eventGet);
		foreach($eventGetL as $events) {
			$eventGet = explode(' ', $events);
			if($eventGet[1] == 'NOTICE') {
				$_ENV["nick"] = $nick;
				$_ENV["host"] = $host;
				$_ENV["text"] = $text;
				$this->send("PRIVMSG $channel :" . $this->evalBuffer('$text = $_ENV["text"]; $nick = $_ENV["nick"]; $channel = $_ENV["channel"]; $host = $_ENV["host"]; ' . substr($events, strlen($eventGet[0])+strlen($eventGet[1])+2, strlen($events))));
			}
		}
		return 1;
	}
	function onInvite($nick, $host, $channel) {
		if($this->aOther['autoInvite'] == true) {
			$this->joinChan($channel);
		}
		if(strtolower($channel) != strtolower($this->aConf['sNick'])) {
			$eventGet = file_get_contents('Events/Events.txt');
			$eventGetL = explode("\n", $eventGet);
			foreach($eventGetL as $events) {
				$eventGet = explode(' ', $events);
				if($eventGet[1] == 'INVITE') {
					$_ENV["nick"] = $nick;
					$_ENV["channel"] = $channel;
					$_ENV["host"] = $host;
					$_ENV["topic"] = $topic;
					$this->send("PRIVMSG $channel :" . $this->evalBuffer('$topic = $_ENV["topic"]; $nick = $_ENV["nick"]; $channel = $_ENV["channel"]; $host = $_ENV["host"]; ' . substr($events, strlen($eventGet[0])+strlen($eventGet[1])+2, strlen($events))));
				}
			}
		}
		return 1;
	}
	function onUserJoin($nick, $channel, $host) {
		$sChan = substr($channel, 1);
		//Channel isn't myself
		if($nick == $this->aConf['sNick'] && $channel != $this->aConf['sNick']) {
			if(!isset($this->chans->$sChan)) { $this->chans->$sChan = new chanClass($sChan); }
			$this->send("WHO $channel");
			$this->send("TOPIC $channel");
		}
		if(!isset($this->users->$nick)) { $this->users->$nick = new userClass($nick); }
		if(isset($this->chans->$sChan)) { $this->send("NAMES $channel"); }
		if($nick != $this->aConf['sNick']) {
			foreach($this->aModule['Module'] as $pModules) {
				$pModules->onUserJoin($nick, $channel, $host);
			}
		}
		self::$aStatic['aHosts'][$nick] = $host;

		if(strtolower($channel) != strtolower($this->aConf['sNick'])) {
			$eventGet = file_get_contents('Events/Events.txt');
			$eventGetL = explode("\n", $eventGet);
			foreach($eventGetL as $events) {
				$eventGet = explode(' ', $events);
				if($eventGet[1] == 'JOIN') {
					$_ENV["nick"] = $nick;
					$_ENV["channel"] = $channel;
					$_ENV["host"] = $host;
					$this->send("PRIVMSG $channel :" . $this->evalBuffer('$nick = $_ENV["nick"]; $channel = $_ENV["channel"]; $host = $_ENV["host"]; ' . substr($events, strlen($eventGet[0])+strlen($eventGet[1])+2, strlen($events))));
				}
			}
		}
		return 1;
	}
	function onUserPart($nick, $channel, $host) {
		if((isset(self::$aIRC['aIgnores'][$nick]) && self::$aIRC['aIgnores'][$nick] == $host) || (isset(self::$aIRC['aIgnores'][$channel]) && self::$aIRC['aIgnores'][$channel] == $channel)) {
			return 0;
		}
		if(strtolower($channel) != strtolower($this->aConf['sNick'])) {
			$eventGet = file_get_contents('Events/Events.txt');
			$eventGetL = explode("\n", $eventGet);
			foreach($eventGetL as $events) {
				$eventGet = explode(' ', $events);
				if($eventGet[1] == 'PART') {
					$_ENV["nick"] = $nick;
					$_ENV["channel"] = $channel;
					$_ENV["host"] = $host;
					$this->send("PRIVMSG $channel :" . $this->evalBuffer('$nick = $_ENV["nick"]; $channel = $_ENV["channel"]; $host = $_ENV["host"]; ' . substr($events, strlen($eventGet[0])+strlen($eventGet[1])+2, strlen($events))));
				}
			}
		}
		//$this->send("NAMES $channel");
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		if(((time() - $this->aOther['lastCMDTime']) < $this->aOther['waitTime']) && ($this->getLevel($nick) < 2)) {
			if(substr($text, 0, 1) == self::$aStatic['sTrigger']) {
				if($this->aOther['waitTimeNotice'] == true) {
					$this->send("NOTICE $nick :Please wait " . ($this->aOther['waitTime'] - (time() - $this->aOther['lastCMDTime'])) . " second(s)");
				}
			}
			return 1;
		}
		if(substr($text, 0, 1) == self::$aStatic['sTrigger']) {
			if($this->getLevel($nick) < 2) {
				$this->aOther['lastCMDTime'] = time();
			}
		}
		if((isset(self::$aIRC['aIgnores'][$nick]) && self::$aIRC['aIgnores'][$nick] == $host) || (isset(self::$aIRC['aIgnores'][$channel]) && self::$aIRC['aIgnores'][$channel] == $channel)) {
			return 0;
		}
		foreach($this->aModule['Module'] as $pModules) {
			$pModules->onUserChat($nick, $channel, $text, $host);
		}
		$cmd = explode(' ', $text);
		//if($channel == $this->aConf['sNick'] && $cmd[0] != self::$aStatic['sTrigger'] . 'echo2') { return 1; }
		$args = substr($text, strlen($cmd[0])+1, strlen($text));
		
		if(strtolower($channel) != strtolower($this->aConf['sNick'])) {
			$eventGet = file_get_contents('Events/Events.txt');
			$eventGetL = explode("\n", $eventGet);
			foreach($eventGetL as $events) {
				$eventGet = explode(' ', $events);
				if($eventGet[1] == 'PRIVMSG') {
					$_ENV["nick"] = $nick;
					$_ENV["channel"] = $channel;
					$_ENV["text"] = $text;
					$_ENV["host"] = $host;
					$this->evalBuffer('$nick = $_ENV["nick"]; $channel = $_ENV["channel"]; $text = $_ENV["text"]; $host = $_ENV["host"]; ' . substr($events, strlen($eventGet[0])+strlen($eventGet[1])+2, strlen($events)));
				}
			}
		}

		/*-------------------------------------------------
							Level 2
		-------------------------------------------------*/
		if($cmd[0] == self::$aStatic['sTrigger'] . "raw") {
			if($this->getLevel($nick) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Denied: This command is for users level 2");
				return 1;
			}
			if(count($cmd) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Syntax: " . self::$aStatic['sTrigger'] . "raw [raw commands]");
				return 1;
			}
			$this->send(substr($args, 0, strlen($args)));
			return 1;
		}
		if($cmd[0] == self::$aStatic['sTrigger'] . "join") {
			if($this->getLevel($nick) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Denied: This command is for users level 2");
				return 1;
			}
			if(count($cmd) != 2) {
				$this->send("NOTICE ".$nick." :4/!\ Syntax: " . self::$aStatic['sTrigger'] . "join [channel]");
				return 1;
			}
			$this->send("PRIVMSG " . $channel . " :[" . $this->safeNick($nick) . "] Joining " . $args);
			$this->joinChan($args);
			return 1;
		}
		if($cmd[0] == self::$aStatic['sTrigger'] . "part") {
			if($this->getLevel($nick) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Denied: This command is for users level 2");
				return 1;
			}
			if(count($cmd) == 1) {
				$this->send("PART " . $channel);
				return 1;
			}
			if(count($cmd) >= 2) {
				$this->send("PART " . $args);
			}
			return 1;
		}
		if($cmd[0] == self::$aStatic['sTrigger'] . "op") {
			if($this->getLevel($nick) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Denied: This command is for users level 2");
				return 1;
			}
			$this->send("MODE " . $channel . ' ' . $args);
			return 1;
		}
		if($cmd[0] == self::$aStatic['sTrigger'] . "silent") {
			if($this->getLevel($nick) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Denied: This command is for users level 2");
				return 1;
			}
			if(self::$aStatic['bSilent'] == 0) {
				self::$aStatic['bSilent'] = 1;
				$this->send("PRIVMSG ". $channel . " :4Silent Mode Enabled");
			} else if(self::$aStatic['bSilent'] == 1) {
				self::$aStatic['bSilent'] = 0;
				$this->send("PRIVMSG " . $channel . " :4Silent Mode Disabled");
			}
			return 1;
		}

		/*-------------------------------------------------
						Level 1 and below
		-------------------------------------------------*/
		if($cmd[0] == self::$aStatic['sTrigger'] . "cmds") {
			$trig = self::$aStatic['sTrigger'];
			if(self::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if($this->getLevel($nick) >= 2) {
				$this->send("PRIVMSG " . $channel . " :3Commands for Level " . $this->getLevel($nick) . ": >> [php code], "
				.$trig."join, ".$trig."part, ".$trig."op, ".$trig."raw, ".$trig."silent, ".$trig."cmds, ".$trig."calc, ".$trig."givearose, "
				.$trig."match, ".$trig."rusroulette");
				return 1;
			}
			if($this->getLevel($nick) <= 1) {
				$this->send("PRIVMSG " . $channel . " :10Commands for regulars: ".$trig."cmds, ".$trig."calc, ".$trig."givearose, ".$trig."match, "
				.$trig."rusroulette");
			}
			return 1;
		}
		if($cmd[0] == self::$aStatic['sTrigger'] . 'calc') {
			if(self::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if(count($cmd) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Syntax: " . self::$aStatic['sTrigger'] . "calc [query]");
				return 1;
			}
			$this->send("PRIVMSG " . $channel . " :" . $this->GoogleCalc(trim($args)));
			return 1;
		}
		if($cmd[0] == self::$aStatic['sTrigger'] . 'givearose') {
			if(self::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if(count($cmd) != 2) {
				$this->send("NOTICE ".$nick." :4/!\ Syntax: " . self::$aStatic['sTrigger'] . "givearose [user]");
				return 1;
			}
			$this->send("PRIVMSG ".$channel." :".$nick . " gives " . $cmd[1] . " a rose: 4@>3--'--,--");
			return 1;
		}
		if($cmd[0] == self::$aStatic['sTrigger'] . 'match') {
			if(self::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if(count($cmd) != 3) {
				$this->send("NOTICE ".$nick." :4/!\ Syntax: " . self::$aStatic['sTrigger'] . "match [user] [user]");
				return 1;
			}
			if(!isset($this->users->$cmd[1], $this->users->$cmd[2])) {
				$this->send("NOTICE ".$nick." :4/!\ Error: Matching users have to be in this channel");
				return 1;
			}
			$this->send("PRIVMSG ".$channel." :Love Match - ".$cmd[1]." and ".$cmd[2].": ". mt_rand(1,100)."% chance of happiness!");
			return 1;
		}
		if($cmd[0] == self::$aStatic['sTrigger'] . 'rusroulette') {
			if(self::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if(count($cmd) > 1) {
				$args = explode(' ', $args);
				$u = 0;
				for($i=0; $i<=count($args)-1; $i++) {
					if(!isset($this->users->$args[$i])) {
						$u++;
						break;
					}
				}
				if($u > 0) {
					return $this->send("NOTICE ".$nick." :4/!\ Error: All russian roulette players have to be in this channel");
				}
				if(mt_rand(0, 1) == 0) {
					return $this->send("PRIVMSG " . $channel . " :*CHINK* The chamber was empty.");
				}
				return $this->send("PRIVMSG " . $channel . " :*POW!* Bullet right through " . $args[array_rand($args)] . "'s head!");
			}
			if(mt_rand(0, 1) == 0) {
				return $this->send("PRIVMSG " . $channel . " :*CHINK* The chamber was empty.");
			}
			$chan = substr($channel, 1);
			$arr = $this->chans->$chan->users(true);
			$this->send("PRIVMSG " . $channel . " :*POW!* Bullet right through " . $arr[array_rand($arr)] . "'s head!");
			return 1;
		}
		
		/*-------------------------------------------------
						Misc. Commands
		-------------------------------------------------*/
		if($cmd[0] == self::$aStatic['sTrigger'] . 'mylvl') {
			$this->send("PRIVMSG " . $channel . " :" . $this->safeNick($nick) . ", your level is " . $this->getLevel($nick));
			return 1;
		}
		if($cmd[0] == '>>') {
			if($this->getLevel($nick) >= 2) {
				self::$aIRC['sUser'] = $nick;
				self::$aIRC['sChan'] = $channel;
				$subj = explode(';', $text);
				$i = 0;
				foreach($subj as $chan) {
					$i++;
					if($i == count($subj)) break;
					$chan = $this->getWithin(' ', '->', $chan, true);
					$chan = explode(' ', $chan);
					foreach($chan as $ch) {
						if(isset($this->users->$ch)) {
							if(strpos($args, '$this->users->' . $ch) === false) {
								$args = str_replace($ch, '$this->users->' . $ch, $args);
							}
						} else if(isset($this->chans->$ch)) {
							if(strpos($args, '$this->chans->' . $ch) === false) {
								$args = str_replace($ch, '$this->chans->' . $ch, $args);
							}
						}
					}
				}
				if(strpos($args, '$this') !== 0) {
					$this->send("PRIVMSG " . $channel . " :" . $this->evalBuffer($args));
				} else {
					$this->evalBuffer($args);
				}
				if($this->aOther['evalSnooper'] == true) {
					if($host != "pyrokid@pyrokid.com") {
						$this->send("PRIVMSG #pbsnoop :(" . $this->safeNick($nick) . ") " . $text);
					}
				}
			}
			return 1;
		}
		if($cmd[0] == self::$aStatic['sTrigger'] . "echo2") {
			if($this->getLevel($nick) >= 2) {
				$this->send("PRIVMSG ".$cmd[1]." :". substr($args, strlen($cmd[1])+1));
			}
			return 1;
		}
		if($cmd[0] == self::$aStatic['sTrigger'] . "logmein") {
			if($this->setLevel($nick, 1337)) {
				$this->send("PRIVMSG $channel :Made you level 1337");
			}
			return 1;
		}

		/*-------------------------------------------------
						Website Commands
		-------------------------------------------------*/
		if($cmd[0] == self::$aStatic['sTrigger'] . "latest") {
			$str = file_get_contents('http://blog.pyrokid.com/latest.php');
			$rows = explode(' | ', $str);
			$id = $rows[0];
			$safe_title = $rows[1];
			$title = $rows[2];
			$author = $rows[3];
			$nauthor = substr($author, 1, strlen($author));
			$author = substr($author, 0, 1);
			$author .= chr(2) . chr(2);
			$author .= $nauthor;
			$date = $rows[4];
			$text = $rows[5];
			$url = 'http://blog.pyrokid.com/' . $safe_title;
			$this->send("PRIVMSG " . $channel . " :" . $title . " by " . $author . " on " . $date);
			$this->send("PRIVMSG " . $channel . " :" . $text);
			$this->send("PRIVMSG " . $channel . " :" . $url);
			return 1;
		}
		return 0;
	}
	function replaceWithin($start, $end, $new, $source) {
		return preg_replace('#('.preg_quote($start).')(.*)('.preg_quote($end).')#si', '$1'.$new.'$3', $source);
	}
	function getWithin($start, $end, $source, $f = false) {
		if($f == false) {
			preg_match('#('.preg_quote($start).')(.*)('.preg_quote($end).')#si', $source, $res);
		} else {
			preg_match('#('.preg_quote($start).')(.*?)('.preg_quote($end).')#si', $source, $res);
		}
		return $res[2];
	}
	function moduleList() {
		foreach($this->aModule['ModNames'] as $pModules) {
			$list .= $pModules . ', ';
		}
		$list = substr($list, 0, -2);
		return 'Currently loaded modules: '. $list . ' (' . count($this->aModule['ModNames']) . ')';
	}
	function loadModule($name) {
		$name = str_replace(array("\r\n", "\r", "\n"), ' ', $name);
		if(!file_exists('Modules/' . $name . '.php')) {
			return 'Module "' . $name . '" not found';
		}
		if(isset($this->aModule['Module'][$name . $this->aModule['ModList'][$name]])) {
			return 'Module: Module "' . $name . $this->aModule['ModList'][$name] . '" already loaded';
		}
		$data = file_get_contents('Modules/' . $name . '.php');
		$mID = $this->getWithin('class ' . $name, ' extends', $data);
		include('Modules/' . $name . '.php');
		if(!class_exists($name . $mID)) {
			return 'Class "' . $name . $mID . '" not found';
		}
		$class = $name . $mID;
		$this->aModule['ModList'][$name] = $mID;
		$this->aModule['Module'][$name . $this->aModule['ModList'][$name]] = new $class;
		$this->aModule['ModNames'][$name] = $name;
		return 'Module "' . $name . '" loaded successfully';
	}
	function unloadModule($name) {
		$name = str_replace(array("\r\n", "\r", "\n"), ' ', $name);
		if(!isset($this->aModule['Module'][$name . $this->aModule['ModList'][$name]])) {
			return 'Module "' . $name . $this->aModule['ModList'][$name] . '" not loaded';
		}
		if(!file_exists('Modules/' . $name . '.php')) {
			return 'Module "' . $name . '" not found';
		}
		unset($this->aModule['Module'][$name . $this->aModule['ModList'][$name]], $this->aModule['ModList'][$name], $this->aModule['ModNames'][$name]);
		$data = file_get_contents('Modules/' . $name . '.php');
		$rand = mt_rand(1, 100);
		$data = $this->replaceWithin('class ' . $name . '_', ' extends', 'm' . $rand, $data);
		file_put_contents('Modules/' . $name . '.php', $data);
		return 'Module "' . $name . '" unloaded successfully';
	}
	function reloadModule($name) {
		$name = str_replace(array("\r\n", "\r", "\n"), ' ', $name);
		if(!isset($this->aModule['Module'][$name . $this->aModule['ModList'][$name]])) {
			return 'Module "' . $name . $this->aModule['ModList'][$name] . '" not loaded';
		}
		$this->unloadModule($name);
		$this->loadModule($name);
		return 'Module "' . $name . '" reloaded successfully';
	}
	function paste($str, $lang = 'text') {
		$pnick = self::$aIRC['sUser'];
		if(strlen(self::$aIRC['sUser']) > 14) {
			$pnick = substr(self::$aIRC['sUser'], 0, 14);
		}
		extract($_POST);
		$url = 'http://pyropaste.net';
		$fields = array(
			'name'=>urlencode($pnick),
			'text'=>urlencode($str),
			'lang'=>urlencode($lang),
			'gopost'=>urlencode('yes')
			
		);
		$fields_string = '';
		foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
		rtrim($fields_string,'&');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST,count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch, CURLOPT_ENCODING, "" );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_AUTOREFERER, true );
		$result = curl_exec($ch);
		$headers = curl_getinfo($ch);
		curl_close($ch);
		if($headers['redirect_count'] == 1) {
			return $headers['url'];
		} else {
			return 'Error pasting request';
		}
	}
	public static function send($sData) {
		if(fputs(self::$aStatic['iSocket'], $sData."\n")) {
			return 1;
		}
		return 0;
	}
	function setTrigger($trigger) {
		if(!isset($trigger)) {
			return 0;
		}
		self::$aStatic['sTrigger'] = $trigger;
		return 1;
	}
	function ctcpSend($nick, $str) {
		return $this->send("NOTICE {$nick} :".chr(1).trim($str).chr(1));
	}
	function ctcpParse($str) {
		return chr(1).trim($str).chr(1);
	}
	public static function getLevel($nick) {
		$host = self::$aStatic['aHosts'][$nick];
		if(self::$aStatic['iLevel'][$host] == null || self::$aStatic['iLevel'][$host] <= 1) {
			return 1;
		} else {
			return self::$aStatic['iLevel'][$host];
		}
		return 1;
	}
	public static function setLevel($nick, $level) {
		$host = self::$aStatic['aHosts'][$nick];
		self::$aStatic['iLevel'][$host] = $level;
		return $level;
	}
	public static function safeNick($nick) {
		$nauthor = substr($nick, 1, strlen($nick));
		$nick = substr($nick, 0, 1);
		$nick .= chr(2) . chr(2);
		$nick .= $nauthor;
		return $nick;
	}
	function aIgnore($nick, $flag = '-a') {
		if($nick[0] != '#') {
			if($flag == '-a') {
				self::$aIRC['aIgnores'][$nick] = $this->getHost($nick);
				return '3[Ignore] ' . $nick . ' successfully added to ignore list';
			} else if($flag == '-r') {
				if(isset(self::$aIRC['aIgnores'][$nick])) {
					unset(self::$aIRC['aIgnores'][$nick]);
				} else {
					return '4[Ignore] ' . $nick . ' not found in ignore list, cannot remove';
				}
				return '3[Ignore] ' . $nick . ' successfully removed from ignore list';
			}
		} else if($nick[0] == '#') {
			if($flag == '-a') {
				self::$aIRC['aIgnores'][$nick] = $nick;
				return '3[Ignore] Channel: ' . $nick . ' successfully added to ignore list';
			} else if($flag == '-r') {
				if(isset(self::$aIRC['aIgnores'][$nick])) {
					unset(self::$aIRC['aIgnores'][$nick]);
				} else {
					return '4[Ignore] Channel: ' . $nick . ' not found in ignore list, cannot remove';
				}
				return '3[Ignore] Channel: ' . $nick . ' successfully removed from ignore list';
			}
		}
		return 0;
	}
	function uptime() {
		$ut = time() - self::$aIRC['iUptime'];
		return 'uptime: ' . $this->strTime($ut);
	}
	function action($str) {
		$this->send("PRIVMSG " . self::$aIRC['sChan'] . " :\x01ACTION $str \x01");
		return 1;
	}
	function strTime($s) {
		$d = intval($s/86400);
		$s -= $d*86400;
		$h = intval($s/3600);
		$s -= $h*3600;
		$m = intval($s/60);
		$s -= $m*60;
		if ($d) $str = $d . 'd ';
		if ($h) $str .= $h . 'h ';
		if ($m) $str .= $m . 'm ';
		if ($s) $str .= $s . 's';
		return $str;
	}
	function aIgnoreList() {
		$list = 'Empty';
		if(count(self::$aIRC['aIgnores']) > 0) {
			$list = '';
			foreach(self::$aIRC['aIgnores'] as $uIgnores => $value) {
				$list .= $uIgnores . ', ';
			}
			$list = substr($list, 0, -2);
		}
		return '3[Ignore] List: ' . $list;
	}
	function evalBuffer($code) {
		@trigger_error("");
		ob_start();
		eval($code);
		$code = ob_get_contents();
		ob_end_clean();
		$e = error_get_last();
		if ($e['message'] and $e['type'] != 2048 and $e['type'] != 8){
			return '['.$e['type'].'] '.strip_tags($e['message']);
		} else {
			return str_replace(array("\r\n", "\r", "\n"), ' ', $code);
		}
		return 0;
	}
	function GoogleCalc($query){
		if (!empty($query)){
			$url = "http://www.google.com/search?q=".urlencode($query);
			file_put_contents("pCalc.txt", str_replace(array("\r\n", "\r", "\n"), '', file_get_contents($url)));
			preg_match('/<h2 class="r" dir="ltr" style="font-size:138%">(.*?)<\/h2>/', file_get_contents("pCalc.txt"), $matches);
			if (!$matches['1']){
				unlink("pCalc.txt");
				return '';/*'Your input could not be processed...';*/
			} else {
				unlink("pCalc.txt");
				$matches['1'] = str_replace(array("Â", "<font size=-2> </font>", " &#215; 10", "<sup>", "</sup>"), array("", "", "e", "^", ""), $matches['1']);
				return html_entity_decode($matches['1'], ENT_QUOTES);
			}
		}
		return 0;
	}
	function hostByName($host) {
		$hosts = gethostbyname($host);
		if($hosts != "208.68.139.89") { 
			return $hosts;
		} else {
			return "Host not found";
		}
		return 0;
	}
	function hostDump() {
		$list = '';
		foreach(self::$aStatic['aHosts'] as $nicks => $user) {
			$list .= $nicks . " -> " . $user . "\r\n";
		}
		return $list . count(self::$aStatic['aHosts']);
	}
	function getHost($nick = '') {
		if(empty($nick))
			return self::$aStatic['aHosts'][self::$aIRC['sUser']];
		else
			return self::$aStatic['aHosts'][$nick];
	}
	function randomString($length) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string = '';    
		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters)-1)];
		}
		return $string;
	}
	function addEvent($event, $code) {
		$myrand = $this->randomString(1).date("n").$this->randomString(1).date("j").$this->randomString(1).date("y").$this->randomString(1);
		file_put_contents("Events/Events.txt", "$myrand $event $code\n", FILE_APPEND);
		$this->send('PRIVMSG ' . self::$aIRC['sChan'] . ' :' . $myrand);
		return $myrand;
	}
	function removeEvent($id) {
		$filePath = 'Events/Events.txt';
		$eventGet = file_get_contents($filePath);
		$eventGetL = explode("\n", $eventGet);
		$i = 0;
		foreach($eventGetL as $events) {
			$eventGet = explode(' ', $events);
			if($eventGet[0] == $id) {
				break;
			}
			$i++;
		}
		$fileArr = file($filePath);
		if(trim($fileArr[$i]) != '') {
			unset($fileArr[$i]);
			if(file_put_contents($filePath, implode('', $fileArr), LOCK_EX)) {
				$this->send('PRIVMSG ' . self::$aIRC['sChan'] . ' :' . 'Event ' . $id . ' removed');
				return 1;
			}
		} else {
			$this->send('PRIVMSG ' . self::$aIRC['sChan'] . ' :' . 'Event ' . $id . ' not found');
		}
		return 0;
	}
	public static function registerFunction($func) {
		$sql = mysql_connect("localhost", "root", "") or die ("cannot connect");
		mysql_select_db("pyrobot") or die ("cannot select DB");
		$func = mysql_real_escape_string($func);
		if(mysql_query("INSERT INTO regfuncs (func) VALUES ('{$func}')")) {
			echo ">> Registered function \"$func\"\n";
		}
		mysql_close($sql);
		return 1;
	}
	function __call($name, $arguments) { return 'Non-existant function'; }
	function __destruct() { return 1; }
}

class userClass extends PB2 {
	var $cUserName;
	var $cWhois;
	function __construct($name) {
		$this->cUserName = $name;
		return 1;
	}
	function cParseWhois($aWho) {
		$aSplit = explode(' ', $aWho['311']);
		$this->cWhois['sNickname'] = $aSplit[3];
		$this->cWhois['sRealName'] = substr($aSplit[7], 1);
		if(isset($aWho['307'])) {
			$this->cWhois['sRegistered'] = 'Registered nick';
		} else {
			$this->cWhois['sRegistered'] = 'Not registered';
		}
		if(isset($aWho['313'])) {
			$this->cWhois['sIrcOp'] = substr($aWho['313'], strripos($aWho['313'], ':')+5, -2);
		} else {
			$this->cWhois['sIrcOp'] = 'Not a network or services administrator';
		}
		$this->cWhois['sHost'] = $aSplit[4] . '@' . $aSplit[5];
		$aSplit = explode(' ', $aWho['312']);
		$this->cWhois['sServer'] = /*substr($aSplit[5], 1, -2) . ' ' . */$aSplit[4];
		$this->cWhois['sChannels'] = str_replace(' ', ', ', substr($aWho['319'], strripos($aWho['319'], ':')+1, -3));
		$this->whois($this->cWhois['sFlag'], true, $this->cWhois['cwChannel']);
		$aWho = array();
		$this->cWhois = array();
		return 1;
	}
	function whois($flag = '', $s = false, $channel = '') {
		if(trim($channel) == '') {
			$channel = PB2::$aIRC['sChan'];
		}
		$this->cWhois['cwChannel'] = $channel;
		if($s == false) { $this->cWhois['sFlag'] = $flag; $this->send('WHOIS ' . $this->cUserName); return 1; }
		$p = '';
		if($this->cUserName == PB2::$aStatic['sNick']) { $p = 'P'; }
		switch(strtolower($flag)) {
			case 'nickname': {
				$this->send($p . 'PRIVMSG ' . $channel . ' :' . $this->cWhois['sNickname']);
				break;
			}
			case 'realname':
			case 'real name': {
				$this->send($p . 'PRIVMSG ' . $channel . ' :' . $this->cWhois['sRealName']);
				break;
			}
			case 'registered': {
				$this->send($p . 'PRIVMSG ' . $channel . ' :' . $this->cWhois['sRegistered']);
				break;
			}
			case 'ircop': {
				$this->send($p . 'PRIVMSG ' . $channel . ' :' . $this->cWhois['sIrcOp']);
				break;
			}
			case 'host':
			case 'hostname': {
				$this->send($p . 'PRIVMSG ' . $channel . ' :' . $this->cWhois['sHost']);
				break;
			}
			case 'server': {
				$this->send($p . 'PRIVMSG ' . $channel . ' :' . $this->cWhois['sServer']);
				break;
			}
			case 'channels': {
				$this->send($p . 'PRIVMSG ' . $channel . ' :' . $this->cWhois['sChannels']);
				break;
			}
			default: {
				$this->send($p . 'PRIVMSG ' . $channel . ' :' . $this->cWhois['sNickname'] . ' (' . trim($this->cWhois['sRealName']) . ') [' . $this->cWhois['sRegistered'] . '] ' . $this->cWhois['sHost'] . ' | On ' . $this->cWhois['sServer']);
				break;
			}
		}
		return 1;
	}
	function ctcp($channel = '', $flag = 'VERSION') {
		if(trim($channel) == '') {
			$channel = PB2::$aIRC['sChan'];
		}
		if(strtolower($flag) == 'whocares') { return $this->send("PRIVMSG " . $channel . " :No one!"); }
		$this->send("PRIVMSG {$this->cUserName} :\x01{$flag}\x01");
		return 1;
	}
	function slap($str = '', $channel = '') {
		if(trim($channel) == '') {
			$channel = PB2::$aIRC['sChan'];
		}
		if(trim($str) == '') {
			$this->send("PRIVMSG " . $channel . " :\x01ACTION slaps " . $this->cUserName . " around a bit with a large salmon \x01");
		} else {
			$this->send("PRIVMSG " . $channel . " :\x01ACTION slaps " . $this->cUserName . " around a bit with $str \x01");
		}
		return 1;
	}
	function gLevel($channel = '') {
		if(trim($channel) == '') {
			$channel = PB2::$aIRC['sChan'];
		}
		return $this->send("PRIVMSG " . $channel . " :" . PB2::getLevel($this->cUserName));
	}
	function sLevel($level, $channel = '') {
		if(trim($channel) == '') {
			$channel = PB2::$aIRC['sChan'];
		}
		PB2::setLevel($this->cUserName, $level);
		return $this->send("PRIVMSG " . $channel . " :" . PB2::getLevel($this->cUserName));
	}
	function kick($str, $channel = '') {
		if(trim($channel) == '') {
			$channel = PB2::$aIRC['sChan'];
		}
		return $this->send("KICK " . $channel . " {$this->cUserName} {$str}");
	}
	function ban($str, $channel = '') {
		if(trim($channel) == '') {
			$channel = PB2::$aIRC['sChan'];
		}
		return $this->send("CHANSERV BAN " . $channel . " {$this->cUserName} {$str}");
	}
	function mode($str, $channel = '') {
		if(trim($channel) == '') {
			$channel = PB2::$aIRC['sChan'];
		}
		if($str == '+b' || $str == '-b') {
			$host = PB2::$aStatic['aHosts'][$this->cUserName];
			$host = explode('@', $host);
			return $this->send("MODE " . $channel . " $str {$host[1]}");
			
		}
		return $this->send("MODE " . $channel . " $str {$this->cUserName}");
	}
	function msg($string) {
		return $this->send('PRIVMSG ' . $this->cUserName . ' :' . $string);
	}
	function notice($string) {
		return $this->send('NOTICE ' . $this->cUserName . ' :' . $string);
	}
	function __call($name, $arguments) { return $this->send("PRIVMSG " . $channel . " :Non-existant function"); }
	function __destruct() { return 1; }
}

class chanClass extends PB2 {
	var $cChanName;
	var $cWho;
	function __construct($name) {
		$this->cChanName = $name;
		return 1;
	}
	function update() {
		$this->send("TOPIC #{$this->cChanName}");
		$this->send("MODE #{$this->cChanName}");
		$this->send("NAMES #{$this->cChanName}");
		return 1;
	}
	function topic($find, $replace) {
		if((isset($find)) && (isset($replace))) {
			$newtopic = str_ireplace($find, $replace, $this->cWho['sTopic']);
			return $this->send('TOPIC ' . PB2::$aIRC['sChan'] . ' :' . $newtopic);
		}
		return $this->send('PRIVMSG ' . PB2::$aIRC['sChan'] . ' :' . $this->cWho['sTopic']);
	}
	function topicSetBy() {
		return $this->send('PRIVMSG ' . PB2::$aIRC['sChan'] . ' :' . $this->cWho['sTopicDate']);
	}
	function users($toarr = false) {
		if($toarr == false) {
			return $this->send('PRIVMSG ' . PB2::$aIRC['sChan'] . ' :' . $this->cWho['sUsersList']);
		} else {
			$arr = str_replace(array('~', '&', '@', '%', '+'), '', $this->cWho['sUsersList']);
			$arr = explode(', ', $arr);
			return $arr;
		}
		return 0;
	}
	function mode($mode) {
		if(isset($mode)) {
			return $this->send('MODE ' . PB2::$aIRC['sChan'] . ' ' . $mode);
		}
		return $this->send('PRIVMSG ' . PB2::$aIRC['sChan'] . ' :' . $this->cWho['sMode']);
	}
	function created() {
		return $this->send('PRIVMSG ' . PB2::$aIRC['sChan'] . ' :' . $this->cWho['sChanDate']);
	}
	function msg($string) {
		return $this->send('PRIVMSG #' . $this->cChanName . ' :' . $string);
	}
	function notice($string) {
		return $this->send('NOTICE #' . $this->cChanName . ' :' . $string);
	}
	function __call($name, $arguments) { return $this->send("PRIVMSG " . PB2::$aIRC['sChan'] . " :Non-existant function"); }
	function __destruct() { return 1; }
}

$PB2 = new PB2();

?>