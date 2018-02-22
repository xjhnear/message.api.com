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
namespace Youxiduo\User;

use Youxiduo\Android\TaskService;

use Illuminate\Support\Facades\Config;
use Youxiduo\Base\BaseService;
use Youxiduo\User\Model\Account;
use Youxiduo\User\Model\AccountSession;
use Youxiduo\User\Model\UserMobile;
use Youxiduo\V4\User\Model\MobileBlackList;
use Youxiduo\Helper\Utility;
use Youxiduo\Android\Model\CreditLevel;
use Youxiduo\Android\Model\CreditAccount;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Youxiduo\V4\User\MoneyService;

use Youxiduo\V4\User\Model\LoginIpBlackList;
use Youxiduo\V4\User\Model\LoginLimit;

class AccountService extends BaseService
{
	/**
	 * 发送手机验证码
	 * @param string $mobile 手机号
	 */
	public static function sendPhoneVerifyCode($mobile,$ip,$sms=true)
	{
		if(Utility::validateMobile($mobile)===true){
			$ban = MobileBlackList::checkMobileExists($mobile);
			if($ban==true){
				return self::trace_error('E1','该手机号已被禁用');
			}
			$verifycode = Utility::random(4,'alnum');			
			//$verifycode = '1234';		
			$result = UserMobile::saveVerifyCodeByPhone($mobile,$verifycode,false,$ip);
			$result==true && Utility::sendVerifySMS($mobile,$verifycode,$sms);
			return self::trace_result(array('result'=>$result));
		}
		return self::trace_error('E1','手机号无效');
	}
	
	/**
	 * 验证手机验证码
	 * @param string $mobile
	 * @param string $verifycode
	 */
	public static function verifyPhoneVerifyCode($mobile,$verifycode)
	{		
		if(Utility::validateMobile($mobile)===true && !empty($verifycode)){
			$num = 0;	
			$result = UserMobile::verifyPhoneVerifyCode($mobile,$verifycode,$num);
			if($result===true){
				$exists = Account::isExistsByField($mobile,Account::IDENTIFY_FIELD_MOBILE,0);
				if($exists===true){
					//return self::trace_error('E1','验证成功，请重新登录');
					return self::trace_result(array('result'=>true));
				}
			    return self::trace_result(array('result'=>true));
			}else{
				if($num >= 3){
					return self::trace_error('E1','验证码已失效,请重新获取');
				}
				return self::trace_error('E1','验证码无效');
			}
		}
		return self::trace_error('E1','验证码无效');
	}
	
	/**
	 * 修改手机号码
	 * @param int $uid
	 * @param string $mobile
	 * @param string $verifycode
	 * @param string $password
	 * 
	 * @return array
	 */
	public static function modifyPhone($uid,$mobile,$verifycode,$password)
	{
		$num = 0;
		$valid = UserMobile::verifyPhoneVerifyCode($mobile,$verifycode,$num);
		if($valid===true){
			$user = Account::doLocalLogin($uid, Account::IDENTIFY_FIELD_UID, $password);
			if($user){
				$data = array('mobile'=>$mobile);
				Account::modifyUserInfo($uid, $data);
				return self::trace_result(array('result'=>true));
			}
			return self::trace_error('E1','密码错误');
		}else{
		    if($num >= 3){
				return self::trace_error('E1','验证码已失效,请重新获取');
			}
		    return self::trace_error('E1','验证码无效');
		}
	}
	
