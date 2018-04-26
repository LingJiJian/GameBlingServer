<?php

require_once __DIR__ . '/../Common/GameConfig.php';
require_once __DIR__ . '/../Common/Util.php';

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

		$this->_roomDic[$ret['roomid']] = array(
			'gameid'			=>	$param->{'gameid'},
			'createtime'		=>	$ret['createtime'],
			'places'			=>	$places,
			'placeLimit' 	=>	$placeLimit
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
				$room['places'][$seatIdx] = array(
					'seatIdx'	=>	$seatIdx,
					'status'		=>	'idle',
					'client_id'	=> 	$client_id
				);
				foreach ($room['places'][$seatIdx] as $key => $value) {
					$ret[$key] = $value;
				}
			}
			return array(0,$ret);
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
					unset($roomObj['places'],$seatIdx);
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