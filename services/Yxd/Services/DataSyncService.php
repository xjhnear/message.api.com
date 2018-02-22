<?php

namespace Yxd\Services;

use Yxd\Models\Thread;
use Illuminate\Support\Facades\DB;
use Yxd\Modules\Core\BaseService;
use Illuminate\Support\Facades\Log;

use Yxd\Modules\Message\PushService;

class DataSyncService extends BaseService
{
	/**
	 * 应用初始化
	 */
	public static function appInitRedis()
	{
		//初始化redis
		$token = array('client_id'=>'youxiduo','client_secret'=>'90909090','redirect_uri'=>'localhost','name'=>'游戏多V3');
		self::redis()->set('oauth_clients:youxiduo',json_encode($token));
		self::redis()->set('oauth_scopes:default:global','basic');
	}
	
	public static function appInitUserCache()
	{
	    //初始化用户缓存
		$uids = self::dbClubMaster()->table('account')->orderBy('uid','asc')->select('uid')->lists('uid');
		$pages = array_chunk($uids,500);
		foreach($pages as $page){
			UserService::getBatchUserInfo($page);
		}
	}
	
	public static function appInitFeed()
	{
		$tids = self::dbClubSlave()->table('forum_topic')->where('displayorder','=',0)->where('dateline','>',(time()-3600*24*3))->select('tid')->lists('tid');
		foreach($tids as $tid){
			$data['type'] = 'topic';
			$data['topic'] = Thread::getFullTopic($tid);
			CircleFeedService::makeDataFeed($data);
		}
		
		$comments = self::dbClubSlave()->table('comment')->where('target_table','=','m_games')->where('addtime','>',(time()-3600*24*3))->get();
		foreach($comments as $comment){
			$data['type'] = 'comment';
			$data['comment'] = $comment;
			CircleFeedService::makeDataFeed($data);
		}
	}
	
	public static function syncFeedUser()
	{
		$filename = 'feed_user.txt';
		$file = storage_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $filename;
		if(!file_exists($file)) return ;
		ini_set('max_execution_time',0);
		ini_set('memory_limit','2048M');
		$table = file($file,FILE_SKIP_EMPTY_LINES|FILE_IGNORE_NEW_LINES);
		if($table === false) return;
		$total = count($table);
	    $data = array();
	    $start = 0;
	    $end = microtime(true);
	    foreach($table as $index=>$key){
	    	$feeds = array();
	    	list($_,$_,$_,$uid) = explode(':',$key);
	    	$feeds = self::redis()->zrange($key,0,10000,'WITHSCORES');
	    	//print_r($feeds);
	    	//continue;
	    	if($feeds && is_array($feeds)){
	    		$userfeed = array();
	    		foreach($feeds as $_feed){
	    			$feed = @unserialize($_feed[0]);
	    			if($feed===false) continue;
	    			$onefeed = array();
	    			$onefeed['uid'] = $uid;
	    			$onefeed['feed_linktype'] = $feed['type'];
	    			$onefeed['feed_linkid'] = $feed['type']=='topic' ? $feed['topic']['tid'] : (isset($feed['comment']['id']) ? $feed['comment']['id'] : 0);
	    			$onefeed['score'] = (int)$_feed[1];
	    			$onefeed['data'] = $_feed[0];
	    			$userfeed[] = $onefeed;
	    		}
	    		if($userfeed){
				    $userfeeds = array_chunk($userfeed,10);
					foreach($userfeeds as $row){
	    			    self::dbClubMaster()->table('feed_user')->insert($row);
					}
	    			//echo $index;
	    			//print_r($userfeed);
	    		}
	    	}
	    }
	}
	
    public static function syncFeedAtme()
	{
		$filename = 'feed_atme.txt';
		$file = storage_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $filename;
		if(!file_exists($file)) return ;
		ini_set('max_execution_time',0);
		ini_set('memory_limit','2048M');
		$table = file($file,FILE_SKIP_EMPTY_LINES|FILE_IGNORE_NEW_LINES);
		if($table === false) return;
		$total = count($table);
	    $data = array();
	    $start = 0;
	    $end = microtime(true);
	    foreach($table as $index=>$key){
	    	$feeds = array();
	    	list($_,$_,$_,$uid) = explode(':',$key);
	    	$feeds = self::redis()->zrange($key,0,10000,'WITHSCORES');
	    	//print_r($feeds);
	    	//continue;
	    	if($feeds && is_array($feeds)){
	    		$userfeed = array();
	    		foreach($feeds as $_feed){
	    			$feed = @unserialize($_feed[0]);
	    			if($feed===false) continue;
	    			$onefeed = array();
	    			$onefeed['uid'] = $uid;
	    			$onefeed['feed_linktype'] = $feed['type'];
	    			$onefeed['feed_linkid'] = isset($feed['comment']['id']) ? $feed['comment']['id'] : 0;
	    			$onefeed['score'] = (int)$_feed[1];
	    			$onefeed['data'] = $_feed[0];
	    			$userfeed[] = $onefeed;
	    		}
	    		if($userfeed){
				    $userfeeds = array_chunk($userfeed,10);
					foreach($userfeeds as $row){
	    			    self::dbClubMaster()->table('feed_atme')->insert($row);
					}
	    			//echo $index;
	    			//print_r($userfeed);
	    		}
	    	}
	    }
	}
	
