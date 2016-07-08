<?PHP

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

$enable_jsonp    = true;
$enable_native   = false;
$valid_url_regex = '/.*/';
$baseurl = '';

// ############################################################################

$url = isset($_GET['url']) ? $_GET['url'] : '';
$url = $baseurl . $url;

if (!$url) {
    // Passed url not specified.
    $contents = 'ERROR: url not specified';
    $status = array('http_code' => 'ERROR');
} else if ( !preg_match($valid_url_regex, $url) ) {
    // Passed url doesn't match $valid_url_regex.
    $contents = 'ERROR: invalid url';
    $status = array( 'http_code' => 'ERROR' );
} else {
    $ch = curl_init( $url );
    if (strtolower($_SERVER['REQUEST_METHOD']) == 'post' ) {
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $_POST );
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

    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $ch, CURLOPT_HEADER, true );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

    curl_setopt( $ch, CURLOPT_USERAGENT, isset($_GET['user_agent']) ? $_GET['user_agent'] : $_SERVER['HTTP_USER_AGENT'] );

    list( $header, $contents ) = preg_split( '/([\r\n][\r\n])\\1/', curl_exec( $ch ), 2 );
    $contents = str_replace(array("\n", "\r"), '', $contents);
    $status = curl_getinfo( $ch );

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
    foreach ( $header_text as $header ) {
        if ( preg_match( '/^(?:Content-Type|Content-Language|Set-Cookie):/i', $header ) ) {
            header( $header );
        }
    }

    print $contents;

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

    print $jsonp_callback ? "$jsonp_callback($json);" : $json;
}

?>