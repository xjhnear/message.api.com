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
namespace Youxiduo\User\Model;

use Youxiduo\Base\Model;
use Youxiduo\Base\IModel;
/**
 * 文章搜索模型类
 */
final class MobileSmsHistory extends Model implements IModel
{	
    public static function getClassName()
	{
		return __CLASS__;
	}
	
	public static function checkSmsNumber($mobile,$ip)
	{
		$today = mktime(0,0,0,date('m'),date('d'),date('Y'));
		$mobile_num = self::db()->where('mobile','=',$mobile)->where('sendtime','>',$today)->count();
		$ip_num = self::db()->where('ip','=',$ip)->where('sendtime','>',$today)->count();
		if($mobile_num>=10 || $ip_num>=10){
			return false;
		}else{
			$run = true;
			while($run){
				$max = (int)self::db()->max('flag');			
				$data = array('mobile'=>$mobile,'sendtime'=>time(),'flag'=>$max+1,'ip'=>$ip);
				$res = self::db()->insert($data);
				if($res>0) $run=false; 
				$mobile_num = self::db()->where('mobile','=',$mobile)->where('sendtime','>',$today)->count();
		        $ip_num = self::db()->where('ip','=',$ip)->where('sendtime','>',$today)->count();
				if($mobile_num>=10 || $ip_num>=10) return false;
			}
			return true;
		}
	}
}