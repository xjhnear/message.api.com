<?php
namespace Yxd\Models;

use Illuminate\Support\Facades\DB as DB;

use Yxd\Services\Models\AccountFollow;

class Follow
{
    public static function addFollow($uid,$fuid)
	{
		$data['uid'] = $uid;
		$data['fuid'] = $fuid;
		$data['ctime'] = (int)microtime(true);
		$count = AccountFollow::db()->where('uid','=',$uid)->where('fuid','=',$fuid)->count();
		if($count>0){
			return -1;
		}else{
			return AccountFollow::db()->insertGetId($data);
		}
	}
	
	public static function isFollow($uid,$fuid)
	{
		$count = AccountFollow::db()->where('uid','=',$uid)->where('fuid','=',$fuid)->count();
		return $count>0 ? true : false;
	}
	
	public static function deleteFollow($uid,$fuid)
	{
	    $count = AccountFollow::db()->where('uid','=',$uid)->where('fuid','=',$fuid)->count();
		if($count===0){
			return -1;
		}
		return AccountFollow::db()->where('uid','=',$uid)->where('fuid','=',$fuid)->delete();
	}
	
	public static function getFollowCount($uid)
	{
		return AccountFollow::db()->where('uid','=',$uid)->count();
	}
	
	public static function getFollowList($uid,$page=1,$pagesize=20)
	{
		$uids = AccountFollow::db()->where('uid','=',$uid)->forPage($page,$pagesize)->lists('fuid');

		return $uids;
	}
	public static function getFollowerCount($uid)
	{
		return AccountFollow::db()->where('fuid','=',$uid)->count();
	}
	
    public static function getFollowerList($uid,$page=1,$pagesize=20)
	{
		$uids = AccountFollow::db()->where('fuid','=',$uid)->forPage($page,$pagesize)->lists('uid');

		return $uids;
	}
}