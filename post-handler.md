#POST数据处理

###前端可发送数据类型

		enctype application/x-www-form-urlencoded|multipart/form-data|text/plain
		script text/javascript
		style text/css
		link text/css
		meta text/html
		ajax application/x-www-form-urlencoded|...all content-type;


###后端各数据类型接收

		$_POST方式接收数据
		method:post,content-type:application/x-www-form-urlencoded|multipart/form-data|text/plain..
		form表单中必有name属性，否则接收不到数据
		如果要上传文件则enctype="multipart/form-data"


		$GLOBALS['HTTP_RAW_POST_DATA']方式接收数据
		PHP 5.6.x 中已废止的特性
		method:all,content-type:all (PHP默认只识别application/x-www.form-urlencoded[其它看符表]等标准的数据类型,其它皆为未识别MIME类型,eg.text/xml|soap)


		php://input方式接收数据
		php://input访问原始 POST 数据，除了 enctype="multipart/form-data"
		它给内存带来的压力较小，并且不需要任何特殊的php.ini设置
		$input = file_get_contents('php://input'); 是最保险的方法



###其它

		application/x-www-form-urlencoded
	 	在发送前编码所有字符（默认）不能用于文件上传
		multipart/form-data
		不对字符编码。在使用包含文件上传控件的表单时，必须使用该值(无法获取Hidden数据,但可以通过$(selector).serialize())-后台MultipartFile接收
		text/plain
		空格转换为 "+" 加号，但不对特殊字符编码

		GET传送的数据不能大于2KB,POST传送的数据量一般被默认为不受限制。但理论上，一般认为不能超过100KB。GET安全性非常低，POST安全性较高。

		<form method="get" action="a.asp?b=b">跟<form method="get"action="a.asp">在这种情况下，GET方式会忽略action页面后边带的参数列表。

		通过POST和GET方法提交的所有数据都可以通过$_REQUEST数组获得。
		eg.但是$_REQUEST[]比较慢
		$_REQUEST[] = $_POST[] + $_GET[]

		一般我们都用$_POST或$_REQUEST两个预定义变量来接收POST提交的数据。
		但如果提交的数据没有变量名，而是直接的字符串，则需要使用其他的方式来接收。

		post = form-data + x-www-form-urlencded + raw + binary.

###问题集锦
		1、为什么$_POST没有数据
		2、表单中entry要怎么设置，数据才可以在php://input中取到
		3、$HTTP_RAW_POST_DATA 什么时候废弃的
		4、Ajax中设置了请求头，会不会改变php的数据接收方式
		5、url中数据传输时进行了编码，这样接收时也要对应做解码处理




