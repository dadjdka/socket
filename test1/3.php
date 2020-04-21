<?php
$socket = stream_socket_server("udp://127.0.0.1:1113",$errno,$errstr,STREAM_SERVER_BIND);

if (!$socket){
    dir("$errstr($errno)");
}else{
    do{
        $data = stream_socket_recvfrom($socket,1024,0,$pree);
        stream_socket_sendto($socket,$data,0,$pree);
    }while($data !== false);

}