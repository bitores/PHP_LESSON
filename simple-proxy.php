<?PHP
error_reporting(0);

// ############################################################################

// 根据需要修改下面的配置项，配置项说明见上面的说明文字

$enable_jsonp    = false;//启用true:JSONP
$enable_native   = true;
$valid_url_regex = '/.*/';
$baseurl = '';

// ############################################################################

function getallheaders() 
{ 
    $headers = array();
   // foreach ($_SERVER as $name => $value) 
   // { 
   //     if (substr($name, 0, 5) == 'HTTP_') 
   //     { 
   //          $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
   //         $headers[] = $key.':'.$value;
   //     } 
   // } 

   //  if (isset($_SERVER['PHP_AUTH_DIGEST'])) { 
   //      $headers[] = 'AUTHORIZATION'.':'.$_SERVER['PHP_AUTH_DIGEST']; 
   //  } elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) { 
   //      $headers[] = 'AUTHORIZATION'.':'.base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']); 
   //  } 
   //  if (isset($_SERVER['CONTENT_LENGTH'])) { 
   //      $headers[] = 'Content-Length'.':'.$_SERVER['CONTENT_LENGTH']; 
   //  } 
    if (isset($_SERVER['CONTENT_TYPE'])) { 
        $headers[] = 'Content-Type'.':'.$_SERVER['CONTENT_TYPE']; 
    }


   return $headers; 
} 

$url = isset($_GET['url']) ? $_GET['url'] : '';

$url = $baseurl . rawurldecode($url);

if (!$url) {
    // Passed url not specified.
    $contents = 'ERROR: url not specified ' ;
    $status = array('http_code' => 'ERROR');
} else if ( !preg_match($valid_url_regex, $url) ) {
    // Passed url doesn't match $valid_url_regex.
    $contents = 'ERROR: invalid url';
    $status = array( 'http_code' => 'ERROR' );
} else {

    $ch = curl_init($url);
    $headers = getallheaders();
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);

    curl_setopt( $ch, CURLOPT_HEADER, true );//丢掉头信息0 需要头部1
    

    // curl_setopt($ch, CURLOPT_NOBODY, 0);//这行不能要，如果添上，那么在遇到302重定向的时候就会得不到真正的请求url
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );//将结果保存成字符串
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10);//连接超时时间s
    curl_setopt( $ch, CURLOPT_TIMEOUT, 10);//执行超时时间s
    curl_setopt( $ch, CURLOPT_DNS_CACHE_TIMEOUT, 1800);//DNS解析缓存保存时间半小时

    if(substr($url, 0, 8) == "https://"){
        // 支持https
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    if (strtolower($_SERVER['REQUEST_METHOD']) == 'post' ) {
        // echo var_dump($GLOBALS['HTTP_RAW_POST_DATA']);
        // $_POST只能接收文档类型为Content-Type: application/x-www-form-urlencoded提交的数据
        curl_setopt( $ch, CURLOPT_POST, true );//启用POST数据
        // $curPost = is_array($_POST) ? http_build_query($_POST) : $_POST;
        // print_r($_POST);
        $curPost = $GLOBALS['HTTP_RAW_POST_DATA'];//is_array($_POST) ? json_encode($_POST) : $_POST;
        // $curPost = array(
        //         "touser"=>"manager6116",
        //         "agentid"=>"34008185",
        //         "msgtype"=>"text",
        //         "text"=>array("content"=>"jjjjj")
        //     );

        $str = json_encode($curPost);
        
        // curl_setopt ( $ch, CURLOPT_POST, count( $curPost ) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $curPost);//提交POST数据
        // echo "a". http_build_query($curPost);
    }
    

    if (isset($_GET['send_cookies'])) {
        $cookie = array();
        foreach ( $_COOKIE as $key => $value ) {
            $cookie[] = $key . '=' . $value;
        }
        if ( $_GET['send_session'] ) {
            $cookie[] = SID;
        }
        $cookie = implode( '; ', $cookie );

        curl_setopt( $ch, CURLOPT_COOKIE, $cookie );
    }

    // if(ini_get('open_basedir')==''&&ini_get('safe_mode'=='Off')){
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    // }
    
    curl_setopt( $ch, CURLOPT_USERAGENT, isset($_GET['user_agent']) ? $_GET['user_agent'] : $_SERVER['HTTP_USER_AGENT'] );

    ob_start();//打开缓冲区
    list( $header, $contents ) = preg_split( '/([\r\n][\r\n])\\1/', curl_exec( $ch ), 2 );
    $contents = str_replace(array("\n", "\r"), '', $contents);

    $status = curl_getinfo( $ch );

    if(curl_errno($ch)){
        echo "error:".curl_errno($ch);
    }
    ob_end_clean ();//关闭缓冲区
    curl_close( $ch );
}

// Split header text into an array.
$header_text = isset($header) ? preg_split( '/[\r\n]+/', $header ) : array();

if (isset($_GET['mode']) && $_GET['mode'] == 'native' ) {
    if ( !$enable_native ) {
        $contents = 'ERROR: invalid mode';
        $status = array( 'http_code' => 'ERROR' );
    }

    // Propagate headers to response.
    // echo "string" . $header;
    foreach ( $header_text as $header ) {
        if ( preg_match( '/^(?:Content-Type|Content-Language|Set-Cookie):/i', $header ) ) {
            header( $header );
        }
    }

    echo $contents;

} else {
    // $data will be serialized into JSON data.
    $data = array();

    // Propagate all HTTP headers into the JSON data object.
    if (isset($_GET['full_headers'])) {
        $data['headers'] = array();
        foreach ( $header_text as $header ) {
            preg_match( '/^(.+?):\s+(.*)$/', $header, $matches );
            if ( $matches ) {
                $data['headers'][ $matches[1] ] = $matches[2];
            }
        }
    }

    // Propagate all cURL request / response info to the JSON data object.
    if (isset($_GET['full_status'])) {
        $data['status'] = $status;
    } else {
        $data['status'] = array();
        $data['status']['http_code'] = $status['http_code'];
    }

    // Set the JSON data object contents, decoding it from JSON if possible.
    $decoded_json = json_decode( $contents );
    $data['contents'] = $decoded_json ? $decoded_json : $contents;

    // Generate appropriate content-type header.
    $is_xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' : false;
    header( 'Content-type: application/' . ( $is_xhr ? 'json' : 'x-javascript' ) );

    // Get JSONP callback.
    $jsonp_callback = $enable_jsonp && isset($_GET['cb']) ? $_GET['cb'] : 'spcb';

    // Generate JSON/JSONP string
    $json = json_encode( $data );

    echo $jsonp_callback ? "$jsonp_callback($json);" : $json;
}

?>