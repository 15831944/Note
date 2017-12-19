<?php

ini_set("memory_limit", "512M");
set_time_limit(0);
ignore_user_abort(true);
error_reporting(E_ERROR);

function partition($list, $p)
{
    $listlen = count( $list );
    $partlen = floor( $listlen / $p );
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

if ($argc != 4)
{
    echo "Usage: php $argv[0] input.txt output.txt threads\n";
	die;
}

if (!file_exists("$argv[1]"))
{
    die("Invalid input file!\n");
}

function cURL_GET($url)
{
    $cURL = curl_init();
    curl_setopt($cURL, CURLOPT_URL, $url);
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($cURL, CURLOPT_TIMEOUT, 5);
    $data = curl_exec($cURL);
    curl_close($cURL);
    return $data;
}

echo "XML-RPC List filter developed by Andy Quez - quezstresser.com\n";
$childcount = $argv[3];
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
        echo "failed to fork on loop $i of forking\n";
        exit;
    } else if ($pid)
    {
        continue;
    } else {
        foreach($part[$i] as $main)
        {
            list($forum, $url) = explode(' ', $main);
			$strResponse = cURL_GET($url);
            if (strpos($strResponse, 'XML-RPC server accepts POST requests only.') !== false)
            {
                file_put_contents($argv[2], $main."\r\n", FILE_APPEND);
            }
        }
        die;
    }
}

for ($j = 0; $j < $childcount; $j++)
{
    $pid = pcntl_wait($status);
}
$prevcount = count(file($argv[1]));
$aftercount = count(file($argv[2]));
$final = ($prevcount - $aftercount);
echo "Succesfully removed $final bad reflectors!\n";
?>
