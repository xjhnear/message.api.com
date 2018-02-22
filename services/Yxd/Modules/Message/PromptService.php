<?php
namespace Yxd\Modules\Message;

use Yxd\Services\ChatService;
use Yxd\Services\UserService;
use Yxd\Services\CircleFeedService;

use Yxd\Modules\Activity\GiftbagService;
use Yxd\Services\Cms\GameService;
use Yxd\Services\Models\SystemMessage;
use Yxd\Services\Models\SystemUserMessageDeleted;
use Yxd\Services\Models\SystemUserMessageReaded;
use Yxd\Services\Models\SystemUserMessage;
use Yxd\Services\Models\Account;

use Yxd\Modules\Core\BaseService;

class PromptService extends BaseService
{
	public static function getMsgNum($uid,$type=0)
	{
		$result = self::redis()->pipeline(function($pipe)use($uid,$type){
		    switch($type)
		    {
		    	case 0:
		    		$pipe->select(0);
		    		break;
		    	case 1://圈子
		    		$pipe->select(0);
		    		break;
		    	case 2://预约礼包
		    		$pipe->set('message::reserve_'.$uid,0);
		    		break;
		    	case 3://回复
		    		$pipe->set('message::reply_'.$uid,0);
		    		break;
		    	case 4://聊天
		    		$pipe->select(0);
		    		break;
		    	case 5://反馈
		    		$pipe->select(0);
		    		break;
		    	case 6://系统
		    		$pipe->select(0);
		    		break;
		    	case 7://活动
		    		$pipe->set('message::activity_'.$uid,0);
		    		break;
		    	case 8://热门礼包
		    		$pipe->set('message::hotgiftbag_'.$uid,0);
		    		break;
		    	case 9://寻宝箱
		    		$pipe->set('message::hunt_'.$uid,0);
		    		break;
		    	case 10://商城
		    		$pipe->set('message::shop_'.$uid,0);
		    		break;
		    	case 11://奖品
		    		$pipe->set('message::goods_'.$uid,0);
		    		break;		    	
		    }
		    $pipe->select(0);
		    $pipe->get('message::reserve_'.$uid);
		    $pipe->get('message::reply_'.$uid);
		    $pipe->smembers('message::chat::uid::'.$uid);
		    $type==5 ? $pipe->getset('message::feedback::lastgettime_'.$uid,time()) : $pipe->get('message::chat::lastgettime_'.$uid);
		    //$pipe->smembers('message::system::uid'.$uid);
		    $pipe->select(0);
		    $pipe->get('message::activity_'.$uid);
		    $pipe->get('message::hotgiftbag_'.$uid);
		    $pipe->get('message::hunt_'.$uid);
		    $pipe->get('message::shop_'.$uid);
		    $pipe->get('message::goods_'.$uid);
		});
		$cycleMsgNum = CircleFeedService::getCircleFeedCount($uid,$type==1 ? true : false);
		$reserveMsgNum = (int)$result[2];
		$replyMsgNum = (int)$result[3];
		$chatMsgNum = is_array($result[4]) ? count($result[4]) : 0;
		$last = ((int)$result[5]) ? : time();
		$feedbackMsgNum = ChatService::getNotReadFeedbackNum($uid, $last);
		$all = SystemUserMessageReaded::db()->where('uid','=',$uid)->lists('msg_id');
		$systemMsgNum = PromptService::getNotReadSystemNum($uid, $all,$type==6 ? true : false);
		$activityMsgNum = (int)$result[7];
		$hotgiftbagMsgNum = (int)$result[8];
		$huntMsgNum = (int)$result[9];
		$shopMsgNum = (int)$result[10];
		$goodsMsgNum = (int)$result[11];
		
		return array(
		    'mycycleDynMsg'=>$cycleMsgNum,
		    'myreservedMsg'=>$reserveMsgNum,
		    'myreplyMsg'=>$replyMsgNum,
		    'mychatMsg'=>$chatMsgNum,
		    'myfeedbackMsg'=>$feedbackMsgNum,
		    'systemMsg'=>$systemMsgNum,
		    'activityMsg'=>$activityMsgNum,
		    'hotgiftMsg'=>$hotgiftbagMsgNum,
		    'huntMsg'=>$huntMsgNum,
		    'shopMsg'=>$shopMsgNum,
		    'myPrizeMsg'=>$goodsMsgNum
		);
		
		
	}
	
