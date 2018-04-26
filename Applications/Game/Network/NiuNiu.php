<?php

require_once __DIR__ . '/../Common/MsgIds.php';
require_once __DIR__ . '/../Module/NiuNiuMgr.php';

use \GatewayWorker\Lib\Gateway;

class NiuNiu
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
		if($msgid == MsgIds::NiuNiu_SetPos)
		{
			// NiuNiuMgr::GetInstance()->makeSetPos($message_data);
		}
	}

	public static function SendDealCard($client_ids)
	{
		NiuNiuMgr::GetInstance()->makeDealCard($client_ids);
		
		// Gateway::
	}
}