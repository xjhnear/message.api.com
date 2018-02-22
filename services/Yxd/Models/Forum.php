<?php
namespace Yxd\Models;

use Yxd\Services\UserService;

use Illuminate\Support\Facades\DB;
use Yxd\Services\Models\Forum as V3Forum;
use Yxd\Services\Models\ForumChannel;
use Yxd\Services\Models\ForumNotice;
use Yxd\Services\Models\AccountCircle;

class Forum
{
	/**
	 * 
	 */
	public static function getForumInfoByDomain($domain)
	{
		return V3Forum::db()->where('domain','=',$domain)->first();
	}
	
    /**
	 * 
	 */
	public static function getChannelList($gid,$autoadd=false)
	{
		if($gid==2){
			$channels = ForumChannel::db()
               ->where('gid','=',$gid)
               ->orderBy('displayorder','asc')
               ->get();
		}else{
		    $channels = ForumChannel::db()
               ->where('gid','=',$gid)
               ->orWhere('gid','=',0)
               ->orderBy('displayorder','asc')
               ->get();
		}
		//
		$out = array(); 
	    if($autoadd==true){
		 	$out[] = array('cid'=>'0','name'=>'综合版');
		}
		foreach($channels as $key=>$row)
		{
		 	$channel = array();
		 	$channel['cid'] = $row['cid'];
		 	$channel['name'] = $row['channel_name'];
		 	$out[] = $channel;		 	
		}		               
		return $out;
	}
	
	public static function getChannelKV($gid)
	{
		if($gid==2){
			return ForumChannel::db()
		           ->orderBy('displayorder','asc')
		           ->where('gid','=',$gid)
		           ->lists('channel_name','cid');
		}
		return ForumChannel::db()
		           ->orderBy('displayorder','asc')
		           ->where('gid','=',$gid)
		           ->orWhere('gid','=',0)
		           ->lists('channel_name','cid');
	}
	
    public static function getForumList($gid)
	{
		if($gid==2){
			$forum_list = V3Forum::db()
		                  ->where('displayorder','=',0)
		                  ->where('gid','=',$gid)
		                  ->get();
		    return $forum_list;
		}
		$forum_list = V3Forum::db()
		                  ->where('displayorder','=',0)
		                  ->where('gid','=',$gid)
		                  ->orWhere('gid','=',0)
		                  ->get();
		                  
		return $forum_list;
	}
	
	public static function getNoticeList($gid)
	{
		$notices = ForumNotice::db()
		               ->whereIn('gid',array(0,$gid))
		               ->where('startdate','<=',(int)microtime(true))
		               ->where('enddate','>=',(int)microtime(true))
		               ->orderBy('dateline','desc')
		               ->get();            
		 return $notices;
	}
	
	public static function getNoticeInfo($id)
	{
		return ForumNotice::db()
		               ->where('id','=',$id)
		               ->where('startdate','=<',(int)microtime(true))
		               ->where('enddate','>=',(int)microtime(true))
		               ->first();
	}
	
	public static function getCircleUserCount($gid)
	{
		return AccountCircle::db()
		           ->where('game_id','=',$gid)->count();
	}
	
	public static function getCircleUsers($gid,$page=1,$pagesize=10)
	{
		return AccountCircle::db()
		           ->where('game_id','=',$gid)
		           ->forPage($page,$pagesize)
		           ->orderBy('id','desc')
		           ->lists('uid');
	}	
}