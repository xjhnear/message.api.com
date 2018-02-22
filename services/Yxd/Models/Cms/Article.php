<?php
namespace Yxd\Services\Models\Cms;

use Illuminate\Support\Facades\DB;

class Article
{
	const CONN = 'cms';	
	
	public static function getGameList($page=1,$size=10)
	{
		return DB::connection(self::$CONN)->table('games')->forPage($page,$size)->get();
	}
}