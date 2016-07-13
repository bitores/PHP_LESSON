enctype 与 Content-Type
Content-Type: multipart/form-data; boundary=---------------------------7d33a816d302b6

enctype 编码类型
Content-Type内容类型

form表单中enctype只有三个
script text/javascript
style text/css
...


而在Content-Type中有所有的类型
所以通过表单传数据、或通过script style link标签来获取数据，当然这些我们都可以通过在Ajax中设置Content-Type来获取相应的数据


$_REQUEST[]具用$_POST[] $_GET[]的功能，但是$_REQUEST[]比较慢。通过POST和GET方法提交的所有数据都可以通过$_REQUEST数组获得。
eg.
$_REQUEST[] = $_POST[] + $_GET[]



GET传送的数据量较小，不能大于2KB。
POST传送的数据量较大，一般被默认为不受限制。但理论上，一般认为不能超过100KB。
 GET安全性非常低，POST安全性较高。
<form method="get" action="a.asp?b=b">跟<form method="get"action="a.asp">是一样的，也就是说，在这种情况下，GET方式会忽略action页面后边带的参数列表。

一般我们都用$_POST或$_REQUEST两个预定义变量来接收POST提交的数据。但如果提交的数据没有变量名，而是直接的字符串，则需要使用其他的方式来接收。

$_POST方式接收数据
$_POST方式是通过 HTTP POST 方法传递的变量组成的数组，是自动全局变量。如使用$_POST['name']就可以接收到网页表单以及网页异步方式post过来的数据，即$_POST只能接收文档类型为Content-Type: application/x-www-form-urlencoded提交的数据。
$GLOBALS['HTTP_RAW_POST_DATA']方式接收数据
如果用过post过来的数据不是PHP能够识别的文档类型，比如 text/xml 或者 soap 等等，我们可以用$GLOBALS['HTTP_RAW_POST_DATA']来接收。$HTTP_RAW_POST_DATA 变量包含有原始的POST数据。此变量仅在碰到未识别MIME 类型的数据时产生。$HTTP_RAW_POST_DATA 对于enctype="multipart/form-data" 表单数据不可用。也就是说使用$HTTP_RAW_POST_DATA无法接收网页表单post过来的数据。
php://input方式接收数据
如果访问原始 POST 数据的更好方法是 php://input。php://input 允许读取 POST 的原始数据。和 $HTTP_RAW_POST_DATA 比起来，它给内存带来的压力较小，并且不需要任何特殊的php.ini设置，而php://input不能用于 enctype="multipart/form-data"。
例如，用户使用某个客户端应用程序post给服务器一个文件，文件的内容我们不管它，但是我们要把这个文件完整的保存在服务器上，我们可以使用如下代码：
$input = file_get_contents('php://input'); 
file_put_contents($original, $input); //$original为服务器上的文件 
以上代码使用file_get_contents('php://input')接收post数据，然后将数据写入$original文件中，其实可以理解为从客户端上传了一个文件到服务器上，此类应用非常多，尤其是我们PHP开发要与C,C++等应用程序开发进行产品联合开发时会用到，例如本站有文章：拍照上传就是结合flash利用此原理来上传照片的。
file_get_contents("php://input")来获取是最保险的方法。

enctype 属性
application/x-www-form-urlencoded 在发送前编码所有字符（默认）不能用于文件上传
multipart/form-data	
不对字符编码。在使用包含文件上传控件的表单时，必须使用该值(无法获取Hidden数据,但可以通过$(selector).serialize())-后台MultipartFile接收
text/plain	空格转换为 "+" 加号，但不对特殊字符编码。

PHP http_build_query note
<?php 
$post_url = ''; 
foreach ($_POST AS $key=>$value) 
    $post_url .= $key.'='.$value.'&'; 
$post_url = rtrim($post_url, '&'); 
?> 

You can then use this to pass along POST data in CURL. 

<?php 
    $ch = curl_init($some_url); 
    curl_setopt($ch, CURLOPT_POST, true); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_url); 
    curl_exec($ch); 
?>

<?php
$data = array('foo'=>'bar',               
'baz'=>'boom',               
'cow'=>'milk',               
'php'=>'hypertext processor');               
echo http_build_query($data);
输出：       
foo=bar&baz=boom&cow=milk&php=hypertext+processor 
?>

