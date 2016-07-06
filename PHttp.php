<?php
/**
 * Http URL请求类
 *
 * 通过curl实现的快捷方便的接口请求类
 * 
 * <br>示例：<br>
 * 
 *  // 失败时再重试2次
 *  $curl = new Url(2);
 *
 *  // GET
 *  $rs = $curl->get('http://phalapi.oschina.mopaas.com/Public/demo/?service=Default.Index');
 *
 *  // POST
 *  $data = array('username' => 'dogstar');
 *  $rs = $curl->post('http://phalapi.oschina.mopaas.com/Public/demo/?service=Default.Index', $data);
 *
 */

class Http {

    /**
     * 最大重试次数
     */
    const MAX_RETRY_TIMES = 10;

	/**
	 * @var int $retryTimes 超时重试次数；注意，此为失败重试的次数，即：总次数 = 1 + 重试次数
	 */
    protected $retryTimes;

	/**
	 * @param int $retryTimes 超时重试次数，默认为1
	 */
    public function __construct($retryTimes = 1) {
        $this->retryTimes = $retryTimes < self::MAX_RETRY_TIMES 
            ? $retryTimes : self::MAX_RETRY_TIMES;
    }

    /**
     * GET方式的请求
     * @param string $url 请求的链接
     * @param int $timeoutMs 超时设置，单位：毫秒
     * @return string 接口返回的内容，超时返回false
     */
    public function get($url, $param=array(), $timeoutMs = 3000){
        if(!is_array($param)){
            throw new Exception("参数必须为array");
        }
        $p='';
        foreach($param as $key => $value){
            $p=$p.$key.'='.$value.'&';
        }
        if(preg_match('/\?[\d\D]+/',$url)){//matched ?c
            $p='&'.$p;
        }else if(preg_match('/\?$/',$url)){//matched ?$
            $p=$p;
        }else{
            $p='?'.$p;
        }
        $p=preg_replace('/&$/','',$p);
        $url=$url.$p;
        //echo $url;
        $rst=request($url, null, $timeoutMs);
        return $rst;
     }

    /**
     * POST方式的请求
     * @param string $url 请求的链接
     * @param array $data POST的数据
     * @param int $timeoutMs 超时设置，单位：毫秒
     * @return string 接口返回的内容，超时返回false
     */
    public function post($url, $param=array(), $timeoutMs = 3000){
        if(!is_array($param)){
            throw new Exception("参数必须为array");
        }

        $rst=request($url, $param, $timeoutMs);
        return $rst;
     }

    /**
     * 统一接口请求
     * @param string $url 请求的链接
     * @param array $param POST的数据
     * @param int $timeoutMs 超时设置，单位：毫秒
     * @return string 接口返回的内容，超时返回false
     */
    protected function request($url, $param, $timeoutMs = 3000) {
        $httph = curl_init();
        curl_setopt($httph, CURLOPT_URL, $url);

        curl_setopt($httph, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($httph, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($httph,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($httph, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");


        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeoutMs);

        if (!empty($param)) {
            curl_setopt($httph, CURLOPT_POST, 1);
            curl_setopt($httph, CURLOPT_POSTFIELDS, $param);
        }

        $curRetryTimes = $this->retryTimes;
        do {
            $rs = curl_exec($httph);
            $curRetryTimes--;
        } while($rs === FALSE && $curRetryTimes >= 0);

        curl_close($httph);

        return $rs;
    }






    /**
     * 增加静态函数
     */

    public static function AjaxPost($url, $parms){
        $url = $url . $parms; 
        if (($ch = curl_init($url)) == false) { 
            throw new Exception(sprintf("curl_init error for url %s.", $url)); 
        } 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_HEADER, 0); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 600); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        if (is_array($parms)) { 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data;')); 
        } 
        $postResult = @curl_exec($ch); 
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
        if ($postResult === false || $http_code != 200 || curl_errno($ch)) { 
            $error = curl_error($ch); 
            curl_close($ch); 
            throw new Exception("HTTP POST FAILED:$error"); 
        } else { 
            // $postResult=str_replace()("\xEF\xBB\xBF", '', $postResult); 
            switch (curl_getinfo($ch, CURLINFO_CONTENT_TYPE)) { 
                case 'application/json': 
                    $postResult = json_decode($postResult); 
                    break; 
            } 
            curl_close($ch); 
            return $postResult; 
        } 
    }

    public static function AjaxGet($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

}