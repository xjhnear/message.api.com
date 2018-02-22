<?php
namespace Yxd\Models;

use Illuminate\Support\Facades\DB as DB;
use Illuminate\Support\Facades\Event;
use Yxd\Services\Models\AccountThirdLogin;
use Yxd\Services\Models\Account;

class Passport extends User
{
    /**
	 * 本地帐号登录
	 */
	public static function verifyLocalLogin($identify,$password,$identify_field = 'email')
	{
		return parent::verifyLocalLogin($identify, $password,$identify_field);	
	}
	
	/**
	 * 第三方帐号登录
	 */
	public static function verifyThirdLogin($account_type,$access_token)
	{
		$third = array('sina'=>1,'qq'=>2);
		if(!array_key_exists($account_type,$third)) return false;
		$account_type = $third[$account_type];
		$token = AccountThirdLogin::db()
		         ->where('type','=',$account_type)
		         ->where('access_token','=',$access_token)
		         ->first();
		if($token && isset($token['uid'])){
			$user = self::getUserInfo($token['uid'],'uid');
			return $user;
		}else{
			return null;
		}
	}
	
	/**
	 * 
	 */
	public static function checkEmailIsExists($email)
	{
		$count = Account::db()->where('email','=',$email)->count();
		return $count>0 ? true : false;
	}
	
	public static function checkNickNameIsExists($nickname)
	{
		$count = Account::db()->where('nickname','=',$nickname)->count();
		return $count>0 ? true : false;
	}
	
    /**
	 * 绑定第三方帐号
	 */
	public static function bindThirdLogin($uid,$type,$type_uid,$access_token,$expires_in,$refresh_token)
	{
		$third = array('sina'=>1,'qq'=>2);
		if(!array_key_exists($type,$third)) return false;
		$data = array();
		$data['uid'] = $uid;
		$data['type'] = $third[$type];
		$data['type_uid'] = $type_uid;
		$data['access_token'] = $access_token;
		$data['expires_in'] = $expires_in;
		$data['refresh_token'] = $refresh_token;
		
		$token = AccountThirdLogin::db()->where('type','=',$third[$type])->where('type_uid','=',$type_uid)->first();
		if($token){
			return 1;
		}
		$id = AccountThirdLogin::db()->insertGetId($data);
		if($id){
			Event::fire('user.bindthird',array($data));
		}
		return $id;
	}
	
	/**
	 * 解除所有第三方帐号绑定
	 */
	public static function unbindThirdLogin($uid,$access_token)	
	{
		return AccountThirdLogin::db()->where('uid','=',$uid)->where('access_token','=',$access_token)->delete();
	}
	
	public static function unbindAllThirdLogin($uid)
	{
		return AccountThirdLogin::db()->where('uid','=',$uid)->delete();
	}
	
	/**
	 * 判断是否被绑定
	 */
	public static function isExistsBind($access_token)
	{
		return AccountThirdLogin::db()->where('account_token','=',$access_token)->count() > 0 ? true : false;
	}
	
    /**
	 * 检查用户权限
	 * @deprecated
	 */
	public static function checkUserAuthorize($uid,$node_rule)
	{
		return true;
		$user_nodes = self::getUserGroupView($uid);
		$nodes = DB::table('authorize_node')->lists('id','rule');
		if(isset($nodes[$node_rule])){
			$node = $nodes[$node_rule];			
			if(in_array($node,$user_nodes['authorize'])){
				return true;
			}
		}
		return false;
	}    
}