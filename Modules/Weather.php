<?php

/*
*		Gets the last twitter entry of a specified user.
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class Weather_m51 extends PB2 {
	function __construct() {
		parent::PB2();
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		$args = substr($text, strlen($cmd[0])+1, strlen($text));
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'weather') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE ".$nick." :4/!\ Notice: I am in silent mode");
				return 1;
			}
			@trigger_error("");
			if(count($cmd) < 2) {
				$this->send("NOTICE ".$nick." :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "weather [zip code/city]");
				return 1;
			}
			$xml = simplexml_load_string(utf8_encode(file_get_contents('http://www.google.com/ig/api?weather=' . urlencode($args))));
			$info = $xml->xpath("/xml_api_reply/weather/forecast_information");
			if(isset($info[0]->city['data'])) {
				$current = $xml->xpath("/xml_api_reply/weather/current_conditions");
				$forecast_list = $xml->xpath("/xml_api_reply/weather/forecast_conditions");
				$this->send("PRIVMSG $channel :2[Weather] " . $info[0]->city['data']);
				$this->send("PRIVMSG $channel :Condition: " . $current[0]->condition['data']);
				$this->send("PRIVMSG $channel :Temperature: " . $current[0]->temp_f['data'] . '°F, ' . $current[0]->temp_c['data'] . '°C');
				$this->send("PRIVMSG $channel :" . $current[0]->wind_condition['data']);
				$this->send("PRIVMSG $channel :Tomorrow: " . $forecast_list[0]->condition['data']);
			} else {
				$this->send("PRIVMSG $channel :4[Weather] Could not retrieve weather for '" . $args . "'");
			}
			return 1;
		}
		return 0;
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>