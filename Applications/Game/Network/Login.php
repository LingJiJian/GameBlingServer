<?php

require_once __DIR__ . '/../Common/MsgIds.php';
require_once __DIR__ . '/../Module/LoginMgr.php';

use \GatewayWorker\Lib\Gateway;

class Login
{
	public static function onMessage($client_id, $message)
	{
		$message_data = json_decode($message);
		$msgid = $message_data->{'msgid'};

		$json_obj = array();
		$json_obj['msgid'] = $msgid;
		$json_obj['ret'] = 999;
		$json_obj['msg'] = '';

		if($msgid == MsgIds::Login_Login)
		{
			if(empty($message_data->{"account"})){
				$json_obj['ret'] = 1;
				$json_obj['msg'] = "参数有误！";
			}else{
				$json_obj = LoginMgr::GetInstance()->makeLoginClient($json_obj,$client_id,$message_data->{"account"});
			}
		}

		Gateway::sendToCurrentClient(json_encode($json_obj));
	}
}