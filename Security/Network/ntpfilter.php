<?php
    ini_set("memory_limit","512M");
    set_time_limit(0);
    ignore_user_abort(true);
    error_reporting(E_ERROR);

    if (threadfound($argv) == true) {
        die(thread($argv[1], $argv[2], $argv[3]));
    } else {
        die(start_threading($argv[1], $argv[2], $argv[3], $argv[4]));
    }

    function MSEARCH($host, $timeout = 1)
    {
        $data = "\x17\x00\x03\x2a\x00\x00\x00\x00";
        $socket  = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
        socket_connect($socket, $host, 123);
        socket_send($socket, $data, strLen($data), 0);
        $buf = "";
        $from = "";
        $port = 0;
        @socket_recvfrom($socket, $buf, 1000, 0, $from, $port);
        socket_close($socket);
        return $buf;
    }

    function thread($ip, $output, $responselength)
    {
        $len = strlen(MSEARCH($ip, 1));
        if ($len>=$responselength)
        {
            addentry($output, $ip.' '.$len);
            print("\n".$ip." ".$len." [x".round($len/108, 2)."]");
        }
    }

    function start_threading($input, $output, $responselength, $maxthreads)
    {
        $self = basename($_SERVER["SCRIPT_FILENAME"], '.php').'.php';
        $usage = "Usage: php {$self} [Input.txt] [Output.txt] [Response Size (400)] [Threads(200)]";
        if (strlen($input) == 0) {
          $error = "Error: Invalid Filename!";
        }
        if (strlen($output) == 0) {
            $error.= "\nError: Invalid Filename!";
        }
        if (is_numeric($responselength) == false) {
            $error.= "\nError: Invalid Response Length!";
        }
        if ($maxthreads<1 || $maxthreads > 1000) {
            $error.= "\nError: Invalid Threads!";
        }
        if (strlen($error) >= 1) {
            die($error."\n".$usage."\n");
            exit();
        }
        print("\nNTP Filter\nCoded\n\n");
        $threads = 0;
        $threadarr = array();
        $j = 0;
        $handle = fopen($input, "r");
        while (!feof($handle))
        {
            $line = fgets($handle, 4096);
            if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $line, $match))
            {
                if (filter_var($match[0], FILTER_VALIDATE_IP))
                {
                    $ip = $match[0];
                    JMP:
                    if ($threads < $maxthreads)
                    {
                        $pipe[$j] = popen('php'.' '.$self.' '.$ip.' '.$output.' '.$responselength.' '.'THREAD', 'w');
                        $threadarr[] = $j;
                        $j = $j + 1;
                        $threads = $threads + 1;
                    } else {
                        usleep(50000);
                        foreach ($threadarr as $index)
                        {
                            pclose($pipe[$index]);
                            $threads = $threads - 1;
                        }
                        $j = 0;
                        unset($threadarr);
                        goto JMP;
                    }
                }
            }
        }
        fclose($handle);
    }

    function threadfound(array $argv)
    {
        $thread = false;
        foreach ($argv as $arg)
        {
            if ($arg == 'THREAD')
            {
                $thread = true;
                break;
            }
        }
        return $thread;
    }

    function addentry($file, $entry)
    {
        if (!file_exists($file))
        {
            touch($file);
            chmod($file, 0755);
        }
        $fh = fopen($file, 'a') or die("Can't open file: ".$file);
        fwrite($fh, "\n".$entry);
        fclose($fh);
    }
?>
