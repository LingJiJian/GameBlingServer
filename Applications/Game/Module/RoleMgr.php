<?php

require_once __DIR__ . '/../Common/GameConfig.php';
require_once __DIR__ . '/../Common/Util.php';
require_once __DIR__ . '/../Entity/EntityVo.php';


use \GatewayWorker\Lib\Gateway;

class RoleMgr
{
	private static $_instance;
	public static function GetInstance(){
		if(self::$_instance === null){
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	private $_roleIdx;
	private $_roleDic;

	function __construct() {
		$this->_roleIdx = 1000;
		$this->_roleDic = array();
	}

	public function createRole($client_id,$account,$nickname,$coin,$gold,$level)
	{
		$role = new EntityVo();
		$role->nickname = $nickname;
		$role->level = $level;
		$role->coin = $coin;
		$role->gold = $gold;
		$role->client_id = $client_id;
		return $role;
	}

	public function getRoleDic()
	{
		return $this->_roleDic;
	}

	public function getRoleByClientId($client_id)
	{
		foreach ($this->_roleDic as $account => $role) {
			if($role && $role->client_id == $client_id)
			{
				return $role;
			}
		}
		return null;
	}

	public function onClose($client_id)
	{
		foreach ($this->_roleDic as $account => $role) {
			if($role && $role->client_id == $client_id)
			{
				$this->_roleDic[$account] = null;
				break;
			}
		}
	}

}