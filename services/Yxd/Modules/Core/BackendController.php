<?php
namespace Yxd\Modules\Core;

use Illuminate\Routing\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Yxd\Modules\System\SettingService;
use Yxd\Modules\System\OperatorService;
use Yxd\Modules\System\PermissionService;
use Youxiduo\System\AuthService;
include_once(base_path().'/libraries/convert.php');

class BackendController extends Controller
{
	public $current_module = '';
	
	protected $current_user = null;
	
	/**
	 * 构造函数
	 */
    public function __construct()
    {
    	$lang = Session::get('language_lang');
    	if($lang == 'zht'){
    		App::setLocale('zht');
    	}else{
    		App::setLocale('zhs');
    	}
    	$this->beforeFilter('auth', array('except' => 'getLogin'));

    	View::share('global_tips',Session::get('global_tips'));
        $this->current_user = AuthService::getAuthAdmin();
        View::share('current_user',$this->current_user);
	    if(method_exists($this,'_initialize')){
	    	$this->_initialize();
            //echo $this->current_module;exit;
	    	View::share('module_name',$this->current_module);
			$menu = AuthService::getAdminMenu();//当前账号拥有的菜单
			View::share('admin_menu',$menu);
	    }
	    $keyname = 'selected_' . $this->current_user['id'] . '_uids';
        if(Session::has($keyname)){
			$selecteds = Session::get($keyname);
			View::share('selected_uids',$selecteds);
		}	  
    }
    /**
     * 初始化
     */
    public function _initialize()
    {
    }

    /**
     * 跳转
     * @param $route
     * @param string $tips
     * @return
     */
    protected function redirect($route,$tips='')
    {
        if($tips){
			return Redirect::to($route)->with('global_tips',$tips);
		}
    	return Redirect::to($route);
    }

    /**
     * 返回
     * @param string $tips
     * @return
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

    /**
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function json($data)
    {
    	return Response::json($data);
    }

    /**
     * 显示内容
     * @param $file
     * @param array $data
     * @return
     */
	protected function display($file,$data=array())
	{	
		if(Session::get('language_lang') == 'zht'){
			$tmp_dir = app_path() . '/modules/' . $this->current_module . '/views/'.$file.'.twig';
			$tmp_zht_dir = storage_path().'/cache/'.$this->current_module;
			View::addLocation(app_path() . '/modules/' . $this->current_module . '/views');
			View::addLocation($tmp_zht_dir);
			$file_name = $file.'_zht.twig';
			if(file_exists($tmp_zht_dir.'/'.$file_name)){
				return View::make($file.'_zht',$data);
			}
			if(file_exists($tmp_dir) && !file_exists($tmp_zht_dir.'/'.$file_name)){
				$tmp_content = file_get_contents($tmp_dir);
				$zht_content = zhconversion_tw($tmp_content);
				$new_file = $tmp_zht_dir.'/'.$file_name;
				if(!is_readable($tmp_zht_dir)) mkdir($tmp_zht_dir,0777);
				$handle = fopen($new_file, 'a');
				if($handle){
					if(is_writable($new_file)){
						if(fwrite($handle, $zht_content) !== FALSE){
							return View::make($file.'_zht',$data);
						}
					}
					fclose($handle);
				}
			}
		}
		if(!empty($this->current_module))View::addLocation(app_path() . '/modules/' . $this->current_module . '/views');
        return View::make($file,$data);
	}

    /**
     * 获取内容
     * @param $file
     * @param array $data
     * @return
     */
	protected function html($file,$data=array())
	{
		View::addLocation(app_path() . '/modules/' . $this->current_module . '/views');
		return View::make($file,$data)->render();
	}

    /**
     * 后台操作记录日志
     * @param $admin_name
     * @param $admin_group
     * @param $action
     * @param $info
     * @param $data
     */
	protected function recordOperateLog($admin_name,$admin_group,$action,$info,$data){
		OperatorService::operationLog($admin_name, $admin_group, $action, $info, $data);
	}

    /**
     * 后台操作记录日志
     * @param $info
     * @param $data
     */
	protected function operationPdoLog($info,$data){
		//OperatorService::operationPdoLog($this->getOpreateChannel(),$this->getSessionUserName(), $this->getSessionUserRole(), Request::segment(3), $info, $data);
	}
	
	/**
	 * 获取Session数据
	 * @param string $key
	 */
	protected function getSessionData($key=null){
		if(!$key) return Session::all();
		return Session::get($key);
	}
	
	/**
	 * 获取登录用户名
	 * @return Ambigous <string, unknown>
	 */
	protected function getSessionUserName(){
		$userinfo = Session::get('youxiduo_admin');
		return $userinfo ? $userinfo['username'] : '';
	}

    /**
	 * 获取登录用户UID
	 * @return Ambigous <string, unknown>
	 */
	protected function getSessionUserUid(){
		$userinfo = Session::get('youxiduo_admin');
		return $userinfo ? $userinfo['id'] : 0;
	}
	
	/**
	 * 获取操作日志标示名
	 * @return string
	 */
	protected function getOpreateChannel(){
		return Request::segment(1).'/'.Request::segment(2);
	}

    public function block_($type_,$Description,$url='')
    {
        if(empty($Description))
            $Description='发生错误';
        switch($type_){
            case 'del':
                return Redirect::back()->with('global_tips',$Description);
            break;
            case 'ByView':
                return Redirect::back()->withInput()->with('global_tips',$Description);
            break;
            case 'Bypost':
                return  self::redirect($url,$Description);
            break;
        }
    }



}