	public static function syncFeedAtme2($page,$size,$start,$end)
	{
		$tb = self::dbClubMaster()->table('feed_atme');
		if($start){
			$tb = $tb->where('score','>',$start);
		}
		if($end){
			$tb = $tb->where('score','<',$end);
		}
		$result = $tb->forPage($page,$size)->orderBy('id','asc')->get();
		$out = array();
		foreach($result as $row){
			$feed = @unserialize($row['data']);
			if($feed===false) continue;
			$row['from_uid'] = isset($feed['comment']) ? $feed['comment']['uid'] : 0;
			$out[] = $row;
		}
		return $out;
	}
	
	
	public static function syncUser()
	{
		//self::autoCreateUser();
		//self::syncUserCache();
		self::autoMakeZhucema();
	}
	
	public static function autoMakeZhucema()
	{
		$users = self::dbClubSlave()->table('account')->where('zhucema','=','')->select('uid')->get();
		foreach($users as $row){
			$uid = $row['uid'];
			$data = array('zhucema'=>PassportService::getZhucema());
			self::dbClubMaster()->table('account')->where('uid','=',$uid)->update($data);
		}
	}
	
	public static function autoSendMessage()
	{
		/*
		$max_uid = self::dbClubMaster()->table('account_verifycode')->max('uid');
		if(!$max_uid) $max_uid = 100000;
		
		$users = self::dbClubMaster()->table('account')->where('uid','>',$max_uid)->where('email','!=','')
		->select(array('uid','email'))
		->orderBy('uid','asc')
		->forPage(1,5000)
		->get();
		foreach($users as $row){
			$email = $row['email'];
			$verifycode = PassportService::getZhucema();
			$user = array('uid'=>$row['uid'],'email'=>$email,'verifycode'=>$verifycode,'is_valid'=>1,'is_send_msg'=>0,'update_time'=>0);
			$success = self::dbClubMaster()->table('account_verifycode')->insert($user);
		}
		*/
		$max_uid = self::dbClubMaster()->table('account_verifycode')->where('is_send_msg','=',1)->max('uid');
		if(!$max_uid) $max_uid = 100000;
		$users = self::dbClubMaster()->table('account_verifycode')->where('is_send_msg','=',0)->where('uid','>',$max_uid)->orderBy('uid','asc')->forPage(1,500)->get();
		//$users = self::dbClubMaster()->table('account_verifycode')->where('is_send_msg','=',0)->where('uid','=',100240)->get();
		foreach($users as $row){
			$email = $row['email'];
			$verifycode = $row['verifycode'];
			$uid = $row['uid'];
		    $title = '新版本登录问题自助处理方案';
			$content = '游戏多新版本登录问题自助处理方案上线，请于游戏多首页进入“公告帖子”重置您的游戏多账号密码，重置密码凭证信息：（您的邮箱：'.$email.'，验证码：'.$verifycode.'）。';
			$data = array();
			$data['type'] = 0;//0:被动(是指程序自动发的)1:主动(指后台人为发送的)
			$data['linktype'] = 0;
			$data['link'] = '';
			$data['title'] = $title;
			$data['content'] = $content;		
			$data['sendtime'] = time();
			$data['istop'] = 0;
			$data['to_uid'] = $row['uid'];
			
			if($row['uid']){
				$send = self::dbClubMaster()->table('system_message')->insert($data);
				if($send){
					self::dbClubMaster()->table('account_verifycode')->where('uid','=',$row['uid'])->update(array('is_send_msg'=>1));
					//$params = array('type'=>17,'linkid'=>'69766');
					//PushService::sendOne($row['apple_token'], '游戏多账号密码自助修改服务上线,现已允许全体玩家重置账号密码！',$params);
				}
			}
		}
	}
	
	/**
	 * 自动生成用户
	 */
	protected static function autoCreateUser()
	{
		$start = 80000;
		$end = 99999;
		//$end = 80009;
		$userdata = array();
		$creditdata = array();
		$min = mktime(0,0,0,1,1,2013);
		$max = mktime(0,0,0,6,1,2014);
		for($i=$start;$i<=$end;$i++){			
			$uid = $i;
			$user = array();
			$user['uid'] = $uid;
			$user['nickname'] = '玩家' . $uid;
			$user['email'] = $uid . '@youxiduo.com';
			$user['password'] = self::cryptPwd($uid);
			$user['avatar'] = '/userdirs/common/avatar_' . rand(1,10) . '@2x.png';
			$user['dateline'] = rand($min,$max); 
			$userdata[] = $user;
			$credit = array();
			$credit['uid'] = $uid;
			$credit['score'] = rand(0,100);
			$credit['experience'] = rand(5,200);
			$creditdata[] = $credit;
			if(count($userdata)==100 || $i==$end){
			    self::dbClubMaster()->table('account')->insert($userdata);
			    self::dbClubMaster()->table('credit_account')->insert($creditdata);
			    $userdata = array();
			    $creditdata = array();
			}
		}
	}
	
