<?php

namespace Yxd\Events;

use Illuminate\Support\ServiceProvider;

class EventHandlerProvider extends ServiceProvider
{
	protected $config = array();
	
	public function register()
	{				
		$this->config = require __DIR__ . '/../../config/config.php';
		$this->config = array_merge($this->config,$this->app['config']->get('event'));
	}
	
	public function boot()
	{
		//注册事件监听器
		if(isset($this->config['listens'])){
			foreach($this->config['listens'] as $event=>$handler){
				$this->app['events']->listen($event,$handler);
			}
		}
		
		//注册事件订阅者
		if(isset($this->config['handlers'])){
			foreach($this->config['handlers'] as $event=>$handler){
				
				$this->app['events']->subscribe(new $handler());
			}
		}
	}
}