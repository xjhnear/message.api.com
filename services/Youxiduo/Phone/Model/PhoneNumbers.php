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
final class PhoneNumbers extends Model implements IModel
{		
    public static function getClassName()
	{
		return __CLASS__;
	}

	public static function getList($search,$pageIndex=1,$pageSize=20)
	{
		$tb = self::db();
		if(isset($search['batch_id']) && !empty($search['batch_id'])) $tb = $tb->where('batch_id','=',$search['batch_id']);
		return $tb->orderBy('num_id','desc')->forPage($pageIndex,$pageSize)->get();
	}

	public static function getCount($search)
	{
		$tb = self::db();
		if(isset($search['batch_id']) && !empty($search['batch_id'])) $tb = $tb->where('batch_id','=',$search['batch_id']);
		return $tb->count();
	}

	public static function save($data)
	{
		if(isset($data['num_id']) && $data['num_id']){
			$batch_id = $data['num_id'];
			unset($data['num_id']);
			$data['updated_at'] = time();
			return self::db()->where('num_id','=',$batch_id)->update($data);
		}else{
			unset($data['num_id']);
			$data['created_at'] = time();
			$data['updated_at'] = time();
			return self::db()->insertGetId($data);
		}
	}

	public static function delByBatchId($batch_id)
	{
		if($batch_id > 0){
			$re = self::db()->where('batch_id','=',$batch_id)->delete();
		}
		return $re;
	}
}