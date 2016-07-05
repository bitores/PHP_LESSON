<?php 

/**
*  API
*/
abstract class API
{
	protected $mysql;
	protected $http;
	protected $logger;
	protected $responce;
	
	
	public function __construct()
	{
		# code...
		// $this->init();
	}

	protected function init()
	{
		require_once('PDB.php');
		require_once('PHttp.php');
		require_once('PLog.php');
		require_once('PResponce.php');

		$this->http = new Http();
		$this->responce = new Response();
		$this->mysql = DB::getInstance();
		$this->logger = new Logger_Explorer(Logger::LOG_LEVEL_DEBUG);

	}

	public function output()
	{
		$this->responce->output();
		$this->mysql->close();
	}

	abstract public function execSQL();
}

?>