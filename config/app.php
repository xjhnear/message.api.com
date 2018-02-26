<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Application Debug Mode
	|--------------------------------------------------------------------------
	|
	| When your application is in debug mode, detailed error messages with
	| stack traces will be shown on every error that occurs within your
	| application. If disabled, a simple generic error page is shown.
	|
	*/

	'debug' => true,
    'openlog'=>true,
    'firephp'=>true,
    'close_cache' => true,
    'close_redis_user'=>true,
    'api'=>'http://47.100.101.44:6060',
    'domain'=>'youxiduo.com',
    'urlsecret'=>'ABCD1234YOUXIDUO',
    'urlsecret_311'=>'youxiduo$jk@55293',
	/*add by jk,2014-07-16 for test*/
	'test_urlsecret'=>'ABCD1234YOUXIDUO',
    'lion_token_key'=>'lionshare@po$jk@55293',
	 'apple_push_debug'=>false,
    'apple_push_pem'=> dirname(__FILE__) . '/push.pem',
    'apple_push_passphrase'=>'',
    'apple_push'=>array(
        array('apple_push_pem'=> dirname(__FILE__) . '/push_chaoren.pem','apple_push_passphrase'=>false),
        array('apple_push_pem'=> dirname(__FILE__) . '/push_jqb.pem','apple_push_passphrase'=>false),
        array('apple_push_pem'=> dirname(__FILE__) . '/push_20150129_1.pem','apple_push_passphrase'=>false),
        array('apple_push_pem'=> dirname(__FILE__) . '/push_20150129_2.pem','apple_push_passphrase'=>false),
        array('apple_push_pem'=> dirname(__FILE__) . '/push_20150129_3.pem','apple_push_passphrase'=>false),
        //array('apple_push_pem'=> dirname(__FILE__) . '/push_youxiduo.pem','apple_push_passphrase'=>false),
    ),
	/*
	|--------------------------------------------------------------------------
	| Application URL
	|--------------------------------------------------------------------------
	|
	| This URL is used by the console to properly generate URLs when using
	| the Artisan command line tool. You should set this to the root of
	| your application so that it is used when running Artisan tasks.
	|
	*/

	'url' => '',
    'img_url' => 'http://test.img.youxiduo.com',
    'image_url' => 'http://test.img.youxiduo.com',
	'test_image_url' => 'http://test.img.youxiduo.com',
    'image_activity_url' => 'http://action.api.youxiduo.com',
    'game_icon_path' => dirname(dirname(dirname(__DIR__))).'/yxd_www/bestofbest',
    'v4my_img_url' => 'http://112.124.121.34:58080/module_file_system/file/',

    'android_core_api_url'=>'http://112.124.121.34:28080/',
    'android_chat_api_url'=>'http://112.124.121.34:8080/chat/',
	'php_v4_module_api_url' => 'http://test.open.youxiduo.com/',
    'android_admin_huanxin_id'=>'yxdadmin1',
    'ios_core_api_url'=>'http://112.124.121.34:58080/',
    'ios_core_api_url_t'=>'http://112.124.121.34:38686/',
