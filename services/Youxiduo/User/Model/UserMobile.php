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
namespace Youxiduo\User\Model;

use Youxiduo\Base\Model;
use Youxiduo\Base\IModel;
/**
 * 用户手机模型类
 */
final class UserMobile extends Model implements IModel
{	
    public static function getClassName()
	{
		return __CLASS__;
	}	
	
	public static function saveVerifyCodeByPhone($mobile,$type,$verifycode,$is_valid=false,$udid='')
	{
//		if($ip && MobileSmsHistory::checkSmsNumber($mobile,$ip)===false){
//			return false;
//		}
		$info = self::db()->where('mobile','=',$mobile)->first();
		if($info){
			$data = array();
			$data['verifycode'] = $verifycode;
			$data['type'] = $type;
			$data['expire'] = time() + 60*30;
			$data['last_sendtime'] = time();
			$data['updated_at'] = time();
			$data['error_num'] = 0;
			$res = self::db()->where('mobile','=',$mobile)->update($data);
		}else{
			$data = array();
			$data['mobile'] = $mobile;
			$data['created_at'] = time();
			$data['updated_at'] = time();
			$data['is_valid'] = $is_valid==true ? 1 : 0;
			$data['verifycode'] = $verifycode;
			$data['type'] = $type;
			$data['expire'] = time() + 60*30;
			$data['last_sendtime'] = time();
			$data['error_num'] = 0;
			$res = self::db()->insertGetId($data);
		}
		return $res ? true : false;
	}

	public static function verifyPhoneVerifyCode($mobile,$type,$verifycode,&$num)
	{
		$info = self::db()->where('mobile','=',$mobile)->where('verifycode','=',$verifycode)->where('type','=',$type)->where('expire','>',time())->first();
		if($info){
			$data['is_valid'] = 1;
			//$data['verifycode'] = '';
			$data['last_sendtime'] = 0;
			$data['updated_at'] = time();
			$data['error_num'] = 0;
			$res = self::db()->where('mobile','=',$mobile)->where('verifycode','=',$verifycode)->update($data);
			return true;
		}else{
			self::db()->where('mobile','=',$mobile)->increment('error_num',1,array('expire'=>0));
			$num = self::db()->where('mobile','=',$mobile)->pluck('error_num');
		}
		return false;
	}
	
	public static function phoneVerifyStatus($mobile,$expire=false)
	{
		if(!$mobile) return false;
		$tb = self::db()->where('mobile','=',$mobile)->where('is_valid','=',1);
		if($expire===true){
			$tb = $tb->where('expire','>',time());
		}
		$info = $tb->first();
		return $info ? true : false;
	}
}