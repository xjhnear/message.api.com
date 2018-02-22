<?php
/**
 * @category Modules/Core
 * @link http://www.youxiduo.com
 * @author mawenpei<mawenpei@cwan.com>
 * @since 2014-03-15
 * @version 3.0.0
 */
namespace Yxd\Modules\Core;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis as Redis;
/**
 * 模型基类
 */
abstract class BaseModel
{
	protected static $redis = array();
	
	/**
	 * 
	 */
	public static function redis($config='')
	{
		$key = 'conn_' . $config;
		if(!isset(static::$redis[$key])){
		    static::$redis[$key] = Redis::connection($config);
		}
		return static::$redis[$key];
	}
	
	public static function queue($config='')
	{
		//$config = 'queue';
		$key = 'conn_queue_' . $config;
		if(!isset(static::$redis[$key])){
		    static::$redis[$key] = Redis::connection($config);
		}
		return static::$redis[$key];
	}
	
	/**
	 * 资讯数据库主库
	 */
    public static function dbCmsMaster()
	{
		return DB::connection('cms');
	} 
	
	/**
	 * 社区数据库主库
	 */
	public static function dbClubMaster()
	{
		return DB::connection('mysql');
	}
	
	/**
	 * 资讯数据库从库
	 */
	public static function dbCmsSlave()
	{
		return DB::connection('cms');
	}
	
	/**
	 * 社区数据库从库
	 */
    public static function dbClubSlave()
	{
		return DB::connection('mysql');
	}
	
	/**
	 * 论坛V4数据库
	 */
	public static function dbForumMaster()
	{
		return DB::connection('forum');
	}
	
	/**
	 * 资讯数据库主库
	 */
	public static function dbYxdMaster()
	{
		return DB::connection('yxd');
	}

	/**广告表***/
	public static function dbAdvMaster()
	{
		return DB::connection('adv');
	}

	public static function DB($database='')
	{
		return DB::connection($database);
	}

	public static  function getlog(){
		return DB::getQueryLog();
	}
	
	public static function getAppVersion($field = 'version')
	{
		$info = Input::only('appname','version');
		if($field && isset($info[$field])){
			return $info[$field];
		}
		return $info['version'];
	}
	
}