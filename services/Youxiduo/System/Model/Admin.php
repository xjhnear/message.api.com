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
 * 账号模型类
 */
final class Admin extends Model implements IModel
{		
    public static function getClassName()
	{
		return __CLASS__;
	}
	
	public static function getInfoByUsername($username)
	{
		$info = self::db()->where('username','=',$username)->where('isopen','=',1)->first();
		if($info['menus_nodes']) $info['menus_nodes'] = json_decode($info['menus_nodes'],true);
		return $info;
	}
	
	public static function getInfoById($admin_id)
	{
		$info = self::db()->where('id','=',$admin_id)->first();
		if($info['menus_nodes']) $info['menus_nodes'] = json_decode($info['menus_nodes'],true);
		return $info;
	}
	
	public static function getList($search,$pageIndex=1,$pageSize=20)
	{
		$tb = self::db();
		if(isset($search['group_id']) && $search['group_id']>0) $tb = $tb->where('group_id','=',$search['group_id']);
		if(isset($search['in_group_id']) && $search['in_group_id']) $tb = $tb->whereIn('group_id',$search['in_group_id']);
		if(isset($search['username']) && !empty($search['username'])) $tb = $tb->where('username','like','%'.$search['username'].'%');
		if(!isset($search['showall']) || empty($search['showall']) || $search['showall']==0) $tb = $tb->where('isopen','=',1);
		return $tb->orderBy('id','asc')->forPage($pageIndex,$pageSize)->get();
	}
	
	public static function getCount($search)
	{
		$tb = self::db();
		if(isset($search['group_id']) && $search['group_id']>0) $tb = $tb->where('group_id','=',$search['group_id']);
		if(isset($search['username']) && !empty($search['username'])) $tb = $tb->where('username','like','%'.$search['username'].'%');
		if(!isset($search['showall']) || empty($search['showall']) || $search['showall']==0) $tb = $tb->where('isopen','=',1);
		return $tb->count();
	}
	
	public static function saveInfo($data)
	{
		if(isset($data['password']) && $data['password']){
			$data['password'] = md5($data['password']);
		}
		if(isset($data['id']) && $data['id']){
			$id = $data['id'];
			unset($data['id']);
			self::db()->where('id','=',$id)->update($data);
			return $id;
		}else{
			return self::db()->insertGetId($data);
		}
	}
	
	public static function modifyStatus($admin_id,$status)
	{
		return self::db()->where('id','=',$admin_id)->update(array('isopen'=>$status));
	}

	public static function del($admin_id)
	{
		if($admin_id > 0){
			$re = self::db()->where('id','=',$admin_id)->delete();
		}
		return $re;
	}
}