<?php
/**
 * @category Forum
 * @link http://www.youxiduo.com
 * @author mawenpei<mawenpei@cwan.com>
 * @since 2014-03-15
 * @version 3.0.0
 */
namespace Yxd\Models;

use Yxd\Services\UserService;

use Illuminate\Support\Facades\DB;
use Yxd\Services\Models\ForumTopic;

class Thread
{
    /**
	 * 获取主题帖数量
	 */
	public static function getThreadCount($gid,$cid=0,$feature=0)
	{
		$tb = ForumTopic::db()->where('gid','=',$gid);
		
		if($cid>0){
			$tb = $tb->where('cid','=',$cid);
		}
		
		if($feature==1){
			$tb = $tb->where('stick','=',1);
		}elseif($feature==2){
			$tb = $tb->where('digest','=',1);
		}else{
			$tb = $tb->where('displayorder','=',0);
		}
		return $tb->count();
	}
	
    /**
	 * 获取主题帖列表
	 */
	public static function getThreadList($gid,$cid=0,$page=1,$pagesize=20,$feature=0,$sort='dateline')
	{
	    $tb = ForumTopic::db()->where('gid','=',$gid);
		
		if($cid>0){
			$tb = $tb->where('cid','=',$cid);
		}
		
		if($feature==1){
			$tb = $tb->where('stick','=',1);
		}elseif($feature==2){
			$tb = $tb->where('digest','=',1);
		}else{
			$tb = $tb->where('displayorder','=',0);
		}
		if(!in_array($sort,array('dateline','replies','lastpost'))) $sort = 'lastpost';
		return $tb->orderBy($sort,'desc')->forPage($page,$pagesize)->get();
	}
	
    public static function getFullTopic($tid)
	{
		$thread = ForumTopic::db()->where('tid','=',$tid)->first();
		if(!$thread) return null;
		$author = UserService::getUserInfo($thread['author_uid'],'basic');
		$thread['author'] = $author;
		return $thread;
	}
}