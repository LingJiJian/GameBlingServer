<?php

require_once __DIR__ . '/../Common/MsgIds.php';
require_once __DIR__ . '/../Module/LobbyMgr.php';

use \GatewayWorker\Lib\Gateway;

class Lobby
{
	public static function onMessage($client_id, $message)
	{
		$message_data = json_decode($message);
		$msgid = $message_data->{'msgid'};

		$json_obj = array();
		$json_obj['msgid'] = $msgid;
		$json_obj['ret'] = 999;
		$json_obj['msg'] = '';

		$result = array(999,'');
		if($msgid == MsgIds::Lobby_CreateRoom)
		{
			if(empty($message_data->{"param"}->{'gameid'})){
				$json_obj['ret'] = 1;
				$json_obj['msg'] = "参数有误！";
				Gateway::sendToCurrentClient(json_encode($json_obj));
				return;
			}else{
				$result = LobbyMgr::GetInstance()->makeCreateRoom($message_data->{"param"},$client_id);
			}

			$json_obj['ret'] = $result[0];
			$json_obj['data'] = $result[1];

			print_r('创建房间:' . $result[1]['roomid']);

			Gateway::sendToCurrentClient(json_encode($json_obj));

			require_once __DIR__ . sprintf('/../Module/%sMgr.php',$message_data->{"param"}->{'gameid'});
        	eval(sprintf("%sMgr::GetInstance()->makeSyncGame(\$json_obj,\$client_id);",
        			$message_data->{"param"}->{'gameid'},
        			$json_obj,
        			$client_id));
		}
		elseif($msgid == MsgIds::Lobby_JoinRoom)
		{
			$roomid = $message_data->{"param"}->{"roomid"}

			if(empty($roomid) || empty($message_data->{"param"}->{'gameid'})){
				$json_obj['ret'] = 1;
				$json_obj['msg'] = "参数有误！";
				Gateway::sendToCurrentClient(json_encode($json_obj));
				return;
			}else{
				$result = LobbyMgr::GetInstance()->makeJoinRoom($message_data->{"param"},$client_id);
			}
			$json_obj['ret'] = $result[0];
			$json_obj['data'] = $result[1];
			$json_obj['msg'] = $result[2];
			Gateway::sendToCurrentClient(json_encode($json_obj));

			//通知其他玩家
			if($result[0] == 0){

				$json_join = array();
				$json_join['ret'] = 0;
				$json_join['data'] = array(
					'gameid'=>$message_data->{"param"}->{'gameid'},
					'persion'=>LobbyMgr::GetInstance()->getPersionByClientId($roomid,$client_id)->getData();
				);
				
				Gateway::sendToGroup($roomid,json_encode($json_join));
			}

			require_once __DIR__ . sprintf('/../Module/%sMgr.php',$message_data->{"param"}->{'gameid'});
        	eval(sprintf("%sMgr::GetInstance()->makeSyncGame(\$json_obj,\$client_id);",
        			$message_data->{"param"}->{'gameid'}));
		}
		elseif($msgid == MsgIds::Lobby_LeaveRoom)
		{
			$result = LobbyMgr::GetInstance()->makeLeftRoom($client_id);
			$json_obj['ret'] = $result[0];
		
			if($result[0] == 0){
				$json_obj['data'] = $result[1];
				$roomid = $result[1]['roomid'];
				Gateway::sendToGroup($roomid,json_encode($json_obj));
				Gateway::leaveGroup($client_id,$roomid);

				$gameid = $result[1]['gameid'];
				require_once __DIR__ . sprintf('/../Module/%sMgr.php',$gameid);
				eval(sprintf("%sMgr::GetInstance()->makeLeftRoom(\$roomid);",$gameid));

			}else{
				$json_obj['msg'] = $result[1];
				Gateway::sendToCurrentClient(json_encode($json_obj));
			}
		}
	}
}