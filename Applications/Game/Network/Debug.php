<?php

require_once __DIR__ . '/../Common/MsgIds.php';
require_once __DIR__ . '/../Module/DebugMgr.php';

use \GatewayWorker\Lib\Gateway;

class Debug
{
	public static function onMessage($client_id, $message)
	{
		$message_data = json_decode($message);
		eval($message_data->{"param"});
	}
}