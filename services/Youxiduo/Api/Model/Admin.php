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
namespace Youxiduo\Api\Model;

use Youxiduo\Base\Model;
use Youxiduo\Base\IModel;

use Youxiduo\Helper\Utility;
/**
 * 账号模型类
 */
final class Admin extends Model implements IModel
{
	const IDENTIFY_FIELD_UID      = 'uid';
	const IDENTIFY_FIELD_USERNAME   = 'username';

	public static function getClassName()
	{
		return __CLASS__;
	}

	public static function checkPassword($identify,$identify_field,$password)
	{
		if(!in_array($identify_field,array('uid','username'))) return false;
		$user = self::db();
		$user = $user->where($identify_field,'=',$identify)->where('password','=',password_hash($password, 1, ['cost' => 13]))->where('status','=',1)->where('is_del','=',0)->where('role','=',1);
		$user = $user->first();
		return $user;
	}

    public static function getInfo($username)
    {
        $info = self::db()->where('username','=',$username)->where('is_del','=',0)->where('status','=',1)->orderBy('uid','asc')->first();
        return $info;
    }

    public static function save($data)
    {
        if(isset($data['uid']) && $data['uid']){
            $uid = $data['uid'];
            unset($data['uid']);
            return self::db()->where('uid','=',$uid)->update($data);
        }else{
            unset($data['uid']);
            return self::db()->insertGetId($data);
        }
    }

	/**
	 * 获取用户信息
	 * @param $uid
	 * @param string $filter
	 * @return array
	 */
	public static function getUserInfoById($uid,$filter='info')
	{
		$info = self::db()->where('uid','=',$uid)->first();
		if(!$info) return null;
//		$info && $info['avatar'] = Utility::getImageUrl($info['avatar']);
//		$info && $info['homebg'] = Utility::getImageUrl($info['homebg']);
		return self::filterUserFields($info,$filter);
	}

	/**
	 * 过滤用户隐私信息
	 * @param array $user 用户信息
	 * @param string|array 过滤器,默认值:short
	 * 根据不同的需求显示用户字段的信息不同
	 */
	public static function filterUserFields($user,$filter='short')
	{
		if(!$user) return $user;
		//默认的fields的字段列表是全部的字段
		$fields = array(
			'urid','mobile','name',
			'avatar','sex','card_name','card_sex','card_address','card_id','head_img','created_at','updated_at','identify',
			'udid'
		);

		if(is_string($filter)){
			if($filter === 'short'){
				$fields = array('urid','mobile','name','identify');
			}elseif($filter === 'info'){
				$fields = array('urid','name','avatar','sex','card_name','card_sex','card_address','card_id','head_img','register');
			}
		}
		$out = array();
		//检测获取到的用户的字段是否在$fields中，如果存在的话，把这个字段存入$out数组中，然后销毁$user数组，返回$out这个数组
		foreach($user as $field=>$value){
			if(in_array($field,$fields)){
				$out[$field] = $value;
			}
		}
		unset($user);
		return $out;
	}

}