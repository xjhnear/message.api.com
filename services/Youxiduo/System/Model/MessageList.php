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
final class MessageList extends Model implements IModel
{
	public static function getClassName()
	{
		return __CLASS__;
	}

	public static function getInfoById($message_id)
	{
		$info = self::db()->where('message_id','=',$message_id)->first();
		return $info;
	}

	public static function save($data)
	{
		if(isset($data['message_id']) && $data['message_id']){
			$message_id = $data['message_id'];
			unset($data['message_id']);
			return self::db()->where('message_id','=',$message_id)->update($data);
		}else{
			unset($data['message_id']);
			return self::db()->insertGetId($data);
		}
	}

}