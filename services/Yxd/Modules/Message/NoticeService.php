<?php
namespace Yxd\Modules\Message;

use Yxd\Modules\Core\BaseService;

use Yxd\Services\Models\SystemMessage;
use Yxd\Services\Models\SystemMessageTpl;

class NoticeService extends BaseService
{
	public static $TypeList = array('1'=>'礼包','2'=>'系统更新','3'=>'','4'=>'','5'=>'',);
	
	const SYSTEM_REDIRECT_METHOD_NO = 0;
	const SYSTEM_REDIRECT_METHOD_APP = 1;
	const SYSTEM_REDIRECT_METHOD_BUILT_IN_BROWSER = 2;
	const SYSTEM_REDIRECT_METHOD_OUT_BROWSER = 3;
	
	public static $LinkTypeList   = array(
	    '0'=>'不跳转',
	    '1'=>'游戏详情','2'=>'美女视频详情','3'=>'专题','4'=>'新游预告详情','5'=>'攻略列表',
	    //'6'=>'评测列表','7'=>'新闻列表',
	    '10'=>'攻略详情','9'=>'评测详情','8'=>'新闻详情',
	    //'11'=>'活动(1,2,3)',
	    '12'=>'礼包详情',
	    '16'=>'论坛首页','17'=>'帖子详情','18'=>'资料大全','19'=>'有奖问答详情','20'=>'商城详情','21'=>'社区广场(1,2,3)',
	
	);
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
	
	
	
	
	const AUTO_NOTICE_SUBSCRIBE_GIFTBAG_SUCCESS = 'subscribe_giftbag_success';
	const AUTO_NOTICE_SUBSCRIBE_GIFTBAG_UPDATE  = 'subscribe_giftbag_update';
	const AUTO_NOTICE_GET_GIFTBAG_SUCCESS = 'get_giftbag_success';
	const AUTO_NOTICE_HUNT_AWARD_MONEY = 'hunt_award_money';
	const AUTO_NOTICE_HUNT_AWARD_PRODUCT = 'hunt_award_product';
	const AUTO_NOTICE_HUNT_AWARD_GIFTBAG = 'hunt_award_giftbag';	
	const AUTO_NOTICE_GAMEASK_AWARD_MONEY = 'gameask_award_money';
	const AUTO_NOTICE_GAMEASK_AWARD_PRODUCT = 'gameask_award_product';
	const AUTO_NOTICE_GAMEASK_AWARD_GIFTBAG = 'gameask_award_giftbag';
	const AUTO_NOTICE_SHOP_GOODS_GIFT_EXCHANGE_SUCCESS = 'shop_goods_giftbag_exchange_success';
	const AUTO_NOTICE_SHOP_GOODS_PRODUCT_EXCHANGE_SUCCESS = 'shop_goods_product_exchange_success';
	const AUTO_NOTICE_COMMENT_DELETED = 'comment_deleted';
	const AUTO_NOTICE_TOPIC_DELETED = 'topic_deleted';
	const AUTO_NOTICE_TOPIC_DIGEST = 'topic_digest';
	const AUTO_NOTICE_TOPIC_UNDIGEST = 'topic_undigest';
	const AUTO_NOTICE_REPLY_BEST = 'reply_best';
	const AUTO_NOTICE_REPLY_BEST_NO_SCORE = 'reply_best_no_score';
	const AUTO_NOTICE_ATTENTION_YOU = 'attention_you';
	const AUTO_NOTICE_REGISTER = 'register';
	const AUTO_NOTICE_REGISTER_TUIGUANG = 'register_tuiguang';
	const AUTO_NOTICE_TUIGUANG_SCORE = 'tuiguang_score';
	const AUTO_NOTICE_EXTRA_SCORE = 'extra_score';
	const AUTO_NOTICE_INVALID_INVITECODE = 'invalid_invitecode';
	const AUTO_NOTICE_EXISTS_IOS = 'exists_ios';
	/**
	 * 发送主动消息
	 */
	public static function sendInitiativeMessage($type,$linktype,$link,$title,$content,$is_top=false,$is_push=false,$uids=array())
	{
		$data['type'] = $type;
		$data['linktype'] = $linktype;
		$data['link'] = $link;
		$data['title'] = $title;
		$data['content'] = $content;		
		$data['sendtime'] = time();
		$data['istop'] = $is_top ? 1 : 0;
		if($uids && is_array($uids) && count($uids)>0){
			$muli_data = array();
			foreach($uids as $uid){
				if($uid==0) continue;
				$one = $data;
				$one['to_uid'] = $uid;
				$muli_data[] = $one;
			}
			self::save($muli_data,$is_push);
		}else{
			$data['to_uid'] = 0;
			self::save($data,$is_push);
		}
		return true;
	}
	
