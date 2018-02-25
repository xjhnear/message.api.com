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
final class AccountDetail extends Model implements IModel
{
	public static function getClassName()
	{
		return __CLASS__;
	}

	public static function getInfo($channel_id)
	{
		$info = self::db()->where('channel_id','=',$channel_id)->where('is_del','=',0)->where('status','=',1)->orderBy('channel_id','asc')->first();
		return $info;
	}

	public static function getList()
	{
		$info = self::db()->where('is_del','=',0)->where('status','=',1)->orderBy('channel_id','asc')->get();
		return $info;
	}

}