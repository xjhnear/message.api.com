<?php
namespace Yxd\Models;
use modules\comment\models\CommentModel;

use modules\forum\models\TopicModel;

use Yxd\Utility\ForumUtility;

use Yxd\Services\UserService;

use Yxd\Modules\Core\BaseModel;

use Yxd\Services\Models\ForumTopic;
use Yxd\Services\Models\Comment;
use Yxd\Services\Models\Inform;

class InformModel extends BaseModel
{
	public static function getList($page=1,$size=10,$type=0)
	{
		$search = array('type'=>$type);
		$total = self::buildSearch($search)->count();
		$list = self::buildSearch($search)->forPage($page,$size)->orderBy('addtime','desc')->get();
		$tids = array();
		$cids = array();
		$uids = array();
		foreach($list as $row){
			if((int)$row['type']==1){
				$tids[] = $row['target_id'];
			}else{
				$cids[] = $row['target_id'];
			}
			$uids[] = $row['uid'];
		}
		$tids = array_unique($tids);
		$cids = array_unique($cids);
		$uids = array_unique($uids);
		
		$tps = array();//帖子
		$cmts = array();
		if($tids){
		    $ts = ForumTopic::db()->whereIn('tid',$tids)->get();
		    foreach($ts as $t){
		    	$tps[$t['tid']] = $t;
		    }
		}
		if($cids){
			$cs = Comment::db()->whereIn('id',$cids)->get();
			foreach($cs as $c){
				$cmts[$c['id']] = $c;
			}
		}
		$users = UserService::getBatchUserInfo($uids);
		foreach($list as $key=>$i){
			
			if($i['type'] == 1) {
				$i['topic'] = isset($tps[$i['target_id']]) ? $tps[$i['target_id']]: null;
			}
			if($i['type'] == 2){
				$cmt = array();
				if(isset($cmts[$i['target_id']])){
					$cmt = $cmts[$i['target_id']];
					$content = json_decode($cmt['content'],true);
					$cmt['format_content'] = ForumUtility::formatTopicMessage($content);
				}
				$i['comment'] = $cmt;
			}
			$i['user'] = $users[$i['uid']];
			$list[$key] = $i;
		}
		return array('list'=>$list,'total'=>$total);
	}
	
	protected static function buildSearch($search)
	{
		$tb = Inform::db();
		if(isset($search['type']) && $search['type']){
			$tb = $tb->where('type','=',$search['type']);
		}
		
		return $tb;
	}
	
	public static function doDelete($id)
	{
		$inform = Inform::db()->where('id','=',$id)->first();
		if($inform){
			if($inform['type']==1){				
				TopicModel::deleteTopicInfo($inform['target_id']);				
			}elseif($inform['type']==2){
				CommentModel::doDelete(array($inform['target_id']));
			}
			return self::doIgnore($id);
		}
		return false;
	}
	
	public static function doIgnore($id)
	{
		return Inform::db()->where('id','=',$id)->delete();
	}
}