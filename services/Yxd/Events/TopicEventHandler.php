<?php
namespace Yxd\Events;

use Yxd\Services\TaskService;

use Yxd\Services\UserFeedService;
use Yxd\Services\CreditService;
use Yxd\Services\CircleFeedService;

class TopicEventHandler
{
	/**
	 * 发帖前预处理
	 */
	public function onPostBefore($event)
	{		
		$out = $event;
		/*
		$thread = $event[0];
		$message = $event[1];
		
	    $obj = json_decode($message,true);
		foreach($obj as $key=>$val){
			
		    if(!isset($thread['summary']) && $val['text']){
				$thread['summary'] = $obj[$key]['text'] = $val['text'] . ',这里是预处理增加的';
			}
			
		    if(!isset($thread['listpic']) && $val['img']){
				$thread['listpic'] = $val['img'];
			}
		}
		$message = json_encode($obj);		
		$out[0] = $thread;
		$out[1] = $message;
		*/
		return $out;
	}	    
	
	/**
	 * 处理发帖事件
	 */
	public function onPost($event)
	{
		//圈子动态
		$circlefeed['type'] = 'topic';
		$circlefeed['topic'] = $event[0];
		CircleFeedService::makeDataFeed($circlefeed);
		//用户动态
		$userfeed['type'] = 'topic';
		$userfeed['topic'] = $event[0];
		UserFeedService::makeDataFeed($userfeed);
		
		//游币
		CreditService::doUserCredit($event[0]['author_uid'],CreditService::CREDIT_RULE_ACTION_POST_TOPIC);
	}	    
	
    /**
	 * 订阅事件
	 */
	public function subscribe($events)
	{
		$events->listen('topic.post_before','\\Yxd\\Events\\TopicEventHandler@onPostBefore');
		$events->listen('topic.post','\\Yxd\\Events\\TopicEventHandler@onPost');
	}
}