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
namespace Yxd\Utility;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Lang;

class OutLog
{
	public static function outSuccess($result,$append=array())
	{
		$openlog = true;
		//程序执行性能日志
		$time = round((microtime(true) - LARAVEL_START)*1000,2);
		$time = number_format($time,4,'.','');
		if($openlog==true){
			$content = date('Y-m-d H:i:s') . ' ' . Request::getPathInfo() . ' ' . $time . 'ms ' . Request::getUri().json_encode($result) . "\r\n";
			$file = storage_path() . '/logs/' . 'android-pro-' . date('Y-m-d-H') . '.txt';
			file_put_contents($file,$content,FILE_APPEND);
		}
		$res = array('errorCode'=>0,'errorDescription'=>'','result'=>$result);
		if($append && is_array($append)){
			$res = array_merge($res,$append);
		}
		$callback = Input::get('callback');
		if($callback){
		    return Response::json($res)->setCallback($callback);
		}else{
			return Response::json($res);
		}
	}
	
	public static function outError($error_code,$error_id)
	{
		if(Lang::has('out_error.'.$error_id)){
		    $message = Lang::get('out_error.'.$error_id);
		}else{
			$message = $error_id;
		}
		$error = array('errorCode'=>$error_code,'errorDescription'=>$message);
		return Response::json($error);
	}

    public static function outLog($result)
    {
        $openlog = true;
        //程序执行性能日志
        $time = round((microtime(true) - LARAVEL_START)*1000,2);
        $time = number_format($time,4,'.','');
        if($openlog==true){
            $content = date('Y-m-d H:i:s') . ' ' . Request::getPathInfo() . ' ' . $time . 'ms ' . Request::getUri().json_encode($result) . "\r\n";
            $file = storage_path() . '/logs/' . 'php-' . date('Y-m-d-H') . '.txt';
            file_put_contents($file,$content,FILE_APPEND);
        }
    }
}