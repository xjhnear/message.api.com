<?php
use Yxd\Modules\System\ProfileService;
use Illuminate\Routing\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

use LucaDegasperi\OAuth2Server\Proxies\AuthorizationServerProxy;
use LucaDegasperi\OAuth2Server\Facades\AuthorizationServerFacade as AuthorizationServer;
use League\OAuth2\Server\Exception\ClientException;


class BaseController extends Controller 
{
	
	public function send($status=200,$data,$error_code='',$error_description='')
	{
		$format = Input::get('format','json');
		
		if(!in_array($format,array('json','jsonp','xml','text'))){
			$format = 'json';
		}		
		if($format == 'jsonp'){
			$callback = Input::get('callback');
			$_d = array('status'=>$status,'error_code'=>$error_code,'error_description'=>$error_description,'data'=>$data);
			return Response::json($_d)->setCallback($callback);
		}elseif($format == 'json'){
			$_d = array('status'=>$status,'error_code'=>$error_code,'error_description'=>$error_description,'data'=>$data);
			return Response::json($_d);
		}elseif($format=='xml'){
			
		}elseif($format=='text'){
			
		}
	}	
	
	public function success($data=array())
	{
		if(!is_array($data)) $data = array();
		$result = array_merge(array('status'=>'200','message'=>'成功'),$data);
		array_walk_recursive($result,function(&$val,$key){
		    if(!is_array($val)){
		    	if(is_numeric($val)) $val = strval($val);
		    }
		});
		$this->profile();
		return Response::json($result);
	}
	
	public function fail($errorCode,$errorMsg)
	{
		$data = array('status'=>$errorCode,'message'=>$errorMsg);
		//$this->profile();
		return Response::json($data);
	}
	
	protected function joinImgUrl($img)
	{
		if(!$img) return '';
		if(strpos($img,'http')===0) return $img;
		$last = '';
		//if(strpos($img,'avatar')!==false) $last = '?time=' . time();
		return 'http://img.youxiduo.com' . $img . $last;
	}
	
	protected function getComVersion()
	{
		$version = Input::get('version','3.0.0');
		$route_name = Route::currentRouteName();
		$route_name = trim($route_name,'/');
		$route_name = str_replace('{symbol}','',$route_name);
		$compatibility_vers = Config::get($route_name);
		if($compatibility_vers && is_array($compatibility_vers) && in_array($version,$compatibility_vers)){
			return '3.0.0';
		}
		return $version;
	}
	
    protected function checkAccessToken($accessToken)
	{
		return AuthorizationServer::getGrantType('authorization_code')->verifyAccessToken(array('access_token'=>$accessToken));
	}
	/**
	 * 获取当前登录用户的UID
	 */
	protected function getCurrentUid()
	{
		$accessToken = Input::get('access_token');
		$token = $this->checkAccessToken($accessToken);
		if($token==false){
			return 0;
		}else{
			return $token['uid'];
		}
	}
	
	protected function profile()
	{
		//$firephp = Config::get('app.firephp',true);
		$openlog = Config::get('app.openlog',true);
		//程序执行性能日志
		$time = round((microtime(true) - LARAVEL_START)*1000,2);
                if($time<2000) return;
		$time = number_format($time,4,'.','');		
		$fb = App::make('firephp');
		$fb->info('profile:' . '' . $time . 'ms' . ' ' .  Request::getUri());
		if($openlog==true){
			$content = date('Y-m-d H:i:s') . ' ' . Request::getPathInfo() . ' ' . $time . 'ms ' . Request::getUri() . "\r\n";		
			$file = storage_path() . '/logs/' . 'profile-apache2handler-' . date('Y-m-d-H') . '.txt';
			file_put_contents($file,$content,FILE_APPEND);
	        //SQL操作性能日志
	        //ProfileService::add($content);
	        //ProfileService::save();
		}
        //$end_memory = memory_get_usage();
        //$real_memory = $end_memory - Laravel_START_MEMORY;
        //$fb->info('memory:' . $real_memory/1024 . 'KB');		
	}
}
