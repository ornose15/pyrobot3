<?php

$debug = true; // true or false

$bots = new bots;

/*
 $bots->netBot("server address","server port","bot nick","join channels","nickserv pass","bind address");
 NickServ password is not used here, more needs to be done in order to use it correctly.
*/
$bots->newBot("irc.gtanet.com",6667,"Pyrobot","#chan",null,"192.168.0.6");
$bots->newBot("irc.androidirc.org",6667,"Pyrobot","#chan",null,"192.168.0.6");

$sockets = $bots->getCons(); // the array of sockets
while (!defined("ABORT")) {
      if (count($sockets) == 0) {
         define("ABORT",true);
      }
      if (stream_select($sockets, $w = null, $e = null, 0, 3000) !== false) {
         foreach ($sockets as $sock) {
            if (feof($sock)) {
               $bots->delBot($sock);
               continue;
            }
            if ($raw = trim(fgets($sock,512))) {
               if ($debug == true) {
                  echo "(".$bots->getbot($sock,"nick")." IN): ".$raw."\r\n";
               }
               $exp = explode(' ', $raw);
               if ($exp[0] == "PING") {
                  fwrite($sock,"PONG ".$exp[1]);
               }
            }
         }
      }
      $sockets = $bots->getCons(); // you need to remake this array each time.
}
die("While loop must have stopped. Have all sockets died?\r\n");

function raw($sock,$str) {
         global $bots,$debug;
         if (fwrite($sock,$str."\r\n")) {
            if ($debug == true) {
               echo "(".$bots->getbot($sock,"nick")." OUT): ".$str."\r\n";
            }
            return true;
         }
         return false;
}

class bots {
      private $cons;
      private $bots;

      function __construct () {
               $this->cons = array();
               $this->bots = array();
      }

      function newBot ($serv,$port,$nick,$chan = null,$nspass = null,$bip = "127.0.0.1") {
               if ($con = $this->con($serv,$port,$nick,$chan,$bip)) {
                  $id = count($this->cons);
                  $this->cons[] = array("con" => $con, "id" => $id);
                  $this->bots[] = array("nick" => $nick, "nspass" => $nspass, "chan" => explode(",",$chan), "id" => $id);
               }
      }

      function con ($serv,$port,$nick,$chan,$bip) {
               $opts = array('socket' => array('bindto' => $bip.':0'));
               $context = stream_context_create($opts);
               if ($con = @stream_socket_client("tcp://".$serv.":".$port, $errno, $errstr, 5,STREAM_CLIENT_CONNECT,$context)) {
                     fwrite($con, "NICK $nick \r\n");
                     fwrite($con, "USER $nick 127.0.0.1 127.0.0.1 :Pyrobot.\r\n");
                     if ($chan !== NULL) { fwrite($con, "JOIN :".$chan."\r\n"); }
                     return $con;
                  } else {
                     return false;
               }
      }

      function delBot ($res) {
               @fclose($res);
               for ($i = 0; $i < count($this->cons); $i++) {
                   if ($res == $this->cons[$i]["con"]) {
                       unset($this->cons[$i]);
                       $this->cons = array_values($this->cons);
                       unset($this->bots[$i]);
                       $this->bots = array_values($this->bots);
                   }
               }
      }

      function getCons () {
               $ret = array();
               for ($i = 0; $i < count($this->cons); $i++) {
                   $ret[] = $this->cons[$i]["con"];
               }
               return $ret;
      }

      function getBot ($res,$var) {
               for ($i = 0; $i < count($this->cons); $i++) {
                   if ($res == $this->cons[$i]["con"]) {
                       $botid = $this->cons[$i]["id"];
                       return $this->bots[$botid][$var];
                   }
               }
      }
}
?>