	protected static function syncUserCache()
	{
		for($uid=80000;$uid<=99999;$uid++){
			UserService::getUserInfo($uid);
		}
	}
	
	/**
	 * 同步评论
	 */
	public static function syncComment()
	{
		self::syncGameComment();//同步游戏评论
		//self::syncVideoComment();//同步视频评论
		//self::syncArticleComment();//修正文章评论数
		self::syncCommentOfGameStorey();//修正游戏评论楼层数
		//self::syncCommentOfVideoStorey();//修正视频评论楼层数
		//self::syncGiftbag();//同步礼包
		//self::syncCardNo();
		
	}
	
	/**
	 * 同步礼包
	 */
	public static function syncGiftbag()
	{
		//self::syncTmpGiftbagData();
		self::syncGiftbagData();
		//self::syncCardNo();
	}
	
	/**
	 * 同步游戏下载统计
	 */
	public static function syncGameDownload()
	{
		$keys = self::redis()->keys('game::game_download_*_times');
		$data = array();
		foreach($keys as $key){
			$sec = explode('_',$key);
			if(isset($sec[2]) && isset($sec[2])){
				$game_id = $sec[2];
				$uid = $sec[3];
				$data[] = array('game_id'=>$game_id,'uid'=>$uid,'times'=>1,'lastupdatetime'=>time());
			}else{
				continue;
			}
		}
		if($data && count($data)>0){
			self::dbClubMaster()->table('game_download_count')->insert($data);
		}
	}
	
	/**
	 * 同步游戏广告下载统计
	 */
	public static function syncGameAdvDownload()
	{
	    $keys = self::redis()->keys('game::game_ad_download_*_times');
		$data = array();
		foreach($keys as $key){
			$sec = explode('_',$key);
			if(isset($sec[3]) && isset($sec[4])){
				$adv_id = $sec[3];
				$uid = $sec[4];
				$data[] = array('adv_id'=>$adv_id,'uid'=>$uid,'times'=>1,'lastupdatetime'=>time());
			}else{
				continue;
			}
		}
		if($data && count($data)>0){
			self::dbClubMaster()->table('game_download_adv_count')->insert($data);
		}
	}
	
	protected static function syncGameComment()
	{
		
		//$total = 254973;
		//第一次275506
		//第二次286619	
		$startid = 275506;
		$endid = 286619;
		$baseid = 1200000;
		$total = self::dbCmsSlave()->table('comment')->where('ptype','=',0)->where('gid','>',0)->where('vid','=',0)->where('id','>',$startid)->where('id','<',$endid)->count();	
		$page = 1;
		$size = 500;
		$pages = ceil($total/$size);
		
		for($page=1;$page<=$pages;$page++){
			$table = self::dbCmsSlave()->table('comment')->where('ptype','=',0)->where('gid','>',0)->where('vid','=',0)->where('id','>',$startid)->where('id','<',$endid)->forPage($page,$size)->orderBy('id','asc')->get();
			$data = array();
			foreach($table as $row){
				$cmt = array();
				$cmt['id'] = $row['id']+$baseid;
				$cmt['pid'] = $row['pcid'] ? $row['pcid']+$baseid : 0;
				$cmt['target_table'] = 'm_games';
				$cmt['target_id'] = $row['gid'];
				$cmt['format_content'] = self::decode_comment($row['content']);
				$cmt['content'] = json_encode(array(array('img' =>'','text' => self::decode_comment($row['content']))));
				$cmt['addtime'] = $row['addtime'];
				$cmt['uid'] = rand(80000,99999);
				$cmt['storey'] = 1;
				$cmt['best'] = 0;
				$cmt['is_admin'] = 1;
				$data[] = $cmt;
			}
			if($data){
				self::dbClubMaster()->table('comment')->insert($data);
			}
		}			
	}	
	
	protected static function syncVideoComment()
	{
	    $total = self::dbCmsSlave()->table('comment')->where('ptype','=',0)->where('gid','=',0)->where('vid','>',0)->count();	
		$page = 1;
		$size = 500;
		$pages = ceil($total/$size);
		for($page=1;$page<=$pages;$page++){
			$table = self::dbCmsSlave()->table('comment')->where('ptype','=',0)->where('gid','=',0)->where('vid','>',0)->forPage($page,$size)->orderBy('id','asc')->get();
			$data = array();
			foreach($table as $row){
				$cmt = array();
				$cmt['id'] = $row['id']+1000000;
				$cmt['pid'] = $row['pcid'] ? $row['pcid']+1000000 : 0;
				$cmt['target_table'] = 'm_videos';
				$cmt['target_id'] = $row['vid'];
				$cmt['format_content'] = self::decode_comment($row['content']);
				$cmt['content'] = json_encode(array(array('img' =>'','text' => self::decode_comment($row['content']))));
				$cmt['addtime'] = $row['addtime'];
				$cmt['uid'] = rand(80000,99999);
				$cmt['storey'] = 1;
				$cmt['best'] = 0;
				$cmt['is_admin'] = 1;
				$data[] = $cmt;
			}
			if($data){
				self::dbClubMaster()->table('comment')->insert($data);
			}
		}
	    //修正评论数
		$groups = self::dbClubSlave()->table('comment')->where('target_table','=','m_videos')->groupBy('target_id')->select(DB::raw('target_id as vid,count(*) as total'))->get();
		foreach($groups as $row){
			self::dbCmsMaster()->table('videos')->where('id','=',$row['vid'])->update(array('commenttimes'=>$row['total']));
		}
	}
	
