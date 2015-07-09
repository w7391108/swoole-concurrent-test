<?php
class Client
{
    private $client;

    private $totalTime = 0;

    private $totalNum = 0;

    private $readNum = 0;

    private $putNum = 0;

    private $type = 'write';

    private $readData = array();

    private $putData = array();

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->client    = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->client->on('Connect', array($this, 'onConnect'));
        $this->client->on('Receive', array($this, 'onReceive'));
        $this->client->on('Close', array($this, 'onClose'));
        $this->client->on('Error', array($this, 'onError'));
    }

    public function connect()
    {
        $fp = $this->client->connect("127.0.0.1", 9501, 1);
        if (!$fp) {
            echo "Error: {$fp->errMsg}[{$fp->errCode}]\n";
            return;
        }
    }
    public function onReceive($cli, $data)
    {
        // echo "--------client receive data:" . $data . "\n";
        $temp = explode("\n", $data);
        foreach ($temp as $data) {
            var_dump($data);
            if (!empty($data)) {
                list($data, $spendtime, $type) = explode("\t", $data);
                // if ($data == 'success') {
                $this->totalNum++;
                echo "you have done: $this->totalNum\n";
                echo "spendtime ({$type}):" . $spendtime . "\n";
                $this->totalTime += $spendtime;
                echo "totaltime :" . $this->totalTime . "\n";
                // }

                if ($this->type == 'write') {
                    if ($this->totalNum == ceil(1 / 6 * 1024 * 1024)) {
                        // $totalTime = microtime(true) - $startTime;
                        // echo "It takes : $totalTime\ns";
                        // $cli->send('close' . "\n");
                        $cli->close();
                    }
                    $cli->send("write" . "\n");
                } elseif ($this->type == 'read') {
                    if ($this->totalNum == count($this->readData)) {
                        // $totalTime = microtime(true) - $startTime;
                        // echo "It takes : $totalTime\ns";
                        // $cli->send('close' . "\n");
                        $cli->close();
                    }
                    $cli->send($this->readData[$this->totalNum + 1] . "\n");
                } elseif ($this->type == 'mix') {
                    switch ($type) {
                        case 'write':
                            $data = "write" . "\n";
                            $cli->send($data);
                            break;
                        case 'read':
                            $this->readNum++;
                            var_dump($this->readData[$this->readNum + 1]);
                            $cli->send($this->readData[$this->readNum + 1] . "\n");
                            break;
                        case 'mix':
                            $this->putNum++;
                            var_dump($this->putData[$this->putNum + 1]);
                            $cli->send("mix_" . $this->putData[$this->putNum + 1] . "\n");
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        // echo "Get Message From Server: {$data}\n";
    }
    public function onConnect($cli)
    {
        if ($this->type == 'read') {
            $file_name = "/home/wwwroot/swoole/logwrite_100.txt";
            $fp        = fopen($file_name, 'r');
            while (!feof($fp)) {
                $buffer           = fgets($fp, 4096);
                $this->readData[] = rtrim($buffer, "\n");
            }
            fclose($fp);
            $tmp = array_slice($this->readData, 0, 10000);
            foreach ($tmp as $value) {
                $data = $value . "\n";
                $cli->send($data);
            }
        } elseif ($this->type == 'write') {
            for ($i = 0; $i < 10000; $i++) {
                $data = $this->type . "\n";
                $cli->send($data);
            }
        } elseif ($this->type == 'mix') {
            //写：改：读 ＝ 1：3：6
            for ($i = 0; $i < 1000; $i++) {
                //写文件
                $data = "write" . "\n";
                $cli->send($data);
            }

            // //改文件
            $file_name = "/home/wwwroot/swoole/log.txt";
            $fp        = fopen($file_name, 'r');
            while (!feof($fp)) {
                $buffer          = fgets($fp, 4096);
                $this->putData[] = rtrim($buffer, "\n");
            }
            fclose($fp);
            $tmp = array_slice($this->putData, 0, 3000);
            foreach ($tmp as $value) {
                $data = "mix_" . $value . "\n";
                $cli->send($data);
            }

            //读文件
            $file_name = "/home/wwwroot/swoole/logwrite_10.txt";
            $fp        = fopen($file_name, 'r');
            while (!feof($fp)) {
                $buffer           = fgets($fp, 4096);
                $this->readData[] = rtrim($buffer, "\n");
            }
            fclose($fp);
            $tmp = array_slice($this->readData, 0, 6000);
            foreach ($tmp as $value) {
                $data = $value . "\n";
                $cli->send($data);
            }

        }

    }
    public function onClose($cli)
    {
        echo "It takes : $this->totalTime\n";
        // $cli->close(); // 1.6.10+ 不需要
        echo "close\n";
    }
    public function onError()
    {
    }
    public function send($data)
    {
        $this->client->send($data);
    }
    public function isConnected()
    {
        return $this->client->isConnected();
    }
}
$cli = new Client();
$cli->connect();