	/**
	 * 手机注册
	 * @param string $mobile 手机号
	 * @param string $password 密码
	 * @param array  $params 其他数据
	 * 
	 * @return int $uid 用户唯一标识UID
	 */
	public static function createUserByPhone($mobile,$password,$params=array(),$ip='')
	{
	    $limit_time = mktime(0,0,0,date('m'),date('d'),date('Y'));
	    $limit_num = 3;
	    $idcode = Input::get('idcode');
	    $allow = LoginIpBlackList::checkIsAllowLoginByIp($idcode, $limit_time, $limit_num,$mobile,LoginIpBlackList::LIMIT_TYPE_REGISTER);
	    //$allow = true;
	    if($allow===false){
	    	//return self::trace_error('E1','该IP今日注册次数太频繁,已经被禁用');
	    	return self::trace_error('E1','一台手机最多只能注册3个账号，您已经达到上限');
	    } 
		if(Utility::validateMobile($mobile)===true && !empty($password)){
			if(Account::isExistsByField($mobile,Account::IDENTIFY_FIELD_MOBILE)===true){
				return self::trace_error('E1','该手机号已经存在');
				//$uid = self::modifyUserPwd($mobile, $password);
			}else{
				if(UserMobile::phoneVerifyStatus($mobile,true)===false) return self::trace_error('E1','手机未验证');
				$params['ip'] = $ip;
			    $uid = Account::createUserByPhone($mobile,$password,$params);
			}
			if($uid>0){
				$session = self::makeAccessToken($uid);
				$money_success = MoneyService::registerAccount($uid);
				if($money_success){
					Account::modifyUserInfo($uid,array('is_open_android_money'=>1));
				}
				return self::trace_result(array('result'=>array('uid'=>$uid,'session_id'=>$session)));
			}
			return self::trace_error('E1','注册失败');
		}
		return self::trace_error('E1','手机号无效');	
	}

	public static function webCreateUserByPhone($mobile,$password,$params=array())
	{
		if(Utility::validateMobile($mobile)===true && !empty($password)){
			if(Account::isExistsByField($mobile,Account::IDENTIFY_FIELD_MOBILE)===true){
				return array('errorCode'=>1,'message'=>'该手机号已经存在');
				$uid = self::modifyUserPwd($mobile, $password);
			}else{
//				if(UserMobile::phoneVerifyStatus($mobile)===false) return array('errorCode'=>1,'message'=>'手机未验证');
			    $uid = Account::createUserByPhone($mobile,$password);
			}
			if($uid>0){
				return array('result'=>array('uid'=>$uid));
			}
			return array('errorCode'=>1,'message'=>'注册失败');
		}
		return array('errorCode'=>1,'message'=>'手机号无效');
	}

	/**
	 * 邮箱注册
	 * @param string $email 邮箱
	 * @param string $password 密码
	 * @param array  $params 其他数据
	 * 
	 * @return int $uid 用户唯一标识UID
	 */
	public static function createUserByEmail($email,$password,$params=array())
	{
		if(Utility::validateEmail($email)===true){
			if(Account::isExistsByField($email,Account::IDENTIFY_FIELD_EMAIL)===true){
				return self::trace_error('E1','该邮箱已经存在');
			}else{
			    $uid = Account::createUserByEmail($email,$password);
			}
			if($uid>0){
				return self::trace_result(array('result'=>array('uid'=>$uid)));
			}
			return self::trace_error('E1','注册失败');
		}
		return self::trace_error('E1','邮箱无效');
	}
	
	public static function h5CreateUserByEmail($email,$password,$params=array())
	{
		if(Utility::validateEmail($email)===true){
			if(Account::isExistsByField($email,Account::IDENTIFY_FIELD_EMAIL)===true){
				return array('errorCode'=>1,'message'=>'该邮箱已经存在');
			}else{
				$uid = Account::createUserByEmail($email,$password);
			}
			if($uid>0){
				return array('uid'=>$uid);
			}
			return array('errorCode'=>1,'message'=>'注册失败');
		}
		return array('errorCode'=>1,'message'=>'邮箱无效');
	}
	
	/**
	 * 第三方注册
	 */
	public static function createUserByThird()
	{
		
	}
	
	public static function checkPassword($uid,$password)
	{
		$user = Account::doLocalLogin($uid,Account::IDENTIFY_FIELD_UID,$password);
		$exists = $user ? true : false;		
		return self::trace_result(array('result'=>$exists));
	}
	
