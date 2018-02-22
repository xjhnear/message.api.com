<?php
use Yxd\Modules\Core\CacheService;
use Illuminate\Support\Facades\Input;

class AppController extends BaseController 
{
	/**
	 * 检查版本
	 */
	public function upGrade()
	{
		$version = Input::get('version','');
		$udid = Input::get('udid','');
		$urid = Input::get('urid','');
		if($version != '1.0.0'){
			return $this->fail(203,'没有版本信息');
		}
		return $this->success(array('urid'=>$urid));
	}	
}