//    'ios_core_api_url'=>'http://android.api.youxiduo.com/',
    'module_data_url'=>'http://open.youxiduo.com:8080/',
    'mall_module_diamond'=>'http://112.124.121.34:48080/module_diamond',
    'module_product_url'=>'http://112.124.121.34:48080/',
    'virtual_card_url'=>'http://112.124.121.34:48080/module_virtualcard/',
    'bbs_api_url'=>'http://112.124.121.34:58080/module_forum/',
	'mall_api_url'=>'http://112.124.121.34:48080/module_mall/',
	'virtualcard_api_url'=>'http://112.124.121.34:48080/module_virtualcard/',

	'mall_rlt_api_url'=>'http://112.124.121.34:8080/module_relevance/',
	'android_module_promoter'=>'http://112.124.121.34:48080/',
	'android_module_share'=>'http://112.124.121.34:3080/',
    //'mall_mml_api_url'=>'http://ios40.open.youxiduo.com/module_mall/',
    'mall_mml_api_url'=>'http://112.124.121.34:48080/module_mall/',

    'message_api_url'=>'http://112.124.121.34:28888/module_message/',
    'message_lion_api_url'=>'http://test.api.shihou.tv/',
	'account_api_url'=>'http://112.124.121.34:48080/module_account/',
	'game_forum_api_url'=>'http://112.124.121.34:8080/module_relevance/',
	'bbs_base_path' => 'http://test.forum.youxiduo.com/',
	'android_phone_api_url'=>'http://test.api.youxiduo.com/android/',
    'android_api_url'=>'http://112.124.121.34:8080/', //http://android.api.youxiduo.com
    'push_api_url'=>'http://112.124.121.34:58080/service_push/', //http://android.api.youxiduo.com
    'push_api_url_new'=>'http://112.124.121.34:28888/ios_push/',
	'mobile_service_path' => 'http://test.www.youxiduo.com/service/',
	/**** 4.0新商城 IOS版接口地址 ****/
	//'ios_mall_api_url'=>'http://ios40.open.youxiduo.com/module_mall/',
    'ios_mall_api_url'=>'http://112.124.121.34:48080/module_mall/',
	'ios_mall_rlt_api_url'=>'http://112.124.121.34:8080/module_relevance/',
	//'ios_mall_mml_api_url'=>'http://121.40.78.19:8080/module_mall/',
	'ios_bbs_api_url'=>'http://112.124.121.34:58080/module_forum/',
	//'ios_virtual_card_url'=>'http://ios40.open.youxiduo.com/module_virtualcard/',
    'ios_virtual_card_url'=>'http://112.124.121.34:48080/module_virtualcard/',
	//IOS 4 活动接口
	'module_activity_api_url'=>'http://112.124.121.34:28888/module_activity/',
	/** 彩票管理系统 **/
	'module_lottery_api_url'=>'http://112.124.121.34:58080/module_lottery/',
	'module_account_api_url'=>'http://112.124.121.34:48080/',
	'module_wheel_api_url'=>'http://112.124.121.34:48080/module_wheel/',
	'material_api_url'=>'http://112.124.121.34:48080/module_material/',
    /* 360 游戏及应用API接入 */
    'plat360_path' => 'http://api.np.mobilem.360.cn',
    'from360' => 'lm_115448',
    'SecretKey' => '7d610b51df8f32b25073042f5f1971e0',
    /* 游戏电竞 */
    'ESports_api_url' => 'http://112.124.121.34:9081/',
    /* 游戏电竞 */
    'ESports_api_url2' => 'http://112.124.121.34:9081/',
    //勋章
    'API_MODULE_MEDAL_URL'=>'http://112.124.121.34:58080/module_medal/',
    //推广员
    'Tuiguang_api_url' => 'http://112.124.121.34:48080/',
    //v4share
    'V4share_api_url' => 'http://112.124.121.34:3080/',
//    'V4share_api_url' => 'http://youxiduo-java-slb-4:48080/module_share/',
    //联赛
    'liansai_api_url' => 'http://112.124.121.34:8011/esports-biz-1.2-SNAPSHOT/',
    //用户yb和钻石信息<48080>
    '48080_api_url' => 'http://112.124.121.34:48080/',
    //统计管理后台
    'api_module_task_url' => "http://112.124.121.34:58080/module_task/",
    //task
    'task_api_url' => "http://112.124.121.34:58080/module_task/",
    'task_lion_api_url' => "http://112.124.121.34:20017/task_distribute/",
    //task夏
//    'task_lion_api_url' => "http://192.168.40.230:20017/",
    'shihou_tv_api_url' => "http://api.shihou.tv/api/",
    'task_lion_api_url_local' => "http://192.168.40.200:20017/",
    //'task_lion_api_url_local' => "http://192.168.40.249:8080/task_distribute/",
    //发现及其它辅助功能
    'other_api_url' => "http://112.124.121.34:28888/module_adapter_other/",
    //杨浩翔-消息
    'MESSAGE_BASE_URL' => "http://112.124.121.34:28888/",
    //李陈毅-推送消息模版<58080>
    '58080_api_url' =>  'http://112.124.121.34:58080/',
    //杨浩翔-<28888>
    '28888_api_url' => "http://112.124.121.34:28888/",
    '18888_api_url' => "http://112.124.121.34:18888/",
    //李陈毅
    '8484_api_url' => "http://112.124.121.34:8348/",
    '11080_api_url' => "http://112.124.121.34:11080/",
    //
    'android_module_share' => "http://112.124.121.34:3080/",

    'neirong_api_url' => "http://112.124.121.34:8096/",
	//刘勇 天娱
	'8338_api_url'=>"http://112.124.121.34:8338/",

    'android_module_promoter' =>"http://112.124.121.34:48080/",

    '7069_api_url' => "http://112.124.121.34:7069/",

    '11080_api_url' => "http://112.124.121.34:11080/",
    //李陈毅 一元购
    '8089_api_url' => "http://112.124.121.34:8089/",
    //慈祥
    '11105_api_url' => "http://112.124.121.34:11105/",
