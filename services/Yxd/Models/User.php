<?php
namespace Yxd\Models;

use Illuminate\Support\Facades\Log;
use Yxd\Modules\Core\CacheService;
use Yxd\Modules\Core\BaseModel;
use Illuminate\Support\Facades\DB;
use Yxd\Services\TaskService;

use Yxd\Services\Models\Account;
use Yxd\Services\Models\AccountPage;
use Yxd\Services\Models\AccountCreditHistory;
use Yxd\Services\Models\CreditAccount;
use Yxd\Services\Models\AccountGroupLink;
use Yxd\Services\Models\CreditLevel;
use Yxd\Services\Models\CreditSetting;
use Youxiduo\Helper\Utility;
use Illuminate\Support\Facades\Config;

class User extends BaseModel
{
    const MALL_API_ACCOUNT = 'app.account_api_url';
	/*
	 * 检查用户是否存在
	 */
	public static function checkUserById($uid)
	{
		return Account::db()->where('uid', $uid)->count();
	}
	//获取用户游币的值
	public static function getScore($uid)
	{
	    //迁移后游币获取
	    $params = array('accountId'=>$uid,'platform'=>'ios');
	    $params_ = array('accountId','platform');
	    $new = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'account/query');
	    if ($new['result']) {
	        return isset($new['result'][0]['balance']) ? $new['result'][0]['balance'] : 0;
	    }
	    
