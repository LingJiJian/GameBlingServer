<?php

require_once __DIR__ . '/../Entity/EntityVo.php';
require_once __DIR__ . '/../Module/RoleMgr.php';


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

	function __construct() {
		
	}

	public function makeLoginClient($json_obj,$client_id,$account)
	{
		$dic = &RoleMgr::GetInstance()->getRoleDic();

		if(array_key_exists($account,$dic)){
			$json_obj['ret'] = 1;
			$json_obj['msg'] = '该账号已经登陆!';
			return $json_obj;
		}

		echo('---- ' .$account . " ------------登陆-----------\n");

		$json_obj['ret'] = 0;
		$role = RoleMgr::GetInstance()->createRole($client_id,$account,"游客",1000,100,1);
		
		$dic[$account] = $role;

		$json_obj['data'] = $role->getData();
		return $json_obj;
	}
}