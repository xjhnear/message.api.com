<?php
namespace Yxd\Services;
use Yxd\Modules\Activity\GiftbagService;
use Yxd\Modules\Message\PromptService;
use Illuminate\Support\Facades\DB;

use Yxd\Services\Models\SystemMessage;
use Yxd\Services\Models\SystemMessageTpl;
use Yxd\Services\Models\SystemSetting;
use Yxd\Services\Models\SystemUserMessage;
use Yxd\Services\Models\SystemUserMessageDeleted;
use Yxd\Services\Models\SystemUserMessageReaded;
use Yxd\Services\Models\ChatLog;

/**
 * 通知服务
 */
class NoticeService extends Service
{
	//推送
	const SYSTEM_MESSAGE_TYPE_MASS = 1;
	//礼包
	const SYSTEM_MESSAGE_TYPE_GIFT = 2;
	//应用更新
	const SYSTEM_MESSAGE_TYPE_APP = 3;
	//活动
	const SYSTEM_MESSAGE_TYPE_ACTIVITY = 4;
	//提示
	const SYSTEM_MESSAGE_TYPE_PROMPT = 5;
	//管理
	const SYSTEM_MESSAGE_TYPE_ADMIN = 6;

	const SYSTEM_REDIRECT_TYPE_GAME_DETAIL = 1;//游戏详情
	const SYSTEM_REDIRECT_TYPE_VIDEO_DETAIL = 2;//美女视频详情
	const SYSTEM_REDIRECT_TYPE_SPECIAL = 3;//专题
	const SYSTEM_REDIRECT_TYPE_NEWGAME_DETAIL = 4;//新游预告详情
	const SYSTEM_REDIRECT_TYPE_GUIDE_LIST = 5;//攻略列表
	const SYSTEM_REDIRECT_TYPE_OPINION_LIST = 6;//评测列表
	const SYSTEM_REDIRECT_TYPE_NEWS_LIST = 7;//新闻列表
	const SYSTEM_REDIRECT_TYPE_GUIDE_DETAIL = 8;//攻略详情
	const SYSTEM_REDIRECT_TYPE_OPINION_DETAIL = 9;//评测详情
	const SYSTEM_REDIRECT_TYPE_NEWS_DETAIL = 10;//新闻详情
	const SYSTEM_REDIRECT_TYPE_ACTIVITY_DETAIL = 11;//活动详情
	const SYSTEM_REDIRECT_TYPE_GIFT_DETAIL = 12;//礼包详情
	const SYSTEM_REDIRECT_TYPE_FORUM = 16;//论坛首页
	const SYSTEM_REDIRECT_TYPE_TOPIC = 17;//帖子详情
	const SYSTEM_REDIRECT_TYPE_ARTICLE = 18;//资料大全
	const SYSTEM_REDIRECT_TYPE_GAMEASK = 19;//有奖问答详情
	const SYSTEM_REDIRECT_TYPE_SHOP_GOODS = 20;//商城兑换详情
	const SYSTEM_REDIRECT_TYPE_CLUB = 21;//社区广场
	
	/**
	 * 系统消息列表
	 */
	public static function getSystemMessageList($uid,$page=1,$pagesize=10)
	{
		$user = UserService::getUserInfo($uid);
		$last = isset($user['dateline']) ? $user['dateline'] : time();
		
		//$key = 'message::deleted::system::uid' . $uid;
		//$del_ids = self::redis()->smembers($key);
		
		$del_ids = SystemUserMessageDeleted::db()->where('uid','=',$uid)->lists('msg_id');
		
		$list = self::buildList($uid,$last,$del_ids)
			->orderBy('istop','desc')
			->orderBy('sendtime','desc')
			->forPage($page,$pagesize)
			->get();
		$total = self::buildList($uid,$last,$del_ids)->count();
		return array('messages'=>$list,'total'=>$total);
	}
	
	protected static function buildList($uid,$last,$del_ids)
	{
		$tb = SystemMessage::db()
			->where(function($query)use($uid){
			    $query = $query->where('to_uid','=',0)->orWhere('to_uid','=',$uid);
			})
			->where('sendtime','>=',$last);
			
		if($del_ids && is_array($del_ids) && count($del_ids)>0){
			$tb = $tb->whereNotIn('id',$del_ids);
		}
		return $tb;			
	}
	
	public static function getNotReadSystemMsgNum($uid)
	{
		$user = UserService::getUserInfo($uid);
		$last = isset($user['dateline']) ? $user['dateline'] : time();
		$ids = SystemMessage::db()
			->where(function($query)use($uid){
			    $query = $query->where('to_uid','=',0)->orWhere('to_uid','=',$uid);
			})
			->where('sendtime','>=',$last)->lists('id');
		
		//$key = 'message::system::uid' . $uid;
		//$all = self::redis()->smembers($key);
		
		$all = SystemUserMessageReaded::db()->where('uid','=',$uid)->where('is_read','=',0)->lists('msg_id');
		$total = array_diff($ids,$all);
		return is_array($total) ? count($total) : 0;
	}
	
