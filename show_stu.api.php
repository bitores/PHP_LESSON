<?php 
require_once("API.php");
/**
* 
*/
class Show_Stu extends API
{
	
	public function __construct()
	{
		# code...
		$this->init();
	}

	public function execSQL()
	{

		$res = $this->mysql->doSQL('select * from stu where id>40 and id<300 order by id desc');

		$this->responce->setData($res);

		$this->output();
	}
}

$stu = new Show_Stu();
$stu->execSQL();
?>