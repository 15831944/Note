<?php

ini_set("memory_limit", "512M");
set_time_limit(0);
ignore_user_abort(true);
error_reporting(E_ERROR);

function discover($host, $timeout = 1)
{
    $data = "M-SEARCH * HTTP/1.1\r\nHOST: 239.255.255.250:1900\r\nMAN: \"ssdp:discover\"\r\nMX: 3\r\nST: ssdp:all\r\n\r\n";
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
    socket_connect($socket, $host, 1900);
    socket_send($socket, $data, strLen($data), 0);
    $buf = "";
    $from = "";
    $port = 0;
    @socket_recvfrom($socket, $buf, 1000, 0, $from, $port);
    socket_close($socket);
    return $buf;
}

function partition($list, $p)
{
    $listlen = count($list);
    $partlen = floor($listlen / $p);
    $partrem = $listlen % $p;
    $partition = array();
    $mark = 0;
    for ($px = 0; $px < $p; $px++)
    {
        $incr = ($px < $partrem) ? $partlen + 1 : $partlen;
        $partition[$px] = array_slice($list, $mark, $incr);
        $mark += $incr;
    }
    return $partition;
}

if ($argc != 6)
{
    echo "Usage: php $argv[0] ssdplist.txt newssdplist.txt threads reply_size resolves_per_reflector\n";
	echo "SSDP Filter Coded\n";
    die();
}

if (!file_exists("$argv[1]"))
{
    die("Invalid input file!\n");
}

$childcount = $argv[3];
$ReplySize = $argv[4];
$rpr = $argv[5];
$part = array();
$array = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$array = array_unique($array);
$part = partition($array, $childcount);
file_put_contents($argv[2], "");

for ($i = 0; $i < $childcount; $i ++)
{
    $pid = pcntl_fork();
    if ($pid == -1)
    {
        echo "Forking failed on loop $i\n";
        exit;
    } else if ($pid) {
        continue;
    } else {
        foreach ($part[$i] as $ip)
        {
            $addr = explode(" ", $ip);
            $arl = 0;
            for ($y = 0; $y <= $rpr; $y++)
            {
                $content = discover($addr[0]);
                $arl += strlen($content);
            }
            $alen = ($arl / $rpr);
            if ($alen != 0)
            {
                echo "$addr[0] - Average reply: $alen\n";
                if ($alen > $ReplySize)
                {
                    file_put_contents($argv[2], "$addr[0] $alen\r\n", FILE_APPEND);
                }
            } else {
                echo "$addr[0] - DEAD\n";
            }
        }
        die;
    }
}

for ($j = 0; $j < $childcount; $j++)
{
    $pid = pcntl_wait($status);
}
