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

use Illuminate\Support\Facades\Config;
use Youxiduo\Base\Model;
use Youxiduo\Base\IModel;

use Youxiduo\Helper\Utility;
/**
 * 账号模型类
 */
final class AccountSession extends Model implements IModel
{	
    public static function getClassName()
	{
		return __CLASS__;
	}
	
	public static function saveSession($uid,$session,$client='android')
	{
		$data = array('uid'=>$uid,'access_token'=>$session,'client'=>$client,'ctime'=>time());
		$info = self::db()->where('uid','=',$uid)->where('client','=',$client)->orderBy('ctime','desc')->first();
		if($info){			
			return self::db()->where('id','=',$info['id'])->update($data);
		}else{					
			return self::db()->insertGetId($data);
		}
	}
	
	public static function makeSession()
	{
		return md5(str_random(32));
	}
	
	public static function getUidFromSession($session_id)
	{
		$info = self::db()->where('access_token','=',$session_id)->first();
		if($info) return $info['uid'];
		return 0;
	}
	
    public static function getSessionFromUid($uid)
	{
		$info = self::db()->where('uid','=',$uid)->first();
		if($info) return $info['access_token'];
		return '';
	}
	
	public static function verifySession($uid,$session)
	{
		if(!$uid) return true;
		$expire = time() - 3600*24*7;
		$exists = self::db()->where('uid','=',$uid)->where('access_token','=',$session)->first();
		return $exists ? true : false;
	}
}