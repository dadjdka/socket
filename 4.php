<?php

$fp = stream_socket_client("udp://127.0.0.1:1113",$errno,$errstr);

if (!$fp){
    echo "$errno";
}else{
    fwrite($fp,"hello word");
//    echo fread($fp,1204);
    fclose($fp);
}