<?php

require_once __DIR__ . '/../Common/GameConfig.php';
require_once __DIR__ . '/../Common/Util.php';
require_once __DIR__ . '/../Module/LobbyMgr.php';

use \Workerman\Lib\Timer;
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

	private $_fsmDic;

	function __construct() {
		$this->_fsmDic = array();
	}

	public function onUpdate()
	{
		foreach ($this->_fsmDic  as $roomid => $fsm) {
			if($fsm['status'] == 'idle'){
				$room = LobbyMgr::GetInstance()->getRoomById($roomid);
				
				$hasBanker = false;
				$hasPersion = false;
				foreach ($room['places'] as $seatIdx => $persion) {
					if($persion !=null){
						if($persion['pos'] == 'banker'){
							$hasBanker = true;
						}elseif($persion['pos'] == 'watcher' || $persion['pos'] == 'poker'){
							$hasPersion = true;
						}
					}
				}
				// start game
				if($hasPersion && $hasBanker){
					$fsm['cards'] = get_pokers(52);
					$fsm['status'] = 'prepare';
					$fsm['nextst'] = time() + 5;

					Gateway::sendToGroup($room['roomid']);
				}
			}
			elseif($fsm['status'] == 'prepare')
			{
				if($fsm['nextst'] >= time()){
					$fsm['status'] = 'deal';
				}
				Gateway::sendToGroup($room['roomid']);
			}
			elseif($fsm['status'] == 'deal')
			{
				$fsm['status'] = 'betting';
				$fsm['nextst'] = time() + 5;
				Gateway::sendToGroup($room['roomid']);
			}
			elseif($fsm['status'] == 'betting')
			{
				if($fsm['nextst'] >= time()){
					$fsm['status'] = 'finish';
					$fsm['nextst'] = time() + 5;
					Gateway::sendToGroup($room['roomid']);
				}
			}
			elseif($fsm['status'] == 'finish')
			{
				if($fsm['nextst'] >= time()){
					$fsm['status'] = 'idle';
					$fsm['nextst'] = time() + 5;
					Gateway::sendToGroup($room['roomid']);
				}
			}
		}
	}

	public function makeSyncGame($client_id)
	{
		$json_obj = array();
		$room = LobbyMgr::GetInstance()->getRoomByClientId($client_id);
		$fsm = null;
		if($room != null){
			if(array_key_exists($room['roomid'] ,$_fsmDic)){

				$fsm = $this->_fsmDic[$room['roomid']];

			}else{
				
				$fsm = array(
					'status'		=>	'idle',
					'nextst'		=>	0
				);
				$this->_fsmDic[$room['roomid']] = $fsm;
			}
		}

		$json_obj['ret'] = 0;
		$json_obj['data'] = $fsm;
		Gateway::sendToGroup($room['roomid'],json_encode($json_obj));
	}

	public function makeSetPos($client_id,$pos,$target_seatIdx)
	{
		$json_obj = array();
		$room = LobbyMgr::GetInstance()->getRoomByClientId($client_id);
		if($room){

			if($room['status'] != 'idle'){

				$json_obj['ret'] = 1;
				$json_obj['msg'] = '游戏过程中不能更换位置!';
				Gateway::sendToClient($client_id,json_encode($json_obj));
				return;
			}

			$lastPersion = null;
			$curPersion = null;
			foreach ($room['places'] as $seatIdx => $persion) {
				if($persion && $persion['pos'] == $pos){
					$lastPersion = $persion;
					$room['places'][$seatIdx] = null;
				}
				if($persion && $persion['client_id'] == $client_id){
					$curPersion = $persion;
					$room['places'][$seatIdx] = null;
				}
			}

			if($pos == 'banker'){

				$lastSeatIdx = $curPersion['seatIdx'];

				$curPersion['pos'] = $pos;
				$curPersion['seatIdx'] = $target_seatIdx;
				$room['places'][$target_seatIdx] = $curPersion;

				if($lastPersion != null){
					$lastPersion['pos'] = 'watcher';
					$lastPersion['seatIdx'] = $lastSeatIdx;
					$room['places'][$lastSeatIdx] = $lastPersion;
				}

			}elseif($pos =='poker'){

				$lastSeatIdx = $curPersion['seatIdx'];

				$curPersion['pos'] = $pos;
				$curPersion['seatIdx'] = $target_seatIdx;
				$room['places'][$target_seatIdx] = $curPersion;

				if($lastPersion != null){
					$lastPersion['pos'] = 'watcher';
					$lastPersion['seatIdx'] = $lastSeatIdx;
					$room['places'][$lastSeatIdx] = $lastPersion;
				}

			}elseif($pos == 'watcher'){
				
				$lastSeatIdx = $curPersion['seatIdx'];

				$null_seatIdx = null;
				foreach ($room['places'] as $seatIdx => $persion) {
					if($persion == null){
						$null_seatIdx = $seatIdx;
						break;
					}
				}

				$curPersion['pos'] = $pos;
				$curPersion['seatIdx'] = $null_seatIdx;
				$room['places'][$null_seatIdx] = $curPersion;

				if($lastPersion != null){
					$lastPersion['pos'] = 'watcher';
					$lastPersion['seatIdx'] = $lastSeatIdx;
					$room['places'][$lastSeatIdx] = $lastPersion;
				}
			}

			$json_obj['ret'] = 0;
			$json_obj['data'] = array(
				'client_id'	=>	$client_id,
				'pos'		=>	$pos
			);
			Gateway::sendToGroup($room['roomid'],json_encode($json_obj));
		}else{
			$json_obj['ret'] = 1;
			$json_obj['msg'] = '房间不存在!';
			Gateway::sendToClient($client_id,json_encode($json_obj));
		}
	}

	public function makeDealCard($client_id)
	{
		$json_obj = array();
		$room = LobbyMgr::GetInstance()->getRoomByClientId($client_id);
		if($room){

		}
	}	
}

