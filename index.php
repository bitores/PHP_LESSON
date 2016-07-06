<?php 
require_once("PRequest.php");
require_once("Stu.mod.php");

$stu = new Stu();
// $stu->execSQL('insert into stu values (2,"z",22)');
// $stu->showAll();
// $stu->execSQL('delete from stu');




// 使用事例
$data = RestUtils::processRequest();
	 
switch($data->getMethod())
{
	case 'get':
		// echo "get method";
		$str = $data->getRequestVars();
		$sql = $str['sql'];
		$stu->execSQL($sql);
		break;
	case 'post':
		echo "post method";
		break;
	case 'put':
		echo "put method";
		break;
		
	case 'delete':
		echo "delete method";
		break;
}

?>