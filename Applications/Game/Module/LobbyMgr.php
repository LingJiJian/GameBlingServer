<?php

require_once __DIR__ . '/../Common/GameConfig.php';
require_once __DIR__ . '/../Common/Util.php';

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

		$ret = array();
		$ret['roomid'] = $this->_createRoomId();
		$ret['createtime'] = time();
		$ret['gameid'] = $param->{'gameid'};

		$placeLimit = GameConfig::$gameDefs[$param->{'gameid'}]['limit'];
		$places = array();
		for ($i=0; $i<$placeLimit; $i++) {
			$places[$i + 1] = null;
		}

		$places[1] = new EntityVo();
		$places[1]->seatIdx		=	1;
		$places[1]->status		=	'idle';
		$places[1]->client_id	= 	$client_id;
		$places[1]->pos 			= 	'watcher';

		$this->_roomDic[$ret['roomid']] = new EntityVo();
			'roomid'			=> 	$ret['roomid'],
			'gameid'			=>	$param->{'gameid'},
			'createtime'		=>	$ret['createtime'],
			'places'			=>	$places,
			'placeLimit' 	=>	$placeLimit,
			'status'			=> 	'idle'
		);
		Gateway::joinGroup($client_id,$ret['roomid']);

		return array(0,$ret);
	}

	public function makeJoinRoom($param,$client_id)
	{
		$ret = array();
		if(array_key_exists($param->{'roomid'}, $this->_roomDic)){
			$room = $this->_roomDic[$param->{'roomid'}];
			$seatIdx = -1;
			foreach ($room['places'] as $key => $value) {
				if($value == null){
					$seatIdx = $key;
					break;
				}
			}

			if($seatIdx == -1){
				return array(1,$ret,"房间满人了!");
			}else{
				echo "-------- seatIdx " . $seatIdx ;
				$room['places'][$seatIdx] = new EntityVo();
				$room['places'][$seatIdx]->seatIdx	= $seatIdx;
				$room['places'][$seatIdx]->status		= 'idle';
				$room['places'][$seatIdx]->client_id	= $client_id;
				$room['places'][$seatIdx]->pos		= 'watcher';
			}
			return array(0,$room,'');
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
			foreach ($roomObj['places'] as $seatIdx => $persion) {
				if($persion['client_id'] == $client_id){
					$roomObj['places'][$seatIdx] = null;
					$ret['roomid'] = $roomId;
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
		foreach ($this->_roomDic as $roomId => $roomObj) {
			foreach ($roomObj['places'] as $seatIdx => $persion) {
				if($persion['client_id'] == $client_id){
					return $roomObj;
				}
			}
		}
		return null;
	}
}