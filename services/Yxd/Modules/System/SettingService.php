<?php
/**
 * @category System
 * @link http://www.youxiduo.com
 * @author mawenpei<mawenpei@cwan.com>
 * @since 2014-03-15
 * @version 3.0.0
 */
namespace Yxd\Modules\System;

use Yxd\Modules\Core\CacheService;
use Yxd\Services\Models\SystemSetting;
use Yxd\Modules\Core\BaseService;
/**
 * 配置服务类
 * CacheService::has($keyname)判断缓存是否存在
 */
class SettingService extends BaseService
{
    public static function getConfig($keyname='')
	{
		/*
		if(CLOSE_CACHE===false && $keyname && CacheService::has($keyname)){
			//return CacheService::get($keyname);取出缓存的值,并返回
			return CacheService::get($keyname);
		}elseif(CLOSE_CACHE===false && CacheService::has('tb::system_config')){

			return CacheService::get('tb::system_config');
		}
		*/
		$config = array();
		$tb = SystemSetting::db();
		if(!empty($keyname)){
			$data = $tb->where('keyname','=',$keyname)->first();
			if($data){
				$config = array('keyname'=>$keyname,'data'=>unserialize($data['data']));
				//CacheService::put($keyname,$config,3600);
			}
			return $config;
		}else{
			//取出所有的配置文件
			$list = $tb->orderBy('id','asc')->get();
			foreach($list as $key=>$val){
				$val['data'] = unserialize($val['data']);
				$list[$key] = $val;
			}
			$config = $list;
			//CacheService::put('tb::system_config',$config,3600);
		}
		return $config;
	}
	public static function setConfig($keyname,$data)
	{	
		$json = serialize($data);
		$count = SystemSetting::db()->where('keyname','=',$keyname)->count();
		if($count){
			SystemSetting::db()->where('keyname','=',$keyname)->update(array('data'=>$json));			
		}else{
			SystemSetting::db()->insertGetId(array('keyname'=>$keyname,'data'=>$json));
		}
		
		CacheService::forget($keyname);
		return true;
	}
}