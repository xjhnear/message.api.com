<?php
/**
 * @category Forum
 * @link http://www.youxiduo.com
 * @author mawenpei<mawenpei@cwan.com>
 * @since 2014-03-15
 * @version 3.0.0
 */
namespace Yxd\Services;

use PHPImageWorkshop\ImageWorkshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Yxd\Models\Topic;
use Yxd\Models\Forum;
/**
 * 论坛主题帖服务
 */
class TopicService extends Service
{
	/**
	 * 发主题帖
	 * @param array $topic 
	 * @param array|null $atFriends
	 * 
	 */
	public static function createTopic($topic,$atFriends=null)
	{		
		$rule = array(
		    'subject'=>array('required'),
		    'gid'=>array('required','num eric'),
		    'cid'=>array('required','numeric'),
		    'message'=>array('required'),
		    //'access_token'=>array('required')
		);
		$validator = Validator::make($topic,$rule);
		if($validator->fails()){
			return self::send(1303,null,'miss_params','参数不全');
		}
		if(!isset($topic['uid'])){
			$token = PassportService::accessTokenToUid($topic['access_token']);
			if($token===false){
				return self::send(1104,null,'invalid_access_token','无效的令牌access_token');
			}
			$uid = $token['user_id'];
		}else{
			$uid = $topic['uid'];
		}
		$author = UserService::getUserInfo($uid);
		
		$thread = array(
		    'gid'=>$topic['gid'],
		    'cid'=>$topic['cid'],
		    'subject'=>$topic['subject'],
		    'author_uid' =>$author['uid'],
		    'author'=>$author['nickname'],
		    'award'=>isset($topic['award']) ? $topic['award'] : 0,
		    'ask'=>isset($topic['ask']) ? $topic['ask'] : 0,
		    'dateline'=>(int)microtime(true)
		);		 
		$message = $topic['message'];
        $event = Event::fire('topic.post_before',array(array($thread,$message)));
        if($event && $event[0]){
        	$thread = $event[0][0];
        	$message = $event[0][1];
        }
		$tid = Topic::createThread($thread, $message);
		if($tid){
			$out = Topic::getFullTopic($tid);
			Event::fire('topic.post',array(array($out)));
			//AT好友
			AtmeService::atmeOfPostTopic($atFriends,$out);
			return self::send(200,$out);
		}else{
			return self::send(1106,null,'server_error','服务器端错误');
		}
	} 
	
	/**
	 * 发回复帖
	 */
	public static function createReply($reply,$atFriends=null)
	{
		$rule = array(		    
		    'tid'=>array('required','numeric'),
		    'rid'=>array('required','numeric'),
		    'gid'=>array('required','numeric'),
		    'message'=>array('required'),
		    //'access_token'=>array('required')
		);
		$validator = Validator::make($reply,$rule);
		if($validator->fails()){
			return self::send(1303,null,'miss_params','参数不全');
		}
		
		if(!isset($reply['uid'])){
			$token = PassportService::accessTokenToUid($reply['access_token']);
			if($token===false){
				return self::send(1104,null,'invalid_access_token','无效的令牌access_token');
			}
			$uid = $token['user_id'];
		}else{
			$uid = $reply['uid'];
		}
		$author = UserService::getUserInfo($uid);
		$topic = DB::table('forum_thread')->where('tid','=',$reply['tid'])->first();
		$post = array();
		$post['tid'] = $reply['tid'];
		$post['subject'] = $topic['subject'];
		$post['rid'] = $reply['rid'];
		$post['gid'] = $topic['gid'];
		$post['message'] = $reply['message'];
		$post['author_uid'] = $author['uid'];
		$post['author'] = $author['nickname'];
		$post['first'] = 0;
		$pid = Topic::createReply($post);
		if($pid){
			$out = Topic::getFullPost($pid);
			Event::fire('forum.topic_reply',array(array($out)));
			//AT好友
			AtmeService::atmeOfReplyTopic($atFriends, $out);
			return self::send(200,$out);
		}else{
			return self::send(1106,null,'server_error','服务器端错误');
		}
	}
	
	/**
	 * 显示主题帖详情
	 * 包含回帖列表
	 */
	public static function showTopicInfo($tid,$page=1,$size=20)
	{
		
		$topic = Topic::getFullTopic($tid);
		if(!$topic){
			return self::send(1306,null,'invalid_topic','主题不存在');
		}
		$topic['ctime'] = date('Y-m-d',$topic['dateline']);
		$topic['content'] = json_decode($topic['message'],true);
		
		$posts = Topic::getPostList($tid,$page,$size);
		
		$out = array('topic'=>$topic,'posts'=>$posts);		
		return self::send(200,$out);
	}
	
	public static function showPostList($tid,$page=1,$size=20)
	{
		$posts = Topic::getPostList($tid,$page,$size);
		return self::send(200,$posts);
	}
	
	public static function showTopicList($gid,$cid=0,$page=1,$pagesize=20,$feature=0)
	{
		$data = array();
		$data['total'] = Topic::getThreadCount($gid,$cid,$feature);
		$topics = Topic::getThreadList($gid,$cid,$page,$pagesize,$feature);
		$channels = Forum::getChannelKV($gid);
		$uids = array();
		foreach($topics as $key=>$row){
			$uids[] = $row['author_uid'];
		}
		$uids = array_unique($uids);
		$users = UserService::getUserInfoCacheByUids($uids);
		foreach($topics as $key=>$topic){
			$topic['author'] = $users[$topic['author_uid']];
			$topic['channel_name'] = $channels[$topic['cid']];
			$topic['ctime'] = date('Y-m-d',$topic['dateline']);
			$topics[$key] = $topic;
		}
		$data['topics'] = $topics;
		return $data;
	}
	