	public static function sendInitiativeMessageFromArray($message_data)
	{
		$data = array();
		foreach($message_data as $row){
			if(!$row['uid']) continue;
			$message['type'] = 1;
			$message['linktype'] = $row['linktype'];
			$message['link'] = $row['link'];
			$message['title'] = $row['title'];
			$message['content'] = $row['content'];
			$message['to_uid'] = $row['uid'];
			$message['sendtime'] = time();
			$message['istop'] = 0;
			
			$data[] = $message;
		}
		if(!$data) return false;
		return self::save($data);
	}
	
	/**
	 * 发送被动消息
	 */
    protected static function sendPassiveMessage($uid,$type,$linktype,$link,$tpl_ename,$params,$is_top=false)
	{
		$tpl = self::parseTpl($tpl_ename, $params);
		if($tpl===false) return false;
		$content = $tpl;
		$data['type'] = 0;//0:被动(是指程序自动发的)1:主动(指后台人为发送的)
		$data['linktype'] = $linktype;
		$data['link'] = $link;
		$data['title'] = $content;
		$data['content'] = $content;		
		$data['sendtime'] = time();
		$data['istop'] = $is_top ? 1 : 0;
		$data['to_uid'] = $uid;
		self::save($data);
		return true;
	}
	//关于推广发送的系统消息
	protected static function sendPassiveMessageScore($uid,$type,$linktype,$link,$tpl_ename,$params,$is_top=false)
	{
		$tpl = self::parseTpl($tpl_ename, $params);
		if($tpl===false) return false;
		$content = $tpl;
		$data['type'] = 0;//0:被动(是指程序自动发的)1:主动(指后台人为发送的)
		$data['linktype'] = $linktype;
		$data['link'] = $link;
		$data['title'] = $content;
		$data['content'] = $content;		
		$data['sendtime'] = time();
		$data['istop'] = $is_top ? 1 : 0;
		$data['to_uid'] = $uid;
		self::save($data);
		//self::dbclubMaster()->table('system_message')->insert($data);
		return true;
	}
	
	public static function parseTpl($tpl_ename,$params)
	{
	    $tpl = SystemMessageTpl::db()->where('ename','=',$tpl_ename)->first();
		if(!$tpl) return false;
		$tpl = $tpl['content'];
		foreach($params as $key=>$val){
			$tpl = str_replace('{'.$key.'}',$val,$tpl);
		}
		return $tpl;
	}
	
	/**
	 * 保存消息数据
	 */
	protected static function save($data,$push=false)
	{
		return SystemMessage::db()->insert($data);
	}
	
	/**
	 * 搜索消息
	 */
	public static function search($search,$page=1,$size=10)
	{
		$total = self::buildSearch($search)->count();
		$data = self::buildSearch($search)->forPage($page,$size)->orderBy('id','desc')->get();
		return array('result'=>$data,'total'=>$total);
	}
	
	protected static function buildSearch($search)
	{
		$tb = SystemMessage::db();
		$tb = $tb->where('type','=',1);
		return $tb;
	}
	
	public static function getInfo($id)
	{
		return SystemMessage::db()->where('id','=',$id)->first();
	}
	
	/**
	 * 预约游戏礼包成功
	 * @deprecated
	 */
	public static function sendSubscribeGiftbagSuccess($uid,$game_id,$params)
	{
		//已去掉
		//if(!$uid) return false;
		//$tpl = self::AUTO_NOTICE_SUBSCRIBE_GIFTBAG_SUCCESS;
		//return self::sendPassiveMessage($uid,0,self::SYSTEM_REDIRECT_TYPE_GAME_DETAIL,$game_id,$tpl,$params);
	}
	
	/**
	 * 预约游戏有新礼包
	 */
	public static function sendSubscribeGiftbagUpdate($uid,$giftbag_id,$params)
	{
		if(!$uid) return false;
		$tpl = self::AUTO_NOTICE_SUBSCRIBE_GIFTBAG_UPDATE;
		return self::sendPassiveMessage($uid,0,self::SYSTEM_REDIRECT_TYPE_GIFT_DETAIL,$giftbag_id,$tpl,$params);
	}
	
	/**
	 * 成功领取礼包
	 */
	public static function sendGetGiftbagSuccess($uid,$giftbag_id,$params)
	{
		//已去掉
		//if(!$uid) return false;
		//$tpl = self::AUTO_NOTICE_GET_GIFTBAG_SUCCESS;
		//return self::sendPassiveMessage($uid,0,self::SYSTEM_REDIRECT_TYPE_GIFT_DETAIL,$giftbag_id,$tpl,$params);
	}
	
