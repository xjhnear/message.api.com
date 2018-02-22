<?php
/**
 * @package Youxiduo
 * @category IOS 
 * @link http://dev.youxiduo.com
 * @copyright Copyright (c) 2008 Youxiduo.com 
 * @license http://www.youxiduo.com/license
 * @since 3.0.0
 *
 */
namespace Yxd\Services\Models;

use Youxiduo\Base\Model;
use Youxiduo\Base\IModel;

use Youxiduo\Helper\Utility;
/**
 * 模型类
 */
final class Task extends Model implements IModel
{		
    public static function getClassName()
	{
		return __CLASS__;
	}
}