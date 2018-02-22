<?php
/**
 * @category System
 * @link http://www.youxiduo.com
 * @author mawenpei<mawenpei@cwan.com>
 * @since 2014-03-15
 * @version 3.0.0
 */
namespace Yxd\Modules\System;

use Yxd\Modules\Core\BaseService;
use modules\system\models\PermissionModel;
/**
 * 权限服务类
 */
class PermissionService extends BaseService
{
    public static function getPermissionSettingData($group_name){
    	if(!$group_name) return false;
    	$permission_result = PermissionModel::getPermissionByGroupName($group_name);
    	$sys_set_data = array();
    	if($permission_result){
    		foreach ($permission_result as $key=>$row){
    			$sys_set_data[$row['permission_id']] = $row;
    		}
    	}
    	return $sys_set_data;
    }
}