	protected static function syncArticleComment()
	{
	    //修正评论数
	    /*
	    //新闻
		$news = self::dbClubSlave()->table('comment')
		    ->where('target_table','=','m_news')
		    ->groupBy('target_id')
		    ->select(DB::raw('target_id as id,count(*) as total'))
		    ->get();
		foreach($news as $row){
			self::dbCmsMaster()->table('news')->where('id','=',$row['id'])->update(array('commenttimes'=>$row['total']));
		}
		//攻略
	    $guide = self::dbClubSlave()->table('comment')
		    ->where('target_table','=','m_gonglue')
		    ->groupBy('target_id')
		    ->select(DB::raw('target_id as id,count(*) as total'))
		    ->get();
		foreach($guide as $row){
			self::dbCmsMaster()->table('gonglue')->where('id','=',$row['id'])->update(array('commenttimes'=>$row['total']));
		}
		//评测
	    $opinion = self::dbClubSlave()->table('comment')
		    ->where('target_table','=','m_feedback')
		    ->groupBy('target_id')
		    ->select(DB::raw('target_id as id,count(*) as total'))
		    ->get();
		foreach($news as $row){
			self::dbCmsMaster()->table('feedback')->where('id','=',$row['id'])->update(array('commenttimes'=>$row['total']));
		}
		//新游
	    $news = self::dbClubSlave()->table('comment')
		    ->where('target_table','=','m_game_notice')
		    ->groupBy('target_id')
		    ->select(DB::raw('target_id as id,count(*) as total'))
		    ->get();
		foreach($news as $row){
			self::dbCmsMaster()->table('game_notice')->where('id','=',$row['id'])->update(array('commenttimes'=>$row['total']));
		}
		*/
		//游戏
		$games = self::dbClubSlave()->table('comment')->where('target_table','=','m_games')->groupBy('target_id')->select(DB::raw('target_id as gid,count(*) as total'))->get();
		foreach($games as $row){
			self::dbCmsMaster()->table('games')->where('id','=',$row['gid'])->update(array('commenttimes'=>$row['total']));
		}
		
	}
	
	protected static function syncCommentOfGameStorey()
	{			
		//$total = self::dbCmsSlave()->table('games')->where(function($query){$query = $query->where('type','=',1)->orWhere('type','=',3);})->where('commenttimes','>',0)->count();
		//$page = 1;
		//$size = 1000;
		//$pages = ceil($total/$size);
		//for($page=1;$page<=$pages;$page++){
		    /*	
		    $games = self::dbCmsSlave()->table('games')
		        ->where('commenttimes','>',0)
		        ->where(function($query){
		        	$query = $query->where('type','=',1)->orWhere('type','=',3);
		        })
		        ->select('id')
		        ->get();
		        */
		    $games = self::dbClubSlave()->table('comment')->where('target_table','=','m_games')->distinct()->select('target_id')->get();
		    foreach($games as $row){
		    	$gid = $row['target_id'];
		    	$comments = self::dbClubSlave()->table('comment')->where('target_table','=','m_games')->where('target_id','=',$gid)->orderBy('addtime','asc')->select('id')->get();
		    	$ids = array();
		    	foreach($comments as $index=>$row){
		    		$id = $row['id'];
		    		$data = array('storey'=>$index+1);
		    		self::dbClubMaster()->table('comment')->where('id','=',$id)->update($data);
		    		$ids[] = $id;
		    	}
		    	$ids && self::redis()->sadd('sync::'.$gid,$ids);
		    }
		//}
		
	}
	
    protected static function syncCommentOfVideoStorey()
	{	
		$total = self::dbCmsSlave()->table('videos')->where('commenttimes','>',0)->count();
		$page = 1;
		$size = 1000;
		$pages = ceil($total/$size);
		for($page=1;$page<=$pages;$page++){	
		    $games = self::dbCmsSlave()->table('videos')->where('commenttimes','>',0)->select('id')->forPage($page,$size)->lists('id');
		    foreach($games as $gid){
		    	$comments = self::dbClubSlave()->table('comment')->where('target_table','=','m_videos')->where('target_id','=',$gid)->orderBy('addtime','asc')->select('id')->get();
		    	foreach($comments as $index=>$row){
		    		$id = $row['id'];
		    		$data = array('storey'=>$index+1);
		    		self::dbClubMaster()->table('comment')->where('id','=',$id)->update($data);
		    	}
		    }
		}
		
	}
	
