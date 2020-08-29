<?php
define('CURL_RETURN_JSON', 0); //返回类型为json 会自动把字符串转成json对象
define('CURL_RETURN_RAW', 1);  //返回类型为string
class Curl
{
    protected $url = "";
    protected $ch = null;
    protected $data = null;
    protected $header = null;

    public static function init($url)
    {
        $curl = new Curl($url);
        return $curl;
    }

    public function __construct($url)
    {
        $this->url = $url;
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $this->ch = $ch;
    }

    public function url($url)
    {
        $this->url = $url;
        curl_setopt($this->ch, CURLOPT_URL, $url);
        return $this;
    }

    public function head($head)
    {
        $this->header = $head;
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $head);
        return $this;
    }

    public function data($data)
    {
        $this->data = $data;
        return $this;
    }

    public function down()
    {
        curl_setopt($this->ch, CURLOPT_POST, false);
        $url_new = $this->url;
        $res = file_get_contents($url_new);
        if ($res === false) {
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
    public function get($returntype = CURL_RETURN_RAW)
    {
        curl_setopt($this->ch, CURLOPT_POST, false);
        $url_new = $this->url;
        $opts = array('http' =>
            array(
                'method' => 'GET',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'timeout' => 15 * 60
            )
        );
        if (!empty($this->header)) {
            $header = "";
            if (is_array($this->header)) {
                foreach ($this->header as $k=>$v) {
                    if (is_numeric($k)) {
                        $header .= $v."\r\n";
                    } else {
                        $header .= $k.": ".$v."\r\n";
                    }
                }
            } elseif (!empty($this->header)) {
                $header = trim($this->header)."\r\n";
            }
            $opts['http']['header'] .= $header;
        }
        if (!empty($this->data)) {
            $urls = parse_url($this->url);
            $params = $this->data;
            if (is_array($this->data)) {
                $params = http_build_query($this->data);
            }
            if (empty($urls['query'])) {
                $url_new = $this->url."?".$params;
            } else {
                $url_new = $this->url."&".$params;
            }
            curl_setopt($this->ch, CURLOPT_URL, $url_new);
        }
        $context = stream_context_create($opts);
        $res = file_get_contents($url_new, false, $context);
        if ($res === false) {
            $cont=curl_exec($this->ch);
            curl_close($this->ch);
            $res = $cont;
        }

        $res=mb_convert_encoding($res, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
        if ($returntype === CURL_RETURN_RAW) {
            return $res;
        } else {
            return json_decode($res, true);
        }
    }

    /**
     * @param int $returntype 返回类型 CURL_RETURN_RAW string | CURL_RETURN_JSON json对象
     * @return bool|mixed|string
     */
    public function post($returntype = CURL_RETURN_RAW)
    {
        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'timeout' => 15 * 60
            )
        );
        if (!empty($this->header)) {
            $header = "";
            if (is_array($this->header)) {
                foreach ($this->header as $k=>$v) {
                    if (is_numeric($k)) {
                        $header .= $v."\r\n";
                    } else {
                        $header .= $k.": ".$v."\r\n";
                    }
                }
            } elseif (!empty($this->header)) {
                $header = trim($this->header)."\r\n";
            }
            $opts['http']['header'] .= $header;
        }
        curl_setopt($this->ch, CURLOPT_POST, true);
        if (!empty($this->data)) {
            if (is_array($this->data)) {
                $params = http_build_query($this->data);
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
                $opts['http']['content'] = $params;
            } else {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->data);
                $opts['http']['content'] = $this->data;
            }
        }
        $context = stream_context_create($opts);
        $res = file_get_contents($this->url, false, $context);

        if ($res === false) {
            $cont=curl_exec($this->ch);
            curl_close($this->ch);
            $res = $cont;
        }

        $res=mb_convert_encoding($res, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
        if ($returntype === CURL_RETURN_RAW) {
            return $res;
        } else {
            return json_decode($res, true);
        }
    }
}
