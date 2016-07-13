<?PHP
error_reporting(0);
// 简单php透传代理脚本
//
// 版本: 1.0
//
// 最后更新: 2015-05-18
//
// git@osc：   http://git.oschina.net/atwal/php-simple-proxy
// 源码：      http://git.oschina.net/atwal/php-simple-proxy/raw/master/simple_proxy.php
// 参考项目：  http://github.com/cowboy/php-simple-proxy/
// 优化修改点：加上了异常处理，baseurl设置，会更安全，默认为jsonp格式
//
// GET请求参数
//
//    url            - 经过 urlencoded 编码的远程地址
//    mode           - 如果 mode=native ，内容会透传，如果忽略默认为JSON格式
//    cb             - JSONP格式回调函数名，默认为spcb
//    user_agent     - 请求头中的 `User-Agent:` 值，不传默认为本浏览器的User-Agent值
//    send_cookies   - 如果 send_cookies=1 ，所有的cookies将被写入请求头
//    send_session   - 如果 send_session=1 并且 send_cookies=1 ，SID cookie 将被写入请求头
//    full_headers   - 如果是一个JSON格式的请求，并且 full_headers=1 ，在返回值中将包含完整的 header 信息
//    full_status    - 如果是一个JSON格式的请求，并且 full_status=1, 在返回值中将包含完整的 cURL 状态信息，
//                     否则只有 http_code 信息
//
// POST请求参数
//
//    所有的 POST 请求参数会自动加到远程地址请求中
//
// JSON格式请求
//
//    结果将会以JSON格式返回
//
//    Request:
//
//      > simple_proxy.php?url=http://example.com/
//
//    Response:
//
//      > { "contents": "<html>...</html>", "headers": {...}, "status": {...} }
//
//    JSON对象属性:
//      contents - (String) 远程请求返回的内容
//      headers - (Object) 远程请求返回的header信息
//      status - (Object) cURL返回的HTTP状态码
//
// JSONP格式请求
//
//     结果将会以JSONP格式返回（只有 $enable_jsonp 设置为 true的时才生效）
//
//     Request:
//
//       > simple_proxy.php?url=http://example.com/&cb=foo
//
//     Response:
//
//       > foo({ "contents": "<html>...</html>", "headers": {...}, "status": {...} })
//
//     JSON对象属性:
//       同上面的json请求
//
// Native请求
//
//     结果将会直接原样返回 （只有 $enable_native 设置为true时才生效）
//
//     Request:
//
//       > simple_proxy.php?url=http://example.com/&mode=native
//
//     Response:
//
//       > <html>...</html>
//
// 设置项
//
//   $enable_jsonp    - 是否启用JSONP格式返回。默认为true
//   $enable_native   - 是否直接返回请求信息。建议用 $valid_url_regex 配置白名单来避免XSS攻击，
//                      默认为false
//   $valid_url_regex - 正则形式的白名单。默认允许所有网址
//   $baseurl         - 如果是代理固定的请求地址，为了安全，可以设置 $baseurl 为要请求的地址，
//                      配合url参数使用，这样最终用到的地址是 $baseurl . $url
//
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