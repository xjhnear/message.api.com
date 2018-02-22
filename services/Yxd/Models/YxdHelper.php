<?php

namespace Yxd\Models;

use Illuminate\Support\Facades\DB;

class YxdHelper
{
	public static function Id()
	{
		return 1;
	} 
	
	public static function NickName()
	{
		return '游戏多助手';
	}
	
	public static function Avatar()
	{
		return '/userdirs/common/yxd_logo.png?time=' . time();
	}
	
	public static function LevelIcon()
	{
		return '/userdirs/common/level/level10@2x.png?v=4';
	}
	
	public static function LevelName()
	{
		return '管理员';
	}
	
	public static function getHelper()
	{
		return array(
		    'uid'=>self::Id(),
		    'nickname'=>self::NickName(),
		    'avatar'=>self::Avatar(),
		    'level_name'=>self::LevelName(),
		    'level_icon'=>self::LevelIcon()
		);
	}
}