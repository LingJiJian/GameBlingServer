<?php

require_once __DIR__ . '/../Entity/EntityVo.php';

use \GatewayWorker\Lib\Gateway;

class LoginMgr
{
	private static $_instance;
	public static function GetInstance(){
		if(self::$_instance === null){
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	private $_clientDic;

	function __construct() {
		$this->_clientDic = array();
	}

	public function makeLoginClient($json_obj,$client_id,$account)
	{
		if(array_key_exists($account,$this->_clientDic)){
			$json_obj['ret'] = 1;
			$json_obj['msg'] = '该账号已经登陆!';
			return $json_obj;
		}

		echo($account . " ------------登陆-----------");

		$json_obj['ret'] = 0;

		$client = new EntityVo();
		$client->nickname = "游客";
		$client->level = 1;
		$client->coin = 100;
		$client->client_id = $client_id;
		$this->_clientDic[$account] = $client;
		$json_obj['data'] = $client->getData();
		return $json_obj;
	}

	public function onClose($client_id)
	{
		foreach ($this->_clientDic as $account => $client) {
			if($client && $client->client_id == $client_id)
			{
				$this->_clientDic[$account] = null;
				break;
			}
		}
	}
}