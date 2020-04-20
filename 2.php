<?php

$conn = stream_socket_client("tcp://127.0.0.1:8000",$errno,$errstr,1);

if (!$conn){
    echo "$errstr($errno)<br/>";
}else{
    stream_socket_sendto($conn,"hello word");
    echo  stream_get_contents($conn);
    fclose($conn);
}