	public static function resetSystemMsgNum($uid,$id)
	{
		//$key = 'message::system::uid' . $uid;
		//self::redis()->sadd($key,$id);
		$exists = SystemUserMessageReaded::db()->where('uid','=',$uid)->where('msg_id','=',$id)->first();
		if($exists){
			SystemUserMessageReaded::db()->where('id','=',$exists['id'])->update(array('is_read'=>1));
		}else{
			SystemUserMessageReaded::db()->insert(array('uid'=>$uid,'msg_id'=>$id,'is_read'=>1));
		}
	}
	
	public static function isReadSystemMsg($uid,$id)
	{
		//$key = 'message::system::uid' . $uid;
		//$all = self::redis()->smembers($key);
		
		$all = SystemUserMessageReaded::db()->where('uid','=',$uid)->where('is_read','=',1)->lists('msg_id');
		
		return in_array($id,$all) ? 0 : 1;
		
	}
	
	public static function getReadSystemMsg($uid)
	{
		//$key = 'message::system::uid' . $uid;
		//$all = self::redis()->smembers($key);
		$all = SystemUserMessageReaded::db()->where('uid','=',$uid)->where('is_read','=',1)->lists('msg_id');
		return $all;
	}
	
	/**
	 * 删除系统通知
	 */
	public static function deleteNotice($mid,$uid)
	{
		//$key = 'message::deleted::system::uid' . $uid;
		//self::redis()->sadd($key,$mid);
		$exists = SystemUserMessageDeleted::db()->where('uid','=',$uid)->where('msg_id','=',$mid)->first();
		!$exists && SystemUserMessageDeleted::db()->insert(array('uid'=>$uid,'msg_id'=>$mid));
	}
	
	/**
	 * 发送系统消息
	 */
	public static function sendNotice($notice)
	{
		return DB::table('system_message')->insertGetId($notice);
	}
	
	/**
	 * 获取系统消息数
	 * @param int $type 1:圈子2:预约礼包3:回复4:聊天5:反馈6:系统7:活动8:热门礼包9:寻宝箱10商城 
	 * 
	 */
	public static function getMyMessageNumber($uid,$type=0)
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
		return PromptService::getMsgNum($uid,$type);
		$key_last_gettime_chat = 'message::chat::lastgettime_' . $uid;//聊天:4
		$key_last_gettime_feedback = 'message::feedback::lastgettime_' . $uid;//反馈:5		
		//$key_last_gettime_system = 'message::system::lastgettime_' . $uid;//系统:6
		//活动:7
		//热门礼包:8
		//寻宝箱:9
		//游币商城:10
		
		if(!$uid) return $out;
		//return $out;
		//圈子动态
		$out['mycycleDynMsg'] = CircleFeedService::getCircleFeedCount($uid,$type==1 ? true : false);
		
		//预约礼包
		$out['myreservedMsg'] = PromptService::getMyReserveGiftMsgNum($uid,$type==2 ? true : false);
		//回复
		$out['myreplyMsg'] = PromptService::getMyReplyMsgNum($uid,$type==3 ? true : false);//消息中心回复消息数量
		
		//聊天
		
		$chat = ChatService::getNotReadChatMsgNum($uid);
		$out['mychatMsg'] = $chat;//消息中心聊天消息数量
		
		//反馈
	    if($type==5){
			$last = self::redis()->getset($key_last_gettime_feedback,time()) ? : time();
		}else{
			$last = self::redis()->get($key_last_gettime_feedback) ? : time();
		}
		$feedback = ChatLog::db()->where('to_uid','=',$uid)->where('from_uid','=',1)->where('addtime','>=',$last)->count();
		$out['myfeedbackMsg'] = $feedback;//反馈消息数量	
		/*				
		$out['activityMsg'] = PromptService::getMyActivityMsgNum($uid,$type==7 ? true : false);//新活动消息数量7
		$out['hotgiftMsg'] =  PromptService::getMyHotGiftMsgNum($uid,$type==8 ? true : false);//热门礼包消息数量8
		$out['huntMsg'] = PromptService::getMyHuntMsgNum($uid,$type==9 ? true : false);//寻宝箱消息数量9
		$out['shopMsg'] = PromptService::getMyShopMsgNum($uid,$type==10 ? true : false);//游币商城消息数量10
		$out['myPrizeMsg'] = PromptService::getMyGoodsMsgNum($uid,$type==11 ? true : false);//我的商品消息数量11
		*/
		//系统	    
		$sys = self::getNotReadSystemMsgNum($uid);
		$out['systemMsg'] = $sys;//消息中心系统消息数量			
		return $out;
	}
}