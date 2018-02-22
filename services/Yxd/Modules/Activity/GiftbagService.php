<?php
namespace Yxd\Modules\Activity;

use Yxd\Services\Cms\GameService;
use Yxd\Services\CreditService;
use Yxd\Services\UserFeedService;
use Yxd\Services\UserService;
use Youxiduo\Helper\Utility;
use Yxd\Modules\Message\NoticeService;
use Yxd\Modules\Core\BaseService;
use modules\giftbag\models\GiftbagModel;
use Illuminate\Support\Facades\Config;
use Yxd\Services\Models\Giftbag;
use Yxd\Services\Models\GiftAccount;
use Yxd\Services\Models\GiftbagAppoint;
use Yxd\Services\Models\GiftbagCard;
use Yxd\Services\Models\GiftReserve;
use Yxd\Services\Models\Games;
use Youxiduo\Helper\DES;

/**
 * 礼包服务类
 */
class GiftbagService extends BaseService
{
    const API_URL_CONF = 'app.mall_api_url';
    const MALL_API_ACCOUNT = 'app.account_api_url';
	/**
	 * 礼包列表
	 */
	public static function getList($game_id,$page=1,$size=10)
	{
		$total = self::buildList($game_id)->count();
		$list = self::buildList($game_id)
		    ->forPage($page,$size)
		    //->orderBy('sort','desc')
		    ->orderBy('ctime','desc')
		    ->get();	
		return array('result'=>$list,'total'=>$total);	
	}
	
	/**
	 * 热门礼包
	 */
	public static function getHotList()
	{
		return Giftbag::db()->where('is_ios','=',1)->where('is_show','=',1)->where('is_activity','=',0)->where('is_hot','=',1)->where('is_top','=',1)->orderBy('sort','desc')->orderBy('ctime','desc')->get();
	}
	
	/**
	 * 
	 */
	protected static function buildList($game_id)
	{
		$tb = Giftbag::db();
		if($game_id){
		    $tb->where('game_id','=',$game_id);		    
		}else{
			$tb = $tb->where('is_hot','=',0);
			$tb = $tb->where('is_top','=',0);
		}
		$tb = $tb->where('is_ios','=',1)->where('is_activity','=',0)->where('is_show','=',1);
		return $tb;
	}
	
	/**
	 * 礼包详情
	 */
	public static function getDetail($giftbag_id,$uid=0)
	{
		$giftbag = Giftbag::db()->where('id','=',$giftbag_id)->where('is_show','=',1)->first();
		if(!$giftbag) return null;
		$game_id = $giftbag['game_id'];
		$giftbag['condition'] = json_decode($giftbag['condition'],true);
		$game = GameService::getGameInfo($game_id);
		$giftbag['game'] = $game;
		if($uid){
		    $my_cardno = GiftAccount::db()->where('gift_id','=',$giftbag_id)->where('uid','=',$uid)->pluck('card_no');
		}else{
			$my_cardno = '';
		}
		$giftbag['ishas'] = $my_cardno ? 1 : 0;
		$giftbag['cardno'] = $my_cardno;
		//清空预约礼包
		$uid && GiftReserve::db()->where('gift_id','=',$giftbag_id)->where('uid','=',$uid)->update(array('gift_id'=>'0'));
		return $giftbag; 
	}
	
