<?php
namespace Yxd\Modules\Message;

use Yxd\Modules\Activity\GiftbagService;
use Yxd\Services\Cms\GameService;
use Yxd\Services\ChatService;
use Yxd\Services\CircleFeedService;
use Yxd\Modules\Core\BaseService;

use Yxd\Services\Models\ChatLog;
use Yxd\Services\Models\Account;

class NumberService extends BaseService
{
	const KEY_CYCLE_NUM = 'mycycleDynMsg';
    const KEY_RESERVE_NUM = 'myreservedMsg';
    const KEY_REPLY_NUM = 'myreplyMsg';
    const KEY_CHAT_NUM = 'mychatMsg';
    const KEY_FEEDBACK_NUM = 'myfeedbackMsg';
    const KEY_SYSTEM_NUM = 'systemMsg';
    const KEY_ACTIVITY_NUM = 'activityMsg';
    const KEY_HOTGIFT_NUM = 'hotgiftMsg';
    const KEY_HUNT_NUM = 'huntMsg';
    const KEY_SHOP_NUM = 'shopMsg';
    const KEY_PRIZE_NUM = 'myPrizeMsg';

    /**
	 * 获取系统消息数
	 * @param int $type 1:圈子2:预约礼包3:回复4:聊天5:反馈6:系统7:活动8:热门礼包9:寻宝箱10商城 
	 * 
	 */
    public static function getMessageNumber($uid,$type=0)
    {
    	$out = array(
		    'mycycleDynMsg'=>0,
		    'myreservedMsg'=>0,
		    'myreplyMsg'=>0,
		    'mychatMsg'=>0,
		    'myfeedbackMsg'=>0,
		    'systemMsg'=>0,
		    'activityMsg'=>0,
		    'hotgiftMsg'=>0,
		    'huntMsg'=>0,
		    'shopMsg'=>0,
		    'myPrizeMsg'=>0
		);
		if(!$uid) return $out;
		//圈子动态
		$out['mycycleDynMsg'] = CircleFeedService::getCircleFeedCount($uid,$type==1 ? true : false);
		//聊天		
		$chat = ChatService::getNotReadChatMsgNum($uid);
		$out['mychatMsg'] = $chat;//消息中心聊天消息数量
		//反馈
		$key_last_gettime_feedback = 'message::feedback::lastgettime_' . $uid;//反馈:5
	    if($type==5){
			$last = self::redis()->getset($key_last_gettime_feedback,time()) ? : time();
		}else{
			$last = self::redis()->get($key_last_gettime_feedback) ? : time();
		}
		$feedback = ChatLog::db()->where('to_uid','=',$uid)->where('from_uid','=',1)->where('addtime','>=',$last)->count();
		$out['myfeedbackMsg'] = $feedback;//反馈消息数量
		//系统	    
		$sys = self::getNotReadSystemMsgNum($uid);
		
		$out['systemMsg'] = $sys;//消息中心系统消息数量
    	$key = 'message::hashmap::' . $uid;
    	$data = self::redis()->hgetall($key);
    	if(is_array($data)){
    		$out = array_merge($out,$data);
    	}
    	return $out;
    }
	
    /**
	 * 回复消息数加一 
	 */
	public static function addMyReplyMsgNum($uid)
	{
		$key = 'message::hashmap::' . $uid;
		self::redis()->hincrby($key,self::KEY_REPLY_NUM,1);
	}	
	
    /**
	 * 活动消息数加一
	 */
	public static function addMyActivityMsgNum($uid)
	{
		$key = 'message::hashmap::' . $uid;
		self::redis()->hincrby($key,self::KEY_ACTIVITY_NUM,1);
	}    
	
    /**
	 * 预定礼包消息数加一
	 */
	public static function addMyReserveGiftMsgNum($uid)
	{
		$key = 'message::hashmap::' . $uid;
		self::redis()->hincrby($key,self::KEY_RESERVE_NUM,1);
	}	
	 
