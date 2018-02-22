<?php
namespace Yxd\Modules\System;
use Yxd\Modules\Core\BaseService;
use modules\system\models\AdminModel;

class AdminService extends BaseService
{
	/**
	 * 获取管理员总数
	 */
	public static function getAdminListCount(){
		return AdminModel::getAdminListCount();
	}
	
	/**
	 * 获取管理员列表
	 * @param int $page 第几页
	 * @param int $pagesize 每页显示条数
	 * @return array 
	 */
    public static function getAdminList($page,$pagesize){
    	$admins = AdminModel::getAdmins($page,$pagesize);
    	if(!$admins) return array();
    	$admin_ids = array();
    	foreach ($admins as $row){
    		$admin_ids[] = $row['id'];
    	}
    	$result = AdminModel::getAdminsRoleByIds($admin_ids);
    	$relation = array();
    	if($result){
    		foreach ($result as $row){
    			$relation[$row['admin_id']] = $row;
    		}
    	}
    	foreach ($admins as &$item){
    		if(array_key_exists($item['id'], $relation)){
    			$item['group_id'] = $relation[$item['id']]['group_id'];
    			$item['mcp_role'] = $relation[$item['id']]['name'];
    			$item['description'] = $relation[$item['id']]['description'];
    		}else{
    			$item['group_id'] = false;
    			$item['mcp_role'] = false;
    			$item['description'] = '暂无';
    		}
    	}
    	return $admins;
    }
    
    /**
     * 获取管理员信息
     * @param int $admin_id
     */
    public static function getAdminInfo($admin_id){
    	if(!$admin_id) return array();
    	$base_info = AdminModel::getAdminInfo($admin_id);
    	$base_info = current($base_info);
    	$group_info = AdminModel::getAdminGroupByAdminId($admin_id);
    	if($group_info){
    		$base_info['mcp_role'] = $group_info['name'];
    		$base_info['role_level'] = $group_info['level'];
    		$base_info['group_id'] = $group_info['group_id'];
    		$base_info['description'] = $group_info['description'];
    	}else{
    		$base_info['mcp_role'] = false;
    		$base_info['group_id'] = false;
    		$base_info['role_level'] = 99;
    		$base_info['description'] = '暂无';
    	}
    	return $base_info;
    }
}