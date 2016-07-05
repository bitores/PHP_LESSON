<?php
header("Content-Type: text/event-stream");
// header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies

//先访问的页面做设置
// ini_set("session.use_trans_sid",1);
ini_set("session.use_only_cookies",0);
ini_set("session.use_cookies",1);

// ini_set('session.cookie_path', '/');
// ini_set('session.cookie_domain', '.php.dev');
// ini_set('session.cookie_lifetime', '1800');

session_set_cookie_params(1800 , '/', '.php.dev');
//防止返回初始页产生新的session
if(isset($_GET["PHPSESSID"])){
   //设置当前的session为初始的session，session_id()一致即可
   session_id($_GET["PHPSESSID"]);

}else{
    session_start();
}
$i = 0;
while(true){
  // echo "data:".date("Y-m-d H:i:s")."\n\n";
  echo "data:". $i++."\n\n";
  @ob_flush();
  @flush();
  sleep(1);
  }