    protected static function cryptPwd($password)
	{
		$salt = md5(substr($password,-1));
		$password = md5($password . $salt);
		return $password;
	}
	
	protected static function decode_comment($str)
	{
		if(empty($str)) return '';
		$base64 = base64_decode($str);
		if($base64===false) return $str;
		$base64 = mb_convert_encoding($base64,"UTF-8",'UTF-16');
		
		return stripslashes($base64);
	}
	
	protected static function syncTmpGiftbagData()
	{
		$www_gifts = self::dbCmsSlave()->table('gift')
		->where('gid','>',0)
		->where('addtime','>',mktime(0,0,0,'7','1','2014'))
		->where('addtime','<',mktime(0,0,0,'8','1','2014'))
		->where('last_num','=',0)
		->orderBy('id','asc')
		->forPage(1,50)->get();
		$data = array();
		foreach($www_gifts as $row){
			$club_gift = array();
			$club_gift['id'] = $row['id'];
			$club_gift['game_id'] = $row['gid'];
			$club_gift['is_ios'] = 1;
			$club_gift['is_android'] = 0;
			$club_gift['title'] = $row['title'];
			$club_gift['content'] = $row['content'];
			$club_gift['editor'] = 0;
			$club_gift['starttime'] = $row['starttime'];
			$club_gift['endtime'] = $row['endtime'];
			$club_gift['ctime'] = $row['addtime'];
			$club_gift['total_num'] = $row['total_num'];
			$club_gift['last_num'] = $row['last_num'];
			$club_gift['condition'] = json_encode(array('score'=>0));
			$club_gift['is_show'] = $row['isshow'];
			$club_gift['is_hot'] = $row['istop'];
			$club_gift['is_top'] = $row['ishot'];
			$club_gift['is_activity'] = 0;
			$club_gift['sort'] = $row['sort'];
			
			$data[] = $club_gift;
		}
		
		self::dbClubMaster()->transaction(function()use($data){
		    return DataSyncService::dbClubMaster()->table('giftbag')->insert($data);
		});
	}
	
	/**
	 * 同步礼包数据
	 */
    protected static function syncGiftbagData()
	{
		$total = self::dbCmsSlave()->table('gift')->where('agid','>',0)->count();
		//DB::connection('android')->table('giftbag','android')->delete();
		$size = 500;		
		$totalpage = ceil($total/$size);
		for($page = 1;$page <= $totalpage;$page++){
			$www_gifts = self::dbCmsSlave()->table('gift')->where('agid','>',0)->orderBy('id','asc')->forPage($page,$size)->get();
			$data = array();
			foreach($www_gifts as $row){
				$club_gift = array();
				$club_gift['id'] = $row['id'];
				$club_gift['game_id'] = $row['agid'];
				$club_gift['is_ios'] = 0;
				$club_gift['is_android'] = 1;
				$club_gift['title'] = $row['title'];
				$club_gift['content'] = $row['content'];
				$club_gift['editor'] = 0;
				$club_gift['starttime'] = $row['starttime'];
				$club_gift['endtime'] = $row['endtime'];
				$club_gift['ctime'] = $row['addtime'];
				$club_gift['total_num'] = $row['total_num'];
				$club_gift['last_num'] = $row['last_num'];
				$club_gift['condition'] = json_encode(array('score'=>0));
				$club_gift['is_show'] = $row['isshow'];
				$club_gift['is_hot'] = $row['istop'];
				$club_gift['is_top'] = $row['ishot'];
				$club_gift['is_activity'] = 0;
				$club_gift['sort'] = $row['sort'];
				
				$data[] = $club_gift;
			}
			return DB::connection('android')->table('giftbag')->insert($data);
			
		}
		
	}
	
	/**
	 * 同步礼包卡数据
	 */
	protected static function syncCardNo()
	{
		ini_set('max_execution_time',0);
		ini_set('memory_limit','512M');
		$total = \Illuminate\Support\Facades\DB::connection('sqlite')->table('gift_card')->where('uid','=',0)->count();
		$size = 1000;
		//DB::connection('android')->table('giftbag_card')->delete();	
		$totalpage = ceil($total/$size);
		for($page=1; $page<=$totalpage; $page++){
			$items = \Illuminate\Support\Facades\DB::connection('sqlite')->table('gift_card')->where('uid','=',0)->forPage($page,$size)->get();
			$data = array();
			foreach($items as $item){
				$card = array();
				$card['giftbag_id'] = $item['gfid'];
				$card['cardno'] = $item['number'];
				$card['is_get'] = 0;
				$card['gettime'] = 0;
				$data[] = $card;
		    }
		    if($data){
		    	DB::connection('android')->table('giftbag_card')->insert($data);
		    }
		}
	}
	
