<?php
/**
 * Created by PhpStorm.
 * User: Cody
 * Date: 2015/3/3
 * Time: 11:53
 */
namespace Youxiduo\Cache;
use Youxiduo\Base\BaseService;
use Youxiduo\Helper\Utility;
use Illuminate\Support\Facades\Config;



class CacheService extends BaseService
{
    const API_URL_CONF = 'app.mall_api_url';
    const RLT_URL_CONF = 'app.mall_rlt_api_url';
    const BBS_API_URL = 'app.ios_bbs_api_url';
    const VIRTUAL_CARD_URL = 'app.virtual_card_url'; //http://121.40.78.19:18080/module_virtualcard/
    const MALL_MML_API_URL = 'app.mall_mml_api_url';//http://121.40.78.19:8080/module_mall/
    const MALL_API_ACCOUNT = 'app.account_api_url';  //http://121.40.78.19:8080/module_account/
    const CACHE_API_URL = 'app.18888_api_url';

    public static function cache_del_key($type =4)
    {
        $params['type'] = $type;
        return Utility::loadByHttp( Config::get(self::CACHE_API_URL).'iosv4-service-control/cache/del_key',$params);
    }

    public static function cache_add_type_count($gid = '0',$categoryId = '')
    {
        $params['gid'] = $gid;
        $params['categoryId'] = $categoryId;
        return Utility::loadByHttp(Config::get(self::CACHE_API_URL).'iosv4-service-control/cache/add_type_count',$params);
    }

    public static function cache_add_type_count_act($gid = '0',$type='game_activity')
    {
        $params['gid'] = $gid;
        $params['type'] = $type;
        return Utility::loadByHttp(Config::get(self::CACHE_API_URL).'iosv4-service-control/cache/add_type_count',$params);
    }

    public static function cache_add_type_count_gif($gid = '0',$type='game_gift')
    {
        $params['gid'] = $gid;
        $params['type'] = $type;
        return Utility::loadByHttp(Config::get(self::CACHE_API_URL).'iosv4-service-control/cache/add_type_count',$params);
    }

    public static function cache_add_type_count_iostask($gid = '0',$type='game_task')
    {
        $params['gid'] = $gid;
        $params['type'] = $type;
        return Utility::loadByHttp(Config::get(self::CACHE_API_URL).'iosv4-service-control/cache/add_type_count',$params);
    }
}