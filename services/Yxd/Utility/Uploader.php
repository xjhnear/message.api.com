<?php
namespace Yxd\Utility;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;

class Uploader
{
	private $config;
	private $fileField;
	
	
	public function __construct($fileField,$config=array())
	{
		$this->config = array_merge(Config::get('ueditor'),$config);
		$this->fileField = $fileField;
	}
	
	public function upImage()
	{
		if(!Input::hasFile($this->fileField)){
			//文件不存在
		}
		$file = Input::file('upimage');
        $filename = $file->getClientOriginalName();
		$ext = $file->getClientOriginalExtension();
	}
}