	public static function outEveryDayGameDown()
	{
		ini_set('memory_limit','2048M');
		$days = date('z');
		for($i=0;$i<$days;$i++){
			$day = strtotime('2014-01-01') + 3600*24*$i;
			$sql = "select a.id,a.gname as gname,b.downcount from (select gid,ceiling(sum(number)*5113/1000*3) as downcount from m_game_download_count where down_time=".$day." group by gid order by downcount desc) as b left join m_games as a on b.gid=a.id";
			$result = DB::connection('cms')->select($sql);
			self::outExcel($result, $day);
		}
	}
	
	protected static function outHtml($result,$day)
	{
		$html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>游戏下载统计</title>';
		$html .='<link href="base.css" rel="stylesheet">';
		$html .='<head><body><table><tr><th>游戏ID</th><th>游戏名称</th><th>下载数</th></tr>';
		foreach($result as $row){
			$html .= '<tr><td>'.$row['id'].'</td>'.'<td>'.$row['gname'].'</td>'.'<td>'.$row['downcount'].'</td></tr>';
		}
		$html .= '</table></body></html>';
		file_put_contents(storage_path() . '/meta/' . date('Y-m-d',$day).'.html',$html);
	}
	
	protected static function outExcel($result,$day)
	{
		require_once base_path() . '/libraries/PHPExcel.php';
		$excel = new \PHPExcel();
		$excel->setActiveSheetIndex(0);
		$excel->getActiveSheet()->setTitle(date('Y-m-d',$day));
		$excel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
		$excel->getActiveSheet()->setCellValue('A1','游戏ID');
		$excel->getActiveSheet()->setCellValue('B1','游戏名');
		$excel->getActiveSheet()->setCellValue('C1','下载数');
		foreach($result as $index=>$row){
			$excel->getActiveSheet()->setCellValue('A'.($index+2),$row['id']);
			$excel->getActiveSheet()->setCellValue('B'.($index+2),$row['gname']);
			$excel->getActiveSheet()->setCellValue('C'.($index+2),$row['downcount']);
		}
		$writer = \PHPExcel_IOFactory::createWriter($excel,'Excel2007');
		
		$writer->save(storage_path() . '/meta/' . date('Y-m-d',$day).'.xlsx');
		
	}
	
	/**
	 * 每日同步游戏库和AppStore的数据
	 */
	public static function syncGame($cmd)
	{
		switch($cmd){
			case 'crawl-newgame':
				self::crawlNewGameFromAppstore();
				break;
			case 'crawl-gameprice':
				self::crawlGamePriceFromAppstore();
				break;	
			case 'sync-newgame':
				self::syncNewGame();
				break;
			case 'sync-gameprice':
				break;
			default:
				break;			
		}
		//同步架构
		//
		//同步新游戏
		//
	}
	
	/**
	 * 每周清除一次游戏周下载量
	 */
	public static function clearWeekDown()
	{
		self::dbCmsMaster()->table('games')->update(array('weekdown'=>0));
	}
	
	/**
	 * 
	 */
	public static function changGameScoreStatus($open)
	{
		if($open==true){
			self::dbCmsMaster()->table('version')->update(array('scorestate'=>1));
		}else{
			self::dbCmsMaster()->table('version')->update(array('scorestate'=>2));
		}
	}
	
	/**
	 * 从Appstore抓取最新游戏
	 */
	protected static function crawlNewGameFromAppstore()
	{				
		$appstore_rss = 'https://itunes.apple.com/cn/rss/newapplications/limit=200/genre=6014/json';
		$appstore_url	=	'https://itunes.apple.com/lookup?';
		$data_json = self::http_curl($appstore_rss);
		$gameinfo = json_decode($data_json,true);
		$itunesids = array();
		$itunesids_exist = self::dbCmsSlave()->table('games')->where('itunesid','>',0)->select('id','itunesid')->lists('itunesid');
		$itunesids_exist = array_unique($itunesids_exist);
		for($i=0;$i<300;$i++){
			if(!isset($gameinfo['feed']['entry'][$i])) continue;
			$obj = $gameinfo['feed']['entry'][$i];
			if($obj){
				$type = $obj['category']['attributes']['label'];				
				if($type == '游戏'){
				    preg_match("/\/id\d+/",$obj['id']['label'],$id_str);
					preg_match("/\d+/", $id_str[0],$id);
					if (!in_array($id[0],$itunesids_exist))
					{
						$itunesids[]	=	$id[0];	
					}
				}
			}
		}
		
		if($itunesids && is_array($itunesids)){
			$itunesids_str = implode(',',$itunesids);
			$url = $appstore_url . 'id='.$itunesids_str.'&country=cn&entity=software';
			$itunesinfo = self::resolveItunesinfo($url);
			
			if($itunesinfo){
				foreach($itunesinfo as $k=>$v){
				    $data['itunesid']	=	$k;
					$data['version']	=	$v['version'];
					$data['size']		=	$v['fileSizeBytes'];
					$data['price']		=	$v['price'];
					$data['oldprice']	=	$v['price'];
					$data['ico']		=	$v['ico'];
					$data['score']		=	$v['score'];
					$data['downurl']	=	$v['downurl'];
					$data['platform']	=	$v['platform'];
					$data['description']=	$v['description'];
					$data['editorcomt']	=	$v['editorcomt'];
					$data['company']	=	$v['company'];
					$data['gname']		=	$v['gname'];
					$data['language']	=	$v['language'];
					$data['addtime']	=	time();
					$data['isdel']		=	3;
					$data['zonetype']	=	3;
					if ($data['price']>0)
					{
						$data['pricetype']	=	3;
					}
					if ($data['price']==0)
					{
						$data['pricetype']	=	1;
					}
					$value = serialize(array('v'=>$v,'data'=>$data));
					self::redis()->rpush('queue::crawl::newgame',$value);
					
				}
			}
		}		
	}
	
