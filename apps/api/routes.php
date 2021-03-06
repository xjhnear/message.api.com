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

//发送
Route::any('api/sms{symbol}',array('before'=>'uri_verify','uses'=>'ApiController@sms'));

//余额及已发送量查询
Route::any('api/overage{symbol}',array('before'=>'uri_verify','uses'=>'ApiController@overage'));

//状态报告
Route::any('api/status{symbol}',array('before'=>'uri_verify','uses'=>'ApiController@status'));

//上行
Route::any('api/call{symbol}',array('before'=>'uri_verify','uses'=>'ApiController@call'));

/*-------------------------------系统-----------------------------*/
//短信发送
Route::any('system/sms{symbol}',array('before'=>'uri_verify','uses'=>'SystemController@sms'));

//获取状态报告
Route::any('system/getstatus{symbol}',array('before'=>'uri_verify','uses'=>'SystemController@getstatus'));
//处理状态报告
Route::any('system/dostatus{symbol}',array('before'=>'uri_verify','uses'=>'SystemController@dostatus'));
//处理状态报告(暗改)
Route::any('system/dostatusdark{symbol}',array('before'=>'uri_verify','uses'=>'SystemController@dostatusdark'));
//手动处理文件状态报告
Route::any('system/statushand{symbol}',array('before'=>'uri_verify','uses'=>'SystemController@statushand'));

//日报
Route::any('system/report{symbol}',array('before'=>'uri_verify','uses'=>'SystemController@report'));

//上行
Route::any('system/call{symbol}',array('before'=>'uri_verify','uses'=>'SystemController@call'));

//超时
Route::any('system/timeout{symbol}',array('before'=>'uri_verify','uses'=>'SystemController@timeout'));

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

