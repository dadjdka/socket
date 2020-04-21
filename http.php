<?php

 $socket = stream_socket_server("tcp://127.0.0.1:80",$errno,$errstr);


 if(!$socket){
     echo "$errstr";
 }else{
     for(;;){
         //阻塞
         $client = stream_socket_accept($socket,-1);

         if($client){
             $http = fread($client,1024);

             $content = "HTTP/1.1 200 ok\r\n
                Server:http_imooc/1.0.0\r\nContent-Length:
             ".strlen($http)."\r\n\r\n{$http}";

             fwrite($client,$content);
         }
         fclose($client);
     }
     fclose($socket);
 }