json_encode 函数中中文被编码成 null 了，Google 了一下，很简单，为了与前端紧密结合，Json 只支持 utf-8 编码，我认为是前端的 Javascript 也是 utf-8 的原因。

 json_decode($data,true)输出的一个关联数组array(),
 json_decode($data）输出的是对象stdClass Object (),
 而json_decode("$arr",true)是把它强制生成PHP关联数组.

$a = urlencode(iconv("gb2312", "UTF-8", "电影"));      //等同于javascript encodeURI("电影"); 
echo $a; 
 
//等同于javascript decodeURI("%E7%94%B5%E5%BD%B1"); 
$b = iconv("utf-8","gb2312",urldecode("%E7%94%B5%E5%BD%B1"));  
echo $b; 
 
//如果编码是UTF-8的话就可以直接用urlencode 或 urldecode 转换! 



$a = array('<foo>',"'bar'",'"baz"','&blong&', "\xc3\xa9"); 

echo "Normal: ", json_encode($a), "\n"; 
echo "Tags: ", json_encode($a, JSON_HEX_TAG), "\n"; 
echo "Apos: ", json_encode($a, JSON_HEX_APOS), "\n"; 
echo "Quot: ", json_encode($a, JSON_HEX_QUOT), "\n"; 
echo "Amp: ", json_encode($a, JSON_HEX_AMP), "\n"; 
echo "Unicode: ", json_encode($a, JSON_UNESCAPED_UNICODE), "\n"; 
echo "All: ", json_encode($a, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE), "\n\n"; 

$b = array(); 

echo "Empty array output as array: ", json_encode($b), "\n"; 
echo "Empty array output as object: ", json_encode($b, JSON_FORCE_OBJECT), "\n\n"; 

$c = array(array(1,2,3)); 

echo "Non-associative array output as array: ", json_encode($c), "\n"; 
echo "Non-associative array output as object: ", json_encode($c, JSON_FORCE_OBJECT), "\n\n"; 

$d = array('foo' => 'bar', 'baz' => 'long'); 

echo "Associative array always output as object: ", json_encode($d), "\n"; 
echo "Associative array always output as object: ", json_encode($d, JSON_FORCE_OBJECT), "\n\n"; 

Normal: ["<foo>","'bar'","\"baz\"","&blong&","\u00e9"] 
Tags: ["\u003Cfoo\u003E","'bar'","\"baz\"","&blong&","\u00e9"] 
Apos: ["<foo>","\u0027bar\u0027","\"baz\"","&blong&","\u00e9"] 
Quot: ["<foo>","'bar'","\u0022baz\u0022","&blong&","\u00e9"] 
Amp: ["<foo>","'bar'","\"baz\"","\u0026blong\u0026","\u00e9"] 
Unicode: ["<foo>","'bar'","\"baz\"","&blong&","é"] 
All: ["\u003Cfoo\u003E","\u0027bar\u0027","\u0022baz\u0022","\u0026blong\u0026","é"] 

Empty array output as array: [] 
Empty array output as object: {} 

Non-associative array output as array: [[1,2,3]] 
Non-associative array output as object: {"0":{"0":1,"1":2,"2":3}} 

Associative array always output as object: {"foo":"bar","baz":"long"} 
Associative array always output as object: {"foo":"bar","baz":"long"}



// urldecode 解码
// urlencode()函数原理就是首先把中文字符转换为十六进制，然后在每个字符前面加一个标识符%。

// urldecode()函数与urlencode()函数原理相反，用于解码已编码的 URL 字符串，其原理就是把十六进制字符串转换为中文字符

// rawurldecode() 不会把加号（'+'）解码为空格，而 urldecode() 可以

// 有一点需要注意的地方是，urldecode() 和 rawurldecode() 解码出的字符串是 UTF-8格式的编码，如果URL中含有中文的话，而页面设置又不是 UTF-8 的话，则要把解码出的字符串进行转换，才能正常显示！ 

$arr = array
(
  'Name'=>'希亚',
  'Age'=>20
);

$jsonencode = json_encode($arr);
echo $jsonencode;
// 在php5.2中做json_encode的时候。中文会被unicode编码， 
// php5.3加入了options参数， 
// 5.4以后才加入JSON_UNESCAPED_UNICODE，这个参数，不需要做escape和unicode处理。 
// 所以在5.4之前都需要对中文做个处理 
// 5.4里面的处理 
// Php代码  收藏代码
// json_encode($str, JSON_UNESCAPED_UNICODE);
// 5.4之前，有两种方法处理 
// 方法1. 
// Php代码  收藏代码
// function encode_json($str){  
//     $code = json_encode($str);  
//     return preg_replace("#\\\u([0-9a-f]+)#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", $code);  
// }  
// http://scnjl.iteye.com/blog/1724447
// http://www.nowamagic.net/php/php_FunctionJsonEncode.php
// 方法1.在实际应用中有个问题，部分字符会掉，不止为何，如字符串："日期11.2"会被变成"日期.2" 

// 方法2. 
// 先对需要处理的做urlencode处理，然后json_encode，最后做urldecode处理 
// Php代码  收藏代码
// function encode_json($str) {  
//     return urldecode(json_encode(url_encode($str)));      
// }  
  
// /** 
//  *  
//  */  
// function url_encode($str) {  
//     if(is_array($str)) {  
//         foreach($str as $key=>$value) {  
//             $str[urlencode($key)] = url_encode($value);  
//         }  
//     } else {  
//         $str = urlencode($str);  
//     }  
      
//     return $str;  
// }  


// 在类或函数中使用，获取传如arguments..
// int func_num_args( void )
// func_num_args()
// func_get_args();




/*
 php 来 模拟post 提交数据有三种方式:
 file_get_contents、curl、socket
*/


escape（69个） */@+-._0-9a-zA-Z
encodeURI（82个） !#$&’()*+,/:;=?@-._~0-9a-zA-Z
encodeURIComponent（71个） !’()*-._~0-9a-zA-Z
escape 函数是从Javascript1.0的时候就存在了
其他两个函数是在Javascript1.5才引入的。但是由于 Javascript1.5已经非 常普及了，所以实际上使用encodeURI和encodeURIComponent并不会有什么兼容性问题。