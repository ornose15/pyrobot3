<?php

/*
*		IMDb movie selections
*
*		@author 	Pyrokid
*		@copyright	None
*		@category	IRC bot module
*		@package	Pyrobot
*/

class IMDb_m4 extends PB2 {
	function __construct() {
		parent::PB2();
		PB2::registerFunction("movie");
		foreach(glob("IMDb/*.php") as $filename) {
			unlink("$filename");
		}
		return 1;
	}
	function onUserChat($nick, $channel, $text, $host) {
		$cmd = explode(' ', $text);
		$args = substr($text, strlen($cmd[0])+1, strlen($text));
		if($cmd[0] == PB2::$aStatic['sTrigger'] . 'movie') {
			if(PB2::$aStatic['bSilent'] == 1) {
				$this->send("NOTICE " . $nick . " :4/!\ Notice: I am in silent mode");
				return 1;
			}
			$aGenres = array('action', 'adventure', 'animation', 'biography', 'comedy', 'crime', 'documentary', 'drama', 'family', 'fantasy', 'film_noir', 'game_show', 'history', 'horror', 'music', 'musical', 'mystery', 'news', 'reality_tv', 'romance', 'sci_fi', 'sport', 'talk_show', 'thriller', 'war', 'western');
			if(count($cmd) < 2) {
				$cmd[1] = $aGenres[array_rand($aGenres)];
				$cmd[2] = $cmd[1];
				$cmd[2] = strtoupper($cmd[2][0]) . substr($cmd[2], 1);
				$cmd[2] = str_replace('_', ' ', $cmd[2]);
			}
			if(!in_array($cmd[1], $aGenres)) {
				$this->send("NOTICE " . $nick . " :Not a valid movie genre");
				$this->send("NOTICE " . $nick . " :Genres: " . implode($aGenres, ', '));
				return 1;
			}
			$arr = array();
			$arr2 = array();
			if(!is_dir('IMDb')) { mkdir('IMDb'); }
			if(!file_exists('IMDb/' . $cmd[1] . '.php')) {
				file_put_contents('IMDb/' . $cmd[1] . '.php', file_get_contents('http://www.imdb.com/genre/' . $cmd[1] . '/'));
			}
			$file = file_get_contents('IMDb/' . $cmd[1] . '.php');
			$count = substr_count($file, '<td class="number">');
			$file2 = $file;
			for($i=0; $i<$count; $i++) {
				$bet = $this->strbet($file, '/" title="', '"><img src="http://', true);
				array_push($arr, html_entity_decode($bet[0], ENT_QUOTES));
				$file = substr($file, $bet[1], strlen($file));
			}
			for($i=0; $i<$count+1; $i++) {
				$bet = $this->strbet($file2, '/" title="', '"><img src="http://', true);
				$bet2 = $this->strbet($file2, '<a href="/title/', '/"', true);
				array_push($arr2, 'http://www.imdb.com/title/' . $bet2[0]);
				$file2 = substr($file2, $bet[1], strlen($file2));
			}
			unset($arr2[0]);
			$rand = mt_rand(0, $count-1);
			$this->send("PRIVMSG {$channel} :{$arr[$rand]}");
			if(isset($cmd[2])) { $this->send("PRIVMSG {$channel} :{$cmd[2]}"); }
			$this->send("PRIVMSG {$channel} :{$arr2[$rand+1]}");
			return 1;
		}
	}
	function onUserJoin($nick, $channel, $host) {
		return 1;
	}
	function strbet($inputStr, $delimeterLeft, $delimeterRight, $mod = false) {
		$posLeft=strpos($inputStr, $delimeterLeft);
		$posLeft+=strlen($delimeterLeft);
		$posRight=strpos($inputStr, $delimeterRight, $posLeft);
		if($mod == false) {
			return substr($inputStr, $posLeft, $posRight-$posLeft);
		} else {
			return array(substr($inputStr, $posLeft, $posRight-$posLeft), $posRight);
		}
	}
	function __destruct() { return 1; }
	public function __call($name, $arguments) { return 1; }
}

?>