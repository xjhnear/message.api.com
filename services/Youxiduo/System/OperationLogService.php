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
namespace Youxiduo\System;


use Youxiduo\Base\BaseService;

class OperationLogService extends BaseService
{
    /**
     * 记录操作日志
     * @param $module_name
     * @param $log
     * @param $ip
     */
    public static function recordLog($module_name,$log,$ip)
    {
        $data = array();
        $admin = AuthService::getAuthAdmin();
        $data['admin_id'] = $admin['id'];
        $data['admin_name'] = $admin['username'];
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['module_name'] = $module_name;
        $data['log'] = $log;
        $data['ip'] = $ip;
    }
}