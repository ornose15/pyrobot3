<?php

/*
*		Queries IV:MP servers
*
*		@author 	Boylett
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class IVMPQuery_m6 extends PB2 {
	var $socket;
	var $ip;
	var $port;
	
	function __construct() {
		parent::PB2();
		PB2::registerFunction("query");
		return 1;
	}
	function Query($ip,$port,&$errno,&$errstr,$timeout = 5,$gettimeout = 1) {
        $this->Close();
        $this->ip = $ip;
        $this->port = $port;
        $this->socket = @fsockopen('udp://'.$ip,$port,$errno,$errstr,$timeout);
        if($this->socket === false) return false;
        @stream_set_timeout($this->socket,$gettimeout);
        return true;
    }
    function Close() {
        if($this->socket !== false)
        {
            fclose($this->socket);
            $this->socket = false;
        }
    }
    function GetPacketData($command) {
		$packet = 'IVMP';
        $packet .= $command;
        return $packet;
    }
    function ServerData() {
		fputs($this->socket,$this->GetPacketData('i'));
		@fread($this->socket,5); // IVMPi
		
        $len = ord(@fread($this->socket,4));		
        $hostname = @fread($this->socket,$len); // read hostname
        $players = ord(@fread($this->socket,4)); // read players
        $maxplayers = ord(@fread($this->socket,4)); // read max players
        $passworded = ord(@fread($this->socket,1)); // 1 byte for password

        return array(
            'hostname' => $hostname,
            'players' => $players,
            'maxplayers' => $maxplayers,
            'passworded' => (bool)$passworded
        );
    }
    function ServerRules() {
		fputs($this->socket,$this->GetPacketData('r'));
		@fread($this->socket,5); // IVMPr
		
        $count = ord(@fread($this->socket,4));
		$arr = array();
		for($i = 0; $i < $count; $i++)
		{
			$len = ord(@fread($this->socket,4));
			$key = @fread($this->socket,$len);
			$len = ord(@fread($this->socket,4));
			$arr[$key] = @fread($this->socket,$len);
		}
		
		return $arr;
    }
    function Players() {
		fputs($this->socket,$this->GetPacketData('l'));
		@fread($this->socket,5); // IVMPl
		
        $count = ord(@fread($this->socket,4));
		$arr = array();
		for($i = 0; $i < $count; $i++)
		{
			$id = ord(@fread($this->socket,4));
			$len = ord(@fread($this->socket,4));
			$arr[$id] = @fread($this->socket,$len);
		}
		
		return $arr;
    }
	// Default return is ms int. If $asfloat == true, will return as seconds and as a float
    function Ping($asfloat = false) {
		$time = microtime(true);
		fputs($this->socket,$this->GetPacketData('p'));
		@fread($this->socket,5); // IVMPp		
        $reply = @fread($this->socket,4);
		$time = microtime(true) - $time;
		if(!$asfloat)
		{
			$time *= 1000;
			$time = (int)round($time);
		}
		if($reply == 'PONG')
		{
			return $time;
		}
		
		return false;
    }
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		
		if($cmd[0] == PB2::$aStatic['sTrigger'] . "query") {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if(count($cmd) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "query [ip:port]");
				return 1;
			}
			$ip = explode(":", $cmd[1]);
			if(!$this->Query($ip[0],intval($ip[1]),$errno,$errstr,2)) {
				$this->send("PRIVMSG ".$channel." :Failed to query server ('.$errstr.')");
			} else {
				$server = $this->ServerData();

				if($server['maxplayers'] == 0) {
					$this->send("PRIVMSG ".$channel." :Failed to query server (no reply)");
				} else {
					$this->send("PRIVMSG ".$channel." :".$server['hostname'].' | '.$cmd[1].' | Players: '.$server['players'].' / '.$server['maxplayers']. ' | Ping: ' . $this->Ping() . ($server['passworded'] ? ' | Passworded' : ''));
				}
				$this->Close();
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