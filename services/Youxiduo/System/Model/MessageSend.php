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
final class MessageSend extends Model implements IModel
{
	public static function getClassName()
	{
		return __CLASS__;
	}

	public static function getInfo($phonenumber,$task_id)
	{
		$info = self::db()->where('phonenumber','=',$phonenumber)->where('task_id','=',$task_id)->where('status','=',0)->orderBy('message_sid','asc')->first();
		return $info;
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

    public static function getList($message_id)
    {
        $info = self::db()->where('message_id','=',$message_id)->orderBy('message_sid','asc')->get();
        return $info;
    }

    public static function getCount($uid)
    {
        $info = self::db()->where('uid','=',$uid)->orderBy('message_sid','asc')->count();
        return $info;
    }

    public static function getListToday()
    {
        $start = mktime(0,0,0,date("m"),date("d"),date("Y"));
        $end = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
        $info = self::db()->select(DB::raw('count(*) as count, uid'))->whereBetween('create_time', [$start, $end])->groupBy('uid')->get();
        return $info;
    }

    public static function getSuccessListToday()
    {
        $start = mktime(0,0,0,date("m"),date("d"),date("Y"));
        $end = mktime(0,0,0,date("m"),date("d")+1,date("Y"));
        $info = self::db()->select(DB::raw('count(*) as count, uid'))->whereBetween('create_time', [$start, $end])->where('status','=',10)->groupBy('uid')->get();
        return $info;
    }
}