	public static function getNotReadSystemNum($uid,$all,$reset=false)
	{
		$user = UserService::getUserInfo($uid);
		$last = isset($user['dateline']) ? $user['dateline'] : time();
		$ids = SystemMessage::db()->where('is_read','=',0)
			->where(function($query)use($uid){
			    $query = $query->where('to_uid','=',0)->orWhere('to_uid','=',$uid);
			})
			->where('sendtime','>=',$last)->lists('id');
		
		$total = array_diff($ids,$all);
		if($reset==true && $total && is_array($total)){
			$data = array();
			foreach($total as $msg_id){
				$data[] = array('uid'=>$uid,'msg_id'=>$msg_id,'is_read'=>0);
			}
			if($data){
				SystemUserMessageReaded::db()->insert($data);
			}
		}
		return is_array($total) && $reset==false ? count($total) : 0;
	}
	
    /**
	 * 我的回复消息数
	 */
	public static function getMyReplyMsgNum($uid,$reset=false)
	{
	    $key = 'message::reply_' . $uid;
		if($reset){
			self::redis()->set($key,0);
			return 0;
		}else{
			return (int)self::redis()->get($key);
		}
	}
	
    /**
	 * 回复消息数加一 
	 */
	public static function addMyReplyMsgNum($uid)
	{
		$key = 'message::reply_' . $uid;
		self::redis()->incr($key);
	}
	
	/**
	 * 我的活动消息数
	 */
	public static function getMyActivityMsgNum($uid,$reset=false)
	{
	    $key = 'message::activity_' . $uid;
		if($reset){
			self::redis()->set($key,0);
			return 0;
		}else{
			return (int)self::redis()->get($key);
		}
	}
	
    /**
	 * 活动消息数加一
	 */
	public static function addMyActivityMsgNum($uid)
	{
		$key = 'message::activity_' . $uid;
		self::redis()->incr($key);
	}
	
    /**
	 * 我的预定礼包消息数
	 */
	public static function getMyReserveGiftMsgNum($uid,$reset=false)
	{
		$key = 'message::reserve_' . $uid;
		if($reset){
			self::redis()->set($key,0);
			return 0;
		}else{
			return (int)self::redis()->get($key);
		}
	}
	
    /**
	 * 预定礼包消息数加一
	 */
	public static function addMyReserveGiftMsgNum($uid)
	{
		$key = 'message::reserve_' . $uid;
		self::redis()->incr($key);
	}
	
	/**
	 * 我的热门礼包消息数
	 */
	public static function getMyHotGiftMsgNum($uid,$reset=false)
	{
	    $key = 'message::hotgiftbag_' . $uid;
		if($reset){
			self::redis()->set($key,0);
			return 0;
		}else{
			return (int)self::redis()->get($key);
		}
	}  
	 
    /**
	 * 热门礼包消息数加一
	 */
	public static function addMyHotGiftMsgNum($uid)
	{
		$key = 'message::hotgiftbag_' . $uid;
		self::redis()->incr($key);
	} 
	
    /**
	 * 我的寻宝箱消息数
	 */
	public static function getMyHuntMsgNum($uid,$reset=false)
	{
	    $key = 'message::hunt_' . $uid;
		if($reset){
			self::redis()->set($key,0);
			return 0;
		}else{
			return (int)self::redis()->get($key);
		}
	}
	
    /**
	 * 寻宝箱消息数加一
	 */
	public static function addMyHuntMsgNum($uid)
	{
		$key = 'message::hunt_' . $uid;
		self::redis()->incr($key);
	}
	
    /**
	 * 我的游币商城消息数
	 */
	public static function getMyShopMsgNum($uid,$reset=false)
	{
	    $key = 'message::shop_' . $uid;
		if($reset){
			self::redis()->set($key,0);
			return 0;
		}else{
			return (int)self::redis()->get($key);
		}
	}
	
    /**
	 * 我的奖品消息数
	 */
	public static function getMyGoodsMsgNum($uid,$reset=false)
	{
	    $key = 'message::goods_' . $uid;
		if($reset){
			self::redis()->set($key,0);
			return 0;
		}else{
			return (int)self::redis()->get($key);
		}
	}
	
