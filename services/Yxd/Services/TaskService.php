<?php
namespace Yxd\Services;

use Yxd\Modules\Core\CacheService;

use Yxd\Modules\System\SettingService;

use Yxd\Services\RelationService;
use Yxd\Services\UserService;
use Illuminate\Support\Facades\Input;
use Yxd\Services\Service;
use Yxd\Services\Cms\GameService;
use Yxd\Models\Cms\Game;
use Yxd\Modules\Core\BaseService;
use Yxd\Services\Models\Task;
use Yxd\Services\Models\TaskAccount;
use Yxd\Services\Models\Account;
use Yxd\Services\Models\CheckInfo;
use Yxd\Services\Models\CheckInfoLimit;
use Yxd\Services\Models\ShareLimit;

class TaskService extends BaseService
{	
	const TASK_EXEC_TYPE_EVERYDAY = 1;
	const TASK_EXEC_TYPE_ONCE = 2;
	const TASK_EXEC_TYPE_LIMIT = 3;
	/**
	 * 获取任务列表
	 */
	public static function getList($type,$uid,$version='3.0.0')
	{		
		$tasks = Task::db()->where('type','=',$type)->orderBy('id','asc')->get();		
		$user_task_ids = self::canExecTaskList($uid,$version);
		$tasklist = array();
		foreach($tasks as $key=>$row){
			if(in_array($row['id'],$user_task_ids)){
				$row['iscomplete'] = 0;
			}else{
				
				$row['iscomplete'] = 1;
			}
			$reward = json_decode($row['reward'],true);
			$error = json_last_error();
			if($error == JSON_ERROR_NONE){
				$row['reward'] = $reward;
			}
			$condition = json_decode($row['condition'],true);
			$row['condition'] = $condition;
			if(isset($condition['closed']) && intval($condition['closed'])==1){
				continue;
			}
			$tasklist[] = $row;
		}
		return $tasklist;
	}
	/**
	 * 可执行的任务数
	 */
	public static function getCanExecTaskCount($uid,$version='3.0.0')
	{		
		$process_task_ids = self::canExecTaskList($uid,$version);
		return count($process_task_ids);
	}
	
	/**
	 * 可以执行的任务
	 */
	public static function canExecTaskList($uid,$version='3.0.0')
	{
		/*
		$cachekey_table_task = 'table::task::' . $uid;
		if(CLOSE_CACHE!==false && CacheService::has($cachekey_table_task)){
			$tasks = CacheService::get($cachekey_table_task);
		}else{
		    $tasks = Task::db()->orderBy('id','asc')->get();
		      
		    CLOSE_CACHE!==false && CacheService::forever($cachekey_table_task,$tasks);
		}
		*/
		if($version != '3.0.0'){
		    $tasks = Task::db()->orderBy('id','asc')->get();
		}else{
			$tasks = Task::db()->where('type','<>',3)->orderBy('id','asc')->get();
		}
	    $task_ids = array();
	    $limit_task_ids = array();
		foreach($tasks as $row){			
			$condition = json_decode($row['condition'],true);
		    if(isset($condition['closed']) && intval($condition['closed'])==1){
				continue;
			}
			$task_ids[] = $row['id'];
			//$condition = json_decode($row['condition'],true);
			$task_condition[$row['id']] = $condition;
			$limit_task_ids[$row['id']] = $condition[$row['action']];
		}

		$user_normal_list = TaskAccount::db()->where('uid','=',$uid)->where('task_type','=',2)->where('status','=',1)->get();
		$user_normal_task_ids = array();
		foreach($user_normal_list as $row){
			$user_normal_task_ids[] = $row['task_id'];
		}
		$start = mktime(0,0,0,date('m'),date('d'),date('Y'));
		$end = mktime(23,59,59,date('m'),date('d'),date('Y'));
		$tmp_user_everyday_task_ids = TaskAccount::db()
			->where('uid','=',$uid)
			->where('task_type','=',1)
			->where('status','=',1)
			->where('ctime','>=',$start)
			->where('ctime','<=',$end)
			->lists('task_id');
		$user_everyday_task_ids = array();
		$exec_times = array_count_values($tmp_user_everyday_task_ids);
		foreach($limit_task_ids as $task_id=>$limit){
			if(isset($exec_times[$task_id])&&$exec_times[$task_id]>=$limit){
				$user_everyday_task_ids[] = $task_id;
			}
		}
		/*
		 * 推广任务
		 */
		$tmp_user_tuiguang_task = TaskAccount::db()->where('uid','=',$uid)->where('task_type','=',3)->get();
		$user_tuiguang_task_ids = array();
		foreach($tmp_user_tuiguang_task as $tuiguang_task){
				$user_tuiguang_task_ids[]=$tuiguang_task['task_id'];
		}
		$user_tuiguang_task_ids = array_unique($user_tuiguang_task_ids);
		$process_task_ids = array_diff($task_ids,$user_normal_task_ids,$user_everyday_task_ids,$user_tuiguang_task_ids);
		return $process_task_ids;
	}
	
