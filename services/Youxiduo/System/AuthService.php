<?php
/**
 * @package Youxiduo
 * @category Android 
 * @link http://dev.youxiduo.com
 * @copyright Copyright (c) 2008 Youxiduo.com 
 * @license http://www.youxiduo.com/license
 * @since 4.0.0
 *
 */
namespace Youxiduo\System;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

use Youxiduo\Base\BaseService;
use Youxiduo\System\Model\AuthGroup;
use Youxiduo\System\Model\Admin;
use Youxiduo\System\Model\Module;

class AuthService extends BaseService
{
	/**
	 * 登录
	 * @param $username
	 * @param $password
	 * @param bool|false $remember
	 * @return bool
	 */
	public static function doLogin($username,$password,$remember=false)
	{
		$password = strlen($password)==32 ? $password : md5($password);
		$admin = Admin::getInfoByUsername($username);
		if($admin){
			if($admin['password']===$password && $admin['isopen']==1){				
				Session::put('mcp_admin',$admin);
				if($remember===true){
					$local_login = Crypt::encrypt($username.'@'.$password);
					$cookie = Cookie::make('mcp_local_hash',$local_login,60*24*7);
					$admin['cookie'] = $cookie;					
				}
				return $admin;
			}
			return false;
		}
		return false;
	}
	
	/**
	 * 退出
	 */	
	public static function doLogout()
	{
		Session::flush();
		$cookie = Cookie::forget('mcp_local_hash');
		return $cookie;
	}
	
	/**
	 * 验证登录状态
	 * @param string $node
	 * @return bool
	 */
    public static function verifyAuth($node='')
	{
		$admin = false;
		$admin = self::getAuthAdmin();
	    if($admin !== false){
			$group = AuthGroup::getInfo($admin['group_id']);
			if($group){
			    $nodes = $group['menus_nodes']['nodes'];
				if(is_array($admin['menus_nodes']) && $admin['menus_nodes']) $nodes = $admin['menus_nodes']['nodes'];
			    if($node && !empty($node)){
			    	//return in_array($node,$nodes);
			    	$segments = explode('/',$node);
			    	if(count($segments)<3) $segments[2] = '*';
			    }else{
			        $segments = array_slice(Request::segments(),0,3);
			    }
			    $m = $segments[0];
			    $c = $segments[1];
			    $a = $segments[2];
			    $uri = implode('/',$segments);
			    if(in_array($uri,$nodes) || in_array($m.'/*',$nodes) || in_array($m.'/' . $c .'/*',$nodes)){
			    	return true;
			    }
			    return false;
			}
		}
		return null;
	}
	
	public static function verifyNodeAuth($node)
	{
		return self::verifyAuth($node);
	}
	
	/**
	 * 获取当前账号拥有权限的菜单
	 */
	public static function getAdminMenu()
	{
		$allMenu = self::getAllMenu();
		$admin = self::getAuthAdmin();
		if($admin !== false){
			$group = AuthGroup::getInfo($admin['group_id']);
			if($group){
				if(is_array($admin['menus_nodes'])) return $admin['menus_nodes']['menus'];
				return $group['menus_nodes']['menus'];
			}
		}
		return array();
		
	}
	
	/**
	 * 获取当前登录用户的信息
	 */
	public static function getAuthAdmin()
	{
	    if(Session::has('mcp_admin')){
			$admin = Session::get('mcp_admin');
			return Admin::getInfoById($admin['id']);
		}elseif(($local_login = Cookie::get('mcp_local_hash',false))!==false)
		{
			list($username,$password) = explode('@',Crypt::decrypt($local_login));
			if($username && $password){
				$admin = self::doLogin($username, $password);
				return $admin;
			}
		}
		return false;
	}
	
	/**
	 * 获取所有菜单
	 */
	public static function getAllMenu()
	{
		//$modules = Module::getNameList();
		$modules = Module::getList(1,100);
		//$module_name_list = array_keys($modules);
		$module_root_path = app_path() . '/modules/';
		$all_menu = array();
		foreach($modules as $module){
			$module_name = $module['module_name'];
			$file = $module_root_path . $module_name . '/auth_menu.php';
			if(is_file($file) && is_readable($file)){
				$menu = require $file;
				$menu['id'] = $module['id'];
				$all_menu[$menu['module_name']] = $menu;
			}
		}
		return $all_menu;
	}
}