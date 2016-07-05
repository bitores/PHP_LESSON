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

	public function showAll()
	{

		$this->responce->setData("ad");
	}
}

$stu = new Show_Stu();
$stu->showAll();
$stu->output();
?>