	public static function loginByUsername($username,$password,$ip='')
	{
		$limit_time = mktime(0,0,0,date('m'),date('d'),date('Y'));
	    $limit_num = 3;
	    $idcode = Input::get('idcode');
	    $allow = LoginLimit::checkIsAllowLogin($idcode,$limit_time,$limit_num,$username);
	    //$allow = LoginIpBlackList::checkIsAllowLoginByIp($idcode, $limit_time, $limit_num,$username,LoginIpBlackList::LIMIT_TYPE_LOGIN);
	    //$allow = true;
	    if($allow===false){
	    	return self::trace_error('E1','该用户账号异常登录，已被锁定部分功能，请明天再试。');
	    }
		if(Utility::validateEmail($username)){
			return self::loginByEmail($username, $password);
		}elseif(Utility::validateMobile($username)){
			if(MobileBlackList::checkMobileExists($username)==true) return self::trace_error('E1','该手机已经被禁止登录'); 
			return self::loginByPhone($username, $password);
		}else{
			return self::trace_error('E1','账号或密码错误');
		}
	}
	
	public static function h5login($account,$passwd){
		if(Utility::validateEmail($account)){
			return self::h5LoginByEmail($account, $passwd);
		}elseif(Utility::validateMobile($account)){
			return self::h5LoginByPhone($account, $passwd);
		}else{
			return array('errorCode'=>1,'message'=>'登录失败,账号或密码错误');
		}
	}
	
	
	
	/**
	 * 手机号登录
	 */
	public static function loginByPhone($mobile,$password)
	{
		if(Utility::validateMobile($mobile)===true && !empty($password)){
			$user = Account::doLocalLogin($mobile,Account::IDENTIFY_FIELD_MOBILE,$password);
			if($user){
				TaskService::doLogin($user['uid']);
				$mobile_verify = UserMobile::phoneVerifyStatus($user['mobile']);
				$session = self::makeAccessToken($user['uid']);
				return self::trace_result(array('result'=>array('uid'=>$user['uid'],'mobile_is_verify'=>$mobile_verify,'session_id'=>$session)));
			}else{
				return self::trace_error('E1','账号或密码错误');
			}
		}
		return self::trace_error('E1','手机号无效');	
	}
	
	public static function h5LoginByPhone($mobile,$password){
		if(Utility::validateMobile($mobile)===true && !empty($password)){
			$user = Account::doLocalLogin($mobile,Account::IDENTIFY_FIELD_MOBILE,$password);
			if($user){
				TaskService::doLogin($user['uid']);
				$mobile_verify = UserMobile::phoneVerifyStatus($user['mobile']);
				$session = self::makeAccessToken($user['uid']);
				return array('result'=>array('uid'=>$user['uid'],'mobile_is_verify'=>$mobile_verify,'session_id'=>$session));
			}else{
				return array('errorCode'=>1,'message'=>'登录失败，账号或密码错误');
			}
		}
		return array('errorCode'=>1,'message'=>'手机号无效');
	}
	
	/**
	 * 邮箱登录
	 */
	public static function loginByEmail($email,$password)
	{
		if(Utility::validateEmail($email)===true && !empty($password)){
			$user = Account::doLocalLogin($email,Account::IDENTIFY_FIELD_EMAIL,$password);
			if($user){
				TaskService::doLogin($user['uid']);
				$mobile_verify = UserMobile::phoneVerifyStatus($user['mobile']);
				$session = self::makeAccessToken($user['uid']);
				return self::trace_result(array('result'=>array('uid'=>$user['uid'],'mobile_is_verify'=>$mobile_verify,'session_id'=>$session)));
			}else{
				return self::trace_error('E1','登录失败,账号或密码错误');
			}
		}
		return self::trace_error('E1','邮箱地址无效');	
	}
	
	public static function h5LoginByEmail($email,$password){
		if(Utility::validateEmail($email)===true && !empty($password)){
			$user = Account::doLocalLogin($email,Account::IDENTIFY_FIELD_EMAIL,$password);
			if($user){
				TaskService::doLogin($user['uid']);
				$mobile_verify = UserMobile::phoneVerifyStatus($user['mobile']);
				return array('result'=>array('uid'=>$user['uid'],'mobile_is_verify'=>$mobile_verify));
			}else{
				return array('errorCode'=>1,'message'=>'登录失败，账号或密码错误');
			}
		}
		return array('errorCode'=>1,'message'=>'邮箱地址无效');
	}
	
	protected static function makeAccessToken($uid)
	{
		$session = AccountSession::makeSession();
		$id = AccountSession::saveSession($uid, $session);
		if($session && $id) return $session;
		return '';
	}
	
