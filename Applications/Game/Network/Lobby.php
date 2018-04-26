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
			}else{
				$result = LobbyMgr::GetInstance()->makeCreateRoom($message_data->{"param"},$client_id);
			}
			$json_obj['ret'] = $result[0];
			$json_obj['data'] = $result[1];
			Gateway::sendToCurrentClient(json_encode($json_obj));

			require_once __DIR__ . sprintf('/Module/%sMgr.php',$message_data->{"param"}->{'gameid'});
        		eval(sprintf("%s::makeSyncGame(\$client_id);",$client_id));
		}
		elseif($msgid == MsgIds::Lobby_JoinRoom)
		{
			if(empty($message_data->{"roomid"}) || empty($message_data->{"param"}->{'gameid'})){
				$json_obj['ret'] = 1;
				$json_obj['msg'] = "参数有误！";
			}else{
				$result = LobbyMgr::GetInstance()->makeJoinRoom($message_data,$client_id);
			}
			$json_obj['ret'] = $result[0];
			$json_obj['data'] = $result[1];
			$json_obj['msg'] = $result[2];
			Gateway::sendToCurrentClient(json_encode($json_obj));

			require_once __DIR__ . sprintf('/Module/%sMgr.php',$message_data->{"param"}->{'gameid'});
        		eval(sprintf("%s::makeSyncGame(\$client_id);",$client_id));
		}
		elseif($msgid == MsgIds::Lobby_LeaveRoom)
		{
			$result = LobbyMgr::GetInstance()->makeLeftRoom($client_id);
			$json_obj['ret'] = $result[0];
		
			if($result[0] == 0){
				$json_obj['data'] = $result[1];
				$roomId = $result[1]['roomid'];
				Gateway::sendToGroup($roomId,json_encode($json_obj));
				Gateway::leaveGroup($client_id,$roomId);
			}else{
				$json_obj['msg'] = $result[1];
				Gateway::sendToCurrentClient(json_encode($json_obj));
			}
		}
	}
}