	/**
	 * 礼包详情
	 */
	public static function getDetailTest($giftbag_id,$uid=0)
	{
	    $giftbag = self::dbClubSlave()->table('giftbag')->where('id','=',$giftbag_id)->where('is_show','=',1)->first();
	    if(!$giftbag) return null;
	    $game_id = $giftbag['game_id'];
	    $giftbag['condition'] = json_decode($giftbag['condition'],true);
	    $game = GameService::getGameInfo($game_id);
	    $giftbag['game'] = $game;
	    if($uid){
	         
	        //V4礼包领取记录接口
	        $params = array('productType'=>2,'accountId'=>$uid,'platform'=>'ios','productCode'=>'ios'.$giftbag_id);
	        $params_ = array('productType','accountId','platform','productCode');
	        $result = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'accountproduct/query');
	         
	        $my_cardno = isset($result['result'][0]['card'])?DES::decrypt($result['result'][0]['card'],11111111):'';
	        if($giftbag['is_not_limit'] == 1 || $giftbag['limit_count']>0 ){
	            $my_cardno = '';
	        }
	    }else{
	        $my_cardno = '';
	    }
	    $giftbag['ishas'] = $my_cardno ? 1 : 0;
	    $giftbag['cardno'] = $my_cardno;
	    //清空预约礼包
	    $uid && self::dbClubMaster()->table('gift_reserve')->where('gift_id','=',$giftbag_id)->where('uid','=',$uid)->update(array('gift_id'=>'0'));
	    return $giftbag;
	}
	
	
	public static function getInfo($giftbag_id)
	{
		return Giftbag::db()->where('id','=',$giftbag_id)->where('is_show','=',1)->first();
	}
	
	public static function getInfoById($giftbag_id)
	{
		return Giftbag::db()->where('id','=',$giftbag_id)->first();
	}
	
	public static function getDetailByGameID($game_id)
	{
		return Giftbag::db()->where('game_id','=',$game_id)->where('is_ios','=',1)->where('is_show','=',1)->orderBy('ctime','desc')->forPage(1,1)->get();
	}
	
	
	/**
	 * 搜索礼包
	 */
	public static function search($keyword,$page=1,$size=10)
	{
		$gids = Games::db()->where('isdel','=',0)->where('shortgname','like','%'.$keyword . '%')->lists('id','ico');
	    if(!$gids){
			return array('result'=>array(),'total'=>0);
		}
		
		$tb = Giftbag::db()->where('is_show','=',1)->where('is_activity','=',0);
		if($gids) $tb = $tb->whereIn('game_id',array_values($gids));
		$games = array_flip($gids);
		$total = $tb->count();
		$gifts = $tb->orderBy('ctime','desc')
		       ->orderBy('sort','desc')
		       ->forPage($page,$size)
		       ->get();
		foreach($gifts as $key=>$row){
			if(!$row['listpic']){
				if(isset($games[$row['game_id']])) $row['listpic'] = $games[$row['game_id']];
			}
			$gifts[$key] = $row;
		} 
		return array('result'=>$gifts,'total'=>$total); 
	}
	
	/**
	 * 我的礼包
	 */
	public static function getMyGift($uid,$page=1,$size=10)
	{
		$total = GiftAccount::db()->where('uid','=',$uid)->count();
		$my_gifts = GiftAccount::db()->where('uid','=',$uid)->orderBy('addtime','desc')->get();
		return array('result'=>$my_gifts,'total'=>$total);
	}
	
	public static function getMyCardNoList($uid)
	{
		return GiftAccount::db()->where('uid','=',$uid)->orderBy('addtime','desc')->lists('card_no','gift_id');
	}
	
	public static function getListByIds($ids)
	{
		if(!$ids) return array();
		return Giftbag::db()->whereIn('id',$ids)->get();
	}
	
	/**
	 * 我的预定
	 */
	public static function myReserve($uid,$page=1,$pagesize=10)
	{
		$my_reserve = GiftReserve::db()->where('uid','=',$uid)->orderBy('addtime','desc')->get();
		$total = GiftReserve::db()->where('uid','=',$uid)->count();
		if($total==0) return array('result'=>array(),'total'=>$total);
	    $game_ids = $games = array();
		foreach($my_reserve as $row){
			$game_ids[] = $row['game_id'];
		}
		$games = GameService::getGamesByIds($game_ids);
		
		$gift_ids = Giftbag::db()->whereIn('game_id',$game_ids)->lists('id','game_id');
		
		$my_gift_ids = GiftAccount::db()->where('uid','=',$uid)->lists('gift_id');		
		$first_section = $second_section = array();
		foreach($my_reserve as $key=>$row){	
			
			$row['game'] = $games[$row['game_id']];
			/*
		    if(isset($gift_ids[$row['game_id']])){
		    	$row['giftbag_id'] = !in_array($gift_ids[$row['game_id']],$my_gift_ids) ? $gift_ids[$row['game_id']] : '';
				$first_section[] = $row;
			}else{
				$row['giftbag_id'] = '';
				$second_section[] = $row;
			}
			*/
		    if($row['gift_id']){
				$first_section[] = $row;
			}else{
				$second_section[] = $row;
			}
		}
		
		$out = array_merge($first_section,$second_section);
		$pages = array_chunk($out,$pagesize,false);
		return array('result'=>isset($pages[$page-1])?$pages[$page-1]:array(),'total'=>$total);
	}
	
	/**
	 * 预定礼包
	 */
    public static function doMyReserve($game_id,$uid)
	{
		$data = array(
		    'game_id'=>$game_id,
		    'uid'=>$uid,
		    'addtime'=>time()
		);
		if(self::isReserve($game_id, $uid)) return -1;
		$game = GameService::getGameInfo($game_id);
		if(!$game) return false;
		//产生动态
		UserFeedService::makeFeedReserve($uid, $game_id);		
		$params = array('game_name'=>$game['shortgname']);
		NoticeService::sendSubscribeGiftbagSuccess($uid, $game_id, $params);
		return GiftReserve::db()->insertGetId($data);
	}
	
	public static function isReserve($game_id,$uid)
	{
		$count = GiftReserve::db()->where('game_id','=',$game_id)->where('uid','=',$uid)->count();
		return $count>0 ? true : false;
	}
	
	/**
	 * 删除预定
	 */
    public static function removeMyReserve($game_id,$uid)
	{
		$row = GiftReserve::db()->where('game_id','=',$game_id)->where('uid','=',$uid)->delete();
		return true;
	}
	
	/**
	 * 领取礼包new
	 */
	public static function doMyGiftNew($gift_id,$uid)
	{
	    $out = array();
	    // 获取礼包详情
	    $params = array('productType'=>2,'productCode'=>$gift_id,'uid'=>$uid,'platform'=>'ios','currencyType'=>0);
	    $params_ = array('productType','productCode','uid','platform','currencyType');
	    $gift = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::API_URL_CONF).'product/query_product');
	
	    if($gift['result']) $gift = $gift['result'][0];
	
	    if(!$gift){
	        $out['errorCode'] = 500;
	        $out['result'] = "礼包不存在";
	        return $out;//礼包不存在
	    }
	
	    $user_score = UserService::getUserRealTimeCredit($uid,'score');
	    // 下单
	    $params = array('gfid'=>$gift_id,'uid'=>'ios'.$uid,'platform'=>'ios');
	    $params_ = array('gfid','uid','platform');
	    $order = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::API_URL_CONF).'order/placeorder_reform');
	    $out['errorCode'] = $order['errorCode'];
	    if ($order['errorCode'] == 0) {
	        $order_id = $order['result'];
	        // 支付
	        $params = array('orderId'=>$order_id,'payGameAmount'=>$gift['price'],'payer'=>'ios'.$uid,'platform'=>'ios');
	        $params_ = array('orderId','payGameAmount','payer','platform');
	        $pay = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::API_URL_CONF).'order/payorder_reform');
	        $out['errorCode'] = $pay['errorCode'];
	        if ($pay['errorCode'] == 0) {
	            $card['cardno'] = isset($pay['result'][0]['productInfo'])?DES::decrypt($pay['result'][0]['productInfo'],11111111):"";
	            //发送系统消息
	            $params = array('title'=>$gift['title'],'cardno'=>$card['cardno']);
	            NoticeService::sendGetGiftbagSuccess($uid, $gift_id, $params);
	            //产生用户动态
	            UserFeedService::makeFeedGift($uid,$gift_id);
	            $content = NoticeService::parseTpl(NoticeService::AUTO_NOTICE_GET_GIFTBAG_SUCCESS, $params);
	            $out['result'] = array('title'=>$gift['title'],'content'=>$content);
	        } else {
	            if (isset($pay['errorDescription'])) {
	                $err_msg = explode("|||", $pay['errorDescription']);
	                $out['result'] = $err_msg[1]?:$pay['errorDescription'];
	            } else {
	                $out['result'] = "未知错误";
	            }
	        }
	
	    } else {
	        $err_msg = explode("|||", $order['errorDescription']);
	        $out['result'] = $err_msg[1]?:$order['errorDescription'];
	    }
	
	    return $out;
	
	}
	
	
	/**
	 * 领取礼包
	 */
    public static function doMyGift($gift_id,$uid)
	{
		$gift = Giftbag::db()->where('id','=',$gift_id)->first();
		if(!$gift){
			return -1;//礼包不存在
		}
		//判断是否是指定用户可领
		if($gift['is_appoint'] == 1){ //是指定派送类型
			$appointed = GiftbagService::isGiftbagAppointUser($gift_id, $uid);
			if(!$appointed) return -2; //该用户无权领取
		}
		
// 		//设备限制
// 		$exists = self::isGetGiftbagByAppleIdentify($gift_id, $uid);
// 		$apple_identify = UserService::getUserAppleIdentify($uid);
// 		if($exists){
// 			return -3;
// 		}
		if($gift['limit_count']>0){
		    //限制领取数量
		    $exists = self::isGetGiftbagLimitByUID($gift['limit_count'], $gift_id, $uid);
		     
		    if($exists){
		        return -5;
		    }
		}
		
		$gift['condition'] = json_decode($gift['condition'],true);
		if(!isset($gift['condition']['score'])) $gift['condition']['score'] = 0;
		
		$user_score = UserService::getUserRealTimeCredit($uid,'score');
		//如果需要游币领取则验证游币是否充足
		if($gift['condition']['score']>0 && $user_score < $gift['condition']['score']){
			return 2;
		}
// 		$my_card = GiftAccount::db()
// 		->where('gift_id','=',$gift_id)
// 		->where('uid','=',$uid)
// 		->first();

		//V4礼包领取记录接口
		$params = array('productType'=>2,'accountId'=>$uid,'platform'=>'ios','productCode'=>'ios'.$gift_id);
		$params_ = array('productType','accountId','platform','productCode');
		$result = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'accountproduct/query');
		
		$game = GameService::getGameInfo($gift['game_id']);
		
		if($gift['is_not_limit']==0 && $gift['limit_count']==0 && isset($result['result'][0])){
// 		if($my_card){//已经领取过礼包
			$out['cardNum'] = !empty($result['result'][0]['card'])?DES::decrypt($result['result'][0]['card'],11111111):'';
			$out['remainTourCurrency'] = UserService::getUserRealTimeCredit($uid,'score');
			return $out;
		}else{
			//$card = self::lockGiftbagCardNo($gift_id);
			$cards = GiftbagCard::db()->where('giftbag_id','=',$gift_id)->where('is_get','=',0)->get();
		    $success = false;
			$card = array();
			foreach($cards as $key=>$row){
				$success = self::updateGiftbagCardStatus($row['id'],$uid)>0 ? true : false;
				if($success===true){
					$card = $row;
					break;
				}
			}
			//			
			if($card){																
				if($success){
// 					$data = array(
// 					    'gift_id'=>$gift_id,
// 					    'uid'=>$uid,
// 					    'game_id'=>$gift['game_id'],
// 					    'card_no'=>$card['cardno'],
// 					    'idfa'=>$apple_identify,
// 					    'addtime'=>time()
// 					);
// 					GiftAccount::db()->insertGetId($data);
					
					//V4礼包领取记录接口
					$detailBean = array(
					    'productCode'=>'ios'.$gift_id,
					    'productName'=>$gift['title'],
					    'productSummary'=>$gift['shorttitle'],
					    'productGamePrice'=>$gift['condition']['score'],
					    'title'=>$gift['title'],
					    'body'=>$gift['content'],
					    'productType'=>2,
					    'inventedType'=>0,
					    'productNumber'=>1,
					    'productInfo'=>DES::encrypt($card['cardno']),
					    'tradeStatus'=>1,
					    'productPrice'=>$gift['condition']['score'],
					    'productInstruction'=>$gift['content'],
					    'img'=>json_encode(array('listPic'=>$game['ico'],'detailPic'=>$game['ico'])),
					    'platform'=>'ios',
					    'gid'=>$gift['game_id'],
					    'gname'=>$game['shortgname']?$game['shortgname']:$game['gname'],
					    'orderId'=>$uid.$gift_id.time(),
					);
					$params = array(
					    'accountId'=>'ios'.$uid,
					    'platform'=>'ios',
					);
					$params['detailBean'][] = $detailBean;
					$params_ = array(
					    'accountId',
					    'platform',
					    'detailBean'
					);

					$r = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'accountproduct/add','POST');

					//更新礼包卡剩余数量
					Giftbag::db()->where('id','=',$gift_id)->where('last_num','>',0)->decrement('last_num');
					//减游币
					if((int)$gift['condition']['score']>0){
						$info = '领取礼包花费游币' . $gift['condition']['score'] . '枚';
						CreditService::handOpUserCredit($uid, (0-$gift['condition']['score']),0,'get_giftbag',$info);
					}
					
					//$out['cardNum'] = $card['cardno'];
					//$out['remainTourCurrency'] = UserService::getUserRealTimeCredit($uid,'score');
					//发送系统消息
					$params = array('title'=>$gift['title'],'cardno'=>$card['cardno']);
					NoticeService::sendGetGiftbagSuccess($uid, $gift_id, $params);
					//产生用户动态
					UserFeedService::makeFeedGift($uid,$gift_id);
					$content = NoticeService::parseTpl(NoticeService::AUTO_NOTICE_GET_GIFTBAG_SUCCESS, $params);
					$out = array('title'=>$gift['title'],'content'=>$content);
					return $out;
				}else{
					return 1;
				}
			}else{
				return 0;//礼包已经被领取完
			}
		}
	}

	/**
	 * 是否已领取礼包
	 */
	public static function isGetGiftbag($giftbag_id,$uid)
	{
		if(!$uid) return false;
		$mygift = GiftbagService::getMyCardNoList($uid);
		if($mygift && is_array($mygift)){
				$mygift_ids = array_keys($mygift);
				return in_array($giftbag_id,$mygift_ids);
		}
		return false;
	}
	
	public static function isGetGiftbagByAppleIdentify($giftbag_id,$uid)
	{
		if(!$giftbag_id || !$uid) return false;
	    $apple_identify = UserService::getUserAppleIdentify($uid);
		if($apple_identify){
			$today = mktime(0,0,0,date('m'),date('d'),date('Y'));
			$exists = GiftAccount::db()
			->where('gift_id','=',$giftbag_id)
			->where('idfa','=',$apple_identify)
			//->where('addtime','>',$today)
			->first();
			if($exists){
				return true;
			}		
		}
		return false;
	}
	
	/**
	 * 是否是指定可领礼包用户
	 */
	public static function isGiftbagAppointUser($giftbag_id,$uid){
		if(!$giftbag_id || !$uid) return 1;
		$appointed = GiftbagAppoint::db()->where('giftbag_id',$giftbag_id)->where('uid',$uid)->first();
		return $appointed ? 1 : 0;
	}
	
    public static function getReserveUids($game_id)
	{
		return GiftReserve::db()->where('game_id','=',$game_id)->lists('uid');
	}
	
	public static function updateReserve($uids,$giftbag_id)
	{
		if($uids && is_array($uids) && count($uids)){
			return GiftReserve::db()->whereIn('uid',$uids)->update(array('gift_id'=>$giftbag_id));
		}
	}

	public static function updateReserveByGameId($game_id,$giftbag_id)
	{
		return GiftReserve::db()->where('game_id','=',$game_id)->update(array('gift_id'=>$giftbag_id));
	}
	
	public static function lockGiftbagCardNo($giftbag_id)
	{
		$queue = 'giftbag::queue::'.$giftbag_id;
		$len = self::redis()->llen($queue);		
		
	    if($len>0){
			$card = self::redis()->lpop($queue);
			return $card ? unserialize($card) : null;
		}
		return null;
		//$card = GiftbagCard::db()->where('giftbag_id','=',$giftbag_id)->where('is_get','=',0)->first();
		//return $card;
	}
	
	public static function initGiftbagCardNoQueue($giftbag_id)
	{
		$queue = 'giftbag::queue::'.$giftbag_id;
		self::redis()->del($queue);	    			
	    $table = GiftbagCard::db()->where('giftbag_id','=',$giftbag_id)->where('is_get','=',0)->get();
	    $cards = array();
	    foreach($table as $row){
	        $cards[] = serialize($row);
	    }
	    $cards && !empty($cards) && self::redis()->rpush($queue,$cards);
	    $len = self::redis()->llen($queue);
	}
	
	public static function updateGiftbagCardStatus($id,$uid,$lock=true)
	{
		$lock_uid = $lock==true ? $uid : null;
		return GiftbagCard::db()->where('id','=',$id)->where('is_get','=',0)->update(array('is_get'=>1,'gettime'=>time(),'uid'=>$uid,'lock_uid'=>$lock_uid));
	}
	
	public static function isGetGiftbagLimitByUID($limit_count,$gift_id,$uid)
	{
	    if(!$limit_count || !$uid) return false;
	    //V4礼包领取记录接口
	    $params = array('productType'=>2,'accountId'=>$uid,'platform'=>'ios','productCode'=>'ios'.$gift_id);
	    $params_ = array('productType','accountId','platform','productCode');
	    $result = Utility::preParamsOrCurlProcess($params,$params_,Config::get(self::MALL_API_ACCOUNT).'accountproduct/query');
	    $count = $result['totalCount'];
	    return $count>=$limit_count ? true : false;
	}
	
}