    /**
	 * 热门礼包消息数加一
	 */
	public static function addMyHotGiftMsgNum($uid)
	{
		$key = 'message::hashmap::' . $uid;
		self::redis()->hincrby($key,self::KEY_HOTGIFT_NUM,1);
	}     
	
    /**
	 * 寻宝箱消息数加一
	 */
	public static function addMyHuntMsgNum($uid)
	{
		$key = 'message::hashmap::' . $uid;
		self::redis()->hincrby($key,self::KEY_HUNT_NUM,1);
	}    
	
    /**
	 * 我的奖品消息数加一
	 */
	public static function addMyGoodsMsgNum($uid)
	{
		$key = 'message::hashmap::' . $uid;
		self::redis()->hincrby($key,self::KEY_PRIZE_NUM,1);
	}
	
	/**
	 * 商城消息数加一
	 */
	public static function addMyShopMsgNum($uid)
	{
		$key = 'message::hashmap::' . $uid;
		self::redis()->hincrby($key,self::KEY_SHOP_NUM,1);
	}
	
	public static function pushActivityToQueue($data)
	{
		return self::pushQueue('activity', $data);
	}
	
    public static function pushReserveToQueue($data)
	{
		return self::pushQueue('reserve', $data);
	}
	
    public static function pushHotGiftToQueue($data)
	{
		return self::pushQueue('hotgift', $data);
	}
	
    public static function pushHuntToQueue($data)
	{
		return self::pushQueue('hunt', $data);
	}
	
    public static function pushShopToQueue($data)
	{
		return self::pushQueue('shop', $data);
	}
	
	protected static function pushQueue($type,$input)
	{
		$queue_name = 'queue:msg:user';
		$data['type'] = $type;
		$data['data'] = $input;
		$data = serialize($data);
		return self::queue()->rpush($queue_name,$data);
	}
	
	/**
	 * 分发消息
	 */
	public static function distributeData()
	{
		$queue_name = 'queue:msg:user';
		$data = self::queue()->lpop($queue_name);
		while($data){
			$data = unserialize($data);
			$type = $data['type'];
			$uids = Account::db()->lists('uid');			
			switch($type)
			{
				case 'activity':
					foreach($uids as $uid){
						self::addMyActivityMsgNum($uid);
					}
					break;
				case 'reserve':
					$giftbag = GiftbagService::getInfo($data['data']['giftbag_id']);
					self::queue()->rpush('queue:reserve:msg:user',serialize($data));
					if($giftbag){
						$game_id = $giftbag['game_id'];
						$game = GameService::getGameInfo($game_id);
						$uids = GiftbagService::getReserveUids($game_id);
						GiftbagService::updateReserveByGameId($game_id, $giftbag['id']);
						if($uids && is_array($uids)){
							foreach($uids as $uid){
								//更新消息数
								self::addMyReserveGiftMsgNum($uid);								
								//发送系统消息提示
								$params = array('game_name'=>$game['shortgname']);
								NoticeService::sendSubscribeGiftbagUpdate($uid,$data['data']['giftbag_id'], $params);																
							}
							//发送推送消息提示
							PushService::sendSubscribeGiftbagUpdate($uids,$data['data']['giftbag_id'], $params);
						}
					}
			        
					break;
				case 'hotgift':
					$giftbag = GiftbagService::getInfo($data['data']['giftbag_id']);
					self::queue()->rpush('queue:hotgift:msg:user',serialize($data));
					if($giftbag['is_hot']){
				        foreach($uids as $uid){
							self::addMyHotGiftMsgNum($uid);
						}
					}
					break;
				case 'hunt':
			       foreach($uids as $uid){
						self::addMyHuntMsgNum($uid);
					}
					break;
				case 'shop':
			        foreach($uids as $uid){
							self::addMyShopMsgNum($uid);
					}			       
					break;				
			}
		    $data = self::queue()->lpop($queue_name);
		}
	}
}