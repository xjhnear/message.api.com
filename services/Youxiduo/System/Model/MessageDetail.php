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
namespace Youxiduo\System\Model;

use Youxiduo\Base\Model;
use Youxiduo\Base\IModel;

use Youxiduo\Helper\Utility;
/**
 * 账号模型类
 */
final class MessageDetail extends Model implements IModel
{
	public static function getClassName()
	{
		return __CLASS__;
	}

	public static function getList($search,$pageIndex=1,$pageSize=20)
	{
		$tb = self::db();
		if(isset($search['message_id']) && !empty($search['message_id'])) $tb = $tb->where('message_id','=',$search['message_id']);
		if(isset($search['operator']) && !empty($search['operator'])) $tb = $tb->where('operator','=',$search['operator']);
		return $tb->orderBy('message_did','desc')->forPage($pageIndex,$pageSize)->get();
	}

	public static function getCount($search)
	{
		$tb = self::db();
		if(isset($search['message_id']) && !empty($search['message_id'])) $tb = $tb->where('message_id','=',$search['message_id']);
		if(isset($search['operator']) && !empty($search['operator'])) $tb = $tb->where('operator','=',$search['operator']);
		return $tb->count();
	}

}