		$res = CreditAccount::db()->where('uid', $uid)->first();
		return $res['score'];
	}
	
    public static function getZhucema()
	{
	    $chars_array = array(
	        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
	        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
	        'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
	        'w', 'x', 'y',
	    );
	    $charsLen = count($chars_array) - 1;
	    $outputstr = "";
	    for ($i=0; $i<10; $i++)
	    {
	    $outputstr .= $chars_array[mt_rand(0, $charsLen)];
	    }
	    $out = array();
	    if(in_array($outputstr, $out)){
	    	self::getZhucema();
	    }else{
	    	$out[] = $outputstr;
	    }
	    return $outputstr;
	}

	/**
	 * 创建用户
	 */
	public static function createAccount($user)
	{
		$account = array();
		$fields = array('nickname','email','password','avatar','sex','mobile','birthday','summary','homebg','reg_ip','vuser');		
		foreach($user as $field=>$data){
			if(in_array($field,$fields)){
				if($field=='password'){
					$account[$field] = self::cryptPwd($data);
				}else{
				    $account[$field] = $data;
				}
			}
		}
		$account['dateline'] = (int)microtime(true);
		$account['zhucema'] = self::getZhucema();
		$account['reg_ip'] = self::getIp();
		$uid = Account::db()->insertGetId($account);
		if($uid){
			$user['uid'] = $uid;
			AccountGroupLink::db()->insert(array('uid'=>$uid,'group_id'=>5));
			if(!isset($account['nickname']) || empty($account['nickname'])){
				$nickname = '玩家' . $uid;
				Account::db()->where('uid','=',$uid)->update(array('nickname'=>$nickname));
				$user['nickname'] = $nickname;
			}
			//创建游币账户
			$params = array('id'=>$uid,'experience'=>0,'platform'=>'ios');
			$params_ = array('id','experience','platform');
			Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'account/register','POST');

			return $user;
		}
		return null;
	} 
	
    public static function getIp($type=0)
	{
		$type       =  $type ? 1 : 0;
		static $ip  =   NULL;
		if ($ip !== NULL) return $ip[$type];
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$pos    =   array_search('unknown',$arr);
			if(false !== $pos) unset($arr[$pos]);
			$ip     =   trim($arr[0]);
		}elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip     =   $_SERVER['HTTP_CLIENT_IP'];
		}elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$ip     =   $_SERVER['REMOTE_ADDR'];
		}
		// IP地址合法验证
		$long = sprintf("%u",ip2long($ip));
		$ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
		return $ip[$type];
	}
	
	public static function getUserInfoList($uids)
	{		
		if(is_array($uids)){
			return Account::db()->whereIn('uid',$uids)->get();
		}else{
			return Account::db()->where('uid','=',$uids)->first();
		}
	}
	
    /**
	 * 
	 */
	public static function getUserInfo($identify,$identify_field = 'uid')
	{
		$fields = array('uid','nickname','email','avatar','mobile','sex','birthday','dateline','summary','homebg');
		$user = Account::db()->select($fields)->where($identify_field,'=',$identify)->first();
		//$group = self::getUserGroupView($user['uid']);
		//$user['groups'] = $group['groups'];
		//$user['authorize_nodes'] = $group['authorize'];
		return $user;
	}
	
	public static function getUidListByNickname($nickname)
	{
		if(is_string($nickname)){
			return Account::db()->where('nickname','=',$nickname)->lists('uid');
		}elseif(is_array($nickname) && !empty($nickname)){
			return Account::db()->whereIn('nickname',$nickname)->lists('uid');
		}
		return null;
	}
	
	/**
	 * 获取用户全部信息
	 * @param int $uid 
	 * @return array $user 用户基本信息+用户组信息+用户权限信息
	 */
	public static function getUserFullInfo($uid)
	{
		$fields = array('uid','nickname','email','avatar','mobile','sex','summary','homebg','birthday','dateline','phone','province','city','region','address','alipay_num','alipay_name','idfa');
		$user = Account::db()->select($fields)->where('uid','=',$uid)->first();
		if(!$user) return null;
		$group = self::getUserGroupView($uid);
		$credit = self::getUserCredit($uid);
		$user['score'] = isset($credit['score']) ? $credit['score'] : 0;
		$user['experience'] = isset($credit['experience']) ? $credit['experience'] : 0;
		$user['groups'] = $group['groups'];
		$user['authorize_nodes'] = $group['authorize'];
		return $user;
	}
	
    /**
	 * 获取用户全部信息
	 * @param int $uid 
	 * @return array $user 用户基本信息+用户组信息+用户权限信息
	 */
	public static function getUserFullInfoList($uids)
	{
		$fields = array('uid','nickname','email','avatar','mobile','sex','birthday','summary','homebg','dateline');
		$users = Account::db()->select($fields)->whereIn('uid',$uids)->get();
		$credits = self::getUserCreditByUids($uids);
		foreach($users as $key=>$user){
			//$group = self::getUserGroupView($user['uid']);
			//$credit = self::getUserCredit($user['uid']);
		    $users[$key]['score'] = isset($credits[$user['uid']]) ? $credits[$user['uid']]['score'] : 0;
		    $users[$key]['experience'] = isset($credits[$user['uid']]) ? $credits[$user['uid']]['experience'] : 0;
		    //$users[$key]['groups'] = $group['groups'];
		    //$users[$key]['authorize_nodes'] = $group['authorize'];
		}
		return $users;
	}
	/**
	 * 
	 * Enter description here ...
	 */
	public static function getCreditLevel()
	{
		$cachekey = 'credit::credit_level';
		$cache = null;//CacheService::get($cachekey);
		if(!$cache){
		    $cache = CreditLevel::db()->orderBy('start','asc')->get();
		    //CacheService::put($cachekey,$cache,30);
		}
		return $cache;
	}
	
    /**
	 * 获取用户权限视图
	 */
	public static function getUserGroupView($uid)
	{
		$auth = array('groups'=>array(),'authorize'=>array());
		$group_ids = AccountGroupLink::db()->where('uid','=',$uid)->lists('group_id');
		if(empty($group_ids)) return $auth;
		$groups = DB::table('account_group')->whereIn('group_id',$group_ids)->get();				
		foreach($groups as $key=>$group){			
			if(!empty($group['authorize_nodes'])){
				$auth['authorize'] += unserialize($group['authorize_nodes']);
			}
			unset($group['authorize_node']);
			$auth['groups'][$group['group_id']] = $group;
		}		
		unset($groups);
		return $auth;
	}
	
	/**
	 * 获取用户积分
	 */
	public static function getUserCredit($uid)
	{
	    $CreditAccount = CreditAccount::db()->where('uid','=',$uid)->first();
	    //迁移后游币获取
	    $params = array('accountId'=>$uid,'platform'=>'ios');
	    $params_ = array('accountId','platform');
	    $new = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'account/query');
	    if ($new['result']) {
	        $CreditAccount['score'] = isset($new['result'][0]['balance']) ? $new['result'][0]['balance'] : $CreditAccount['score'];
	    }
		return $CreditAccount;
	}
	
	public static function getUserCreditByUids($uids)
	{
		$users = CreditAccount::db()->whereIn('uid',$uids)->get();
		$out = array();
		foreach($users as $one)
		{
			$out[$one['uid']] = $one;
			//迁移后游币获取
			$params = array('accountId'=>$one['uid'],'platform'=>'ios');
			$params_ = array('accountId','platform');
			$new = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'account/query');
			if ($new['result']) {
			    $out[$one['uid']]['score'] = isset($new['result'][0]['balance']) ? $new['result'][0]['balance'] : $out[$one['uid']]['score'];
			}
		}
		return $out;
	}
	
    /**
	 * 获取积分历史记录
	 */
	public static function getCreditHistory($uid,$page=1,$pagesize=10)
	{
		$res = AccountCreditHistory::db()
		           ->where('uid','=',$uid)
		           ->orderBy('mtime','desc')
		           ->forPage($page,$pagesize)
		           ->get();
		return $res;
	}
	
	/**
	 * 积分处理
	 */
    public static function doUserCredit($uid,$action,$info='')
	{
	    //迁移后游币处理  老游币表依旧操作
	    $credit_new = false;
	    $params = array('accountId'=>$uid,'platform'=>'ios');
	    $params_ = array('accountId','platform');
	    $new = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'account/query');
	    if ($new['result']) {
	        $credit_new = true;
	        //$CreditAccount['score'] = isset($new['result'][0]['balance']) ? $new['result'][0]['balance'] : $CreditAccount['score']; 
	    }
	    
		$creditlist = CreditSetting::db()->orderBy('id','asc')->get();
		$list = array();
		foreach($creditlist as $credit){
			$list[$credit['name']] = $credit;
		}
		unset($creditlist);
		if(!isset($list[$action])){
			return false;
		}
		$action_name = $list[$action]['alias'];
		$score = $list[$action]['score'];
		$experience = $list[$action]['experience'];
		
		//规则检测
		$crcletype = $list[$action]['crcletype'];
		$rewardnum = (int)$list[$action]['rewardnum'];
		
		if($rewardnum>0){
			
			if($crcletype==1){//每日
				$start = mktime(0,0,0,date('m'),date('d'),date('Y'));
				
			}elseif($crcletype==2){//每周
				$days = date('N')-1;				
				$start = mktime(0,0,0,date('m'),date('d')-$days,date('Y'));
			}elseif($crcletype==3){//每月
				$start = mktime(0,0,0,date('m'),1,date('Y'));
			}else{
				$start = 0;
			}
			
			if ($credit_new) {
			    $params = array('accountId'=>$uid,'platform'=>'ios','operationType'=>$action,'operationTimeBegin'=>date("Y-m-d H:i:s", $start));
			    $params_ = array('accountId','platform','operationType','operationTimeBegin');
			    $new = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'account/operation_query');
			    if(count($new['result'])>=$rewardnum) return null;
			} else {
			    $total = AccountCreditHistory::db()
    			    ->where('uid','=',$uid)
    			    ->where('action','=',$action)
    			    ->where('mtime','>=',$start)
    			    ->count();
			    if($total>=$rewardnum) return null;
			}
		}
		$score_op_success = false;
		