	/**
	 * 执行任务
	 */
	public static function execTask($uid)
	{
		$task_ids = self::canExecTaskList($uid);
		$tasks = Task::db()->whereIn('id',$task_ids)->get();
		foreach($tasks as $task){
			$condition = json_decode($task['condition'],true);
			$conkey = $exectype = key($condition);
			$complete = false;
			//每日任务
			if($task['type']==1){
				$starttime = mktime(0,0,0,date('m'),date('d'),date('Y'));
				switch($exectype){
					case 'post_topic':
						break;
					case 'reply_topic':
						break;
					case 'post_comment':
						break;
					case 'reply_comment':
						break;
					case 'checkin':
						break;
				}
			}elseif($task['type']==2){//常规任务（也就是刚注册的新手任务，这种任务只有一次）
				switch($exectype){
					case 'modify_nickname':
						break;
					case 'modify_avatar':
						break;
					case 'modify_homebg':
						
						break;
					case 'improveinfo':
						break;
				}
			}
			//
			if($complete==true){
				$user_task = array();
				$user_task['task_type'] = $task['type'];
				$user_task['task_id'] = $task['id'];
				$user_task['status'] = 1;
				$user_task['uid'] = $uid;
				$user_task['receive'] = 0;
				$user_task['ctime'] = time();
				TaskAccount::db()->insertGetId($user_task);
			}
		}
	}

	/**
	 * 执行推广任务
	 */
	public static function doTuiguang($uid, $action)
	{
		return self::doTask($uid, $action, self::TASK_EXEC_TYPE_LIMIT);
	}
	
    public static function doTuiguangNum($uid, $action)
	{
		return self::doTask($uid, $action, self::TASK_EXEC_TYPE_ONCE);
	}
	
	/**
	 * 执行上传头像任务
	 */
	public static function doUploadAvatar($uid)
	{
		return self::doTask($uid,'upload-avatar',self::TASK_EXEC_TYPE_ONCE);	
	}
	
	/**
	 * 执行上传背景任务
	 */
	public static function doUploadHomebg($uid)
	{
	    return self::doTask($uid,'upload-homebg',self::TASK_EXEC_TYPE_ONCE);
	}
	
	/**
	 * 执行完善资料任务
	 */
	public static function doPerfectInfo($uid)
	{
		return self::doTask($uid,'edit-info',self::TASK_EXEC_TYPE_ONCE);	    
	}
	
	/**
	 * 执行游戏下载任务
	 */
	public static function doDownloadGame($uid)
	{
		return self::doTask($uid,'download',self::TASK_EXEC_TYPE_EVERYDAY);
	}
	
	/**
	 * 执行发帖任务
	 */
	public static function doPostTopic($uid)
	{
		return self::doTask($uid,'post-topic',self::TASK_EXEC_TYPE_EVERYDAY);
	}
	
    /**
	 * 执行回帖任务
	 */
	public static function doPostReply($uid)
	{
		return self::doTask($uid,'post-reply',self::TASK_EXEC_TYPE_EVERYDAY);
	}
	
    /**
	 * 执行游戏评论任务
	 */
	public static function doGameComment($uid)
	{
		return self::doTask($uid,'game-comment',self::TASK_EXEC_TYPE_EVERYDAY);
	}
	
	/**
	 * 执行每日分享任务
	 */
	public static function doShare($uid)
	{
		return self::doTask($uid,'share',self::TASK_EXEC_TYPE_EVERYDAY);
	}
	
	public static function checkShareLimit($uid)
	{
		$key = 'user::'.$uid.'::share::limit';
		//判断奖励次数
		$end = mktime(23,59,59,date('m'),date('d'),date('Y'));
		$start = mktime(0,0,0,date('m'),date('d'),date('Y'));
		$expire = $end - time();
		//$limit = (int)self::redis()->get($key);
		//if($limit==0) self::redis()->expire($key,$expire);
		$limit = ShareLimit::db()->where('uid','=',$uid)->where('ctime','>',$start)->count();		
		if($limit>=3){
			//self::redis()->incr($key);
			return true;
		}else{
			//self::redis()->incr($key);
			ShareLimit::db()->insert(array('uid'=>$uid,'ctime'=>time()));
			return false;
		}
	}
	
