<?php
namespace Yxd\Services;

use Yxd\Services\Service;
use Yxd\Modules\Core\CacheService;

class ClearCacheService extends Service
{
	/**
	 * 通过队列清空缓存
	 */
    public static function clearAllByQueue()
	{
		$queue_name = 'queue::clearcache';
		$data = self::queue()->lpop($queue_name);
		while($data){
			$data = unserialize($data);
			$cate = strtolower($data['cate']);
			$type = strtolower($data['type']);
			$method = strtolower($data['method']);
			$args = $data['args'];
			switch($cate){
				case 'game':
					self::clearGameCache($type, $args, $method);
					break;
				case 'article':
					self::clearArticleCache($type, $args, $method);
					break;
				case 'commend':
					self::clearCommendCache($type, $args, $method);
					break;
				case 'adv':
					self::clearAdvCache($type, $args, $method);
					break;
				case 'topic':
					self::clearTopicCache($type, $args, $method);
					break;
				case 'app':
					self::clearAppCache($type, $args, $method);
					break;
			}		
		    $data = self::queue()->lpop($queue_name);
		}
	}
	
	public static function pushDataToQueue($cate,$type,$method,$args=array()){
		$data = array();
		$data['cate'] = $cate;
		$data['type'] =  $type;
		$data['method'] = $method;
		$data['args'] = $args;
		self::queue()->rpush('queue::clearcache',serialize($data));
	}
	
	/**
	 * 情况游戏缓存
	 */
	protected static function clearGameCache($type,$args,$method)
	{
		$game_id = isset($args['game_id']) ? $args['game_id'] : 0;
		if($method=='add'){
			CacheService::section('game::lastupdate')->flush();
		}else{
			CacheService::forget('game::games::info::'.$game_id);
		}
		CacheService::section('commend::hotrecommend')->forget('commend::hotrecommend::home');
	}
	
	/**
	 * 清空文章缓存
	 */
    protected static function clearArticleCache($type,$args,$method)
	{
		switch($type)
		{
			case 'news':
				CacheService::section('article::news')->flush();
				break;
			case 'guide':
				CacheService::section('article::guide')->flush();
				break;
			case 'opinion':
				CacheService::section('article::opinion')->flush();
				break;
			case 'video':
				CacheService::section('article::video')->flush();
				$id = isset($args['aid']) ? $args['aid'] : 0;
				$id && CacheService::forget('object::video::id::' . $id);
				break;
		}
	}
	
	/**
	 * 清空推荐缓存
	 */
    protected static function clearCommendCache($type,$args,$method)
	{
		switch($type)
		{
			case 'mustplay':
				CacheService::section('commend::mustplay')->flush();
				break;
			case 'slide':
				CacheService::forget('commend::slide');
				break;
			case 'share_tpl':
				$typeid = isset($args['typeid']) ? $args['typeid'] : 0;
				$typeid && CacheService::forget('commend::share_tpl::'.$typeid);
				break;
			case 'app':				
				CacheService::section('commend::app')->flush();
				break;
			case 'newgame':
				CacheService::section('commend::newgame')->flush();
				break;
			case 'testtable':				
				CacheService::section('commend::testtable')->flush();
				break;
			case 'hotrecommend':
				break;
			case 'circlerecommend':
				CacheService::forget('commend::circlerecommend');				
				break;
			case 'guesslike':
				CacheService::forget('commend::guesslike');
				break;
		}
	}
	
	/**
	 * 清空广告缓存
	 */
    protected static function clearAdvCache($type,$args,$method)
	{
		switch(intval($type))
		{
			case 1://轮播广告
				CacheService::forget('adv::slide');
				break;
			case 2://热门推荐
				CacheService::forget('adv::hotgame');
				break;
			case 3://游戏详情弹窗
				CacheService::forget('adv::popwin::3');
				break;
			case 4://游戏详情下载
				$game_id = isset($args['game_id']) ? $args['game_id'] : 0;
				$game_id && CacheService::forget('adv::download::btn::' . $game_id);
				break;
			case 5://启动页广告
				CacheService::forget('adv::launch::4');
				CacheService::forget('adv::launch::5');
				break;
			case 6://首页弹窗
				CacheService::forget('adv::popwin::6');
				break;
			case 7://猜你喜欢
				CacheService::forget('adv::guesslike');
				break;
			case 8://首页广告条
				CacheService::forget('adv::homebar');
				break;
		}
	}
	
	/**
	 * 清空专题缓存
	 */	
    protected static function clearTopicCache($type,$args,$method)
	{
		CacheService::section('collect')->flush();
		$id = isset($args['zt_id']) ? $args['zt_id'] : 0;
		$id && CacheService::forget('collect::detail::'.$id);
	}
	
	/**
	 * 清空应用配置缓存
	 */
    protected static function clearAppCache($type,$args,$method)
	{
		CacheService::forget('appconfig::'. '3.0.0');
		CacheService::forget('appconfig::'. '3.0.0' . '::update');
		CacheService::forget('appconfig::'. '3.0.0' . '::data');
	}
}