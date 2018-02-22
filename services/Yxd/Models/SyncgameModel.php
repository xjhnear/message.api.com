<?php
namespace Yxd\Models;

use Yxd\Modules\Core\BaseModel;

class SyncgameModel extends BaseModel{
	
	public static function addArctiny($data){
		return self::dbYxdMaster()->table('arctiny')->insertGetId($data);
	}
	
	public static function addArctype($data){
		return self::dbYxdMaster()->table('arctype')->insertGetId($data);
	}
	
	public static function updateArctype($c_id,$id,$data){
		return self::dbYxdMaster()->table('arctype')->where('refarc',$c_id)->where('id',$id)->update($data);
	}
	
	public static function addArchives($data){
		return self::dbYxdMaster()->table('archives')->insertGetId($data);
	}
	
	public static function getArchives($yxd_id){
		return self::dbYxdMaster()->table('archives')->where('yxdid','g_'.$yxd_id)->where('channel',3)->where('typeid',4)
							->select('id','writer','reftype')->first();
	}
	
	public static function updateArchives($id,$data){
		return self::dbYxdMaster()->table('archives')->where('id',$id)->update($data);
	}
	
	public static function addAddongame($data){
		return self::dbYxdMaster()->table('addongame')->insertGetId($data);
	}
	
	public static function updateAddongame($aid,$data){
		return self::dbYxdMaster()->table('addongame')->where('aid',$aid)->update($data);
	}
	
	public static function getTag($tag){
		return self::dbYxdMaster()->table('tagindex')->where('taggroup','游戏特征')->where('tag',$tag)->select('id')->first();
	}
	
	public static function addTaglist($data){
		return self::dbYxdMaster()->table('taglist')->insert($data);
	}
	
	public static function delTaglist($aid){
		if(!$aid) return false;
		return self::dbYxdMaster()->table('taglist')->where('aid',$aid)->delete();
	}
}