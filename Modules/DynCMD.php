<?php

/*
*		Dynamic commands for Pyrobot
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class DynCMD_m4 extends PB2 {
	var $sql;
	
	function __construct() {
		parent::PB2();
		//Load MySQL
		$this->sql['con'] = mysql_connect("localhost", "root", "") or die ("cannot connect");
		mysql_select_db("pyrobot") or die ("cannot select DB");
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		if($text[0] != PB2::$aStatic['sTrigger']) return 1;
		$cmd = explode(' ', $text);
		$args = substr($text, strlen($cmd[0])+1, strlen($text));
		if($this->checkCmd(substr($cmd[0], 1))) {
			$row = mysql_fetch_assoc($this->sql['rows']);
			if(PB2::getLevel($nick) < $row['level']) {
				return $this->send("NOTICE $nick :4/!\ Denied: This command is for users level {$row['level']} and up");
			}
			if(strlen($row['params']) > 1) {
				$p = explode(' ', $row['params']);
				if(count($p)+1 != count($cmd)) {
					return $this->send("NOTICE $nick :4/!\ Syntax: " . PB2::$aStatic['sTrigger'] . "{$row['name']} {$row['params']}");
				}
			}
			$_ENV["nick"] = $nick;
			$_ENV["channel"] = $channel;
			$_ENV["text"] = $args;
			$_ENV["host"] = $host;
			$this->send("PRIVMSG $channel :" . $this->cEvalBuffer('$nick = $_ENV["nick"]; $channel = $_ENV["channel"]; $text = $_ENV["text"]; $host = $_ENV["host"]; ' . $row['cmd']));
			mysql_free_result($sql);
			mysql_free_result($this->sql['rows']);
			mysql_close($this->sql['con']);
		}
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'addcmd') {
			if(PB2::getLevel($nick) < 3) {
				return $this->send("NOTICE $nick :4/!\ Denied: This command is for users level 3 and up");
			}
			if(count($cmd) < 4) {
				return $this->send("NOTICE $nick :4/!\ Syntax: " . PB2::$aStatic['sTrigger'] . "addcmd [name] [level] [command]");
			}
			if(!is_numeric($cmd[2])) {
				return $this->send("NOTICE $nick :4/!\ Syntax: " . PB2::$aStatic['sTrigger'] . "addcmd [name] [level] [command]");
			}
			if($this->checkCmd($cmd[1], false)) {
				$sql = mysql_query("DELETE FROM commands WHERE name = '{$cmd[1]}'");
				mysql_free_result($sql);
			}
			$this->checkMysql();
			$cmdt = mysql_real_escape_string(substr($text, strlen($cmd[0]) + strlen($cmd[1]) + strlen($cmd[2]) + 3));
			$cmd[1] = mysql_real_escape_string($cmd[1]);
			$cmd[2] = mysql_real_escape_string($cmd[2]);
			if($sql = mysql_query("INSERT INTO commands (name, level, cmd) VALUES ('{$cmd[1]}',  '{$cmd[2]}', '{$cmdt}')")) {
				$this->send("PRIVMSG $channel :Command {$cmd[1]} added successfully");
				mysql_free_result($sql);
				mysql_close($this->sql['con']);
			} else {
				$this->send("PRIVMSG $channel :Failed to add command");
			}
			return 1;
		}
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'delcmd') {
			if(PB2::getLevel($nick) < 3) {
				return $this->send("NOTICE $nick :4/!\ Denied: This command is for users level 3 and up");
			}
			if(count($cmd) < 2) {
				$this->send("NOTICE $nick :4/!\ Syntax: " . PB2::$aStatic['sTrigger'] . "delcmd [name]");
				return 1;
			}
			if($this->checkCmd($cmd[1], false)) {
				if($sql = mysql_query("DELETE FROM commands WHERE name = '{$cmd[1]}'")) {
					$this->send("PRIVMSG $channel :Command {$cmd[1]} deleted successfully");
					mysql_free_result($sql);
					mysql_close($this->sql['con']);
				} else {
					$this->send("PRIVMSG $channel :Failed to delete command");
				}
			} else {
				$this->send("PRIVMSG $channel :Command {$cmd[1]} not found");
			}
			return 1;
		}
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'cmdsrc') {
			if(PB2::getLevel($nick) < 3) {
				return $this->send("NOTICE $nick :4/!\ Denied: This command is for users level 3 and up");
			}
			if(count($cmd) < 2) {
				$this->send("NOTICE $nick :4/!\ Syntax: " . PB2::$aStatic['sTrigger'] . "cmdsrc [name]");
				return 1;
			}
			if(!$this->checkCmd($cmd[1], false)) {
				return $this->send("PRIVMSG $channel :Command {$cmd[1]} not found");
			}
			if($this->checkCmd($cmd[1])) {
				$row = mysql_fetch_assoc($this->sql['rows']);
				$this->send("PRIVMSG $channel :{$row['cmd']}");
				mysql_free_result($sql);
				mysql_free_result($this->sql['rows']);
				mysql_close($this->sql['con']);
			}
			return 1;
		}
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'cmds2') {
			$this->checkMysql();
			$sql = mysql_query("SELECT name FROM commands");
			$sql2 = mysql_query("SELECT func FROM regfuncs");
			$arr = array();
			while($rows = mysql_fetch_array($sql)) {
				array_push($arr, PB2::$aStatic['sTrigger'] . $rows[0]);
			}
			while($rows = mysql_fetch_array($sql2)) {
				array_push($arr, PB2::$aStatic['sTrigger'] . $rows[0]);
			}
			$this->send("PRIVMSG $channel :" . implode(', ', $arr));
			return 1;
		}
		return 0;
	}
	function onUserJoin($nick, $channel, $host) {
		return 1;
	}
	function checkMysql() {
		if(!mysql_ping($this->sql['con'])) {
			$this->sql['con'] = mysql_connect("localhost", "root", "") or die ("cannot connect");
			mysql_select_db("pyrobot") or die ("cannot select DB");
		}
		return 1;
	}
	function checkCmd($cmd, $store = true) {
		$this->checkMysql();
		$sql = mysql_query("SELECT * FROM commands WHERE name = '$cmd'");
		if(mysql_num_rows($sql) != 0) {
			if($store == true) { $this->sql['rows'] = $sql; }
			return 1;
		}
		return 0;
	}
	function cEvalBuffer($code) {
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
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>