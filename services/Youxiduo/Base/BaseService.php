<?php
/**
 * @package Youxiduo
 * @category Base 
 * @link http://dev.youxiduo.com
 * @copyright Copyright (c) 2008 Youxiduo.com 
 * @license http://www.youxiduo.com/license
 * @since 4.0.0
 *
 */

namespace Youxiduo\Base;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Input;

/**
 * 服务类
 * 
 * @author mawenpei
 * @version 4.0.0
 */
abstract class BaseService
{
	
	public static function trace_result($res=array())
	{
	    $openlog = true;
		//程序执行性能日志
		$time = round((microtime(true) - LARAVEL_START)*1000,2);
		$time = number_format($time,4,'.','');		
		if($openlog==true){
			$content = date('Y-m-d H:i:s') . ' ' . Request::getPathInfo() . ' ' . $time . 'ms ' . Request::getUri() . "\r\n";		
			$file = storage_path() . '/logs/' . 'android-pro-' . date('Y-m-d-H') . '.txt';
			file_put_contents($file,$content,FILE_APPEND);
		}
		$res['errorCode'] = 0;
		$callback = Input::get('callback');
		if($callback){
		    return Response::json($res)->setCallback($callback);
		}
		return Response::json($res);
	}
	
	public static function trace_error($code='E1',$message='')
	{
		
		$error = array('errorCode'=>1,'message'=>$message);
		return Response::json($error);
	}

	

}	