	/**
	 * 同步新游戏
	 */
	protected static function syncNewGame()
	{
		$queue_name = 'queue::crawl::newgame';	
		$data = self::queue()->lpop($queue_name);
		while($data){
			$value = unserialize($data);
			$game = $value['data'];
			$v = $value['v'];
			Log::info(json_encode($v));
            $gid = self::dbCmsMaster()->table('games')->insertGetId($game);
			if($gid)
			{
				foreach ($v['pic'] as $row)
				{
					self::dbCmsMaster()->table('games_litpic')->insert(array('gid'=>$gid,'litpic'=>$row));
				}
			}
			$data = self::queue()->lpop($queue_name);
		}
	}
	
	protected static function resolveItunesinfo($url)
	{
		$opts = array('http'=>array('method'=>'GET','timeout'=>360));
		$context = stream_context_create($opts);
		$content = self::http_curl($url);			
		$obj = json_decode($content);
		$itunesid_num	=	$obj->resultCount;
		
		$data_dir		=	date('Ym');
		$store = storage_path();		
		$ico_savePath 	= 	$store . '/u/gameico/'.$data_dir.'/';
		$savePath 		=	$store . '/u/gamepic/'.$data_dir.'/';
		$file = storage_path() . '/logs/' . 'sync-apache2handler-' . date('Y-m-d') . '.txt';
		
		file_put_contents($file,$savePath,FILE_APPEND);
		
        if ( !file_exists( $ico_savePath ) ) {
             mkdir($ico_savePath,0777); 
        }
		
		if ( !file_exists( $savePath ) ) {
            mkdir($savePath , 0777);
        }

		if ($itunesid_num>0)
		{
			for ($j=0;$j<$itunesid_num;$j++)
			{
				$curobj				=	$obj->results[$j];
				$itunesid			=	$curobj->trackId;
				$crawlitunesids[]	=	$itunesid;
				$ico_url			=	substr($curobj->artworkUrl512,0,-4).".175x175-75.png";
				
				$dt = date('Ym');
				$ico_path			=	$ico_savePath.$dt.$itunesid.date('YmdHis').rand(1,10000).".jpg";
				self::curlDownload($ico_url,$ico_path);
				$language	=	$curobj->languageCodesISO2A;
				if (in_array("ZH", $language))
				{
					$itunesinfo[$itunesid]['language']	=	1;
				}elseif(in_array("EN", $language)){
					$itunesinfo[$itunesid]['language']	=	2;
				}else{
					$itunesinfo[$itunesid]['language']	=	3;
				}
				
				if (isset($curobj->averageUserRatingForCurrentVersion))
				{
					$score	=	$curobj->averageUserRatingForCurrentVersion;
				}else{
					$score	=   0;
				}
				$itunesinfo[$itunesid]['gname']			=	$curobj->trackName;
				$itunesinfo[$itunesid]['ico']			=	"/".$ico_path;
				$itunesinfo[$itunesid]['version']		=	$curobj->version;
				$itunesinfo[$itunesid]['fileSizeBytes']	=	self::formatSize($curobj->fileSizeBytes);
				$itunesinfo[$itunesid]['price']			=	$curobj->price;
				$itunesinfo[$itunesid]['score']			=	$score;
				$itunesinfo[$itunesid]['downurl']		=	$curobj->trackViewUrl;
				$itunesinfo[$itunesid]['itunesid']		=	$curobj->trackId;
				if ($curobj->supportedDevices[0] =="all")
				{
					$itunesinfo[$itunesid]['platform']		=	"支持所有平台";
				}else{
					$itunesinfo[$itunesid]['platform']		=	implode("、",$curobj->supportedDevices);
				}
				
				$itunesinfo[$itunesid]['description']	=	$curobj->description;
				$itunesinfo[$itunesid]['editorcomt']	=	$curobj->description;
				$itunesinfo[$itunesid]['company']		=	$curobj->artistName;
				$itunesinfo[$itunesid]['tag']			=	$curobj->genres;
				
				$litpic		=	$curobj->screenshotUrls;
				foreach ($litpic as $row)
				{
					$row	 =	substr($row,0,(strripos($row,'/') + 1));					
					$row 	.=	"screen568x568.jpeg";
					
					$pic_path 	=  $savePath.$dt.$itunesid.date('YmdHis').rand(1,10000).".jpeg";
					self::curlDownload($row,$pic_path);
					$itunesinfo[$itunesid]['pic'][]		=	"/".$pic_path;		
				}
			}
		}
		else
		{
			$itunesinfo	= false;
		}
		
		return $itunesinfo;
	}
	
