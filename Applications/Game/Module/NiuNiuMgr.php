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
				
				echo '-----------------onUpdate---------------------';
				var_dump($room);

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

					$json_obj = array();
					$json_obj['ret'] = 0;
					$json_obj['msgid'] = MsgIds::NiuNiu_Update; 
					$json_obj['data'] = array(
						'status' => $fsm['status'],
						'endst' =>  $fsm['nextst']
					);
					echo 'start game';
					Gateway::sendToGroup($roomid,json_encode($json_obj));
				}
			}
			elseif($fsm['status'] == 'prepare')
			{
				if($fsm['nextst'] >= time()){
					$fsm['status'] = 'deal';

					$json_obj = array();
					$json_obj['ret'] = 0;
					$json_obj['msgid'] = MsgIds::NiuNiu_Update; 
					$json_obj['data'] = array(
						'status' => $fsm['status'],
						'endst' =>  $fsm['nextst']
					);
					Gateway::sendToGroup($roomid,json_encode($json_obj));
				}
			}
			elseif($fsm['status'] == 'deal')
			{
				$fsm['status'] = 'betting';
				$fsm['nextst'] = time() + 5;

				$json_obj = array();
				$json_obj['ret'] = 0;
				$json_obj['msgid'] = MsgIds::NiuNiu_Update; 
				$json_obj['data'] = array(
					'status' => $fsm['status'],
					'endst' =>  $fsm['nextst'],
					'cards' => $this->makeDealCard($fsm,2,$roomid)
				);
				Gateway::sendToGroup($roomid,json_encode($json_obj));
			}
			elseif($fsm['status'] == 'betting')
			{
				if($fsm['nextst'] >= time()){
					$fsm['status'] = 'finish';
					$fsm['nextst'] = time() + 5;

					$json_obj = array();
					$json_obj['ret'] = 0;
					$json_obj['msgid'] = MsgIds::NiuNiu_Update; 
					$json_obj['data'] = array(
						'status' => $fsm['status'],
						'endst' =>  $fsm['nextst'],
						'cards' => $this->makeDealCard($fsm,3,$roomid)
					);
					Gateway::sendToGroup($roomid,json_encode($json_obj));
				}
			}
			elseif($fsm['status'] == 'finish')
			{
				if($fsm['nextst'] >= time()){
					$fsm['status'] = 'idle';

					$json_obj = array();
					$json_obj['ret'] = 0;
					$json_obj['msgid'] = MsgIds::NiuNiu_Update; 
					$json_obj['data'] = array(
						'status' => $fsm['status'],
						'endst' =>  $fsm['nextst']
					);
					Gateway::sendToGroup($roomid,json_encode($json_obj));
				}
			}
		}
	}

	public function makeSyncGame($json_obj,$client_id)
	{
		$room = LobbyMgr::GetInstance()->getRoomByClientId($client_id);
		$fsm = null;
		if($room != null){
			if(array_key_exists($room['roomid'] ,$this->_fsmDic)){

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
		$json_obj['msgid'] = MsgIds::NiuNiu_SyncGame;
		Gateway::sendToGroup($room['roomid'],json_encode($json_obj));
	}

	public function makeSetPos($json_obj,$client_id,$pos,$target_seatIdx)
	{
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

			var_dump($room);

			Gateway::sendToGroup($room['roomid'],json_encode($json_obj));
		}else{
			$json_obj['ret'] = 1;
			$json_obj['msg'] = '房间不存在!';
			Gateway::sendToClient($client_id,json_encode($json_obj));
		}
	}

	public function makeDealCard($pokers,$deal_num,$roomid)
	{
		$json_obj = array();
		$room = LobbyMgr::GetInstance()->getRoomByClientId($client_id);
		if($room){

		}
	}
}