	/**
	 * 精华
	 */
	public static function doDigest($tid,$access_token,$val=true)
	{
		
	    $token = PassportService::accessTokenToUid($access_token);
		if($token===false){
			return self::send(1104,null,'invalid_access_token','无效的令牌access_token');
		}
	}
	
    /**
	 * 置顶
	 */
	public static function doStick($tid,$access_token,$val=true)
	{
	    $token = PassportService::accessTokenToUid($access_token);
		if($token===false){
			return self::send(1104,null,'invalid_access_token','无效的令牌access_token');
		}
	}
	
	/**
	 * 评分
	 */
	public static function doScore($tid,$access_token)
	{
		
	}
	
	/**
	 * 删除主题帖
	 */
	public static function dropTopic($tid,$access_token)
	{
	    $token = PassportService::accessTokenToUid($access_token);
		if($token===false){
			return self::send(1104,null,'invalid_access_token','无效的令牌access_token');
		}
	}
	
	/**
	 * 删除回复帖
	 */
	public static function dropPost($pid,$access_token)
	{
	    $token = PassportService::accessTokenToUid($access_token);
		if($token===false){
			return self::send(1104,null,'invalid_access_token','无效的令牌access_token');
		}
	}
	
	/**
	 * 点击赞
	 */
	public static function doLike($tid,$access_token)
	{
	    $token = PassportService::accessTokenToUid($access_token);
		if($token===false){
			return self::send(1104,null,'invalid_access_token','无效的令牌access_token');
		}
		$aid = Topic::saveLike($tid, $token['user_id']);
		if($aid==-1){
			return self::send(1510,null,'','已经赞过了');
		}elseif($aid>0){
			return self::send(200,null);
		}
		return self::send(500,null,'server_error','服务器错误');
	}
	
    /**
	 * 是否赞过
	 */
	public static function isLike($tid,$access_token)
	{
	    $token = PassportService::accessTokenToUid($access_token);
		if($token===false){
			return self::send(1104,null,'invalid_access_token','无效的令牌access_token');
		}
		$result = Topic::isLike($tid, $token['user_id']);
		
		return self::send(200,$result);
	}
	
	/**
	 * 获取赞过的用户列表
	 */
	public static function getTopicLikes($tid,$page=1,$size=20)
	{
		$uids = Topic::getThreadLikes($tid,$page,$size);
		if($uids){
			$users = UserService::getUserInfoCacheByUids($uids);
			return self::send(200,$users);
		}
		return self::send(200,null);
	}
	/**
	 * 获取对某一篇帖子点赞的用户列表
	 */
	public static function getOneTopiclikes($likeList)
	{
		if(!$likeList) return null;
		$out = array();
		foreach($likeList as $like)
		{
			$uid = $like['uid'];
			$ctime = $like['ctime'];
			$users = UserService::getUserInfoCache($uid);
			$users['ctime']=date('Y-m-d H:i:s', $ctime);
			$out[$uid]=$users;
		}
		return $out;
	}
	
	/**
	 * 上传图片
	 */
	public static function doUpload($field,$access_token,$uid=0)
	{
		if($access_token){
	        $token = PassportService::accessTokenToUid($access_token);	        		
			if($token===false){
				return self::send(1104,null,'invalid_access_token','无效的令牌access_token');
			}
			$uid = $token['user_id'];
	    }
		if(Input::hasFile($field)){
			$file = Input::file($field);
			if($file->isValid()){
				$org_filename = $file->getClientOriginalName();
				$ext = $file->getClientOriginalExtension();
				$size = $file->getClientSize();
				$tmp_path = $file->getRealPath();
				$dir = '/userdirs/' . date('Y') . '/' . date('m') . '/';				
				$server_path = storage_path() . $dir;
				$new_filename = date('YmdHis') . str_random(4);
				$new_filename_m = $new_filename . '_m.' . $ext;
				$new_filename = $new_filename . '.' . $ext;

				if(!in_array($ext,array('png','jpg','jpeg','gif'))){
					return self::send(1331,null,'invalid_filetype','无效的文件类型:' . $ext);
				}
			
				$file->move($server_path,$new_filename);
				$layer = ImageWorkshop::initFromPath($server_path . $new_filename);
				$layer->resizeInPixel(640,null,true);
				$layer->save($server_path,$new_filename_m,true,null,95);
				$url = $dir . $new_filename_m;
				$attach = array(
				    'tid'=>0,
				    'pid'=>0,
				    'uid'=>$uid,
				    'dateline'=>(int)microtime(true),
				    'filename'=>$org_filename,
				    'filetype'=>$ext,
				    'filesize'=>$size,
				    'attachment'=>$dir . $new_filename_m,
				    'isimg'=>1
				);
				
				$attach['aid'] = Topic::saveAttachment($attach);
				
				return self::send(200,$attach);
			}
			return self::send(500,null,'',$file->getError());
		}
		return self::send(500,null,'invalid_file','文件无效');
	}
}