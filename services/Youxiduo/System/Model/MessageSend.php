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
final class MessageSend extends Model implements IModel
{
	public static function getClassName()
	{
		return __CLASS__;
	}

	public static function save($data)
	{
		if(isset($data['message_sid']) && $data['message_sid']){
			$message_sid = $data['message_sid'];
			unset($data['message_sid']);
			return self::db()->where('message_sid','=',$message_sid)->update($data);
		}else{
			unset($data['message_sid']);
			return self::db()->insertGetId($data);
		}
	}

}