	public static function checkSession($uid,$session_id)
	{
		if(!$session_id || !$uid) return self::trace_result(array('result'=>false));
		$exists = AccountSession::verifySession($uid, $session_id);
		return self::trace_result(array('result'=>$exists));
	}
	
	public static function getUserIdByMobile($mobile)
	{
		$user = Account::getUserInfoByField($mobile,'mobile');
		if($user){
			return self::trace_result(array('result'=>array('uid'=>$user['uid'])));
		}
		return self::trace_result(array('result'=>array('uid'=>0)));
	}
	
	/**
	 * 获取用户信息
	 */
	public static function getUserInfo($uid)
	{
		$user = Account::getUserInfoById($uid);
		if($user){
			$user['birthday'] = $user['birthday']>0 ? date('Y-m-d',$user['birthday']) : '';
			$credit = CreditAccount::getUserCreditByUid($uid);
			$user['money'] = $credit[$uid]['money'];
			$user['experience'] = $credit[$uid]['experience'];
			$level = CreditLevel::getUserLevel($user['experience']);
			if($level){
				$user['level_name'] = $level['name'];
				$user['level_max'] = $level['end'];
			}else{
				$user['level_name'] = '';
				$user['level_max'] = '';
			}
			if($user['mobile']){
				 $user['mobile'] = preg_replace('/(1[3578]{1}[0-9])[0-9]{4}([0-9]{4})/i','$1****$2',$user['mobile']);
			}
		}
		return self::trace_result(array('result'=>$user));
	}
	
	public static function getMultiUserInfoByUids($uids)
	{
		$out = array();
		if(!$uids) return self::trace_result(array('result'=>$out));
		$users = Account::getMultiUserInfoByUids($uids);
		$users_level = CreditAccount::getUserCreditByUids($uids);
		foreach($users as $row){
			if(isset($users_level[$row['uid']])){
				$level = CreditLevel::getUserLevel($users_level[$row['uid']]['experience']);
				$row['experience'] = $users_level[$row['uid']]['experience'];
				$row['level_name'] = $level['name'];
				$row['level_max'] = $level['end'];
			}else{
				$row['experience'] = 0;
				$row['level_name'] = '1';
				$row['level_max'] = '50';
			}
		    if($row['mobile']){
				 $row['mobile'] = preg_replace('/(1[3578]{1}[0-9])[0-9]{4}([0-9]{4})/i','$1****$2',$row['mobile']);
			}
			$out[] = $row;
		}
		return self::trace_result(array('result'=>$out));
	}

    public static function bbsGetUserInfo($uid){
        $user = Account::getUserInfoById($uid,'basic');
		if($user){
			$user['nickname'] = $user['nickname'] ? $user['nickname'] : '玩家'.$user['uid'];
            //$user['reg_time'] = date('Y-m-d',$user['dateline']);
			$credit = CreditAccount::getUserCreditByUid($uid);
			$user['money'] = $credit[$uid]['money'];
			$user['experience'] = $credit[$uid]['experience'];
			$level = CreditLevel::getUserLevel($user['experience']);
			if($level){
				$user['level_name'] = $level['name'];
				$user['level_max'] = $level['end'];
                $user['level_pic'] = '/static/images/'.$level['name'].'@2x.png';
			}else{
				$user['level_name'] = 0;
				$user['level_max'] = '';
                $user['level_pic'] = '';
			}
            $user['avatar'] = $user['avatar'] ? Utility::getImageUrl($user['avatar']) : Config::get('app.img_url').'/userdirs/common/avatar@2x.png?v='.$user['level_name'];
		}
		return $user;
    }
	
	public static function h5GetUserInfo($uid)
	{
		$user = Account::getUserInfoByField($uid,'uid');
		return $user;
	}
	
	/**
	 * 修改密码
	 * 
	 */
	public static function modifyUserPwd($mobile,$password)
	{
		$res = Account::modifyUserPwd($mobile,Account::IDENTIFY_FIELD_MOBILE,$password);
		if($res){
			return self::trace_result(array('result'=>true));
		}else{
			return self::trace_error('E1','密码修改失败');
		}
	}
	
