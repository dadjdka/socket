<?php
namespace dream;

set_time_limit(0);
class DreamChat{

    protected $master = null; //服务端
    protected $connectPool = [];
    protected $handPool = []; // http 升级websocket池

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
           //阻塞


           \socket_select($sockets,$write,$except,60);

           foreach ($sockets as $socket){
               if ($sockets == $this->master){
                   //连接成功
                  $this->connectPool[] = $client = \socket_accept($this->master);
                  $keyArr = \array_keys($this->connectPool,$client);
                  $key = end($keyArr);
                  $this->handPool[$key] = false;
               }else{
                  $length = \socket_recv($socket,$buffer,1024,0);

                  if ($length < 1)
                  {
                      $this->close($socket);
                  }else{
                      $key = \array_search($socket,$this->connectPool);

                      if ($this->handPool[$key] == false){
                          $this->handShake($socket,$buffer,$key);
                      }else{
                          //封帧
                         $message = $this->deFrame($buffer);
                         //解帧
                         $message = $this->enFrame($message);
                         $this->send($message,$key);
                      }
                  }
               }
           }
       }
    }

    //客户端断开连接方法

    public function close($socket)
    {
        $key = \array_search($socket,$this->connectPool);
        unset($this->connectPool[$key]);
        unset($this->handPool[$key]);
        socket_close($socket);
    }

    //http 升级websocket  (握手)

    public function handShake($socket,$buffer,$key){

        //截取Sec-WebSocket-Key的值并加密，其中$key后面的一部分258EAFA5-E914-47DA-95CA-C5AB0DC85B11字符串应该是固定的
        $buf  = substr($buffer,strpos($buffer,'Sec-WebSocket-Key:')+18);
        $seckey  = trim(substr($buf,0,strpos($buf,"\r\n")));
        $new_key = base64_encode(sha1($key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",true));
        //按照协议组合信息进行返回
        $new_message = "HTTP/1.1 101 Switching Protocols\r\n";
        $new_message .= "Upgrade: websocket\r\n";
        $new_message .= "Sec-WebSocket-Version: 13\r\n";
        $new_message .= "Connection: Upgrade\r\n";
        $new_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n\r\n";
        socket_write($socket,$new_message,strlen($new_message));
        $this->handPool[$key] = true;
    }

    //数据解帧
    public function enFrame($str,$key){
        $mask = array();

        $data = '';

        $msg = unpack('H*',$str);

        $head = substr($msg[1],0,2);

        if ($head == '81' && !isset($this->slen[$key])) {

            $len=substr($msg[1],2,2);

            $len=hexdec($len);//把十六进制的转换为十进制

            if(substr($msg[1],2,2)=='fe'){

                $len=substr($msg[1],4,4);

                $len=hexdec($len);

                $msg[1]=substr($msg[1],4);

            }else if(substr($msg[1],2,2)=='ff'){

                $len=substr($msg[1],4,16);

                $len=hexdec($len);

                $msg[1]=substr($msg[1],16);

            }

            $mask[] = hexdec(substr($msg[1],4,2));

            $mask[] = hexdec(substr($msg[1],6,2));

            $mask[] = hexdec(substr($msg[1],8,2));

            $mask[] = hexdec(substr($msg[1],10,2));

            $s = 12;

            $n=0;

        }else if($this->slen[$key] > 0){

            $len=$this->slen[$key];

            $mask=$this->ar[$key];

            $n=$this->n[$key];

            $s = 0;

        }



        $e = strlen($msg[1])-2;

        for ($i=$s; $i<= $e; $i+= 2) {

            $data .= chr($mask[$n%4]^hexdec(substr($msg[1],$i,2)));

            $n++;

        }

        $dlen=strlen($data);



        if($len > 255 && $len > $dlen+intval($this->sjen[$key])){

            $this->ar[$key]=$mask;

            $this->slen[$key]=$len;

            $this->sjen[$key]=$dlen+intval($this->sjen[$key]);

            $this->sda[$key]=$this->sda[$key].$data;

            $this->n[$key]=$n;

            return false;

        }else{

            unset($this->ar[$key],$this->slen[$key],$this->sjen[$key],$this->n[$key]);

            $data=$this->sda[$key].$data;

            unset($this->sda[$key]);

            return $data;

        }

    }

    //封帧
    public function deFrame($msg){
        $frame = array();

        $frame[0] = '81';

        $len = strlen($msg);

        if($len < 126){

            $frame[1] = $len<16?'0'.dechex($len):dechex($len);

        }else if($len < 65025){

            $s=dechex($len);

            $frame[1]='7e'.str_repeat('0',4-strlen($s)).$s;

        }else{

            $s=dechex($len);

            $frame[1]='7f'.str_repeat('0',16-strlen($s)).$s;

        }

        $frame[2] = $this->ord_hex($msg);

        $data = implode('',$frame);

        return pack("H*", $data);
    }

    function ord_hex($data)  {

        $msg = '';

        $l = strlen($data);

        for ($i= 0; $i<$l; $i++) {

            $msg .= dechex(ord($data{$i}));

        }

        return $msg;

    }

    //发送消息
    public function send($message){
        foreach ($this->connectPool as $socket){
            if ($socket != $this->master){
                socket_write($socket,$message,strlen($message));
            }
        }
    }
}