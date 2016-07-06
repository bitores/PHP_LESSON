<?php 
require_once("API.php");
/**
* 
*/
class Stu extends API
{
	
	public function __construct()
	{
		# code...
		$this->init();
	}

	public function execSQL($param = '')
	{
		// res 三个值： boolean array integer
		$res = $this->mysql->doSQL($param);
		switch (gettype($res)) {
			case 'integer':
				echo "add/del/mod";
				break;
			case 'boolean':
				echo "fail";
				$this->responce->setCode(300);
				$this->responce->setMsg("fail");
				$this->output();
				break;
			case 'array':
				echo "sccess";
				$this->responce->setCode(0);
				$this->responce->setData($res);
				$this->output();
				break;

			default:
				# code...
				break;
		}

		
	}

	public function showAll()
	{
		$this->execSQL('select * from stu');//where id>40 and id<300 order by id desc
	}
}

?>