<?php

class GameConfig {

 	public static $gameDefs = array(
		'NiuNiu'	=> array('id'=> 1,'placeLimit'=>9,'roomLimit'=>100),
		'MJ_GD'		=> array('id'=> 2,'placeLimit'=>4,'roomLimit'=>100),
		'PokerDZ'	=> array('id'=> 3,'placeLimit'=>6,'roomLimit'=>100)
	);

	public static $gameStatus = array(
		'watch'		=> 0,
		'prepare'	=> 1,
		'beting'		=> 2,
		'offline' 	=> 3,
		'auto'		=> 4,
		'finish'		=> 5,
		'idle'		=> 6,
		'deal'		=> 7,
	);

	public static $gamePos = array(
		'banker',
		'poker',
		'watcher'
	);
}
