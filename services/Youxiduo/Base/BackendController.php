<?php
namespace Youxiduo\Base;
use Youxiduo\System\AuthService;
use Illuminate\Routing\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

use Youxiduo\System\Model\AuthGroup;

class BackendController extends Controller
{
	protected $module_name;
	protected $module_path;
	
	public function __construct()
	{
		//认证
		$this->beforeFilter('auth',array('except'=>'getLogin'));
		$this->__initModule();
		$this->__initView();
		
	}
	
	/**
	 * 初始化模块
	 */
	protected function __initModule()
	{
		//初始化模块
		$current_class = get_class($this);
		list($current_module) = explode('\\',$current_class);
		$this->module_name = $current_module;
		$this->module_path = app_path() . '/modules/' . $this->module_name; 
	}
	
	/**
	 * 初始化视图
	 */
	protected function __initView()
	{
		View::addLocation($this->module_path . '/views');
		View::share('module_name',$this->module_name);
		$menu = AuthService::getAdminMenu();//当前账号拥有的菜单
		View::share('admin_menu',$menu);
		View::share('global_tips',Session::get('global_tips'));
	}
	
	/**
	 * 输出模板
	 */
	protected function display($file,$data=array())
	{						
		return View::make($file,$data);
	}
	
	/**
	 * 跳转
	 * @param string $route 路由URI
	 * @param string $tips 提示信息
	 */
	protected function redirect($route,$tips='')
	{
		if($tips){
			return Redirect::to($route)->with('global_tips',$tips);
		}
		return Redirect::to($route);
	}
	
	/**
	 * 返回上一页
	 * @param string $tips 提示信息
	 */
	protected function back($tips='')
	{
		if($tips){
			return Redirect::back()->with('global_tips',$tips);
		}
		return Redirect::back();
	}

	protected function lang($keyname)
	{
		return Lang::get($keyname);
	}
}