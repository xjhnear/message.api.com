<?php
/**
 * Created by PhpStorm.
 * User: fujiajun
 * Date: 16/2/26
 * Time: 上午11:04
 */

namespace Youxiduo\MyService;

use Illuminate\Support\Facades\DB;
use Youxiduo\Base\Model;
use Youxiduo\V4\Game\GameService;
use Yxd\Services\Models\AppAdvActiveStat;

class StatisticsService extends Model
{
    public static $databas_table='';
    public function __construct()
    {

    }

    public static function Statistics($type='',$key='dateline',$Start='',$End=''){
           $result=array();
           switch($type){
                case 'App日新增':
                    $result=DB::select('SELECT count(*) as count FROM '.self::$databas_table.' WHERE  '.$key.' between  ? AND ?  order by null ',array($Start,$End));
                    break;

               case 'App日活跃':
                   $result=DB::select("SELECT count(*) as count FROM ".self::$databas_table." WHERE  $key between  ? AND ? order by null ",array($Start,$End));
                   break;

               case '我的游戏排行前十':
                   $result=DB::select("SELECT  game_id,count(uid) as count FROM ".self::$databas_table." WHERE 1=1 GROUP BY game_id ORDER BY count DESC LIMIT 10  ");
                   break;

               case '用户性别统计':
                   $result['1']=DB::select("SELECT count(*) as count FROM ".self::$databas_table." where sex=1 order by null ");
                   $result['2']=DB::select("SELECT count(*) as count FROM ".self::$databas_table." where sex=2 order by null ");
                   $result['0']=DB::select("SELECT count(*) as count FROM ".self::$databas_table." where sex=0 order by null ");
                   break;

               case '用户年龄统计':
                   $start=time();
                   $end=strtotime("-30 year");
                   $result['30']=DB::select("SELECT count(*) as count FROM ".self::$databas_table." WHERE  birthday between  ? AND ? order by null  ",array($end,$start));
                   $start=strtotime("-30 year");
                   $end=strtotime("-50 year");
                   $result['50']=DB::select("SELECT count(*) as count FROM ".self::$databas_table." WHERE  birthday between  ? AND ? order by null ",array($end,$start));
                   $start=strtotime("-50 year");
                   $end=strtotime("-80 year");
                   $result['80']=DB::select("SELECT count(*) as count FROM ".self::$databas_table." WHERE  birthday between  ? AND ?  order by null ",array($end,$start));
                   $start=strtotime("-80 year");
                   $end=strtotime("-100 year");
                   $result['100']=DB::select("SELECT count(*) as count FROM ".self::$databas_table." WHERE  birthday between  ? AND ? order by null  ",array($end,$start));
                  break;

               case '广告统计':
                   $result=DB::select("SELECT adv_logo,SUM(click) as clicksum FROM ".self::$databas_table." WHERE create_time >= ? AND create_time <= ? GROUP BY adv_logo order by null  ",array($Start,$End));

                   break;

               case '登录统计':
                   $result['3']=DB::select("select count(*) as count from ".self::$databas_table." where type > 0" );
                   $result['1']=DB::select("SELECT count(*) as count FROM  yxd_club_beta.yxd_account_session WHERE uid > 0");
                   break;
           }
//            $queries = DB::getQueryLog();
//            print_r($queries);
//            print_r($result);
           return $result;
    }



    protected static function buildSearch()
    {
        return DB::connection(self::$databas)->table(self::$table);
    }

    public static function statisticsCMSList($search=array()){

        $db = AppAdvActiveStat::db();
        $db->select($db->raw('count(*) as countNum, `type`, `aid`'));
        $search['startTime']    && $db->where('addtime', '>=', strtotime($search['startTime']));
        $search['endTime']      && $db->where('addtime', '<=', strtotime($search['endTime']));
        $search['aid']          && $db->where('aid', '=', $search['aid']);
        $search['type']         && $db->where('type', '=', $search['type']);
        $db->groupBy('type', 'aid')
            ->orderBy($db->raw('NULL'));
        return $db;
    }

    public static function statisticsCMSListbyAID($search=array()){
    
        $db = AppAdvActiveStat::db();
        $search['aid']          && $db->where('aid', '=', $search['aid']);
        $search['type']         && $db->where('type', '=', $search['type']);
        $db->orderBy($db->raw('NULL'));
        return $db->get();
    }
}