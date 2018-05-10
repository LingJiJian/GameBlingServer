<?php

require_once __DIR__ . '/../Common/GameConfig.php';
require_once __DIR__ . '/../Common/Util.php';
require_once __DIR__ . '/../Entity/EntityVo.php';
require_once __DIR__ . '/../Module/RoleMgr.php';

use \GatewayWorker\Lib\Gateway;

class LobbyMgr
{
	private static $_instance;
	public static function GetInstance(){
		if(self::$_instance === null){
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	private $_roomIdx;
	private $_roomDic;

	function __construct() {
		$this->_roomIdx= 1000;
		$this->_roomDic = array();
	}
	
	public function onUpdate()
	{
		
	}

	public function makeCreateRoom($param,$client_id){

		$roomid = $this->_createRoomId();

		$placeLimit = GameConfig::$gameDefs[$param->{'gameid'}]['placeLimit'];
		$places = array();
		for ($i=0; $i<$placeLimit; $i++) {
			$places[$i + 1] = null;
		}

		$persion = new EntityVo();
		$persion->seatIdx 	= 1;
		$persion->status 	= 'idle';
		$persion->client_id	= $client_id;
		$persion->pos 		= 'banker';
		$persion->role = RoleMgr::GetInstance()->getRoleByClientId($client_id);
		$places[1] = $persion;

		$room = new EntityVo();
		$room->roomid 	=	$roomid;
		$room->gameid 	=	$param->{'gameid'};
		$room->createtime =	time();
		$room->places 	=	$places;
		$room->placeLimit =	$placeLimit;
		$room->roomLimit = GameConfig::$gameDefs[$param->{'gameid'}]['roomLimit'];
		$room->status 	= 	'idle';
		$this->_roomDic[$roomid] = $room;

		Gateway::joinGroup($client_id,$roomid);

		return array(0,$room->getData());
	}

	public function makeJoinRoom($param,$client_id)
	{
		$ret = array();
		if(array_key_exists($param->{'roomid'}, $this->_roomDic)){
			$room = $this->_roomDic[$param->{'roomid'}];
			
			if(count($room->places) >= $room->roomLimit){
				return array(1,$ret,"房间满人了!");
			}else{

				$seatIdx = -1;
				foreach ($room->places as $key => $persion) {
					if($persion == null){
						$seatIdx = $key;
						break;
					}
				}

				$placeidx = $seatIdx;
				if($seatIdx == -1){
					$placeidx = count($room->places) + 1;
				}

				$persion = new EntityVo();
				$persion->seatIdx =	$seatIdx;
				$persion->status = 'idle';
				$persion->client_id	= $client_id;
				$persion->pos =	'watcher';
				$persion->role = RoleMgr::GetInstance()->getRoleByClientId($client_id);
				$room->places[$placeidx] = $persion;
			}
			return array(0,$room->getData(),'');
		}else{
			return array(1,$ret,"房间不存在!");
		}
	}

	public function makeDeleteRoom($param)
	{
		$ret = array();
		$ret['roomid'] = $param->{'roomid'};
		if(array_key_exists($param->{'roomid'}, $this->_roomDic)){
			array_remove($this->_roomDic,$param->{'roomid'});
		}
		return array(0,$ret);
	}

	public function makeLeftRoom($client_id)
	{
		$ret = array();
		foreach ($this->_roomDic as $roomId => $roomObj) {
			foreach ($roomObj->places as $seatIdx => $persion) {
				if($persion && $persion->client_id == $client_id){
					$roomObj->places[$seatIdx] = null;
					$ret['roomid'] = $roomId;
					$ret['gameid'] = $roomObj->gameid;
					return array(0,$ret);
				}
			}
		}
		$ret['msg'] = "找不到所在房间!";
		return array(1,$ret);
	}

	private function _createRoomId()
	{
		$this->_roomIdx++;
		return $this->_roomIdx;
	}

	public function getRoomById($roomId)
	{
		return $this->_roomDic[$roomId];
	}

	public function getRoomByClientId($client_id)
	{
		// var_dump($this->_roomDic);
		foreach ($this->_roomDic as $roomId => $roomObj) {
			foreach ($roomObj->places as $seatIdx => $persion) {
				if($persion && $persion->client_id == $client_id){
					return $roomObj;
				}
			}
		}
		return null;
	}

	public function getPersionByClientId($roomid,$client_id)
	{
		$room = $this->getRoomById($roomid);
		if($room)
		{
			foreach ($room->places as $seatIdx => $persion) {
				if($persion && $persion->client_id == $client_id){
					return $persion;
				}
			}
		}
		return null;
	}

	public function onClose($client_id)
	{

		foreach ($this->_roomDic as $roomId => $room) {
			foreach ($room->places as $seatIdx => $persion) {
				if($persion && $persion->client_id == $client_id){
					$room->places[$seatIdx] = null;

					$has_persion = false;
					foreach ($room->places as $seatIdx => $persion) {
						if($persion){
							$has_persion = true;
							break;
						}
					}
					if(!$has_persion){
						$this->_roomDic[$roomId] = null;
					}
				}
			}
		}
	}

}