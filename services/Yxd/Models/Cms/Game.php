<?php
namespace Yxd\Models\Cms;

use Illuminate\Support\Facades\DB;

class Game
{
	protected static  $CONN = 'cms';
		
	public static function getGameList($page=1,$size=10)
	{
		return DB::connection(self::$CONN)->table('games')->forPage($page,$size)->get();
	}
	
	public static function getGameInfo($identity,$identity_type='id')
	{
		$game = DB::connection(self::$CONN)->table('games')->where($identity_type,'=',$identity)->first();
		
		return $game;
	}
}