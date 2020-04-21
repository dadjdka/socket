<?php
$fp = fsockopen("127.0.0.1",80,$errno,$errstr,1);

if(!$fp){

    echo($errno);
}else{
    $out = "GET / HTTP/1.1\r\n";
    $out .= "HOST:127.0.0.1:80\r\n\r\n";
  
    fwrite($fp,$out);
    
    while(!feof($fp)){
        echo fread($fp,512);
    }
    fclose($fp);
}