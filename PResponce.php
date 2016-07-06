<?php
/**
 * PResponse 响应类
 *
 * - 拥有各种结果返回状态 ，以及对返回结果 的格式化
 * - 其中：200成功，400非法请求，500服务器错误
 *
 */

class Response {

	/**
	 * @var int $ret 返回状态码，其中：200成功，400非法请求，500服务器错误
	 */
    protected $code = 200;
    
    /**
     * @var array 待返回给客户端的数据
     */
    protected $data = array();
    
    /**
     * @var string $msg 错误返回信息
     */
    protected $msg = '';
    
    /**
     * @var array $headers 响应报文头部
     */
    protected $headers = array();

    /** ------------------ setter ------------------ **/

    /**
     * 设置返回状态码
     * @param int $ret 返回状态码，其中：200成功，400非法请求，500服务器错误
     * @return Response
     */
    public function setCode($code) {
    	$this->code = $code;
    	return $this;
    }
    
    /**
     * 设置返回数据
     * @param array/string $data 待返回给客户端的数据，建议使用数组，方便扩展升级
     * @return Response
     */
    public function setData($data) {
    	$this->data = $data;
    	return $this;
    }
    
    /**
     * 设置错误信息
     * @param string $msg 错误信息
     * @return Response
     */
    public function setMsg($msg) {
    	$this->msg = $msg;
    	return $this;
    }
    
    /**
     * 添加报文头部
     * @param string $key 名称
     * @param string $content 内容
     */
    public function addHeaders($key, $content) {
    	$this->headers[$key] = $content;
    }

    /** ------------------ 结果输出 ------------------ **/

    /**
     * 结果输出
     */
    public function output() {
        // 跨域时设置
        // $this->setCrossHeader();
    	$this->handleHeaders($this->headers);

        $rs = $this->getResult();

    	echo $this->ajaxRenter($rs);
    }
    
    /** ------------------ getter ------------------ **/
    
    public function getResult() {
        $rs = array(
            'code' => $this->code,
            'data' => $this->data,
            'msg' => $this->msg,
        );

        return $rs;
    }

	/**
	 * 获取头部
	 * 
	 * @param string $key 头部的名称
	 * @return string/array 对应的内容，不存在时返回NULL，$key为NULL时返回全部
	 */
    public function getHeaders($key = NULL) {
        if ($key === NULL) {
            return $this->headers;
        }

        return isset($this->headers[$key]) ? $this->headers[$key] : NULL;
    }

    /** ------------------ 内部方法 ------------------ **/

    protected function handleHeaders($headers) {
    	foreach ($headers as $key => $content) {
    		header($key . ': ' . $content);
    	}
    }

    protected function setCrossHeader(){
        header("Content-Type:text/html;charset=utf-8");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies

        //先访问的页面做设置
        ini_set("session.use_trans_sid",1);
        ini_set("session.use_only_cookies",0);
        ini_set("session.use_cookies",1);

        ini_set('session.cookie_path', '/');
        ini_set('session.cookie_domain', '.php.dev');
        ini_set('session.cookie_lifetime', '1800');

        // session_set_cookie_params(1800 , '/', '.php.dev');
        //防止返回初始页产生新的session
        if(isset($_GET["PHPSESSID"])){
           //设置当前的session为初始的session，session_id()一致即可
           session_id($_GET["PHPSESSID"]);

        }else{
            session_start();
        }
    }

    /**
     * 格式化需要输出返回的结果
     *
     * @param array $result 待返回的结果数据
     *
     * @see Response::getResult()
     */
    public function ajaxRenter($result) {
        echo json_encode($result);
    }

    public function jsonpReturn($result, $callback) {
        echo $callback . '(' . json_encode($result) . ')';
    }
}