	/**
	 * 执行每日签到任务
	 */
	public static function doEveryCheckin($uid)
	{
		//return self::doTask($uid,'checkin',self::TASK_EXEC_TYPE_EVERYDAY);
		$checkin_credit = SettingService::getConfig('checkin_setting');
		if(!$checkin_credit) return null;
		$continuous_checkin = self::getLastWeekCheckin($uid);
		$continuous_count = count($continuous_checkin);
		if($continuous_count>=1){
			$score = 0;
			switch($continuous_count){
				case 1:
					$score = isset($checkin_credit['data']['first_day']) ? (int)$checkin_credit['data']['first_day'] : 0;
					break;
				case 2:
					$score = isset($checkin_credit['data']['second_day']) ? (int)$checkin_credit['data']['second_day'] : 0;
					break;
				case 3:
					$score = isset($checkin_credit['data']['third_day']) ? (int)$checkin_credit['data']['third_day'] : 0;
					break;
				case 4:
					$score = isset($checkin_credit['data']['fourth_day']) ? (int)$checkin_credit['data']['fourth_day'] : 0;
					break;
				case 5:
					$score = isset($checkin_credit['data']['fifth_day']) ? (int)$checkin_credit['data']['fifth_day'] : 0;
					break;
				case 6:
					$score = isset($checkin_credit['data']['sixth_day']) ? (int)$checkin_credit['data']['sixth_day'] : 0;
					break;
				case 7:
					$score = isset($checkin_credit['data']['seventh_day']) ? (int)$checkin_credit['data']['seventh_day'] : 0;
					break;
				default:
					$score = isset($checkin_credit['data']['greater_seven_day']) ? (int)$checkin_credit['data']['greater_seven_day'] : 0;
					break;
			}
			if($score){
				$time = time();
				$startdate = mktime(0,0,0,1,1,2015);
				$enddate = mktime(0,0,0,1,4,2015);
				$info = '签到奖励游币'.$score;
				if($time>$startdate && $time<$enddate){
					$score = $score * 2;
					$info = '元旦签到奖励双倍游币'.$score;
				}
				CreditService::handOpUserCredit($uid,$score,0,'checkin',$info);
			}
		}
	}
	
	/**
	 * 执行任务
	 */
	protected static function doTask($uid,$action,$execType)
	{
		$finish = false;
		$addtask = false;
		$flag = false;
		$task = Task::db()->where('action','=',$action)->first();
		if($task){
			$reward = json_decode($task['reward'],true);
			$condition = json_decode($task['condition'],true);
			if(isset($condition['closed']) && intval($condition['closed'])==1) return false;
			if($execType == self::TASK_EXEC_TYPE_ONCE){//一次性任务
			    $user_task = TaskAccount::db()->where('uid','=',$uid)->where('task_id','=',$task['id'])->first();
			    if(!$user_task) {
			    	$finish = true;
			    	$addtask = true;
			    }
			}elseif($execType == self::TASK_EXEC_TYPE_EVERYDAY){//每日任务
				$start = mktime(0,0,0,date('m'),date('d'),date('Y'));
				$end = mktime(23,59,59,date('m'),date('d'),date('Y'));				
				$user_task = TaskAccount::db()->where('uid','=',$uid)->where('task_id','=',$task['id'])->where('ctime','>=',$start)->where('ctime','<=',$end)->first();
				$times = TaskAccount::db()->where('uid','=',$uid)->where('task_id','=',$task['id'])->where('ctime','>=',$start)->where('ctime','<=',$end)->count();
				$limit = (int)$condition[$action];
				$max_times = isset($condition['max_times']) ? (int)$condition['max_times'] : 1;
				if($limit==1){					
					if(!$user_task){//没有做完该任务
				        $finish = true;
				        $addtask = true;
					}elseif($times<$max_times){//已完成该任务,判断是否达到最大奖励次数						
						$addtask = true;
						$finish = true;
					}
				}elseif($limit>1){//完成次数验证
					$cycle = $times/$limit;//(除法运算)
					$cycle_times = $times%$limit;//周期内完成次数(取余)
					if($cycle<$max_times){
						if($limit>$cycle_times) $addtask = true;
						if($limit === ($cycle_times+1)) $finish = true;
					}
					
				}
				
			}elseif($execType == self::TASK_EXEC_TYPE_LIMIT){
//				$times = TaskAccount::db()->where('uid','=',$uid)->where('task_id','=',$task['id'])->count();
//				$limit = (int)$condition[$action];
//				if($times >= $limit) $finish = true;
//				if($times<$limit) $addtask = true;
				$finish = true;
				$addtask = true;
				$flag = true;
			}
			
			if($addtask===true){
				$user_task = array();
				$user_task['task_type'] = $task['type'];
				$user_task['task_id'] = $task['id'];
				$user_task['status'] = 1;
				$user_task['uid'] = $uid;
				$user_task['receive'] = 1;
				$user_task['ctime'] = time();
				TaskAccount::db()->insertGetId($user_task);				
			}
		    if($finish==true) {//获取奖励 
		    	$info = '完成' . $task['typename'] . $task['step_name'] . ',获得游币' . $reward['score'] . '个';
		    	if($flag){
		    		CreditService::handOpUserCredit($uid,(int)$reward['score'],(int)$reward['experience'],$action,$info);
		    	}else{
		    		CreditService::handOpUserCredit($uid,(int)$reward['score'],(int)$reward['experience'],'task_'.$action,$info);
		    	}
				return (int)$reward['score'];
			}
			return true;
		}
		return false;
	}
	
