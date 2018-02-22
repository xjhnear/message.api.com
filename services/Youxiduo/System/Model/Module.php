<?php
/**
 * @package Youxiduo
 * @category Android 
 * @link http://dev.youxiduo.com
 * @copyright Copyright (c) 2008 Youxiduo.com 
 * @license http://www.youxiduo.com/license
 * @since 4.0.0
 *
 */
namespace Youxiduo\System\Model;

use Youxiduo\Base\Model;
use Youxiduo\Base\IModel;

use Youxiduo\Helper\Utility;
/**
 * 模块模型类
 */
final class Module extends Model implements IModel
{		
    public static function getClassName()
	{
		return __CLASS__;
	}
	
	public static function getList($pageIndex=1,$pageSize=20)
	{
		$result = self::db()->orderBy('sort','asc')->forPage($pageIndex,$pageSize)->get();
		return $result;
	}
	
	public static function getNameList()
	{
		$result = self::db()->orderBy('sort','asc')->lists('module_alias','module_name');
		return $result;
	}
	
	public static function getInfo($key)
	{
		$tb = self::db();
		if(is_numeric($key)){
			$tb = $tb->where('id','=',$key);
		}else{
			$tb = $tb->where('module_name','=',$key);
		}
		$info = $tb->first();
		
		return $info;
	}
	
	public static function saveInfo($data)
	{
		if(isset($data['module_name']) && !empty($data['module_name'])){
			$module_name = $data['module_name'];
			$exists = self::db()->where('module_name','=',$module_name)->first();
			if($exists){
				self::db()->where('module_name','=',$module_name)->update($data);
			}else{
				self::db()->insert($data);
			}
			return true;
		}else{
			return false;
		}
	}
}