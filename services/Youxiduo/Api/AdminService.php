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
namespace Youxiduo\Api;

use Illuminate\Support\Facades\Config;
use Youxiduo\Base\BaseService;
use Youxiduo\Api\Model\Admin;
use Youxiduo\Helper\Utility;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Redis;


class AdminService extends BaseService
{

	public static function checkRedis()
	{
		$operator = Redis::get("isp_1391743");
		return $operator;
	}

	public static function checkPassword($uid,$password)
	{
		$user = Admin::checkPassword($uid,Admin::IDENTIFY_FIELD_UID,$password);
		$exists = $user ? true : false;
		return array('result'=>$exists,'data'=>$user);
	}

	public static function checkPasswordbyMobile($username,$password)
	{
		$user = Admin::doLocalLogin($username,Admin::IDENTIFY_FIELD_USERNAME,$password);
		$exists = $user ? true : false;
		return array('result'=>$exists,'data'=>$user);
	}

	/**
	 * 获取用户信息
	 */
	public static function getUserInfo($uid)
	{
		$user = Admin::getUserInfoById($uid);
		if($user){
//			if($user['mobile']){
//				$user['mobile'] = preg_replace('/(1[3578]{1}[0-9])[0-9]{4}([0-9]{4})/i','$1****$2',$user['mobile']);
//			}
			return array('result'=>true,'data'=>$user);
		}
		return array('result'=>false,'msg'=>"用户不存在");
	}

	/**
	 * 获取用户状态
	 */
//	public static function getUseridentify($urid)
//	{
//		$user = User::getUserInfoById($urid,'short');
//		if($user){
//			$result = array();
//            $result['result'] = $user['identify'];
//			return array('result'=>true,'data'=>$result);
//		}
//		return array('result'=>false,'msg'=>"用户不存在");
//	}

}