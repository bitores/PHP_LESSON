# Simple-Proxy

###简单php透传代理脚本

版本: 1.0

最后更新: 2015-05-18

git@osc：   [http://git.oschina.net/atwal/php-simple-proxy](http://git.oschina.net/atwal/php-simple-proxy)

源码：      [http://git.oschina.net/atwal/php-simple-proxy/raw/master/simple_proxy.php](http://git.oschina.net/atwal/php-simple-proxy/raw/master/simple_proxy.php)

参考项目：  [http://github.com/cowboy/php-simple-proxy/](http://github.com/cowboy/php-simple-proxy/)

优化修改点：加上了异常处理，baseurl设置，会更安全，默认为jsonp格式

###GET请求参数

   url            - 经过 urlencoded 编码的远程地址
   mode           - 如果 mode=native ，内容会透传，如果忽略默认为JSON格式
   cb             - JSONP格式回调函数名，默认为spcb
   user_agent     - 请求头中的 `User-Agent:` 值，不传默认为本浏览器的User-Agent值
   send_cookies   - 如果 send_cookies=1 ，所有的cookies将被写入请求头
   send_session   - 如果 send_session=1 并且 send_cookies=1 ，SID cookie 将被写入请求头
   full_headers   - 如果是一个JSON格式的请求，并且 full_headers=1 ，在返回值中将包含完整的 header 信息
   full_status    - 如果是一个JSON格式的请求，并且 full_status=1, 在返回值中将包含完整的 cURL 状态信息，
                    否则只有 http_code 信息

###POST请求参数

   所有的 POST 请求参数会自动加到远程地址请求中

###JSON格式请求

   结果将会以JSON格式返回

   Request:

     > simple_proxy.php?url=http://example.com/

   Response:

     > { "contents": "<html>...</html>", "headers": {...}, "status": {...} }

   JSON对象属性:
     contents - (String) 远程请求返回的内容
     headers - (Object) 远程请求返回的header信息
     status - (Object) cURL返回的HTTP状态码

###JSONP格式请求

    结果将会以JSONP格式返回（只有 $enable_jsonp 设置为 true的时才生效）

    Request:

      > simple_proxy.php?url=http://example.com/&cb=foo

    Response:

      > foo({ "contents": "<html>...</html>", "headers": {...}, "status": {...} })

    JSON对象属性:
      同上面的json请求

###Native请求

    结果将会直接原样返回 （只有 $enable_native 设置为true时才生效）

    Request:

      > simple_proxy.php?url=http://example.com/&mode=native

    Response:

      > <html>...</html>

###设置项

    $enable_jsonp    - 是否启用JSONP格式返回。默认为true
    $enable_native   - 是否直接返回请求信息。建议用 $valid_url_regex 配置白名单来避免XSS攻击，
                       默认为false
    $valid_url_regex - 正则形式的白名单。默认允许所有网址
    $baseurl         - 如果是代理固定的请求地址，为了安全，可以设置 $baseurl 为要请求的地址，
                       配合url参数使用，这样最终用到的地址是 $baseurl . $url


###Ajax请求范例
    **GET请求**
    $.ajax({
        type:"GET",
        url:api_url+'?mode=native&url='+encodeURIComponent(OAPI_HOST+"/xxx/xxx?access_token="+access_token+"&yy=45"),
        data:{
        },
      error:function(err){
        alert('fail: ' + JSON.stringify(err));
      },
        success: function(da){
          alert('ticket fail: ' + JSON.stringify(da));
        },dataType:'json'
      }); 


    **POST请求**
    $.ajax({
      type:"POST",
      beforeSend: function(req) {
            req.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
        },
      url:api_url+'?mode=native&url='+ encodeURIComponent(OAPI_HOST+'/xx/yy?access_token='+access_token),
      data:JSON.stringify({
        xx:"xx",
        yy:"yy"
      }),
      error:function(err){
        alert(' fail: ' + JSON.stringify(err));
      },
      success: function(a){
        alert(' suc: ' + JSON.stringify(a));
      }
    });


###代码修改说明
  
    增加 https 协议转发支持
    增加对 请求头 的转发处理
    增加对 GET中url 参数编码处理
    增加对 请求数据的 转发处理
