<?php

require_once __DIR__ . '/../Common/GameConfig.php';
require_once __DIR__ . '/../Common/Util.php';
require_once __DIR__ . '/../Module/LobbyMgr.php';
require_once __DIR__ . '/../Entity/EntityVo.php';

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

			$room = LobbyMgr::GetInstance()->getRoomById($roomid);
			if(!$room){
				continue;
			}

			if($room->status == 'idle'){
				
				$hasBanker = false;
				$hasPersion = false;
				foreach ($room->places as $seatIdx => $persion) {
					if($persion !=null){
						if($persion->pos == 'banker'){
							$hasBanker = true;
						}elseif($persion->pos == 'watcher' || $persion->pos == 'poker'){
							$hasPersion = true;
						}
					}
				}

				// start game
				if($hasPersion && $hasBanker){

					if($fsm->nextst <= time()){
						$fsm->cards = get_pokers(52);
						$room->status = 'prepare';
						$fsm->nextst = time() + 5;
						$fsm->betcards = array();

						$json_obj = array();
						$json_obj['ret'] = 0;
						$json_obj['msgid'] = MsgIds::NiuNiu_Update; 
						$json_obj['data'] = array(
							'status' => $room->status,
							'endst' =>  $fsm->nextst,
							'betcards' => $fsm->betcards
						);
						echo "开始游戏\n";
						Gateway::sendToGroup($roomid,json_encode($json_obj));
					}
				}
			}
			elseif($room->status == 'prepare')
			{
				if($fsm->nextst <= time()){
					$room->status = 'deal';
					$fsm->nextst = time() +5;
					$fsm->betcards = $this->makeDealCard($fsm->cards,2,$roomid);

					$json_obj = array();
					$json_obj['ret'] = 0;
					$json_obj['msgid'] = MsgIds::NiuNiu_Update; 
					$json_obj['data'] = array(
						'status' => $room->status,
						'endst' =>  $fsm->nextst,
						'betcards' => $fsm->betcards
					);
					Gateway::sendToGroup($roomid,json_encode($json_obj));
				}
			}
			elseif($room->status == 'deal')
			{
				$room->status = 'betting';
				$fsm->nextst = time() + 5;
				$fsm->betcards = array();

				$json_obj = array();
				$json_obj['ret'] = 0;
				$json_obj['msgid'] = MsgIds::NiuNiu_Update; 
				$json_obj['data'] = array(
					'status' => $room->status,
					'endst' =>  $fsm->nextst,
					'betcards' => $fsm->betcards
				);
				Gateway::sendToGroup($roomid,json_encode($json_obj));
			}
			elseif($room->status == 'betting')
			{
				if($fsm->nextst <= time()){
					$room->status = 'finish';
					$fsm->nextst = time() + 5;
					$fsm->betcards = $this->makeDealCard($fsm->cards,3,$roomid);

					$json_obj = array();
					$json_obj['ret'] = 0;
					$json_obj['msgid'] = MsgIds::NiuNiu_Update; 
					$json_obj['data'] = array(
						'status' => $room->status,
						'endst' =>  $fsm->nextst,
						'betcards' => $fsm->betcards
					);
					Gateway::sendToGroup($roomid,json_encode($json_obj));
				}
			}
			elseif($room->status == 'finish')
			{
				if($fsm->nextst <= time()){
					$room->status = 'idle';
					$fsm->nextst = time() + 5;
					$fsm->betcards = array();

					$json_obj = array();
					$json_obj['ret'] = 0;
					$json_obj['msgid'] = MsgIds::NiuNiu_Update; 
					$json_obj['data'] = array(
						'status' => $room->status,
						'endst' =>  $fsm->nextst,
						'betcards' => $fsm->betcards
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
			if(array_key_exists($room->roomid ,$this->_fsmDic)){

				$fsm = $this->_fsmDic[$room->roomid];

			}else{
				// 创建状态机
				$fsm = new EntityVo();
				$fsm->nextst = 0;
				$fsm->betcards = array();
				$fsm->betpools = array();
				for ($i=1; $i <= 4; $i++) { 
					$fsm->betpools[$i] = array();
				}
				$this->_fsmDic[$room->roomid] = $fsm;
			}
		}else{
			$json_obj['ret'] = 1;
			$json_obj['msg'] = '房间不存在!';
			Gateway::sendToClient($client_id,json_encode($json_obj));
			return;
		}

		$json_obj['ret'] = 0;
		$json_obj['data'] = array(
							'status' => $room->status,
							'endst' =>  $fsm->nextst,
							'betcards' => $fsm->betcards
						);
		$json_obj['msgid'] = MsgIds::NiuNiu_SyncGame;
		Gateway::sendToGroup($room->roomid,json_encode($json_obj));
	}

	public function makeSetPos($json_obj,$client_id,$pos,$target_seatIdx)
	{
		$room = LobbyMgr::GetInstance()->getRoomByClientId($client_id);
		if($room){

			if($room->status != 'idle'){

				$json_obj['ret'] = 1;
				$json_obj['msg'] = '游戏过程中不能更换位置!';
				Gateway::sendToClient($client_id,json_encode($json_obj));
				return;
			}

			$lastPersion = null;
			$curPersion = null;
			foreach ($room->places as $seatIdx => $persion) {
				if($persion && $persion->pos == $pos){
					$lastPersion = $persion;
					$room->places = $room->insertArray($room->places,$seatIdx,null);
				}
				if($persion && $persion->client_id == $client_id){
					$curPersion = $persion;
					$room->places = $room->insertArray($room->places,$seatIdx,null);
				}
			}


			if($pos == 'banker'){

				$lastSeatIdx = $curPersion->seatIdx;

				$curPersion->pos = $pos;
				$curPersion->seatIdx = $target_seatIdx;
				$room->places[$target_seatIdx] = $curPersion;

				if($lastPersion != null){
					$lastPersion->pos = 'watcher';
					$lastPersion->seatIdx = $lastSeatIdx;
					$room->places[$lastSeatIdx] = $lastPersion;
				}

			}elseif($pos =='poker'){

				$lastSeatIdx = $curPersion->seatIdx;

				$curPersion->pos = $pos;
				$curPersion->seatIdx = $target_seatIdx;
				$room->places[$target_seatIdx] = $curPersion;

				if($lastPersion != null){
					$lastPersion->pos = 'watcher';
					$lastPersion->seatIdx = $lastSeatIdx;
					$room->places[$lastSeatIdx] = $lastPersion;
				}

			}elseif($pos == 'watcher'){
				
				$lastSeatIdx = $curPersion->seatIdx;

				$null_seatIdx = null;
				foreach ($room->places as $seatIdx => $persion) {
					if($persion == null){
						$null_seatIdx = $seatIdx;
						break;
					}
				}

				$curPersion->pos = $pos;
				$curPersion->seatIdx = $null_seatIdx;
				$room->places[$null_seatIdx] = $curPersion;

				if($lastPersion != null){
					$lastPersion->pos = 'watcher';
					$lastPersion->seatIdx = $lastSeatIdx;
					$room->places[$lastSeatIdx] = $lastPersion;
				}
			}

			$json_obj['ret'] = 0;
			$json_obj['data'] = array(
				'client_id'	=>	$client_id,
				'pos'		=>	$pos
			);

			Gateway::sendToGroup($room->roomid,json_encode($json_obj));
		}else{
			$json_obj['ret'] = 1;
			$json_obj['msg'] = '房间不存在!';
			Gateway::sendToClient($client_id,json_encode($json_obj));
		}
	}

	//处理发牌
	public function makeDealCard(&$pokers,$deal_num,$roomid)
	{
		$card_persions = array();
		for ($areaidx=1; $areaidx <= 5; $areaidx++) { 
			$cards = array();
			for ($i=0; $i < $deal_num; $i++) { 
				array_push($cards,array_shift($pokers));
			}
			$card_persions[$areaidx] = $cards;
		}
		return $card_persions;
	}

	public function makeLeftRoom($roomid)
	{
		$room = LobbyMgr::GetInstance()->getRoomById($roomid);
		if($room == null)
		{
			$this->_fsmDic[$roomid] = null;
		}
	}

	public function makeSetBet($json_obj,$client_id,$betidx,$betnum)
	{
		$room = LobbyMgr::GetInstance()->getRoomByClientId($client_id);
		if($room){

			$fsm = $this->_fsmDic[$room->roomid];
			array_push($fsm->betpools[$betidx],$betnum);

			$json_obj['ret'] = 0;
			$json_obj['data'] = array(
				'client_id' => $client_id,
				'betidx' => $betidx,
				'betnum' => $betnum
			);
			Gateway::sendToGroup($room->roomid,json_encode($json_obj));

		}else{
			$json_obj['ret'] = 1;
			$json_obj['msg'] = '房间不存在!';
			Gateway::sendToClient($client_id,json_encode($json_obj));
		}
	}

	public function onClose($client_id)
	{
		foreach ($this->_fsmDic as $roomid => $fsm) {
			$room = LobbyMgr::GetInstance()->getRoomById($roomid);
			if($room == null)
			{
				$this->_fsmDic[$roomid] = null;
			}
		}

	}
}

