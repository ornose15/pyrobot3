<?php

/*
*		Commands
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class Commands_m77 extends PB2 {
	function __construct() {
		parent::PB2();
		$funcArr = array("google", "horoscope", "c", "deform", "lastfm", "twitter", "weather");
		foreach($funcArr as $fA) {
			PB2::registerFunction($fA);
		}
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		$args = substr($text, strlen($cmd[0])+1, strlen($text));
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'google') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE " . $nick . " :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if(count($cmd) < 2) {
				$this->send("NOTICE " . $nick . " :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "google [query]");
				return 1;
			}
			$rez = $this->google_search_api(array('q' => $args));
			$this->send("PRIVMSG {$channel} :" . htmlspecialchars_decode($rez->responseData->results[0]->titleNoFormatting, ENT_QUOTES));
			$this->send("PRIVMSG {$channel} :" . strip_tags(htmlspecialchars_decode($rez->responseData->results[0]->content, ENT_QUOTES)));
			$this->send("PRIVMSG {$channel} :{$rez->responseData->results[0]->url}");
			return 1;
		}
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'horoscope') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE " . $nick . " :4/!\ Notice: I am in silent mode");
				return 1;
			}
			$aSigns = array('aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces');
			if(count($cmd) != 2) {
				$this->send("NOTICE " . $nick . " :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "horoscope [zodiac sign]");
				$this->send("NOTICE " . $nick . " :Signs: " . implode($aSigns, ', '));
				return 1;
			}
			if(!in_array($cmd[1], $aSigns)) {
				$this->send("NOTICE " . $nick . " :Not a valid zodiac sign");
				$this->send("NOTICE " . $nick . " :Signs: " . implode($aSigns, ', '));
				return 1;
			}
			$sHor = file_get_contents("http://my.horoscope.com/astrology/free-daily-horoscope-{$cmd[1]}.html");
			$sHor = $this->strbet($sHor, '<div class="fontdef1" style="padding-right:10px;" id="textline">', '</div>');
			$this->send("PRIVMSG {$channel} :{$sHor}");
			return 1;
		}
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'c') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE " . $nick . " :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if(count($cmd) < 2) {
				$this->send("NOTICE " . $nick . " :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "c [options: -w / -l] [text]");
				return 1;
			}
			if($cmd[1] != '-w' && $cmd[1] != '-l') {
				$args = substr($text, strlen($cmd[0])+1, strlen($text));
				$argsplit = explode(' ', $args);
				$num = 2;
				$new = '';
				foreach($argsplit as $c) {
					$new .= '' . $num . $c . ' ';
					$num++;
					if($num == 16) { $num = 1; }
					if($num == 0 || $num == 1) { $num = 2; }
				}
				$this->send("PRIVMSG $channel :$new");
				return 1;
			}
			if($cmd[1] == '-w') {
				$args = substr($text, strlen($cmd[0])+1, strlen($text));
				$argsplit = explode(' ', $args);
				$num = 2;
				$new = '';
				foreach($argsplit as $c) {
					$new .= '' . $num . $c . ' ';
					$num++;
					if($num == 16) { $num = 1; }
					if($num == 0 || $num == 1) { $num = 2; }
				}
				$new = substr($new, 5);
				$this->send("PRIVMSG $channel :$new");
				return 1;
			}
			if($cmd[1] == '-l') {
				$args = substr($text, strlen($cmd[0])+1, strlen($text));
				$argsplit = str_split($args);
				$num = 2;
				$new = '';
				foreach($argsplit as $c) {
					$new .= '' . $num . $c;
					$num++;
					if($num == 16) { $num = 1; }
					if($num == 0 || $num == 1) { $num = 2; }
				}
				$new = substr($new, 9);
				$this->send("PRIVMSG $channel :$new");
				return 1;
			}
			return 0;
		}
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'deform') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE " . $nick . " :4/!\ Notice: I am in silent mode");
				return 1;
			}
			if(count($cmd) < 2) {
				$this->send("NOTICE " . $nick . " :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "deform [options: -w / -l / -f] [text]");
				return 1;
			}
			if($cmd[1] != '-w' && $cmd[1] != '-l' && $cmd[1] != '-f') {
				$args = substr($text, strlen($cmd[0])+1, strlen($text));
				$args = explode(' ', $args);
				shuffle($args);
				$this->send("PRIVMSG $channel :$author 2[Deformer] " . implode(' ', $args));
				return 1;
			}
			if($cmd[1] == '-w') {
				$args = substr($text, strlen($cmd[0])+strlen($cmd[1])+2, strlen($text));
				$args = explode(' ', $args);
				shuffle($args);
				$this->send("PRIVMSG $channel :$author 2[Deformer] " . implode(' ', $args));
				return 1;
			}
			if($cmd[1] == '-l') {
				$args = substr($text, strlen($cmd[0])+strlen($cmd[1])+2, strlen($text));
				$args = explode(' ', $args);
				//shuffle($args);
				$res = '';
				foreach($args as $key) {
					$key = str_split($key);
					shuffle($key);
					$res .= implode('', $key) . ' ';
				}
				echo $res;
				$this->send("PRIVMSG $channel :$author 2[Deformer] " . $res);
				return 1;
			}
			if($cmd[1] == '-f') {
				$args = substr($text, strlen($cmd[0])+strlen($cmd[1])+2, strlen($text));
				$args = str_split($args);
				shuffle($args);
				$fin = '';
				foreach($args as $res) {
					$fin .= $res;
				}
				$this->send("PRIVMSG $channel :$author 2[Deformer] " . $fin);
				return 1;
			}
			return 0;
		}
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'lastfm') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE " . $nick . " :4/!\ Notice: I am in silent mode");
				return 1;
			}
			@trigger_error("");
			if(count($cmd) < 2) {
				$this->send("NOTICE " . $nick . " :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "lastfm [profile]");
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
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'twitter') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE " . $nick . " :4/!\ Notice: I am in silent mode");
				return 1;
			}
			@trigger_error("");
			if(count($cmd) < 2) {
				$this->send("NOTICE " . $nick . " :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "twitter [profile]");
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
		/*if($cmd[0] == PB2::$aStatic['sTrigger'] . 'weather') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE " . $nick . " :4/!\ Notice: I am in silent mode");
				return 1;
			}
			//@trigger_error("");
			if(count($cmd) < 2) {
				$this->send("NOTICE " . $nick . " :4/!\ Error - Syntax: " . PB2::$aStatic['sTrigger'] . "weather [zip code/ip address/city]");
				return 1;
			}
			$w = json_decode(file_get_contents('http://free.worldweatheronline.com/feed/weather.ashx?q=' . urlencode($args) . '&format=json&num_of_days=2&key=b2c4696289192051111508'), true);
			if(isset($w['data']['request'][0]['query'])) {
				$this->send("PRIVMSG $channel :2[Weather] " . $w['data']['request'][0]['query']);
				$this->send("PRIVMSG $channel :Condition: " . $w['data']['current_condition'][0]['weatherDesc'][0]['value']);
				$this->send("PRIVMSG $channel :Temperature: " . $w['data']['current_condition'][0]['temp_F'] . '°F, ' . $w['data']['current_condition'][0]['temp_C'] . "°C");
				$this->send("PRIVMSG $channel :Wind: " . $w['data']['current_condition'][0]['winddir16Point'] . " at " . $w['data']['current_condition'][0]['windspeedMiles'] . " mph, " . $w['data']['current_condition'][0]['windspeedKmph'] . "kph");
				$this->send("PRIVMSG $channel :Humidity: " . $w['data']['current_condition'][0]['humidity'] . "%");
				$this->send("PRIVMSG $channel :Visibility: " . $w['data']['current_condition'][0]['visibility'] . "%");
				$this->send("PRIVMSG $channel :Precipitation: " . $w['data']['current_condition'][0]['precipMM'] . "mm");
				$this->send("PRIVMSG $channel :Tomorrow: " . $w['data']['weather'][1]['weatherDesc'][0]['value'] . ", " . $w['data']['weather'][1]['tempMaxF'] . '°F, ' . $w['data']['weather'][1]['tempMaxC'] . "°C");
			} else {
				$this->send("PRIVMSG $channel :4[Weather] Could not retrieve weather for '" . $args . "'");
			}
			return 1;
		}*/
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
				$this->send("PRIVMSG $channel :Condition: " . $current[0]->condition['data']);
				$this->send("PRIVMSG $channel :Temperature: " . $current[0]->temp_f['data'] . '°F, ' . $current[0]->temp_c['data'] . '°C');
				$this->send("PRIVMSG $channel :" . str_ireplace("Wind:", "Wind:", $current[0]->wind_condition['data']));
				$this->send("PRIVMSG $channel :" . str_ireplace("Humidity:", "Humidity:", $current[0]->humidity['data']));
				$this->send("PRIVMSG $channel :Tomorrow Temperature: High of " . $forecast_list[1]->high['data'] . "°F (" . round((intval($forecast_list[1]->high['data'])-32)/1.8) . "°C), low of " . $forecast_list[0]->low['data'] . "°F (" . round((intval($forecast_list[1]->low['data'])-32)/1.8) . "°C)");
				$this->send("PRIVMSG $channel :Tomorrow Condition: " . $forecast_list[1]->condition['data']);
			} else {
				$this->send("PRIVMSG $channel :4[Weather] Could not retrieve weather for '" . $args . "'");
			}
			return 1;
		}
		return 1;
	}
	function onUserJoin($nick, $channel, $host) {
		return 1;
	}
	function strbet($inputStr, $delimeterLeft, $delimeterRight) {
		$posLeft=strpos($inputStr, $delimeterLeft);
		$posLeft+=strlen($delimeterLeft);
		$posRight=strpos($inputStr, $delimeterRight, $posLeft);
		return substr($inputStr, $posLeft, $posRight-$posLeft);
	}
	function google_search_api($args, $referer = 'http://gsearch.com/', $endpoint = 'web'){
		$url = "http://ajax.googleapis.com/ajax/services/search/".$endpoint;
	 
		if ( !array_key_exists('v', $args) )
			$args['v'] = '1.0';
	 
		$url .= '?'.http_build_query($args, '', '&');
	 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// note that the referer *must* be set
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string = '';    
		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters)-1)];
		}
		$referer .= $string . '/';
		curl_setopt($ch, CURLOPT_REFERER, $referer);
		$body = curl_exec($ch);
		curl_close($ch);
		//decode and return the response
		return json_decode($body);
	}
	function __destruct() {return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>