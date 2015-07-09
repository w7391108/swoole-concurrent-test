<?php
class RestClient
{
    protected $url;
    protected $verb;
    protected $requestBody;
    protected $responseBody;
    protected $responseInfo;

    public function __construct($url = null, $verb = 'GET', $requestBody = null)
    {
        $this->url          = $url;
        $this->verb         = $verb;
        $this->requestBody  = $requestBody;
        $this->responseBody = null;
        $this->responseInfo = null;

        if ($this->requestBody !== null) {
            $this->buildParam();
        }
        $this->execute();
    }

    public function execute()
    {
        try
        {
            switch (strtoupper($this->verb)) {
                case 'GET':
                    $this->executeGet();
                    break;
                case 'POST':
                    $this->executePost();
                    break;
                case 'PUT':
                    $this->executePut();
                    break;
                case 'DELETE':
                    $this->executeDelete();
                    break;
                default:
                    throw new InvalidArgumentException('Current verb (' . $this->verb . ') is an invalid REST verb.');
            }
            return $this->responseBody;
        } catch (InvalidArgumentException $e) {
            curl_close($ch);
            throw $e;
        } catch (Exception $e) {
            curl_close($ch);
            throw $e;
        }

    }

    public function buildParam($data = null)
    {
        $data = ($data !== null) ? $data : $this->requestBody;
        /*if (!is_array($data))
        {
        throw new Xz_Exception('Invalid data input for postBody.  Array expected');
        }
        //$data = array_merge(array('version' => 0.1), $data);
        $data = http_build_query($data, '', '&');*/
        $this->requestBody = $data;
    }

    protected function executeGet()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 2); //设置超时时间,单位秒
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = 'Accept: ' . $this->requestBody;
        curl_setopt($curl, CURLOPT_HTTPHEADER, array($headers));
        $this->responseBody = curl_exec($curl);
        $this->responseInfo = curl_getinfo($curl);
        curl_close($curl);
    }

    protected function executePost()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 2); //设置超时时间,单位秒
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->requestBody);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $this->responseBody = curl_exec($curl);
        // var_dump($this->responseBody);
        $this->responseInfo = curl_getinfo($curl);
        // var_dump($this->responseInfo);
        curl_close($curl);
    }

    protected function executePut()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 2); //设置超时时间,单位秒
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($this->requestBody)));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->requestBody);
        $this->responseBody = curl_exec($curl);
        $this->responseInfo = curl_getinfo($curl);
        curl_close($curl);
    }

    protected function executeDelete()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 2); //设置超时时间,单位秒
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->responseBody = curl_exec($curl);
        $this->responseInfo = curl_getinfo($curl);
        curl_close($curl);
    }

    public function __destruct()
    {
        $this->requestBody  = null;
        $this->verb         = 'GET';
        $this->responseBody = null;
        $this->responseInfo = null;
    }
}
$file     = realpath('/usr/local/var/www/test.txt'); //要上传的文件
$handle   = fopen($file, "r");
$contents = stream_get_contents($handle);
$fields   = $contents;
$rest     = new RestClient("http://X.X.X.X:7500/v1/tfs/T19tETBmWg1RCvBVdK", "PUT", $fields);
// $rest->buildParam($fields);
$a = $rest->execute();
var_dump($a);
