#PHP数据编码


        http_build_query
        json_decode($data,true)输出的一个关联数组array(),
        json_decode($data）输出的是对象stdClass Object (),
        而json_decode("$arr",true)是把它强制生成PHP关联数组.


        $a = urlencode(iconv("gb2312", "UTF-8", "电影"));      //等同于javascript encodeURI("电影"); 
        echo $a; 
         
        //等同于javascript decodeURI("%E7%94%B5%E5%BD%B1"); 
        $b = iconv("utf-8","gb2312",urldecode("%E7%94%B5%E5%BD%B1"));  
        echo $b; 
         
        //如果编码是UTF-8的话就可以直接用urlencode 或 urldecode 转换! 


        json_encode使用方法



        urldecode 解码
        urlencode()函数原理就是首先把中文字符转换为十六进制，然后在每个字符前面加一个标识符%。

        urldecode()函数与urlencode()函数原理相反，用于解码已编码的 URL 字符串，其原理就是把十六进制字符串转换为中文字符

        rawurldecode() 不会把加号（'+'）解码为空格，而 urldecode() 可以

        有一点需要注意的地方是，urldecode() 和 rawurldecode() 解码出的字符串是 UTF-8格式的编码，如果URL中含有中文的话，而页面设置又不是 UTF-8 的话，则要把解码出的字符串进行转换，才能正常显示！ 

        $arr = array
        (
          'Name'=>'希亚',
          'Age'=>20
        );

        $jsonencode = json_encode($arr);
        echo $jsonencode;

        在php5.2中做json_encode的时候。中文会被unicode编码， 
        php5.3加入了options参数， 
        5.4以后才加入JSON_UNESCAPED_UNICODE，这个参数，不需要做escape和unicode处理。 
        所以在5.4之前都需要对中文做个处理 
        5.4里面的处理 

        json_encode($str, JSON_UNESCAPED_UNICODE);
        5.4之前，有两种方法处理 
        方法1. 
        function encode_json($str){  
            $code = json_encode($str);  
            return preg_replace("#\\\u([0-9a-f]+)#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", $code);  
        }  
        http://scnjl.iteye.com/blog/1724447
        http://www.nowamagic.net/php/php_FunctionJsonEncode.php
        方法1.在实际应用中有个问题，部分字符会掉，不止为何，如字符串："日期11.2"会被变成"日期.2" 

        方法2. 
        先对需要处理的做urlencode处理，然后json_encode，最后做urldecode处理 

        function encode_json($str) {  
            return urldecode(json_encode(url_encode($str)));      
        }  
           
        function url_encode($str) {  
            if(is_array($str)) {  
                foreach($str as $key=>$value) {  
                    $str[urlencode($key)] = url_encode($value);  
                }  
            } else {  
                $str = urlencode($str);  
            }  
              
            return $str;  
        }  


        在类或函数中使用，获取传如arguments..
        int func_num_args( void )
        func_num_args()
        func_get_args();




        php 来 模拟post 提交数据有三种方式:
        file_get_contents、curl、socket


        escape（69个） */@+-._0-9a-zA-Z
        encodeURI（82个） !#$&’()*+,/:;=?@-._~0-9a-zA-Z
        encodeURIComponent（71个） !’()*-._~0-9a-zA-Z
        escape 函数是从Javascript1.0的时候就存在了
        其他两个函数是在Javascript1.5才引入的。但是由于 Javascript1.5已经非 常普及了，所以实际上使用encodeURI和encodeURIComponent并不会有什么兼容性问题。