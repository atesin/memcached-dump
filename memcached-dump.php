#!/bin/php
<?php

// run by web: insecure and unpredictable
if ( PHP_SAPI != 'cli' )
  exit('This script is designed to be ran in console');

// print help page
if ( count($argv) > 1 && preg_match('/^--?(h(elp)?|\?)$/', $argv[1]) )
  exit(
    "memcached-dump.php v1 : dumps all memcached server data, in 'set' format, to stdout\n".
    "Info : I wanted to speed up my website through memcached, but with some data resilience\n".
    "License : GPLv3, Homepage : https://github.com/atesin/memcached-dump, Usage :\n\n".
    "memcached-dump.php [host|host:port|:port] : connect to server and dump data\n".
    "* host : optional server address or hostname, defaults to 'localhost'\n".
    "* port : optional server tcp port, defaults to 11211\n".
    "memcached-dump.php -[-]<h|help|?> : show this help\n"
  );


// ------ begin ------


// establish a connection to memcached server if applies
$server = explode(':', @$argv[1].':11211');
$server = @fsockopen($server[0] ? $server[0] : 'localhost', (int) $server[1]);

if ( $server === false )
{
  fwrite(STDERR, error_get_last()['message']."\n");
  exit();
}

// get a list of items with expiration time
$now = time(); // register time
$metadump = request('lru_crawler metadump all');
// "key=myKey exp=-1.23 .*?\r\n"*
preg_match_all('/^key=(.*?) exp=(-?[0-9]*) /m', $metadump, $matches);
// $matches = [.*, [keys], [exps]]*

$exps = array_combine($matches[1], $matches[2]);
// $exps[<key>] = <exp>
unset($metadump);

// get all items at once
$values = request('get '.implode(' ', $matches[1]));
// "VALUE <key> <flags> <bytes>\r\n<data>\r\n"*

preg_match_all('/^VALUE (.*?) (-?[0-9]+) (-?[0-9]+)/m', $values, $matches, PREG_SET_ORDER);
// $matches = [.*, <key>, <flags>, <bytes>]*

// build a nicer items list
$items = array();
foreach ( $matches as $line )
  $items[$line[1]] = array('flags' => $line[2], 'exp' => $exps[$line[1]], 'bytes' => $line[3]);
  // $items[<key>] = [<flags>, <exp>, <bytes>]*
unset($exps);

// get data and format output
$to_month = $now + 2592000; // 30 days later
while ( preg_match('/^VALUE (.+?) .*?\r\n/', $values, $matches) )
// $matches = [.*, <key>]
{
  $item = $items[$key = $matches[1]]; // [<flags>, <exp>, <bytes>]
  $data = substr($values, $offset = strlen($matches[0]), $len = $item['bytes']);
  $values = substr($values, $offset + $len + 2); // shift values to left
  
  $exp1 = (int) $item['exp'];
  $exp2 = $exp1 > $to_month ? $exp1 : $exp1 < 0 ? 0 : $exp1 - $now;
  echo "set $key {$item['flags']} $exp2 {$item['bytes']}\r\n$data\r\n";
}


// ------ functions ------


// send a command to server and return the response
function request($command)
{
  // send command
  global $server;
  fwrite($server, $command."\r\n");
  
  // get response to send
  $response = '';
  for (;;)
  {
    $frame = fread($server, 8192);
    $response .= $frame;
    if ( strlen($frame) < 8192 ) // i found feof() unreliable in this case
      return $response;
    if ( substr($frame, 8185) == "\r\nEND\r\n" )
      return $response;
  }
}

?>
