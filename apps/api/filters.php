<?php
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function($request)
{
	//
});


App::after(function($request, $response)
{
	//
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

Route::filter('auth', function()
{
	if (Auth::guest()) return Redirect::guest('login');
});


Route::filter('auth.basic', function()
{
	return Auth::basic();
});

Route::filter('uri_verify',function(){
    $input = array_merge($_GET,$_POST);
    $source = Input::get('source');
    unset($input['source']);
    //ksort($input);
    
    $keys = array_keys($input);
	natcasesort($keys);
	$params = array();
	foreach($keys as $k){
	    $params[$k] = $input[$k];
	}
	
    $query = '';    
    foreach($params as $k=>$v){
    	$query .= $k.'='.$v.'&';
    }
    
    if(isset($input['version'])){
        if(version_compare($input['version'],'3.5.0') == 0){
            $query .= 'key=' . Config::get('app.urlmd5secret');   
        }elseif(version_compare($input['version'],'3.4.0') == 0){
            $query .= 'key=' . Config::get('app.url340secret');
        }elseif(version_compare($input['version'],'3.5.1') == 0){
            $query .= 'key=' . Config::get('app.url351secret');
        }elseif($input['version']=='3.6.0'){
            $query .= 'key=' . Config::get('app.url360secret');
        }else{
            $query .= 'key=' . Config::get('app.urlsecret');
        }
    }else{
        $query .= 'key=' . Config::get('app.urlsecret');
    }
	
    $secret = md5($query);
//    if($secret != $source){
//    	$error = serialize($input) . "\r\n";
//    	$error .= $source . "\r\n";
//    	//Log::error($error);
//    	return Response::json(array('result'=>'','errorCode'=>'11211','errorMessage'=>'接口验证错误'));
//    }
});

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest', function()
{
	if (Auth::check()) return Redirect::to('/');
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function()
{
	if (Session::token() != Input::get('_token'))
	{
		throw new Illuminate\Session\TokenMismatchException;
	}
});