	/**
	 * 寻宝箱活动中奖
	 */
	public static function sendHuntAward($uid,$hunt_id,$prize_type,$params)
	{
		if(!$uid) return false;
		if($prize_type==1) {
			$tpl=self::AUTO_NOTICE_HUNT_AWARD_MONEY;
		}elseif($prize_type==2) { 
			$tpl = self::AUTO_NOTICE_HUNT_AWARD_GIFTBAG;
		}elseif($prize_type==3){
			$tpl = self::AUTO_NOTICE_HUNT_AWARD_PRODUCT;
		}
		return self::sendPassiveMessage($uid,0,0,0,$tpl,$params);
	}
	
	/**
	 * 游戏问答活动中奖
	 */
	public static function sendGameAskAward($uid,$params)
	{
		if(!$uid) return false;
		$tpl = '';
		return self::sendPassiveMessage($uid,0,self::SYSTEM_REDIRECT_TYPE_ACTIVITY_DETAIL,0,$tpl,$params);
	}
	
	/**
	 * 发送商品兑换成功的系统通知
	 */
	public static function sendShopGoodsExchange($uid,$goods_type,$params)
	{		
		if(!$uid) return false;
		$tpl = (int)$goods_type==2 ? self::AUTO_NOTICE_SHOP_GOODS_GIFT_EXCHANGE_SUCCESS : self::AUTO_NOTICE_SHOP_GOODS_PRODUCT_EXCHANGE_SUCCESS;
		return self::sendPassiveMessage($uid, 0, self::SYSTEM_REDIRECT_TYPE_SHOP_GOODS, $params['goods_id'], $tpl, $params);
	}
	
	/**
	 * 发送设置最佳答案的系统通知
	 */
	public static function sendReplySetBest($uid,$tid,$params)
	{
		if(!$uid) return false;
		$tpl = self::AUTO_NOTICE_REPLY_BEST;
		if(intval($params['money'])==0){
			$tpl = self::AUTO_NOTICE_REPLY_BEST_NO_SCORE;
		}
		
		return self::sendPassiveMessage($uid, 0, 0,$tid, $tpl, $params);
	}
	
	/**
	 * 发送评论被管理员删除的系统通知
	 */
	public static function sendCommentDeletedByAdmin($uid,$params)
	{
		if(!$uid) return false;
		$tpl = self::AUTO_NOTICE_COMMENT_DELETED;
		return self::sendPassiveMessage($uid,0,0,0,$tpl,$params);
	}
	
	/**
	 * 发送发帖被管理员删除的系统通知
	 */
    public static function sendTopicDeletedByAdmin($uid,$params)
	{
		if(!$uid) return false;
		$tpl = self::AUTO_NOTICE_TOPIC_DELETED;
		return self::sendPassiveMessage($uid,0,0,0,$tpl,$params);
	}
	
	/**
	 * 发送帖子被管理员加精的系统通知
	 */
	public static function sendTopicDigestByAdmin($uid,$params)
	{
		if(!$uid) return false;
		$tpl = self::AUTO_NOTICE_TOPIC_DIGEST;
		return self::sendPassiveMessage($uid,0,0,0,$tpl,$params);
	}
	
    /**
	 * 发送帖子被管理员取消加精的系统通知
	 */
	public static function sendTopicUnDigestByAdmin($uid,$params)
	{
		if(!$uid) return false;
		$tpl = self::AUTO_NOTICE_TOPIC_UNDIGEST;
		return self::sendPassiveMessage($uid,0,0,0,$tpl,$params);
	}
	
	/**
	 * 发送新用户注册成功的系统通知
	 */
	public static function sendRegisterSuccess($uid,$params=array())
	{
		if(!$uid) return false;
		$tpl = self::AUTO_NOTICE_REGISTER;
		return self::sendPassiveMessage($uid,0,0,0,$tpl,$params);
	}
	
	/**
	 * 发送邀请码无效的系统通知
	 */
	public static function sendInvalidInviteCode($uid,$params=array())
	{
		$tpl = self::AUTO_NOTICE_INVALID_INVITECODE;
		return self::sendPassiveMessageScore($uid,0,0,0,$tpl,$params);
	}
	
	/**
	 * 发送IOS设备已经被使用的系统通知
	 */
	public static function sendExistsIOS($uid,$params=array())
	{
		$tpl = self::AUTO_NOTICE_EXISTS_IOS;
		return self::sendPassiveMessageScore($uid,0,0,0,$tpl,$params);
	}
	
	/**
	 * 发送新用户注册成功并填写推荐人的系统通知
	 */
	public static function sendNewRegisterScoreSuccess($uid,$params=array())
	{
		if(!$uid) return false;
		if($params['flag']==-1){
			$tpl = self::AUTO_NOTICE_REGISTER_TUIGUANG;
		}elseif($params['flag']==1){
			$tpl = self::AUTO_NOTICE_TUIGUANG_SCORE;
		}else{
			$tpl = self::AUTO_NOTICE_EXTRA_SCORE;
		}
		
		return self::sendPassiveMessageScore($uid,0,0,0,$tpl,$params);
	}
}