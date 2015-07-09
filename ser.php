<?php
require "curl.php";
// Server
class Server
{
    private $serv;

    public function __construct()
    {
        $this->serv = new swoole_server("0.0.0.0", 9501);
        $this->serv->set(array(
            'worker_num'      => 8,
            'daemonize'       => false,
            'max_request'     => 10000,
            'dispatch_mode'   => 3,
            'debug_mode'      => 1,
            'task_worker_num' => 100,
            'open_eof_check'  => true,
            'package_eof'     => "\n",
        ));

        $this->serv->on('Start', array($this, 'onStart'));
        $this->serv->on('Connect', array($this, 'onConnect'));
        $this->serv->on('Receive', array($this, 'onReceive'));
        $this->serv->on('Close', array($this, 'onClose'));
        $this->serv->on('Task', array($this, 'onTask'));
        $this->serv->on('Finish', array($this, 'onFinish'));
        $this->serv->start();
    }

    public function onStart($serv)
    {
        echo "Start\n";
    }

    public function onConnect($serv, $fd, $from_id)
    {
        echo "Connect successful";
    }

    public function onReceive(swoole_server $serv, $fd, $from_id, $data)
    {
        $tmp = explode("\n", $data);
        foreach ($tmp as $data) {
            if (!empty($data)) {
                $temp = '';
                if (!empty($data)) {
                    $temp = substr($data, 0, 4);
                }
                // var_dump($temp);
                if (trim($data) == 'write' && !empty($data)) {
                    $task = array(
                        'fd'   => $fd,
                        'data' => "",
                        'type' => "write",
                    );
                    $serv->task($task);
                } elseif (trim($data == 'close')) {
                } elseif (trim($temp == 'mix_')) {
                    $string = str_replace("mix_", "", $data);
                    $task   = array(
                        'fd'   => $fd,
                        'data' => trim($string),
                        'type' => 'mix',
                    );
                    $serv->task($task);
                } else {
                    $task = array(
                        'fd'   => $fd,
                        'data' => trim($data),
                        'type' => 'read',
                    );
                    $serv->task($task);
                }
            }
        }
        // echo "Get Message From Client {$fd}:{$data}\n";
    }

    public function onClose($serv, $fd, $from_id)
    {
        echo "Client {$fd} close connection\n";
    }

    public function onTask($serv, $task_id, $from_id, $param)
    {
        $startTime = microtime(true);
        $a         = '';
        if ($param['type'] == 'write') {
            $file     = realpath('/usr/local/var/www/test.txt'); //要上传的文件
            $handle   = fopen($file, "r");
            $contents = stream_get_contents($handle);
            $fields   = $contents;
            $rest     = new RestClient("http://x.x.x.x:7500/v1/tfs", "POST", $fields);
            $a        = $rest->execute();
        } elseif ($param['type'] == 'mix') {
            $file     = realpath('/usr/local/var/www/test.txt'); //要上传的文件
            $handle   = fopen($file, "r");
            $contents = stream_get_contents($handle);
            $fields   = $contents;
            $rest     = new RestClient("http://x.x.x.x:7500/v1/tfs/{$param['data']}", "PUT", $fields);
            $a        = $rest->execute();
// $rest->buildParam($fields);
        } elseif ($param['type'] == 'read') {
            // $file_name = "/usr/local/var/www/swoole/logwrite_10.txt";
            // $fp        = fopen($file_name, 'r');
            // while (!feof($fp)) {
            // $buffer = fgets($fp, 4096);
            $rest = new RestClient("http://x.x.x.x:7500/v1/tfs/{$param['data']}", "GET", '');
            $a    = $rest->execute();
        }

        // $rest->buildParam($fields);

        $endTime = microtime(true);

        $a      = ($a === false) ? "fail" : "success";
        $result = array(
            'fd'        => $param['fd'],
            'data'      => $a,
            'spendtime' => ($endTime - $startTime) * 1000,
            'type'      => $param['type'],
        );
        return $result;
    }
    public function onFinish($serv, $task_id, $param)
    {
        $result = json_decode($param['data'], true);
        if ($param['type'] == 'write') {
            $open = fopen("/usr/local/var/www/swoole/logwrite_10.txt", "a");
            fwrite($open, $result['TFS_FILE_NAME'] . "\r\n");
            fclose($open);
        }
        $serv->send($param['fd'], $param['data'] . "\t" . $param['spendtime'] . "\t" . $param['type'] . "\n");
    }
}
// 启动服务器
$server = new Server();