// 		$userCredit = CreditAccount::db()->where('uid','=',$uid)->first();
// 		if($userCredit){
// 			//$data['score'] = $userCredit['score']+$score;
// 			//$data['experience'] = $userCredit['experience'] + $experience;
// 			//CreditAccount::db()->where('uid','=',$uid)->update($data);
// 			$score!=0 && CreditAccount::db()->where('uid','=',$uid)->increment('score',$score);
// 			$experience !=0 && CreditAccount::db()->where('uid','=',$uid)->increment('experience',$experience);
// 		}else{
// 			$data['score'] = $score;
// 			$data['experience'] = $experience;
// 			$data['uid'] = $uid;
// 			CreditAccount::db()->insert($data);
// 		}
		if($score>0){
			$sign = '增加';
		}else{
			$sign = '减少';
		}
		//
		$info_rule = array('{action}'=>$action_name,'{sign}'=>$sign,'{score}'=>$score,'{typecn}'=>'游币','{experience}'=>$experience);
		if(!empty($list[$action]['info'])){
			$info = str_replace(array_keys($info_rule),array_values($info_rule),$list[$action]['info']);
		}
		if($score){
			$credit_history = array('uid'=>$uid,'info'=>$info,'action'=>$action,'type'=>'游币','credit'=>$score,'mtime'=>(int)microtime(true));
			AccountCreditHistory::db()->insert($credit_history);
		}
		if ($credit_new) {
		    $params = array('rechargeAccountId'=>$uid,'platform'=>'ios','balanceChange'=>$score,'type'=>$action,'operationInfo'=>$info,'platform'=>'ios');
		    $params_ = array('rechargeAccountId','platform','balanceChange','type','operationInfo','platform');
		    $re = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'account/updatebalance');
		    if ($re['result']) {
		        $score_op_success = true;
		    }
		}
		return $score_op_success;
	}
	
	/**
	 * 验证登录
	 */
    public static function verifyLocalLogin($identify,$password,$identify_field = 'email')
	{
		$user = Account::db()->where($identify_field,'=',$identify)->first();
		if($user && isset($user['password'])){			
			$pwd = '';
			if(strlen($password)==32){
				$pwd = $password;
			}else{
				$pwd = self::cryptPwd($password);
			}
			if($user['password']==$pwd){		
				return $user;
			}else{
				return -1;
			}
		}else{
			return null;
		}		
	}
	
	/**
	 * 修改密码
	 * 
	 */	
	public static function modifyAccountPassword($uid,$password)
	{
		$row = Account::db()->where('uid','=',$uid)->update(array('password'=>self::cryptPwd($password)));
		if($row){
			return true;
		}
		return false;
	}
	
	/**
	 * 修改基本信息
	 */
	public static function modifyAccountInfo($uid,$info=null,$group_ids=null)
	{
		$row_1 = $row_2 = false;
		if($info){
			$account = array();
			$fields = array('nickname','sex','mobile','birthday','summary','avatar','homebg','phone','vuser');
		     foreach($info as $field=>$data){
				if(in_array($field,$fields)){
					$account[$field] = $data;
				}
			}
			if($account){
				if(isset($account['nickname'])){
					
				    if(!$account['nickname'] || empty($account['nickname'])){
						unset($account['nickname']);
					}else{
						$count = Account::db()->where('uid','<>',$uid)->where('nickname','=',$account['nickname'])->count();
						if($count){
							return -1;
						}
					}
					
				}					
			    $row_1 = Account::db()->where('uid','=',$uid)->update($account);
			}
		}
		if($group_ids){
			AccountGroupLink::db()->where('uid','=',$uid)->delete();
			if(!is_array($group_ids)) $group_ids = array($group_ids);
			$data = array();			
			foreach($group_ids as $group_id){
				$data[] = array('uid'=>$uid,'group_id'=>$group_id);
			}
			AccountGroupLink::db()->insert($data);
			$row_2 = true;
		}
		//$row = 
		if($row_1 || $row_2){
			return true;
		}
		return false;
	}
	
    /**
	 * 更新用户邮箱
	 */
	public static function modifyAccountEmail($uid,$email)
	{
		$count = Account::db()->where('uid','<>',$uid)->where('email','=',$email)->count();
		if($count){
			return -1;
		}
		$rows = Account::db()->where('uid','=',$uid)->update(array('email'=>$email));
		if($rows>0){
			
		}
		return $rows;
		
	}
	
	/**
	 * 更新用户昵称
	 */
	public static function modifyAccountNickname($uid,$nickname)
	{
		$count = Account::db()->where('uid','<>',$uid)->where('nickname','=',$nickname)->count();
		if($count){
			return -1;
		}
		$rows = Account::db()->where('uid','=',$uid)->update(array('nickname'=>$nickname));
		return $rows;
	}
	
	/**
	 * 更新用户头像
	 */
	public static function modifyAccountAvatar($uid, $avatar)
	{
		$row = Account::db()->where('uid','=',$uid)->update(array('avatar'=>$avatar));
		if($row){
			return true;
		}
		return false;
	}
	
    /**
	 * 更新背景
	 */
	public static function modifyHomeBg($uid, $bg)
	{
		$row = Account::db()->where('uid','=',$uid)->update(array('homebg'=>$bg));
		if($row){
			return true;
		}
		return false;
	}
	
	/**
	 * 屏蔽昵称或头像
	 */
    public static function shieldAccountField($uid,$field,$data)
	{
		if(!in_array($field,array('nickname','avatar'))) return false;
		$user = Account::db()->where('uid','=',$uid)->first();
		$log = array('uid'=>$uid,'field'=>$field,'data'=>$user[$field],'ctime'=>(int)microtime(true));
		DB::table('account_shield_history')->insertGetId($log);
		Account::db()->where('uid','=',$uid)->update(array($field=>$data));
		return true;
	}
	
	/**
	 * 获取用户主页设置
	 */
	public static function getUserPage($uid)
	{
		return AccountPage::db()->where('uid','=',$uid)->first();
	}
	
	/**
	 * 保存用户主页设置
	 */
	public static function saveUserPage($data)
	{
		if(!isset($data['uid']) || empty($data['uid'])) return false;
		$uid = $data['uid'];		
		$count = AccountPage::db()->where('uid','=',$uid)->count();
		if($count>0){
			unset($data['uid']);
			return AccountPage::db()->where('uid','=',$uid)->update($data);
		}else{
			AccountPage::db()->insertGetId($data);
		}
	}
	/*
	 * 密码的加密算法
	 */
	protected static function cryptPwd($password)
	{
		$salt = md5(substr($password,-1));
		$password = md5($password . $salt);
		return $password;
	}
}