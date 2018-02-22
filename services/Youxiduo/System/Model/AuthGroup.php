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
 * 组权限模型类
 */
final class AuthGroup extends Model implements IModel
{		
    public static function getClassName()
	{
		return __CLASS__;
	}
	
	public static function getList($pageIndex=1,$pageSize=20)
	{
		$result = self::db()->orderBy('group_id','asc')->forPage($pageIndex,$pageSize)->get();
		return $result;
	}
	
	public static function getInfo($group_id)
	{
		$info = self::db()->where('group_id','=',$group_id)->first();
		if($info){
			$info['menus_nodes'] = json_decode($info['menus_nodes'],true);
		}
		return $info;
	}
	
	public static function getNameList()
	{
		$groups = self::db()->orderBy('group_id','asc')->lists('group_name','group_id');
		
		return $groups;
	}
	
	public static function saveInfo($data)
	{
		if(isset($data['group_id']) && $data['group_id']){
			$group_id = $data['group_id'];
			unset($data['group_id']);
			self::db()->where('group_id','=',$group_id)->update($data);
			return $group_id;
		}else{
			unset($data['group_id']);
			return self::db()->insertGetId($data);
		}
	}

	public static function del($group_id)
	{
		if($group_id > 0){
			$re = self::db()->where('group_id','=',$group_id)->delete();
		}
		return $re;
	}
}