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

/*-------------------------------系统-----------------------------*/
//检查更新√
Route::any('app/upgrade{symbol}',array('before'=>'uri_verify','uses'=>'AppController@upGrade'));

/*-------------------------------用户-------------------------------*/
//登录√
Route::any('user/login{symbol}',array('before'=>'uri_verify','uses'=>'UserController@login'));
//注册、忘记密码、更换绑定手机
Route::any('user/register{symbol}',array('uses'=>'UserController@register'));
//意见反馈
Route::any('user/feedback{symbol}',array('before'=>'uri_verify','uses'=>'UserController@feedback'));
//获取用户信息
Route::any('user/info{symbol}',array('before'=>'uri_verify','uses'=>'UserController@info'));
//用户资料编辑
Route::any('user/edit{symbol}',array('before'=>'uri_verify','uses'=>'UserController@edit'));
//首页获取用户状态
Route::any('user/identification{symbol}',array('before'=>'uri_verify','uses'=>'UserController@identification'));
//认证--上传视频
Route::any('user/identify',array('uses'=>'UserController@identify'));
//更新认证结果
Route::any('user/identifyrefresh',array('uses'=>'UserController@identifyRefresh'));

//获取验证码
Route::any('sms/verify{symbol}',array('before'=>'uri_verify','uses'=>'UserController@smsVerify'));

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

