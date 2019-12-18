<?php
namespace dream;

class DreamChat{

    protected $master = null; //服务端
    protected $connectPool = [];

    public function __construct($ip,$port)
    {
        $this->startServer($ip,$port);
    }

    public function startServer($ip,$port)
    {
        //嵌套
        $this->connectPool[] = $this->master = \socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
       //链接
       \socket_bind($this->master,$ip,$port);
       //监听链接状态
       \socket_listen($this->master,1000);

       //服务器开启
       while (true){
           $sockets = $this->connectPool;
           $write = $except = null;
           \socket_select($sockets,$write,$except,60);

           foreach ($sockets as $socket){
               if ($sockets == $this->master){
                   //连接成功
                  $this->connectPool[] = $client = \socket_accept($this->master);

               }
           }
       }
    }
}