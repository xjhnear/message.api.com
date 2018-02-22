<?php
/**
 * @category Core
 * @link http://www.youxiduo.com
 * @author mawenpei<mawenpei@cwan.com>
 * @since 2014-03-15
 * @version 3.0.0
 */
namespace Yxd\Modules\Core;

use Illuminate\Support\Facades\App;
use Illuminate\Cache\CacheManager;

/**
 * 缓存服务类
 */

class CacheService
{	
	private static $file;
	private static $memcached;
	private static $redis;
	
	/**
	 * 文件缓存
	 */
	public static function file()
	{
		$app = App::make('app');
		if(!isset(self::$file)){
			$manager = new CacheManager($app);
			self::$file = $manager->driver('file');
		}
		return self::$file;
	}
	
	/**
	 * Memcacahe缓存
	 */
	public static function memcached()
	{
		$app = App::make('app');
		if(!isset(self::$memcached)){
			$manager = new CacheManager($app);
			self::$memcached = $manager->driver('memcached');
		}
		return self::$memcached;
	}
	
	/**
	 * Redis缓存
	 */
	public static function redis()
	{
		$app = App::make('app');
		if(!isset(self::$redis)){
			$manager = new CacheManager($app);
			self::$redis = $manager->driver('redis');
		}
		return self::$redis;
	}
	
	protected static function getDefaultCacheDriver()
	{
		return self::redis();
		//return self::memcached();
	}
	
	/**
	 * 默认缓存处理
	 */
	public static function __callstatic($method,$params)
	{
		return call_user_func_array(array(self::getDefaultCacheDriver(),$method),$params);
	}    
}