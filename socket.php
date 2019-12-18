<?php
set_time_limit(0);


$ip = '127.0.0.1';
$port = 8888;

$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

socket_bind($sock, $ip, $port);
socket_listen($sock, 4);

echo "Server Started, Listen On $ip:$port\n";

$accept = socket_accept($sock);

socket_write($accept, "Hello World!\n", 8192);

$buf = socket_read($accept, 8192);

echo "Receive Msg： " . $buf . "\n";

socket_close($sock);
