<?php

require_once __DIR__ . '/../Common/GameConfig.php';
require_once __DIR__ . '/../Common/Util.php';
require_once __DIR__ . '/../Module/LobbyMgr.php';

use \GatewayWorker\Lib\Gateway;

class NiuNiuMgr
{
	private static $_instance;
	public static function GetInstance(){
		if(self::$_instance === null){
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	public function makeSyncGame($client_id)
	{
		$room = LobbyMgr::GetInstance()->getRoomByClientId($client_id);
		if($room != null){
			// $places = 
		}
	}

	public function makeDealCard($param)
	{

	}

	public function makeSetPos($param)
	{

	}
}

