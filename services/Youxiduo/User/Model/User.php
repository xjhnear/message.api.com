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
final class User extends Model implements IModel
{	
	const IDENTIFY_FIELD_URID      = 'urid';
	const IDENTIFY_FIELD_MOBILE   = 'mobile';
	const IDENTIFY_FIELD_NAME = 'name';
	
	
    public static function getClassName()
	{
		return __CLASS__;
	}

	/**
	 * 账号登录
	 */
	public static function doLocalLogin($identify,$identify_field,$password,$register)
	{
		if(!in_array($identify_field,array('urid','mobile'))) return false;
//		if(strlen($password) != 32){
//			$password = Utility::cryptPwd($password);
//		}
		$user = self::db();
		$user = $user->where($identify_field,'=',$identify)->where('password','=',$password);
		if ($register==1) {
			$user = $user->where('register','=',$register);
		}
		$user = $user->first();
		return $user;
	}

	public static function isExistsByField($identify,$identify_field,$uid=0)
	{
		if(!in_array($identify_field,array('mobile','name'))) return true;
		$tb = self::db()->where($identify_field,'=',$identify);
		if($uid){
			$tb = $tb->where('uid','!=',$uid);
		}
		$user = $tb->first();
		return $user ? true : false;
	}

	/**
	 * 创建用户通过手机号
	 */
	public static function createUserByPhone($mobile,$password)
	{
		$data = array();
		$data['mobile'] = $mobile;
		$data['password'] = Utility::cryptPwd($password);
		$data['created_at'] = time();
		$data['updated_at'] = time();
		$uid = self::db()->insertGetId($data);
		return $uid;
	}

	/**
	 * 修改密码
	 */
	public static function modifyUserPwd($identify,$identify_field,$password)
	{
		if(!in_array($identify_field,array('mobile','urid'))) return false;
		$user = self::getUserInfoByField($identify, $identify_field);
		if($user){
//			if(strlen($password)!=32){
//				$password = Utility::cryptPwd($password);
//			}
			$res = self::db()->where($identify_field,'=',$identify)->update(array('password'=>$password));
			return $user['urid'];
		}
		return false;
	}

	public static function getUserInfoByField($identify,$identify_field)
	{
		if(!in_array($identify_field,array('mobile','urid'))) return false;
		$user = self::db()->where($identify_field,'=',$identify)->first();

		return $user;
	}

	public static function modifyUserMobile($identify,$identify_field,$mobile)
	{
		if(!in_array($identify_field,array('mobile','urid'))) return false;
		$user = self::getUserInfoByField($identify, $identify_field);
		if($user){
			$res = self::db()->where($identify_field,'=',$identify)->update(array('mobile'=>$mobile));
			return $user['urid'];
		}
		return false;
	}
	/**
	 * 获取用户信息
	 * @param $uid
	 * @param string $filter
	 * @return array
	 */
	public static function getUserInfoById($urid,$filter='info')
	{
		$info = self::db()->where('urid','=',$urid)->first();
		if(!$info) return null;
//		$info && $info['avatar'] = Utility::getImageUrl($info['avatar']);
//		$info && $info['homebg'] = Utility::getImageUrl($info['homebg']);
		return self::filterUserFields($info,$filter);
	}

    public static function getUserInfoByMobile($mobile,$filter='info')
    {
        $info = self::db()->where('mobile','=',$mobile)->first();
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
	/**
	 * 修改资料
	 */
	public static function modifyUserInfo($uid,$data)
	{
		$res = self::db()->where('urid','=',$uid)->update($data);
		return $res;
	}

	//后台
	public static function getList($search,$pageIndex=1,$pageSize=20)
	{
		$tb = self::db();
		if(isset($search['name']) && !empty($search['name'])) $tb = $tb->where('card_name','like','%'.$search['name'].'%');
		if(isset($search['mobile']) && !empty($search['mobile'])) $tb = $tb->where('mobile','=',$search['mobile']);
		return $tb->orderBy('created_at','desc')->forPage($pageIndex,$pageSize)->get();
	}

	public static function getCount($search)
	{
		$tb = self::db();
		if(isset($search['name']) && !empty($search['name'])) $tb = $tb->where('card_name','like','%'.$search['name'].'%');
		if(isset($search['mobile']) && !empty($search['mobile'])) $tb = $tb->where('mobile','=',$search['mobile']);
		return $tb->count();
	}

	public static function getInfo($urid)
	{
		$batch = self::db()->where('urid','=',$urid)->first();
		if(!$batch) return array();
		return $batch;
	}
	
	public static function m_search($search)
	{
		$tb = self::m_buildSearch($search);
		return $tb->orderBy('urid','desc')->get();
	}

	protected static function m_buildSearch($search)
	{
		$tb = self::db();
		if(isset($search['name'])){
			$tb = $tb->where('card_name','like','%'.$search['name'].'%');
		}
		return $tb;
	}

	public static function save($data)
	{
		if(isset($data['urid']) && $data['urid']){
			$urid = $data['urid'];
			unset($data['urid']);
			$data['updated_at'] = time();
			return self::db()->where('urid','=',$urid)->update($data);
		}else{
			unset($data['urid']);
			$data['created_at'] = time();
			$data['updated_at'] = time();
			return self::db()->insertGetId($data);
		}
	}

	public static function del($urid)
	{
		if($urid > 0){
			$re = self::db()->where('urid','=',$urid)->delete();
		}
		return $re;
	}

}