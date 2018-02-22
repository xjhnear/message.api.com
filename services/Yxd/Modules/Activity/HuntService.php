<?php

namespace Yxd\Modules\Activity;

use Yxd\Services\Cms\GameService;

use Yxd\Services\CreditService;

use Yxd\Modules\Message\NoticeService;

use Yxd\Modules\Core\BaseService;

use Yxd\Services\Models\ActivityHunt;
use Yxd\Services\Models\ActivityHuntAccount;
use Yxd\Services\Models\HuntTopicClickTimes;
use Yxd\Services\Models\ActivityPrize;
use Yxd\Services\Models\Giftbag;

/**
 * 寻宝箱活动服务类
 */
class HuntService extends BaseService
{
	/**
	 * 检查是否中奖
	 */
	public static function checkIsWinPrize($uid,$game_id,$tid)
	{
		//$start = mktime(0,0,0,date('m'),date('d'),date('Y'));
		//$end = mktime(23,59,59,date('m'),date('d'),date('Y'));
		$time = time();
		//
		if(!$uid || !$game_id || !$tid) return false;	
			
		//检查活动是否存在或是否在进行中
		$hunt = ActivityHunt::db()
		->where('game_id','=',$game_id)
		->where('startdate','<=',$time)
		->where('enddate','>=',$time)
		->first();
		if(!$hunt) {
			return false;//活动不存在
		}
		$expire = (int)$hunt['enddate'] - $time;
		//检查是否已经参加过活动
		$myhunt = ActivityHuntAccount::db()
		->where('uid','=',$uid)
		->where('hunt_id','=',$hunt['id'])
		->first();
		if($myhunt){
			return false;//已经参加过活动
		}	
			
		//检测帖子点开次数
		$limit_times = (int)$hunt['clicktimes'];
		//$clicktimes_key = 'hunt::topic_' . $hunt['id'] . '_' . $uid.'_clicktimes';
		//self::redis()->incr($clicktimes_key);
		//self::redis()->expire($clicktimes_key,$expire);
		//$current_times = (int)self::redis()->get($clicktimes_key);
		HuntTopicClickTimes::db()->insert(array('hunt_id'=>$hunt['id'],'uid'=>$uid,'ctime'=>time(),'game_id'=>$game_id,'tid'=>$tid));
		$current_times = HuntTopicClickTimes::db()->where('hunt_id','=',$hunt['id'])->where('uid','=',$uid)->count();
		
		if($current_times >= $limit_times){
			//中奖计算
			
			$first_reward_num = ActivityHuntAccount::db()->where('hunt_id','=',$hunt['id'])->where('reward_no','=',1)->count();
			$second_reward_num = ActivityHuntAccount::db()->where('hunt_id','=',$hunt['id'])->where('reward_no','=',2)->count();
			$third_reward_num = ActivityHuntAccount::db()->where('hunt_id','=',$hunt['id'])->where('reward_no','=',3)->count();
			$first_reward = json_decode($hunt['first_prize'],true);
			$second_reward = json_decode($hunt['second_prize'],true);
			$third_reward = json_decode($hunt['third_prize'],true);
			//预置中奖概率
			$first_win_pro  = $first_reward['probability'];
			$second_win_pro = $second_reward['probability'];
			$third_win_pro  = $third_reward['probability'];
			//如果奖品已领完则重置中奖概率为零
			if($first_reward_num >= (int)$first_reward['num']){
				$first_win_pro = 0;
			}
			//如果奖品已领完则重置中奖概率为零
		    if($second_reward_num >= (int)$second_reward['num']){
				$second_win_pro = 0;
			}
			//如果奖品已领完则重置中奖概率为零
		    if($third_reward_num >= (int)$third_reward['num']){
				$third_win_pro = 0;
			}
			$no_win_pro = 10000 - ($first_win_pro + $second_win_pro + $third_win_pro)*100; 
			$prizeArray = array(
			    '0'=>$no_win_pro,
			    '1'=>$first_win_pro * 100,
			    '2'=>$second_win_pro * 100,
			    '3'=>$third_win_pro * 100
			);					
			
			$reward_no = (int)self::getRand($prizeArray);
			$reward_score = 0;
			$reward_cardno = '';
			$reward_expense = '';
			$link = 0;
			$gamename = '';
			$name = '';
			
			if($reward_no){
				if($reward_no === 1){
					$prize_id = $first_reward['prize_id'];
				}elseif($reward_no === 2){
					$prize_id = $second_reward['prize_id'];
				}elseif($reward_no === 3){
					$prize_id = $third_reward['prize_id'];
				}
				//
				$prize = ActivityPrize::db()->where('id','=',$prize_id)->first();
				
				if((int)$prize['type'] == 1){//游币
					$reward_score = $prize['score'];
					//增加用户游币
					$info = '参加寻宝箱活动赢得游币' . $reward_score . '个';
					CreditService::handOpUserCredit($uid,$reward_score,0, 'activity_hunt',$info);
				}elseif((int)$prize['type'] == 2){//礼包
					//$card = self::dbClubSlave()->table('giftbag_card')->where('giftbag_id','=',$prize['gift_id'])->where('is_get','=',0)->first();
					$card = GiftbagService::lockGiftbagCardNo($prize['gift_id']);
					if($card){
						GiftbagService::updateGiftbagCardStatus($card['id']);
						$giftbag = Giftbag::db()->where('id','=',$prize['gift_id'])->first();
						$reward_cardno = $card['cardno'];						
						$link = $prize['gift_id'];
						$name = $giftbag['title'];
						$game = GameService::getGameInfo($game_id);
						if($game){
							$gamename = $game['shortgname'];
						}
						//添加到我的礼包
					}else{
						$reward_no=0;
					}
					
				}elseif((int)$prize['type'] == 3){//实物
					$reward_expense = $prize['expense'];
					$name = $prize['name'] . '。' . $reward_expense;
				}
				
			}
			
			$data = array(
			    'hunt_id'=>$hunt['id'],
			    'uid'=>$uid,
			    'reward_no'=>$reward_no,
			    'reward_score'=>$reward_score,
			    'reward_cardno'=>$reward_cardno,
			    'reward_expense'=>$reward_expense,
			    'addtime'=>time()
			);
			ActivityHuntAccount::db()->insertGetId($data);
			if($reward_no > 0){
				//发送中奖通知
				$params = array('reward_no'=>$reward_no,'prize_name'=>$prize['name'],'reward_score'=>$reward_score,'reward_cardno'=>$reward_cardno,'reward_expense'=>$reward_expense);
				NoticeService::sendHuntAward($uid, $hunt['id'],$prize['type'], $params);
				//返回中奖信息
				$win = array(
				    'type'=>($prize['type']-1),//0:游币1:礼包2:实物
				    'tid'=>$link,
				    'coin'=>$reward_score,
				    'name'=>$name,
				    'gamename'=>$gamename,
				    'number'=>$reward_cardno
				);
				return $win;
			}else{
				return 0;
			}
			
			return false;
		}
		return false;
	}
	
	/**
	 * 中奖概率计算
	 */
	protected static function getRand($prizeArray)
	{
		$result = '0';
		$prizeSum = array_sum($prizeArray);
		foreach($prizeArray as $key=>$val){
			$randNum = mt_rand(1, $prizeSum); 
			if ($randNum <=$val){
				$result = $key; 
				break;
			}else{
				$prizeSum -= $val;
			}
		}
		unset($prizeArray);
		return $result;
	}
}