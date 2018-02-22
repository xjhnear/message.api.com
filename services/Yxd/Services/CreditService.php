<?php
namespace Yxd\Services;

use Illuminate\Support\Facades\DB as DB;
use Illuminate\Support\Facades\Event;

use Yxd\Models\User;
use Yxd\Models\Passport;
use Yxd\Services\Models\CreditAccount;
use Yxd\Services\Models\Account;
use Yxd\Services\Models\CreditSetting;
use Yxd\Services\Models\CreditLevel;
use Yxd\Services\Models\AccountCreditHistory;
use Youxiduo\Helper\Utility;
use Illuminate\Support\Facades\Config;

class CreditService extends Service
{	
	const HAND_CREDIT_TYPE_GIFT = 0;
	const HAND_CREDIT_TYPE_SHOP = 1;
	
	
	const CREDIT_RULE_ACTION_POST_TOPIC = 'post_topic';
	const CREDIT_RULE_ACTION_POST_REPLY = 'post_reply';
	const CREDIT_RULE_ACTION_POST_COMMENT = 'post_comment';
	const CREDIT_RULE_ACTION_SHARE = 'share';
	const CREDIT_RULE_ACTION_DELETE_TOPIC = 'delete_topic';
	const CREDIT_RULE_ACTION_DELETE_REPLY = 'delete_reply';
	const CREDIT_RULE_ACTION_DELETE_COMMENT = 'delete_comment';
	
	const CREDIT_RULE_ACTION_DIGEST_TOPIC = 'digest_topic';
	const CREDIT_RULE_ACTION_UNDIGEST_TOPIC = 'undigest_topic';
	
	const MALL_API_ACCOUNT = 'app.account_api_url';
	
    /**
	 * 处理用户积分
	 * @param number $uid 用户唯一标识
	 * @param string $action 动作
	 */
	public static function doUserCredit($uid,$action,$info='')
	{
		$success =  User::doUserCredit($uid, $action,$info);
		if($success){
			Event::fire('user.update_userinfo_cache',array(array($uid)));
		}
		return $success;
	}
	
	/**
	 * 获取动作对应的积分设置
	 */
	public static function getUserOpCredit($action)
	{
		return CreditSetting::db()->where('name','=',$action)->first();
	}
	
	/**
	 * 处理用户积分
	 */
	public static function handOpUserCredit($uid,$score,$experience,$action,$info='')
	{
		$list = array();
		$list[0] = array(
		    'name'=>'领取礼包',
		    'info'=>'{action}{sign}了{score}{typecn}'
		);
		$list[1] = array(
		    'name'=>'兑换商品',
		    'info'=>'{action}{sign}了{score}{typecn}'
		);
// 	    $userCredit = CreditAccount::db()->where('uid','=',$uid)->first();
	    $score_op_success = false;
// 		if($userCredit){
// 			if($score>0){
// 				$score_op_success = CreditAccount::db()->where('uid','=',$uid)->increment('score',$score)>0 ? true : false;
// 			}			
// 			if($score <= 0){
// 				$score_op_success = CreditAccount::db()->where('uid','=',$uid)->whereRaw('score>='.abs($score))->increment('score',$score)>0 ? true : false;
// 			} 
// 			if($experience != 0) CreditAccount::db()->where('uid','=',$uid)->increment('experience',$experience);
// 			Event::fire('user.update_userinfo_cache',array(array($uid)));
// 		}else{
// 			$data['score'] = $score;
// 			$data['experience'] = $experience;
// 			$data['uid'] = $uid;
// 			$score_op_success = CreditAccount::db()->insert($data);
// 			//Event::fire('user.update_userinfo_cache',array(array($uid)));
// 		}
// 		if($score_op_success === false){
// 			return false;
// 		}
		if($score==0) return true;
		
	    if($score>0){
			$sign = '增加';
		}else{
			$sign = '减少';
		}
			    
		if(isset($list[$action]) &&!empty($list[$action])){
			$info_rule = array('{action}'=>$list[$action]['name'],'{sign}'=>$sign,'{score}'=>$score,'{typecn}'=>'游币','{experience}'=>$experience);
			$info = str_replace(array_keys($info_rule),array_values($info_rule),$list[$action]['info']);
		}
		
		$credit_history = array('uid'=>$uid,'info'=>$info,'action'=>$action,'type'=>'游币','credit'=>$score,'mtime'=>(int)microtime(true));
		AccountCreditHistory::db()->insert($credit_history);
		
		//迁移后游币处理  老游币表依旧操作
		$params = array('accountId'=>$uid,'platform'=>'ios');
		$params_ = array('accountId','platform');
		$new = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'account/query');
		if ($new['result']) {
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
	 * 获取积分历史记录
	 */
	public static function getCreditHistory($uid,$page=1,$pagesize=10)
	{
		$res = User::getCreditHistory($uid,$page,$pagesize);
		return $res;
	}
}