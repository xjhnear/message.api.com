<?php

/*
|--------------------------------------------------------------------------
| Register The Laravel Class Loader
|--------------------------------------------------------------------------
|
| In addition to using Composer, you may use the Laravel class loader to
| load your controllers and models. This is useful for keeping all of
| your classes in the "global" namespace without Composer updating.
|
*/

ClassLoader::addDirectories(array(
    app_path() . '/commands',
	app_path().'/controllers',
	app_path().'/controllers/v3',
	app_path().'/models',
	base_path().'/libraries',

));

/*
|--------------------------------------------------------------------------
| Application Error Logger
|--------------------------------------------------------------------------
|
| Here we will configure the error logger setup for the application which
| is built on top of the wonderful Monolog library. By default we will
| build a rotating log file setup which creates a new file each day.
|
*/

define('CLOSE_CACHE',Config::get('app.close_cache', true));

App::singleton('firephp', function ()
{
	$fb = new FirePHP;
	$fb->setEnabled(Config::get('app.firephp', true));

	return $fb;
});

Event::listen('illuminate.log', function ($type, $message,$context=null)
{
	if($context) $message = $context;
	$fb = App::make('firephp');
	if(in_array($type,array('info','error','waring','log'))){
		$fb->{$type}($message);
	}
	//$fb->log($message);
});

Event::listen('illuminate.query', function ($sql, $bindings, $time)
{
	$time = number_format($time,4,'.','');
	$fb = App::make('firephp');
	$sql = vsprintf(str_replace('?','"%s"',$sql), $bindings);
	$fb->info($sql,$time . 'ms');
	$sql_line = 'sql:' . $time . ' ' . $sql . "\r\n";
	Yxd\Modules\System\ProfileService::add($sql_line);
});


$logFile = 'log-open-'.php_sapi_name().'.txt';

Log::useDailyFiles(storage_path().'/logs/'.$logFile);

/*
|--------------------------------------------------------------------------
| Application Error Handler
|--------------------------------------------------------------------------
|
| Here you may handle any errors that occur in your application, including
| logging them or displaying custom views for specific errors. You may
| even register several error handlers to handle different types of
| exceptions. If nothing is returned, the default error view is
| shown, which includes a detailed stack trace during debug.
|
*/

App::error(function(Exception $exception, $code)
{
	Log::error($exception);
//	return Response::json(array('status'=>203,'message'=>'Server Error!!'));
});

/*
|--------------------------------------------------------------------------
| Maintenance Mode Handler
|--------------------------------------------------------------------------
|
| The "down" Artisan command gives you the ability to put an application
| into maintenance mode. Here, you will define what is displayed back
| to the user if maintenace mode is in effect for this application.
|
*/

App::down(function()
{
	return Response::make("Be right back!", 503);
});

/*
|--------------------------------------------------------------------------
| Require The Filters File
|--------------------------------------------------------------------------
|
| Next we will load the filters file for the application. This gives us
| a nice separate location to store our route and application filter
| definitions instead of putting them all in the main routes file.
|
*/

require app_path().'/filters.php';