	/**
	 * 修改用户资料
	 */
	public static function modifyUserInfo($uid,$input)
	{
		if(!$uid) return false;
		
		$fields = array('nickname','summary','birthday','sex','mobile','avatar','homebg');
		$data = array();
		//过滤非法字段
		foreach($fields as $field){
			isset($input[$field]) && !empty($input[$field]) && $data[$field] = $input[$field];
		}
		//验证昵称唯一性
		if(isset($data['nickname']) && $data['nickname']){
			if(Account::isExistsByField($data['nickname'],Account::IDENTIFY_FIELD_NICKNAME,$uid)===true){
				//昵称已经存在
				return self::trace_error('E1','昵称已经存在');
			}
		}        
		//验证手机唯一性
	    if(isset($data['mobile']) && $data['mobile']){
			if(Account::isExistsByField($data['mobile'],Account::IDENTIFY_FIELD_NICKNAME,$uid)===true){
				//昵称已经存在
				return self::trace_error('E1','手机号已经存在');
			}
		}
		
        if($data){
		    $res = Account::modifyUserInfo($uid, $data);
        }else{
        	return self::trace_error('E1','资料修改失败');
        }
        
		if($res){
			return self::trace_result(array('result'=>true));
		}else{
			//return self::trace_error('E1','资料修改失败');
			return self::trace_result(array('result'=>true));
		}
	}
	
	/**
	 * 检查邮箱是否被占用
	 */
	public static function checkIsExistsByEmail($email,$uid=0)
	{
		$res = Account::isExistsByField($email,Account::IDENTIFY_FIELD_EMAIL,$uid);
		if($res==true){
			return self::trace_error('E1','邮箱已经被占用');
		}else{
			return self::trace_result(array('result'=>true));
		}
	}
	
	/**
	 * 检查手机号是否被占用
	 */
    public static function checkIsExistsByPhone($mobile,$uid=0)
	{
		$res = Account::isExistsByField($mobile,Account::IDENTIFY_FIELD_MOBILE,$uid);
	    if($res==true){
			if(UserMobile::phoneVerifyStatus($mobile,false)===false){
				return self::trace_result(array('result'=>true));
			}
			return self::trace_error('E1','手机号已经被占用');
		}else{
			return self::trace_result(array('result'=>true));
		}
	}
	
	/**
	 * 检查昵称是否被占用
	 */
    public static function checkIsExistsByNickname($nickname,$uid=0)
	{
		$res = Account::isExistsByField($nickname,Account::IDENTIFY_FIELD_NICKNAME,$uid);
	    if($res==true){
			return self::trace_error('E1','昵称已经被占用');
		}else{
			return self::trace_result(array('result'=>true));
		}
	}
	
	/**
	 * 搜索用户
	 */
	public static function searchUserByNickName($nickname,$pageIndex=1,$pageSize=10)
	{
		$users = Account::searchUserByNickname($nickname,$pageIndex,$pageSize);
		$out = array();
	    foreach($users as $user)
		{
			$user['avatar'] = $user['avatar'] ? Utility::getImageUrl($user['avatar']) : '';
			$user['summary'] = $user['summary'];
			$out[] = $user;
		}	
			
		$total = Account::searchUserCountByNickname($nickname);
		return self::trace_result(array('result'=>$out,'totalCount'=>$total));
	}
	
	public static function h5SearchUserByNickName($nickname,$pageIndex=1,$pageSize=10)
	{
		$users = Account::searchUserByNickname($nickname,$pageIndex,$pageSize);
		$out = array();
		foreach($users as $user)
		{
			$user['avatar'] = $user['avatar'] ? Utility::getImageUrl($user['avatar']) : '';
			$user['summary'] = $user['summary'];
			$out[] = $user;
		}
			
		$total = Account::searchUserCountByNickname($nickname);
		return array('result'=>$out,'totalCount'=>$total);
	}
	
	public static function matchingUserByMobile($mobiles)
	{
		if(!$mobiles) return self::trace_error('E1','参数错误');
		$users = Account::matchingUserByMobile($mobiles);
		$out = array();
	    foreach($users as $user)
		{
			$user['avatar'] = $user['avatar'] ? Utility::getImageUrl($user['avatar']) : '';
			$out[] = $user;
		}
		return self::trace_result(array('result'=>$out));
	}
}