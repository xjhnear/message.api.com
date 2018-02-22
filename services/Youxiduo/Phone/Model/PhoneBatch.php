<?php
/**
 * @package Youxiduo
 * @category Android 
 * @link http://dev.youxiduo.com
 * @copyright Copyright (c) 2008 Youxiduo.com 
 * @license http://www.youxiduo.com/license
 * @since 4.0.0
 *
 */
namespace Youxiduo\Phone\Model;

use Youxiduo\Base\Model;
use Youxiduo\Base\IModel;

use Youxiduo\Helper\Utility;
use Youxiduo\Helper\MyHelp;
/**
 * 应用配置模型类
 */
final class PhoneBatch extends Model implements IModel
{		
    public static function getClassName()
	{
		return __CLASS__;
	}

	public static function getList($search,$pageIndex=1,$pageSize=20)
	{
		$tb = self::db();
		if(isset($search['batch_code']) && !empty($search['batch_code'])) $tb = $tb->where('batch_code','like','%'.$search['batch_code'].'%');
		if(isset($search['category']) && !empty($search['category'])) $tb = $tb->where('category','=',$search['category']);
		return $tb->orderBy('is_new','desc')->orderBy('down_at','desc')->orderBy('created_at','desc')->forPage($pageIndex,$pageSize)->get();
	}

	public static function getCount($search)
	{
		$tb = self::db();
		if(isset($search['batch_code']) && !empty($search['batch_code'])) $tb = $tb->where('batch_code','like','%'.$search['batch_code'].'%');
		if(isset($search['category']) && !empty($search['category'])) $tb = $tb->where('category','=',$search['category']);
		return $tb->count();
	}

	public static function getInfo($batch_id)
	{
		$batch = self::db()->where('batch_id','=',$batch_id)->first();
		if(!$batch) return array();
		return $batch;
	}

	public static function getInfoByCode($batch_code)
	{
		$batch = self::db()->where('batch_code','=',$batch_code)->first();
		if(!$batch) return array();
		return $batch;
	}
	
    public static function m_search($search)
	{
		$tb = self::m_buildSearch($search);
		return $tb->orderBy('batch_id','desc')->get();
	}
	
	protected static function m_buildSearch($search)
	{
		$tb = self::db();
		if(isset($search['batch_code'])){
			$tb = $tb->where('batch_code','like','%'.$search['batch_code'].'%');
		}
		return $tb;
	}

	public static function save($data)
	{
		if(isset($data['batch_id']) && $data['batch_id']){
			$batch_id = $data['batch_id'];
			unset($data['batch_id']);
			$data['updated_at'] = time();
			return self::db()->where('batch_id','=',$batch_id)->update($data);
		}else{
			unset($data['batch_id']);
			$data['created_at'] = time();
			$data['updated_at'] = time();
			return self::db()->insertGetId($data);
		}
	}

	public static function del($batch_id)
	{
		if($batch_id > 0){
			$re = self::db()->where('batch_id','=',$batch_id)->delete();
		}
		return $re;
	}
}