	/**
	 * 签到
	 */
	public static function doCheckin($uid)
	{
		$start = mktime(0,0,0,date('m'),date('d'),date('Y'));
		$idfa = UserService::getUserAppleIdentifyBy($uid,'idfa');
		if(!$idfa)  $idfa = Input::get('idfa'); 
		if(self::isExistsCheckin($uid,$idfa)){
			return -1;
		}else{
			if($idfa){
			    $success = CheckInfoLimit::db()->insertGetId(array('idfa'=>$idfa,'cdate'=>$start));
			    if(!$success) return false;
			}
			$id = CheckInfo::db()->insertGetId(array('uid'=>$uid,'ctime'=>time()));
			$id && self::doEveryCheckin($uid);
			return $id ? true : false;
		}
	}
	
	/**
	 * 是否已经签到
	 */
	public static function isExistsCheckin($uid,$idfa=null)
	{
		$start = mktime(0,0,0,date('m'),date('d'),date('Y'));
		if($idfa){		
			$exists = CheckInfoLimit::db()->where('idfa','=',$idfa)->where('cdate','=',$start)->count();
			return $exists>0 ? true : false;
		}
		$count = CheckInfo::db()->where('uid','=',$uid)->where('ctime','>=',$start)->count();
		return $count>0 ? true : false;
	}
	/*
	 * 获取用户的注册码
	 */	
	public static function getZhucefa($uid)
	{
		$zhucema = Account::db()->where('uid', $uid)->pluck('zhucema');
		/*
		$cachekey_table_task = 'table::task::zhucema';
		if(CLOSE_CACHE!==false && CacheService::has($cachekey_table_task)){
			$zhucema = CacheService::get($cachekey_table_task);
		}else{
		    
		    CLOSE_CACHE!==false && CacheService::forever($cachekey_table_task,$zhucema);
		}
		*/
		return $zhucema;
	}
	
	/**
	 * 获取最近七天内连续签到记录
	 */
	public static function getLastWeekCheckin($uid)
	{
		$today_start = mktime(0,0,0,date('m'),date('d'),date('Y'));
		$start = $today_start - (60*60*24*7);
		$week = array();
		for($i=0;$i<7;$i++){
			$week[] = $today_start - (60*60*24*($i+1));
		}		
		$list = CheckInfo::db()->where('uid','=',$uid)->where('ctime','>=',$start)->orderBy('ctime','desc')->lists('ctime');
		$checkin_list = array();
		$index = 0;
		foreach($list as $time){
			if($time>=$today_start){
				$checkin_list[] = $time;
			}else{			
				if($time>=$week[$index]){
					$checkin_list[] = $time;
					$index++;
				}else{
				    break;
			    }
			}
		}
		return $checkin_list;
	}
	
	/**
	 * 连续签到记录
	 */
	public static function getContinuousCheckin($uid)
	{
		$today_start = mktime(0,0,0,date('m'),date('d'),date('Y'));
		$list = CheckInfo::db()->where('uid','=',$uid)->orderBy('ctime','desc')->take(8)->lists('ctime');
		$count = count($list);
		$continuous = array();
		for($i=0;$i<$count;$i++){
			$continuous[] = $today_start - (60*60*24*($i+1));
		}
		$checkin_list = array();
		$index = 0;
		foreach($list as $time){
			if($time>=$today_start){
				$checkin_list[] = $time;
			}else{			
				if($time>=$continuous[$index]){
					$checkin_list[] = $time;
					$index++;
				}else{
				    break;
			    }
			}
		}
		return $checkin_list;
	}
	
}