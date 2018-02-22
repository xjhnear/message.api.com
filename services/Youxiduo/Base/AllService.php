<?php
namespace Youxiduo\Base;

use Youxiduo\Base\BaseService;
use Youxiduo\Helper\Utility;
use Illuminate\Support\Facades\Config;
use Youxiduo\Helper\BaseHttp;

class   AllService extends BaseService{


    public static $arr_url = array(
        'LS' => "app.liansai_api_url",//联赛
        'USER' => "app.48080_api_url",//编辑游币和钻石http://121.40.78.19:48080/
        'PUSH' => "app.58080_api_url",//李陈毅/消息推送
        '48080' => "app.48080_api_url",//徐毅迅
        '58080' => "app.58080_api_url",//李陈毅&波波
        '28888'  => "app.28888_api_url", //杨浩翔
        '8484'  => "app.8484_api_url", //李陈毅，
        'NR' => "app.neirong_api_url",
        'android' => 'app.android_phone_api_url',
        '8089'  => 'app.8089_api_url',//李陈毅,IOS宁金强
        '11105'=> 'app.11105_api_url',//慈祥
        '8338'=> 'app.8338_api_url',//刘勇
        'jinyu'=> 'app.jinyu_api_url',//刘勇
    );

    public static $arr = array(
        //联赛  接口写法
        'event/list' => array("esport/event/listByGameId",'POST'),
        'event/getEventInfoById' => array("esport/event/getEventInfoById",'POST'),
        'event/add' => array("esport/event/add",'POST'),
        'event/update' => array("esport/event/update",'POST'),
        'event/updateEventStatus' => array("esport/event/updateEventStatus",'POST'),
        'event/eventSwitcher' => array("esport/event/eventSwitcher",'POST'),
        'event/update' => array("esport/event/update",'POST'),
        'event/updateEventSign' => array("esport/event/updateEventSign",'POST'),
        'team' => array("esport/team/webListByEventId",'POST'),//赛事队伍列表
        'team/findById' => array("esport/team/getDetailByTeamIdAndEventId",'POST'),
        'match/listByEventId' => array("esport/match/listByEventId",'POST'),
        'confirmMatchResult' => array("esport/match/confirmMatchResult",'POST'),
        'dismissTeam' => array("esport/team/dismissTeam",'POST'),
        'game/listForWeb' => array("esport/game/listForWeb",'POST'),
        'game/findById' => array("esport/game/findById",'POST'),
        'game/add' => array("esport/game/add",'POST'),
        'game/updateInfo' => array("esport/game/updateInfo",'POST'),
        //user
        'updateaccount' => array("module_account/account/updatebalance",'GET'),
        'updatediamond' => array("module_rmb/account/updatebalance",'GET'),
        'account/query' => array("module_rmb/account/query",'GET'),
        //PUSH
        'GetPushMessageTemplateList' => array("service_push/GetPushMessageTemplateList",'GET'),
        'AddPushMessageTemplate' => array("service_push/AddPushMessageTemplate",'POST'),
        'UpdatePushMessageTemplate' => array("service_push/UpdatePushMessageTemplate",'POST'),
        //IOS-屏蔽消息-28888
        'get_key_filter_list' => array("module_adapter_other/key/get_key_filter_list",'GET'),
        'save_update_key_filter' => array("module_adapter_other/key/save_update_key_filter",'POST'),
        //活动
        'activity/batchExportIndex' => array("pchd/activity/batchExportIndex",'GET'),
        'activity/CreateGame'=>array("pchdbackstage/CreateGame",'POST'),
        'activity/UpdateGame'=>array("pchdbackstage/UpdateGame",'POST'),
        'activity/QueryGame' => array("pchdbackstage/QueryGame",'GET'),
        'activity/RemoveGame'=>array("pchdbackstage/RemoveGame",'POST'),
        'activity/Export'=>array("pchd/Export",'GET'),
        //内容流
        'rss/update'=>array("rss/update",'POST'),
        'rss/add'=>array("rss/add",'POST'),
        'rss/list'=>array("rss/list",'POST'),
        'rss/getinfo'=>array("rss/getinfo",'POST'),
        'rss/deleteInfo'=>array("rss/deleteInfo",'POST'),
        //美女视频（php）
        'android/articlelist'=>array("articlelist",'GET'),
        //文章（php）
        'android/video'=>array("video",'GET'),
        //游币充值
        'recharge.youbi/GetRechargeList'=>array("recharge.youbi/GetRechargeList",'POST'),
        'recharge.youbi/GetDailyAggregateList'=>array("recharge.youbi/GetDailyAggregateList",'POST'),
        //一元购->商品->商品列表
        'luckyDraw/QueryMerchandise'=>array("luckyDraw/QueryMerchandise",'UTF8'),
        //一元购->商品->添加商品
        'luckyDraw/CreateMerchandise'=>array("luckyDraw/CreateMerchandise",'UTF8'),
        //一元购->商品->修改商品
        'luckyDraw/UpdateMerchandise'=>array("luckyDraw/UpdateMerchandise",'UTF8'),
        //一元购->商品->商品详情
        'luckyDraw/QueryMerchandiseDetail'=>array("luckyDraw/QueryMerchandiseDetail",'UTF8'),
        //一元购->商品->删除商品
        'luckyDraw/DeleteMerchandise'=>array("luckyDraw/DeleteMerchandise",'UTF8'),
        //一元购->商品->根据商品id更新上下架状态
        'luckyDraw/UpdateMerchandiseStatus'=>array("luckyDraw/UpdateMerchandiseStatus",'UTF8'),

        //一元购->系列商品->添加系列商品
        'luckyDraw/CreateSeriesMerchandise'=>array("luckyDraw/CreateSeriesMerchandise",'UTF8'),
        //一元购->系列商品->修改系列商品
        'luckyDraw/UpdateSeriesMerchandise'=>array("luckyDraw/UpdateSeriesMerchandise",'UTF8'),
        //一元购->系列商品->查询系列商品
        'luckyDraw/QuerySeriesMerchandise'=>array("luckyDraw/QuerySeriesMerchandise",'UTF8'),
        //一元购->系列商品->查询系列商品详情
        'luckyDraw/QuerySeriesMerchandiseDetail'=>array("luckyDraw/QuerySeriesMerchandiseDetail",'UTF8'),

        //一元购->后台收货模版->查询收货地址模版
        //收获模版
        'luckyDraw/CreateReceiveTemplate'=>array("luckyDraw/CreateReceiveTemplate",'UTF8'),
        'luckyDraw/DeleteReceiveTemplate'=>array("luckyDraw/DeleteReceiveTemplate",'UTF8'),

        //一元购->广告配置->添加广告
        'luckyDraw/CreateAdConfigInfo'=>array("luckyDraw/CreateAdConfigInfo",'UTF8'),
        //一元购->广告配置->更新广告配置
        'luckyDraw/UpdateAdConfigInfo'=>array("luckyDraw/UpdateAdConfigInfo",'UTF8'),
        //一元购->广告配置->广告配置详细
        'luckyDraw/QueryAdConfigInfoDetail'=>array("luckyDraw/QueryAdConfigInfoDetail",'UTF8'),
        //一元购->广告配置->广告配置查询
        'luckyDraw/QueryAdConfigInfo'=>array("luckyDraw/QueryAdConfigInfo",'UTF8'),

        //一元购->活动期号查询->活动期号查询列表
        'luckyDraw/QueryDrawNum'=>array("luckyDraw/QueryDrawNum",'UTF8'),
        //一元购->活动期号查询->查询指定活动期号的参与用户
        'luckyDraw/QueryDrawNumAndUser'=>array("luckyDraw/QueryDrawNumAndUser",'UTF8'),
        //一元购->活动期号查询->强制结束
        'luckyDraw/ForceCancelDraw'=>array("luckyDraw/ForceCancelDraw",'UTF8'),

		'luckyDraw/CreateReceiveTemplate'=>array("luckyDraw/CreateReceiveTemplate",'UTF8'),
        'luckyDraw/QueryReceiveTemplateForPage'=>array("luckyDraw/QueryReceiveTemplateForPage",'UTF8'),
        'luckyDraw/QueryReceiveTemplateDetail'=>array("luckyDraw/QueryReceiveTemplateDetail",'UTF8'),
        'luckyDraw/QueryDefaultReceiveTemplate'=>array("luckyDraw/QueryDefaultReceiveTemplate",'UTF8'),
        //收获地址
        'luckyDraw/QueryReceiveAddressForPage'=>array("luckyDraw/QueryReceiveAddressForPage",'UTF8'),

        //订单
        'luckyDraw/QueryOrderInfo'=>array("luckyDraw/QueryOrderInfo",'UTF8'),
        'luckyDraw/UpdateOrderAddressIdByAddId'=>array("luckyDraw/UpdateOrderAddressIdByAddId",'UTF8'),
        'luckyDraw/UpdateOrderLogisticsNumberById'=>array("luckyDraw/UpdateOrderLogisticsNumberById",'UTF8'),
        'luckyDraw/UpdateOrderStatusById'=>array("luckyDraw/UpdateOrderStatusById",'UTF8'),

        //数据统计
        'luckyDraw/ParticipateInfoReport'=>array('luckyDraw/ParticipateInfoReport','UTF8'),
        //用户协议
        'luckyDraw/SaveOrUpdateUserAgree'=>array('luckyDraw/SaveOrUpdateUserAgree','UTF8'),
        'luckyDraw/QueryUserAgreement'=>array('luckyDraw/QueryUserAgreement','UTF8'),
        //ios用户协议
        'luckyDraw/QueryUserAgreement'=>array('luckyDraw/QueryUserAgreement','POST'),
        'luckyDraw/SaveOrUpdateUserAgree'=>array('luckyDraw/SaveOrUpdateUserAgree','POST'),
        //app统计
        'module_mall/product/countProduct'=>array('module_mall/product/countProduct','GET'),
        //任务钻石
        'module_task/prize/query_diamond_prize_list'=>array('module_task/prize/query_diamond_prize_list','GET'),
        'module_task/prize/query_diamond_rank_list'=>array('module_task/prize/query_diamond_rank_list','GET'),
        //收支统计
       'module_rmb/account/currencyStatics' => array('module_rmb/account/currencyStatics','GET'),
        // 金娱奖
        'jinyu_vote/search/search_price_info'=>array('jinyu_vote/search/search_price_info','GET'),
        'jinyu_vote/add/add_priceInfo'=>array('jinyu_vote/add/add_priceInfo','GET'),
        'jinyu_vote/update/update_priceInfo'=>array('jinyu_vote/update/update_priceInfo','GET'),
        'jinyu_vote/delete/delete_priceInfo'=>array('jinyu_vote/delete/delete_priceInfo','GET'),
        'jinyu_vote/search/search_games'=>array('jinyu_vote/search/search_games','GET'),
        'jinyu_vote/add/add_gamePrice'=>array('jinyu_vote/add/add_gamePrice','GET'),
        'jinyu_vote/update/update_game'=>array('jinyu_vote/update/update_game','GET'),
        'jinyu_vote/delete/delete_game'=>array('jinyu_vote/delete/delete_game','GET'),
        //专区专题-刘勇
        'search/search_ByGroup'=>array('zqtSystem/search/search_ByGroup','GET'),
        'add/add_model'=>array('zqtSystem/add/add_model','POST'),
        'search/search_ById'=>array('zqtSystem/search/search_ById','POST'),
        'update/update_model'=>array('zqtSystem/update/update_model','POST'),
        'delete/delete_model'=>array('zqtSystem/delete/delete_model','POST'),



        'jinyu_vote/update/updateNum'=>array('jinyu_vote/update/updateNum','GET'),
    );
/*
 * $url:$arr_url中键值表示ip
 * $data：传入参数
 * $method：具体地址
 * $isList：默认需要返回数据
 */
    //完成
    public static function excute($url=false,$data=array(),$method="",$isList=true)
    {
        if(!$url)   return array('success'=>false,'error'=>"接口地址不能为空",'data'=>false);
        $res = Utility::loadByHttp(Config::get(self::$arr_url[$url]).self::$arr[$method][0],$data,self::$arr[$method][1]);

//        echo Config::get(self::$arr_url[$url]).self::$arr[$method][0];
//        print_r($data);
//        print_r($res);

        if(!$res)   return array('success'=>false,'error'=>"接口无返回",'data'=>false);
        //整合code
        if(array_key_exists("errorCode",$res)){
            $res['code'] = $res['errorCode'];
        }
        if(isset($res['code'])&&!$res['code']) {
            if($isList){
                if(isset($res['result']))return array('success' => true, 'error' => false, 'data' => $res['result'],'count' => isset($res['totalCount'])?$res['totalCount']:0);
            }else{
                return array('success' => true, 'error' => false, 'data' => false);
            }
        }
        return array('success'=>false,'error'=>isset($res['errorDescription'])?$res['errorDescription']:"接口未返回错误信息",'data'=>false);
    }

    public static function excute2($url=false,$data=array(),$method="",$isList=true)
    {
        $res = BaseHttp::http(Config::get(self::$arr_url[$url]).self::$arr[$method][0],$data,self::$arr[$method][1]);
//        echo Config::get(self::$arr_url[$url]).self::$arr[$method][0];
//        print_r($data);
//        print_r($res);
        if(!$res){
            return array('success'=>false,'error'=>"接口无返回",'data'=>false);
        }
        //整合code
        if(array_key_exists("errorCode",$res)){
            $res['code'] = $res['errorCode'];
        }
        if(isset($res['code'])&&!$res['code']) {
            if($isList){
                    return array('success' => true, 'error' => false, 'data' => $res['result'],'count' => isset($res['totalCount'])?$res['totalCount']:0);
            }else{
                return array('success' => true, 'error' => false, 'data' => false);
            }
        }
        return array('success'=>false,'error'=>isset($res['errorDescription'])?$res['errorDescription']:"接口未返回错误信息",'data'=>false);
    }


  }