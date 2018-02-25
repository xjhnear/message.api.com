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
final class Account extends Model implements IModel
{
	public static function getClassName()
	{
		return __CLASS__;
	}

    public static function save($data)
    {
        if(isset($data['account_id']) && $data['account_id']){
            $account_id = $data['account_id'];
            unset($data['account_id']);
            return self::db()->where('account_id','=',$account_id)->update($data);
        }else{
            unset($data['account_id']);
            return self::db()->insertGetId($data);
        }
    }

}