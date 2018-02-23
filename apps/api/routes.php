<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
/*-------------------------------认证-------------------------------*/

Route::pattern('symbol', '[\/]?');

/*-------------------------------接口-----------------------------*/
//非法关键词查询
Route::any('api/checkkeyword{symbol}',array('before'=>'uri_verify','uses'=>'ApiController@checkkeyword'));


/*-------------------------------系统-----------------------------*/
//短信发送
Route::any('system/sms{symbol}',array('before'=>'uri_verify','uses'=>'SystemController@sms'));

//状态报告
Route::any('system/status{symbol}',array('before'=>'uri_verify','uses'=>'SystemController@status'));

/*
App::missing(function($exception){
	return Response::json(array('result'=>array(),'errorCode'=>11211,'errorMessage'=>'Page Is Not Exists!!'));
});
*/
/*
App::error(function($exception){
    return Response::json(array('result'=>array(),'errorCode'=>11211,'errorMessage'=>'Server Error!!'));
});
*/

