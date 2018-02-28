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
use Illuminate\Support\Facades\DB;

use Youxiduo\Helper\Utility;
/**
 * 账号模型类
 */
final class MessageCall extends Model implements IModel
{
	public static function getClassName()
	{
		return __CLASS__;
	}

    public static function getList($phonenumber)
    {
        $info = self::db()->where('phonenumber','=',$phonenumber)->orderBy('message_cid','asc')->get();
        return $info;
    }

}