    /**
	 * 我的奖品消息数加一
	 */
	public static function addMyGoodsMsgNum($uid)
	{
		$key = 'message::goods_' . $uid;
		self::redis()->incr($key);
	}
	
	/**
	 * 商城消息数加一
	 */
	public static function addMyShopMsgNum($uid)
	{
		$key = 'message::shop_' . $uid;
		self::redis()->incr($key);
	}
	
	public static function pushActivityToQueue($data)
	{
		return self::pushQueue('activity', $data);
	}
	
    public static function pushReserveToQueue($data)
	{
		$input['type'] = 'reserve';
		$input['data'] = $data;
		$input = serialize($input);
		$today = date('Ymd');
		self::queue()->rpush('queue:reserve:msg:user:'.$today,$input);
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
		$today = date('Ymd');
		$queue_name = 'queue:msg:user:'.$today;
		$data['type'] = $type;
		$data['data'] = $input;
		$data = serialize($data);
		return self::queue()->rpush($queue_name,$data);
	}
	
	public static function distributeReserve()
	{
		$today = date('Ymd');
		$queue_name = 'queue:reserve:msg:user:'.$today;
		$data = self::queue()->lpop($queue_name);
		while($data){
			$data = unserialize($data);
			$giftbag = GiftbagService::getInfo($data['data']['giftbag_id']);
			if($giftbag){
				$game_id = $giftbag['game_id'];
				$game = GameService::getGameInfo($game_id);
				$uids = GiftbagService::getReserveUids($game_id);
				if($uids && is_array($uids)){
					//发送推送消息提示
					$params = array('game_name'=>$game['shortgname']);
					PushService::sendSubscribeGiftbagUpdate($uids,$data['data']['giftbag_id'], $params);
				}
			}
			$data = self::queue()->lpop($queue_name);
		}
	}
	
	/**
	 * 分发消息
	 */
	public static function distributeData()
	{
		$today = date('Ymd');
		$queue_name = 'queue:msg:user:'.$today;
		$data = self::queue()->lpop($queue_name);
		while($data){
			$data = unserialize($data);
			$type = $data['type'];						
			switch($type)
			{
				case 'activity':
					$uids = Account::db()->where('vuser','=',0)->lists('uid');
					foreach($uids as $uid){
						self::addMyActivityMsgNum($uid);
					}
					break;
				case 'reserve':
					$giftbag = GiftbagService::getInfoById($data['data']['giftbag_id']);
					//echo '1';					
					if($giftbag){
						$game_id = $giftbag['game_id'];
						$game = GameService::getGameInfo($game_id);
						//echo '2';
						$uids = GiftbagService::getReserveUids($game_id);
						GiftbagService::updateReserveByGameId($game_id, $giftbag['id']);
						if($uids && is_array($uids)){
							foreach($uids as $uid){																
								//发送系统消息提示
								$params = array('game_name'=>$game['shortgname']);
								NoticeService::sendSubscribeGiftbagUpdate($uid,$data['data']['giftbag_id'], $params);
								//echo '3';	
								//更新消息数
								//self::addMyReserveGiftMsgNum($uid);															
							}
							
						}
					}
			        
					break;
				case 'hotgift':
					$uids = Account::db()->where('vuser','=',0)->lists('uid');
					$giftbag = GiftbagService::getInfo($data['data']['giftbag_id']);
					self::queue()->rpush('queue:hotgift:msg:user:'.$today,serialize($data));
					if($giftbag['is_hot']){
				        foreach($uids as $uid){
							self::addMyHotGiftMsgNum($uid);
						}
					}
					break;
				case 'hunt':
					$uids = Account::db()->where('vuser','=',0)->lists('uid');
			       foreach($uids as $uid){
						self::addMyHuntMsgNum($uid);
					}
					break;
				case 'shop':
					$uids = Account::db()->where('vuser','=',0)->lists('uid');
			        foreach($uids as $uid){
							self::addMyShopMsgNum($uid);
					}			       
					break;				
			}
		    $data = self::queue()->lpop($queue_name);
		}
	}
}