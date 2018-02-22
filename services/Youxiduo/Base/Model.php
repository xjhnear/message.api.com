<?php
/**
 * @package Youxiduo
 * @category Base 
 * @link http://dev.youxiduo.com
 * @copyright Copyright (c) 2008 Youxiduo.com 
 * @license http://www.youxiduo.com/license
 * @since 4.0.0
 *
 */
namespace Youxiduo\Base;

//use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis as Redis;

/**
 * 模型类
 * 
 * @author mawenpei
 * @version 4.0.0
 */
class Model
{	
	protected static $redis = array();
	
	/**
	 * 初始化模型数据库
	 */
	public static function db()
	{
		list($conn,$table,$cache) = static::getConfig();
		return DB::connection($conn)->table($table);
	}
	
	/**
	 * 执行事务
	 */
	protected static function transaction($callback)
	{
		list($conn,$table,$cache) = static::getConfig();		
		return DB::connection($conn)->transaction($callback);
	}
	
	protected static function cache()
	{
		list($conn,$table,$cache) = static::getConfig();	
		$app = App::make('app');
		return $app['cache'];
	}
	
	public static function getClassName()
	{
		return __CLASS__;
	}
	
	protected static function getConfig()
	{
	    $class_name = static::getClassName();
		$class_config = strtolower(str_replace('\\','_',$class_name));
		$db_config = Config::get('dbtable.'.$class_config);
		$conn = array_get($db_config,'db','');
		$table = array_get($db_config,'table');
		$cache = array_get($db_config,'cache','');
		if(!$table){
			throw new \InvalidArgumentException('模型"' . $class_name . '"对应的数据库表没有配置或不存在');
		}
		return array($conn,$table,$cache);
	}
	
	public static function raw($sql)
	{
		list($conn,$table,$cache) = static::getConfig();
		return DB::connection($conn)->raw($sql);
	}

    public static function getQueryLog()
    {
        list($conn,$table,$cache) = static::getConfig();
        return DB::connection($conn)->getQueryLog();
    }
	
	public static function execUpdateBySql($sql,$bindings=array())
	{
		list($conn,$table,$cache) = static::getConfig();
		return DB::connection($conn)->update($sql,$bindings);
	}
	
	public static function execQuery($sql,$bindings = array())
	{
		list($conn,$table,$cache) = static::getConfig();
		return DB::connection($conn)->select($sql,$bindings);
	}
	
    public static function redis($config='')
	{
		$key = 'conn_' . $config;
		if(!isset(static::$redis[$key])){
		    static::$redis[$key] = Redis::connection($config);
		}
		return static::$redis[$key];
	}

    public static function getByMultiCondition($conditions){
        if(!is_array($conditions) || !$conditions) return false;
        list($conn,$table,$cache) = static::getConfig();
        $db = DB::connection($conn)->table($table);

        foreach ($conditions as $item) {
            $db->where($item[0],$item[1],$item[2]);
        }

        $result = $db->get();
        return $result;
    }
	
	public static function __callStatic($method,$args)
	{
		
	}
}