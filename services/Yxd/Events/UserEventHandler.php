<?php
namespace Yxd\Events;

use Yxd\Modules\Message\NoticeService;

use Yxd\Services\UserService;

class UserEventHandler
{
	/**
	 * 处理用户登录事件
	 */
	public function onUserLogin($event)
	{
		$user = $event[0];
		//积分处理
		UserService::doUserCredit($user['uid'], 'user_login');
	}
	
	/**
	 * 处理用户退出事件
	 */
	public function onUserLogout($event)
	{
		
	}
	
	/**
	 * 处理用户注册事件
	 */
	public function onUserRegister($event)
	{
		$user = $event[0];
		//积分处理
		UserService::doUserCredit($user['uid'], 'user_register');
		//发送系统消息
		$params = $user;
		NoticeService::sendRegisterSuccess($user['uid'],$params);
		
	}
	
	/**
	 * 处理绑定第三方帐号事件
	 */
	public function onUserBindThird($event)
	{
		
	}
	
	/**
	 * 处理更新用户信息缓存事件
	 */
	public function onUpdateUserInfoCache($event)
	{
		$uid = $event[0];
		UserService::updateUserInfoCache($uid);
	}
	
	/**
	 * 用户注册时填写注册人事件
	 */
	public function onNewRegisterScore($event)
	{
		$user = $event[0];
		//发送系统消息
		$params = $user;
		NoticeService::sendNewRegisterScoreSuccess($user['uid'],$params);
	}
	
	
	/**
	 * 订阅事件
	 */
	public function subscribe($events)
	{
		$events->listen('user.new_register_score','\\Yxd\\Events\\UserEventHandler@onNewRegisterScore');
		$events->listen('user.register','\\Yxd\\Events\\UserEventHandler@onUserRegister');
		$events->listen('user.login','\\Yxd\\Events\\UserEventHandler@onUserLogin');
		$events->listen('user.logout','\\Yxd\\Events\\UserEventHandler@onUserLogout');
		$events->listen('user.bindthird','\\Yxd\\Events\\UserEventHandler@onUserBindThird');
		$events->listen('user.update_userinfo_cache','\\Yxd\\Events\\UserEventHandler@onUpdateUserInfoCache');
	}
}