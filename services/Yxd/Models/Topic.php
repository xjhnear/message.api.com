<?php
namespace Yxd\Models;

use Yxd\Services\UserService;

use Illuminate\Support\Facades\DB;
use Yxd\Services\Models\Like;

class Topic
{
	/**
	 * 创建主题帖
	 * @param array $thread 主题帖
	 * @param string $message 内容
	 * 
	 * @return int $tid 主题ID 
	 */
	public static function createThread($thread,$message)
	{
		$obj = json_decode($message,true);
		foreach($obj as $val){
			
		    if(!isset($thread['summary']) && $val['text']){
				$thread['summary'] = $val['text'];
			}
			
		    if(!isset($thread['listpic']) && $val['img']){
				$thread['listpic'] = $val['img'];
			}
		}
		$tid = DB::table('forum_thread')->insertGetId($thread);
		if($tid){
			$post = array();
			$post['gid'] = $thread['gid'];
			$post['subject'] = $thread['subject'];
			$post['author'] = $thread['author'];
			$post['author_uid'] = $thread['author_uid'];
			$post['message'] = $message;
			//$post[''] = $thread[''];
			$post['tid'] = $tid;
			$post['first'] = 1;
			$post['rid'] = 0;
			$post['dateline'] = (int)microtime(true);
			DB::table('forum_post')->insertGetId($post);
		}
		return $tid;
	}
	
	/**
	 * 创建回复帖
	 * @param array $post 回复帖
	 */
	public static function createReply($post)
	{
		$post['dateline'] = (int)microtime(true);
		$max = DB::table('forum_post')->where('tid','=',$post['tid'])->max('storey');
		$post['storey'] = $max+1;
		$pid = DB::table('forum_post')->insertGetId($post);
		if($pid){
			//更新主题帖回帖数
			$extra = array('lastpost'=>(int)microtime(true),'lastposter'=>$post['author']);  
			DB::table('forum_thread')->where('tid','=',$post['tid'])->increment('replies',1,$extra);
		}
		return $pid;
	}
	
    /**
	 * 获取主题帖列表
	 */
	public static function getThreadList($gid,$cid=0,$page=1,$pagesize=20,$feature=0,$sort='dateline')
	{
	    $tb = DB::table('forum_thread')->where('gid','=',$gid);
		
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
		if(!in_array($sort,array('dateline','replies'))) $sort = 'dateline';
		return $tb->orderBy($sort,'desc')->forPage($page,$pagesize)->get();
	}
	
	/**
	 * 获取问答主题帖列表
	 */
	public static function getAskThreadList($gid,$page=1,$pagesize=10,$sort='dateline')
	{
		$tb = DB::table('forum_thread')->where('gid','=',$gid)->where('ask','=',1);
		$tb = $tb->where('displayorder','=',0);
		if(!in_array($sort,array('dateline','replies'))) $sort = 'dateline';
		return $tb->orderBy($sort,'desc')->forPage($page,$pagesize)->get();
	}
	
	/**
	 * 获取主题帖数量
	 */
	public static function getThreadCount($gid,$cid=0,$feature=0)
	{
		$tb = DB::table('forum_thread')->where('gid','=',$gid);
		
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
	 * 获取主题帖全部信息
	 * @param int $tid 主题帖ID
	 */
	public static function getFullTopic($tid)
	{
		$thread = DB::table('forum_thread')->where('tid','=',$tid)->first();
		if(!$thread) return null;
		$post = DB::table('forum_post')->where('tid','=',$tid)->where('first','=',1)->first();
		$topic = array();
		$topic = array_merge($topic,$thread,$post);
		$author = UserService::getUserInfo($topic['author_uid'],'basic');
		$topic['author'] = $author;
		
		return $topic;
	}
	
	/**
	 * 获取回复帖全部信息
	 * @param int $pid 
	 */
	public static function getFullPost($pid)
	{
		$post = DB::table('forum_post')->where('pid','=',$pid)->first();
		if(!$post) return null;
		$author = UserService::getUserInfo($post['author_uid']);
		$post['author'] = UserService::filterUserFields($author,'short');
		return $post;
	}
	
	/**
	 * 获取回复帖列表
	 */
	public static function getPostList($tid,$page=1,$size=20)
	{
		$total = DB::table('forum_post')->where('tid','=',$tid)->where('first','=',0)->where('rid','=',0)->count();
		
		$posts = DB::table('forum_post')
		            ->where('tid','=',$tid)
		            ->where('first','=',0)
		            ->where('rid','=',0)		            
		            ->orderBy('pid','asc')
		            ->forPage($page,$size)		            
		            ->get();
		if(!$posts) return array('total'=>$total,'list'=>null);            
		$post_pids = array();
		$sort_posts = array();
		$uids = array();
		foreach($posts as $post){
			$post_pids[] = $post['pid'];
			$sort_posts[$post['pid']] = $post;
			$uids[] = $post['author_uid'];
		}
		
		
		$replys = DB::table('forum_post')->whereIn('rid',$post_pids)->orderBy('pid','asc')->get();
		
		foreach($replys as $reply){
			$uids[] = $reply['author_uid'];
		}
		
		$users = UserService::getBatchUserInfo(array_unique($uids));
		foreach($replys as $reply){
			$reply['author'] = $users[$reply['author_uid']];
			$reply['content'] = json_decode($reply['message'],true);
			$reply['ctime'] = date('Y-m-d',$reply['dateline']);
			$sort_posts[$reply['rid']]['replys'][] = $reply;
		}
		foreach($sort_posts as $key=>$post){
			$post['author'] = $users[$post['author_uid']];
			$post['content'] = json_decode($post['message'],true);
			$post['ctime'] = date('Y-m-d',$post['dateline']);
			$sort_posts[$key] = $post;
		}
		//重置数组索引
		return array('total'=>$total,'list'=>array_merge(array(),$sort_posts));
		
	}
	
	/**
	 * 保存附件
	 */
	public static function saveAttachment($attach)
	{
				
		$aid = DB::table('forum_attachment')->insertGetId($attach);
		
		return $aid;
	}
	
	/**
	 * 是否赞过
	 */
	public static function isLike($tid,$uid)
	{
		$count = Like::db()
		         ->where('target_id','=',$tid)
		         ->where('target_table','=','forum_thread')
		         ->where('uid','=',$uid)
		         ->count();
		return $count>0 ? true : false;
	}
	/**
	 * 保存赞
	 */
	public static function saveLike($tid,$uid)
	{
		if(self::isLike($tid, $uid)) return -1;
		$data = array(
		    'target_id'=>$tid,
		    'uid'=>$uid,
		    'target_table'=>'forum_thread',
		    'ctime'=>(int)microtime(true)
		);		
		return Like::db()->insertGetId($data);
	}
	/**
	 * 获取赞过帖子的用户
	 */
	public static function getThreadLikes($tid,$page=1,$size=20)
	{
		$uids =  Like::db()
		         ->where('target_id','=',$tid)
		         ->where('target_table','=','forum_thread')
		         ->forPage($page,$size)
		         ->lists('uid');
		return $uids;
	}
	
	
}