	protected static function formatSize($byte)
	{
		$units = array('1000000000'=>'G','1000000'=>'M','1000'=>'K');
		foreach($units as $val=>$unit){
			if($byte>$val){
				$byte = $byte/$val;
				$str = sprintf("%.1f",$byte) . $unit;
				return $str;
			}
		}
	}
	
	protected static function curlDownload($remote,$local)
	{
		$ci = curl_init($remote);
		$fp = fopen($local,'w');
		curl_setopt($ci,CURLOPT_FILE,$fp);
		curl_setopt($ci,CURLOPT_HEADER,0);
		curl_setopt($ci,CURLOPT_TIMEOUT,30);
		curl_exec($ci);
		curl_close($ci);
		fclose($fp);
	}
	
	/**
	 * 从Appstore同步游戏价格
	 */
	protected static function crawlGamePriceFromAppstore()
	{
		$appstore = 'https://itunes.apple.com/lookup?';
		$total = self::dbCmsSlave()->table('games')->where('isdel','=',0)->where('itunesid','!=','0')->count();
		$games = self::dbCmsSlave()->table('games')->where('isdel','=',0)->where('itunesid','!=','0')->select('id','itunesid','price','oldprice','downurl')->orderBy('id','desc')->get();
		self::dbCmsMaster()->table('games')->where('isdel','=',0)->update(array('updatestate'=>2));
		$itunesids = array();
		foreach($games as $row){
			if(strpos($row['downurl'],'itunes.apple.com')===false) {
				$total--;
				continue;
			}
			$itunesids[$row['id']] = $row['itunesid'];
			$gameinfo[$row['itunesid']]['price']	=	$row['price'];
			$gameinfo[$row['itunesid']]['oldprice']	=	$row['oldprice'];	
		}
		$limit = 50;
		for($i=0;$i<$total;$i+=$limit){
			$crawlitunesids	=	array();
			$slice_itunesids = array_slice($itunesids,$i,$limit);
			$itunesids_str = implode(',',$slice_itunesids);
			$url = $appstore . 'id=' . $itunesids_str . '&country=cn&entity=software';
			$opts = array('http'=>array('method'=>'GET','timeout'=>360));
			$context = stream_context_create($opts);
			$content = self::http_curl($url);			
			$obj = json_decode($content,true);
			$itunesid_num = (int)$obj['resultCount'];
			if($itunesid_num>0){
				for($j=0;$j<$itunesid_num;$j++){
					$curobj = $obj['results'][$j];
					$itunesid = $curobj['trackId'];
					$crawlitunesids[]	=	$itunesid;
					$itunesinfo[$itunesid]['version']	=	$curobj['version'];
					$itunesinfo[$itunesid]['size']		=	self::formatSize($curobj['fileSizeBytes']);
					$itunesinfo[$itunesid]['price']		=	$curobj['price'];
				}
			}
			$log = $url . "\r\n";		
			$file = storage_path() . '/logs/' . 'sync-apache2handler-' . date('Y-m-d') . '.txt';
			file_put_contents($file,$log,FILE_APPEND);
		    if ($itunesinfo)
			{
				$count			=	count($crawlitunesids);
				for ($j=0;$j<$count;$j++)
				{
					$data		=	array();
					$itunesid	=	$crawlitunesids[$j];
					$oldprice	=	$gameinfo[$itunesid]['price'];
					$appprice	=	$gameinfo[$itunesid]['oldprice'];
					$newprice	=	$itunesinfo[$itunesid]['price'];
					
					if ($newprice>0)
					{
						$data['pricetype'] =3;
					}
					elseif($newprice==0 && $appprice==0)
					{
						$data['pricetype'] =1;
					}
					elseif($newprice==0 && $appprice>0)
					{
						$data['pricetype'] =2;
					}
					
					if($newprice>$appprice)
					{
						$data['oldprice']	=	$newprice;
					}
					$data['version']	=	$itunesinfo[$itunesid]['version'];
					//$data['size']		=	$itunesinfo[$itunesid]['size'];
					$data['price']		=	$newprice;
					$data['updatestate']=	1;
					//self::redis()->rpush('queue::crawl::gameprice',serialize(array('itunesid'=>$itunesid,'data'=>$data)));
					self::dbCmsMaster()->table('games')->where('itunesid','=',$itunesid)->update($data);
					file_put_contents($file,$itunesid."\r\n",FILE_APPEND);
				}
			}
			
		}
		
	}
	
	protected static function http_curl($url)
	{
		$ch			=	curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,10);
		curl_setopt($ch, CURLOPT_TIMEOUT,10);
		$data		=	curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}