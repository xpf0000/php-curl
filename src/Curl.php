<?php
define ('CURL_RETURN_JSON', 0); //返回类型为json 会自动把字符串转成json对象
define ('CURL_RETURN_RAW', 1);  //返回类型为string
class Curl
{
    protected $u = "";
    protected $ch = null;
    protected $d = null;
    protected $h = null;
    protected $f = null;
    protected $timeout = 60;
    protected $rangeStart = 0;
    protected $rangeEnd = 0;
    protected $curlOnly = false;

    public static function init($url){
        $curl = new Curl($url);
        return $curl;
    }

    public function __construct($url)
    {
        $this->u = $url;
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $this->ch = $ch;
    }

    public function curlOnly($only){
        $this->curlOnly = $only;
        return $this;
    }

    public function url($url){
        $this->u = $url;
        curl_setopt($this->ch, CURLOPT_URL, $url);
        return $this;
    }

    public function head($head){
        $this->h = $head;
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $head);
        return $this;
    }

    public function data($data){
        $this->d = $data;
        return $this;
    }

    /**
     * @param $file [
     * "upload"=>[
     *  "name"=>"",
     *  "type"=>"",
     *  "file"=>"",
     * ]
     * ]
     * @return $this
     */
    public function formFile($file){
        $this->f = $file;
        return $this;
    }

    public function timeout($time){
        $this->timeout = $time;
        return $this;
    }

    public function range($start, $end){
        $this->rangeStart = $start;
        $this->rangeEnd = $end;
        return $this;
    }

    public function down(){
        curl_setopt($this->ch, CURLOPT_POST, false);
        $url_new = $this->u;
        $opts = [
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http' => [
                'method' => 'GET',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'timeout' => $this->timeout
            ]
        ];
        $res = false;
        if (!$this->curlOnly) {
            if ($this->rangeEnd > 0 && $this->rangeEnd > $this->rangeStart) {
                curl_setopt($this->ch, CURLOPT_RANGE, "{$this->rangeStart}-{$this->rangeEnd}");
                $res = file_get_contents($url_new, false, stream_context_create($opts), $this->rangeStart, $this->rangeEnd);
            } else {
                $res = file_get_contents($url_new, false, stream_context_create($opts));
            }
        } else {
            if ($this->rangeEnd > 0 && $this->rangeEnd > $this->rangeStart) {
                curl_setopt($this->ch, CURLOPT_RANGE, "{$this->rangeStart}-{$this->rangeEnd}");
            }
        }
        if($res === false)
        {
            $cont=curl_exec($this->ch);
            curl_close($this->ch);
            $res = $cont;
        }
        return $res;
    }

    /**
     * @param int $returntype 返回类型 CURL_RETURN_RAW string | CURL_RETURN_JSON json对象
     * @return bool|mixed|string
     */
    public function get($returntype = CURL_RETURN_RAW){
        curl_setopt($this->ch, CURLOPT_POST, false);
        $url_new = $this->u;
        $opts = [
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http' => [
                'method' => 'GET',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'timeout' => $this->timeout
            ]
        ];
        if(!empty($this->h))
        {
            $header = "";
            if(is_array($this->h))
            {
                foreach ($this->h as $k=>$v)
                {
                    if(is_numeric($k))
                    {
                        $header .= $v."\r\n";
                    }
                    else
                    {
                        $header .= $k.": ".$v."\r\n";
                    }
                }
            }
            else if(!empty($this->h))
            {
                $header = trim($this->h)."\r\n";
            }
            $opts['http']['header'] .= $header;
        }
        if(!empty($this->d))
        {
            $urls = parse_url($this->u);
            $params = $this->d;
            if(is_array($this->d))
            {
                $params = http_build_query($this->d);
            }
            if(empty($urls['query']))
            {
                $url_new = $this->u."?".$params;
            }
            else
            {
                $url_new = $this->u."&".$params;
            }
            curl_setopt($this->ch, CURLOPT_URL, $url_new);
        }
        $res = false;
        if (!$this->curlOnly) {
            $context = stream_context_create($opts);
            if ($this->rangeEnd > 0 && $this->rangeEnd > $this->rangeStart) {
                $res = file_get_contents($url_new, false, $context, $this->rangeStart, $this->rangeEnd);
            } else {
                $res = file_get_contents($url_new, false, $context);
            }
        } else {
            if ($this->rangeEnd > 0 && $this->rangeEnd > $this->rangeStart) {
                curl_setopt($this->ch, CURLOPT_RANGE, "{$this->rangeStart}-{$this->rangeEnd}");
            }
        }
        if($res === false)
        {
            $cont=curl_exec($this->ch);
            curl_close($this->ch);
            $res = $cont;
        }

        $res=mb_convert_encoding($res, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');

        return $returntype === CURL_RETURN_RAW ? $res : json_decode($res,true);
    }

    /**
     * @param int $returntype 返回类型 CURL_RETURN_RAW string | CURL_RETURN_JSON json对象
     * @return bool|mixed|string
     */
    public function post($returntype = CURL_RETURN_RAW){
        $opts = [
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'timeout' => $this->timeout
            ]
        ];
        if(!empty($this->h))
        {
            $header = "";
            if(is_array($this->h))
            {
                foreach ($this->h as $k=>$v)
                {
                    if(is_numeric($k))
                    {
                        $header .= $v."\r\n";
                    }
                    else
                    {
                        $header .= $k.": ".$v."\r\n";
                    }
                }
            }
            else if(!empty($this->h))
            {
                $header = trim($this->h)."\r\n";
            }
            $opts['http']['header'] .= $header;
        }
        curl_setopt($this->ch, CURLOPT_POST, true);
        if(!empty($this->d))
        {
            if(is_array($this->d))
            {
                $params = http_build_query($this->d);
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
                $opts['http']['content'] = $params;
            }
            else
            {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->d);
                $opts['http']['content'] = $this->d;
            }
        }
        $res = false;
        if (!$this->curlOnly) {
            $context = stream_context_create($opts);
            if ($this->rangeEnd > 0 && $this->rangeEnd > $this->rangeStart) {
                $res = file_get_contents($this->u, false, $context, $this->rangeStart, $this->rangeEnd);
            } else {
                $res = file_get_contents($this->u, false, $context);
            }
        } else {
            if ($this->rangeEnd > 0 && $this->rangeEnd > $this->rangeStart) {
                curl_setopt($this->ch, CURLOPT_RANGE, "{$this->rangeStart}-{$this->rangeEnd}");
            }
        }
        if($res === false)
        {
            $cont=curl_exec($this->ch);
            curl_close($this->ch);
            $res = $cont;
        }

        $res=mb_convert_encoding($res, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');

        return $returntype === CURL_RETURN_RAW ? $res : json_decode($res,true);
    }

    /** 文件上传方法
     * @param int $returntype 返回类型 CURL_RETURN_RAW string | CURL_RETURN_JSON json对象
     * @return bool|mixed|string
     */
    public function upload($returntype = CURL_RETURN_RAW){
        curl_setopt($this->ch, CURLOPT_POST, true);
        if(!empty($this->d)) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->d);
        }
        $cont=curl_exec($this->ch);
        curl_close($this->ch);
        $res = $cont;

        $res=mb_convert_encoding($res, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');

        return $returntype === CURL_RETURN_RAW ? $res : json_decode($res,true);
    }

    /** 模拟post提交 可以自定义 文件名称和文件的Content-Type 方便测试上传漏洞
     * @param int $returntype
     * @return mixed|string
     */
    public function formUpload($returntype = CURL_RETURN_RAW) {
        $delimiter = uniqid();
        $data = '';
        if (!empty($this->d)) {
            foreach ($this->d as $name => $content) {
                $data .= "--" . $delimiter . "\r\n"
                    . 'Content-Disposition: form-data; name="' . $name . "\"\r\n\r\n"
                    . $content . "\r\n";
            }
        }
        if (!empty($this->f)) {
            foreach ($this->f as $name => $file) {
                $fileData = is_string($file['file']) ? file_get_contents($file['file']) : $file['file'];
                $data .= "--" . $delimiter . "\r\n"
                    . 'Content-Disposition: form-data; name="'.$name.'"; filename="' . $file['name'] . '"' . "\r\n"
                    . 'Content-Type:'.$file['type']."\r\n\r\n";
                $data .= $fileData . "\r\n";
            }
        }
        $data .= "--" . $delimiter . "--\r\n";

        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
            "Content-Type: multipart/form-data; boundary=" . $delimiter,
            "Content-Length: " . strlen($data)
        ]);

        $cont=curl_exec($this->ch);
        curl_close($this->ch);
        $res = $cont;

        $res=mb_convert_encoding($res, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');

        return $returntype === CURL_RETURN_RAW ? $res : json_decode($res,true);
    }

}
