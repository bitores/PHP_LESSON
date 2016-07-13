<?php
/**
 * ajax业务处理中的接口转发层，解决ajax跨域访问的问题
 *   工作原理：问请求通过本程序做中转，在本地服务器层完成与远程服务接口的交互
 *   备注：使用时 URL_ROOT 这个参数需要根据你的目标接口地址进行修改，本转发层之能用于单接口的Web Service接口服务
 *        程序支持POST数据与GET数量的同时转发;
 * @version 1.0.0.2
 * @author JerryLi lijian@dzs.mobi
 * @copyright b.dzs.mobi 2012-11-16
 * */
class interface_relay
{
	/**接口根地址(此处是需要修改的地方)*/
	const URL_ROOT = 'https://oapi.dingtalk.com/';
	/**字符集*/
	const CHARSET = 'UTF-8';
	/**GET*/
	private $msGets = '';
	/**POST*/
	private $maGetPostData = array();

	function __construct()
	{
		// $this->getPOST();
		// $this->getGET();

		// if($this->msGets != '' || count($this->maGetPostData) > 0)
		// {	
		// 	//存在输入数据
		// 	if(count($this->msGets) > 0)
		// 		$sUrl = self::URL_ROOT .'?'. $this->msGets;
		// 	else
		// 		$sUrl = self::URL_ROOT;
		// 	header('Content-Type: text/html; charset='. self::CHARSET);
		// 	echo $this->getContent($sUrl);
		// }
		// else
		// {
		// 	header('Content-Type: text/html; charset='. self::CHARSET);
		// 	echo $this->getContent(self::URL_ROOT);
		// }

		if(count($_GET) > 0){
			$sUrl = $_GET['url'];
		} else {
			$sUrl = self::URL_ROOT;
		}

		header('Content-Type: text/html; charset='. self::CHARSET);
		// echo $this->getContent($sUrl);
		echo file_get_contents($sUrl);
	}

	function __destruct()
	{
		unset($maGetPostData, $msGets);
	}

	/**
	 * 载入POST数据
	 * @return bool
	 * */
	private function getPOST()
	{
		$handle = @fopen('php://input', 'r');
		$data = '';
		do
		{
			$data = @fread($handle, 1024);
			if (strlen($data) == 0)
				break;
			else
				$this->maGetPostData[] = $data;
		}while(true);
		echo $data;
		fclose($handle);
		unset($data, $handle);
		return count($this->maGetPostData) >= 1;
	}

	/**
	 * 载入GET数据
	 * @return bool
	 * */
	private function getGET()
	{
		/*取得GET内容*/
		if (count($_GET) > 0)
		{
			$aTmp = array();
			foreach ($_GET as $sKey => $sVal)
				$aTmp[] = $sKey .'='. urlencode($sVal);
			$this->msGets = implode('&', $aTmp);
			return true;
		}
		else
			return false;
	}

	/**
	 * 读取远程接口返回的内容
	 * @return string
	 * */
	private function getContent($sGetUrl)
	{

		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $sGetUrl); //设置GET的URL地址
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);//将结果保存成字符串
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 10);//连接超时时间s
		curl_setopt ($ch, CURLOPT_TIMEOUT, 10);//执行超时时间s
		curl_setopt ($ch, CURLOPT_DNS_CACHE_TIMEOUT, 1800);//DNS解析缓存保存时间半小时
		curl_setopt($ch, CURLOPT_HEADER,0);//丢掉头信息
		if (count($this->maGetPostData) > 0)
		{	//存在POST数据需要提交
			curl_setopt($ch, CURLOPT_POST, 1); //启用POST数据
			curl_setopt($ch, CURLOPT_POSTFIELDS, implode('', $this->maGetPostData));//提交POST数据
		}
		$sData = curl_exec($ch);
		curl_close($ch);
		unset($ch);
		return $sData;
	}
}

$o = new interface_relay();
unset($o);
?>