//'8089_api_url' => "http://mobile.cwan.youxiduo.com:19080/",
    //师奇隆 H5活动
    'config_welfaregame_api_url' =>"http://112.124.121.34:20160/welfare/",
    //师奇隆 背包
    'backpack_api_url' =>"http://112.124.121.34:20160/module_knapsack/",
    //陈翔 百宝箱
    'box_api_url' =>"http://112.124.121.34:9999/guide_app_common/",
    //杨皓翔 监控
    'monitor_api_url' =>"http://112.124.121.34:18888/iosv4-service-control/",

    //慈祥 微信提现
    'wechat_api_url' => "http://112.124.121.34/wechatwithdraw_test/",
    
	/*		
	|--------------------------------------------------------------------------
	| Application Timezone
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default timezone for your application, which
	| will be used by the PHP date and date-time functions. We have gone
	| ahead and set this to a sensible default for you out of the box.
	|
	*/

	'timezone' => 'Asia/Shanghai',

	/*
	|--------------------------------------------------------------------------
	| Application Locale Configuration
	|--------------------------------------------------------------------------
	|
	| The application locale determines the default locale that will be used
	| by the translation service provider. You are free to set this value
	| to any of the locales which will be supported by the application.
	|
	*/

	'locale' => 'cn',

	/*
	|--------------------------------------------------------------------------
	| Encryption Key
	|--------------------------------------------------------------------------
	|
	| This key is used by the Illuminate encrypter service and should be set
	| to a random, 32 character string, otherwise these encrypted strings
	| will not be safe. Please do this before deploying an application!
	|
	*/

	'key' => 'zhonghuarenmingongheguo!',
    'verifycode'=>true,

    'oauth2'=>array(
        'driver'=>'redis',
        'pdo'=>array(
            'dsn'=>'mysql:dbname=yxd_club;host=localhost',
            'username'=>'yxd_club',
            'password'=>'zdETfuHtPtzMBAklKluF7Q=='
        ),
        'redis'=>array()
        
    ), 

	'huanxin' => array(
			'client_id'=>'YXA63IO58FsnEeS1RPu-EwfJhA',
			'client_secret'=>'YXA6s9DTMltbJ9Y3Jhk3QvxXfKvxBso',
			'org_name'=>'yxd',
			'app_name'=>'yxdtest1',
			'hxps' => '1234456',
			'appkey' => 'yxd#yxdtest1'
	),
	
	/*
	|--------------------------------------------------------------------------
	| Autoloaded Service Providers
	|--------------------------------------------------------------------------
	|
	| The service providers listed here will be automatically loaded on the
	| request to your application. Feel free to add your own services to
	| this array to grant expanded functionality to your applications.
	|
	*/

	'providers' => array(

		'Illuminate\Foundation\Providers\ArtisanServiceProvider',
		'Illuminate\Auth\AuthServiceProvider',
		'Illuminate\Cache\CacheServiceProvider',
		'Illuminate\Foundation\Providers\CommandCreatorServiceProvider',
		'Illuminate\Session\CommandsServiceProvider',
		'Illuminate\Foundation\Providers\ComposerServiceProvider',
		'Illuminate\Routing\ControllerServiceProvider',
		'Illuminate\Cookie\CookieServiceProvider',
		'Illuminate\Database\DatabaseServiceProvider',
		'Illuminate\Encryption\EncryptionServiceProvider',
		'Illuminate\Filesystem\FilesystemServiceProvider',
		'Illuminate\Hashing\HashServiceProvider',
		'Illuminate\Html\HtmlServiceProvider',
		'Illuminate\Foundation\Providers\KeyGeneratorServiceProvider',
		'Illuminate\Log\LogServiceProvider',
		'Illuminate\Mail\MailServiceProvider',
		'Illuminate\Foundation\Providers\MaintenanceServiceProvider',
		'Illuminate\Database\MigrationServiceProvider',
		'Illuminate\Foundation\Providers\OptimizeServiceProvider',
		'Illuminate\Pagination\PaginationServiceProvider',
		'Illuminate\Foundation\Providers\PublisherServiceProvider',
		'Illuminate\Queue\QueueServiceProvider',
		'Illuminate\Redis\RedisServiceProvider',
		'Illuminate\Auth\Reminders\ReminderServiceProvider',
		'Illuminate\Foundation\Providers\RouteListServiceProvider',
		'Illuminate\Database\SeedServiceProvider',
		'Illuminate\Foundation\Providers\ServerServiceProvider',
		'Illuminate\Session\SessionServiceProvider',
		'Illuminate\Foundation\Providers\TinkerServiceProvider',
		'Illuminate\Translation\TranslationServiceProvider',
		'Illuminate\Validation\ValidationServiceProvider',
		'Illuminate\View\ViewServiceProvider',
		'Illuminate\Workbench\WorkbenchServiceProvider',
        'TwigBridge\TwigServiceProvider',
        'LucaDegasperi\OAuth2Server\OAuth2ServerServiceProvider',
        'Mews\Captcha\CaptchaServiceProvider',
        'Yxd\Events\EventHandlerProvider',
	),

	/*
	|--------------------------------------------------------------------------
	| Service Provider Manifest
	|--------------------------------------------------------------------------
	|
	| The service provider manifest is used by Laravel to lazy load service
	| providers which are not needed for each request, as well to keep a
	| list of all of the services. Here, you may set its storage spot.
	|
	*/

	'manifest' => storage_path().'/meta',

	/*
	|--------------------------------------------------------------------------
	| Class Aliases
	|--------------------------------------------------------------------------
	|
	| This array of class aliases will be registered when this application
	| is started. However, feel free to register as many as you wish as
	| the aliases are "lazy" loaded so they don't hinder performance.
	|
	*/

	'aliases' => array(

		'App'             => 'Illuminate\Support\Facades\App',
		'Artisan'         => 'Illuminate\Support\Facades\Artisan',
		'Auth'            => 'Illuminate\Support\Facades\Auth',
		'Blade'           => 'Illuminate\Support\Facades\Blade',
		'Cache'           => 'Illuminate\Support\Facades\Cache',
		'ClassLoader'     => 'Illuminate\Support\ClassLoader',
		'Config'          => 'Illuminate\Support\Facades\Config',
		'Controller'      => 'Illuminate\Routing\Controllers\Controller',
		'Cookie'          => 'Illuminate\Support\Facades\Cookie',
		'Crypt'           => 'Illuminate\Support\Facades\Crypt',
		'DB'              => 'Illuminate\Support\Facades\DB',
		'Eloquent'        => 'Illuminate\Database\Eloquent\Model',
		'Event'           => 'Illuminate\Support\Facades\Event',
		'File'            => 'Illuminate\Support\Facades\File',
		'Form'            => 'Illuminate\Support\Facades\Form',
		'Hash'            => 'Illuminate\Support\Facades\Hash',
		'HTML'            => 'Illuminate\Support\Facades\HTML',
		'Input'           => 'Illuminate\Support\Facades\Input',
		'Lang'            => 'Illuminate\Support\Facades\Lang',
		'Log'             => 'Illuminate\Support\Facades\Log',
		'Mail'            => 'Illuminate\Support\Facades\Mail',
		'Paginator'       => 'Illuminate\Support\Facades\Paginator',
		'Password'        => 'Illuminate\Support\Facades\Password',
		'Queue'           => 'Illuminate\Support\Facades\Queue',
		'Redirect'        => 'Illuminate\Support\Facades\Redirect',
		'Redis'           => 'Illuminate\Support\Facades\Redis',
		'Request'         => 'Illuminate\Support\Facades\Request',
		'Response'        => 'Illuminate\Support\Facades\Response',
		'Route'           => 'Illuminate\Support\Facades\Route',
		'Schema'          => 'Illuminate\Support\Facades\Schema',
		'Seeder'          => 'Illuminate\Database\Seeder',
		'Session'         => 'Illuminate\Support\Facades\Session',
		'Str'             => 'Illuminate\Support\Str',
		'URL'             => 'Illuminate\Support\Facades\URL',
		'Validator'       => 'Illuminate\Support\Facades\Validator',
		'View'            => 'Illuminate\Support\Facades\View',
	    'AuthorizationServer' => 'LucaDegasperi\OAuth2Server\Facades\AuthorizationServerFacade',
        'ResourceServer' => 'LucaDegasperi\OAuth2Server\Facades\ResourceServerFacade',
	    'Captcha' => 'Mews\Captcha\Facades\Captcha',
	    'HttpQueue' => 'HTTPSQS\Queue',

	),

);
