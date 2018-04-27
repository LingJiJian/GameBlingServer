<?php

class GameConfig {

 	public static $gameDefs = array(
		'NiuNiu'		=> array('id'=> 1,'limit'=>10),
		'MJ_GD'		=> array('id'=> 2,'limit'=>4),
		'PokerDZ'	=> array('id'=> 3,'limit'=>6)
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
