<?php
namespace Yxd\Utility;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;

use PHPImageWorkshop\ImageWorkshop;

class ImageHelper
{
	/**
	 * 文件上传配置
	 */
	private $_config = array(
	    'mimes'=>array('image/jpeg','image/png'),//允许上传的文件MiMe类型
	    'maxSize'=>0,//上传的文件大小限制 (0-不做限制)
	    'exts'=>array('png','jpg','gif','jpeg'),//允许上传的文件后缀
	    'autoSubDirs'=>true,//自动子目录保存文件
	    'subDirNameRule'=>array('date','Y/m/d'),//子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
	    'savePath'=>'/userdirs/',	    	    
	    'saveName'=>array('uniqid',array('',false)),//上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
	    'saveExt'=>'',//文件保存后缀，空则使用原后缀	    
	    'driver'=>'LocalUploader',// 文件上传驱动
	    'driverConfig'=>array(
	    )   
	);
	
	/**
	 * 上传错误信息
	 */
	protected $error;
	
	protected $driver;
	protected $driverConfig = array();
	
	/**
	 * 上传类实例
	 */
	protected $uploader;
	
	
	public function __construct($config=array())
	{		
		$this->_config = array_merge($this->_config,$config);
		if($this->_config['driver']){
			$this->driver = $this->_config['driver'];
		}
	    if($this->_config['driverConfig']){
			$this->driverConfig = $this->_config['driverConfig'];
		}
		$this->setDriver();
	}
	
	/**
	 * 设置上传驱动
	 */
	public function setDriver($driver=null,$driverConfig=null)
	{
		$driver && $this->driver = $driver;
		$driverConfig && $this->driverConfig = $driverConfig;
		$class = '\\Yxd\\Utility\\' . $this->driver;
		$this->uploader = new $class($this->driverConfig);
	}
	
	public function upload($fileField)
	{
		if(!Input::hasFile($fileField)){
			$this->error = '没有上传的文件！';
			return false;
		}
		//检测根目录
		//检测上传目录
		$file = Input::file($fileField);

		if(!$this->check($file)){
			return false;
		}
		
		$subpath = $this->getSubPath();
		$filename = $this->getFileName($file);
				
		if($this->uploader->save($file,$subpath,$filename)){
			$info = array(
			    'filename'=>$filename,
			    'filepath'=>$subpath,
			    'ext'=>$file->getClientOriginalExtension()
			);
			return $info;
		}else{
			$this->error = $this->uploader->getError();
			return false;
		}
	}
	
	protected function check($file)
	{
		if(!$file->isValid()){
			$this->error = '非法上传文件';
			return false;
		}
		//检测文件类型
		$ext = $file->getClientOriginalExtension();
		if(!in_array(strtolower($ext),$this->_config['exts'])){
			$this->error = '无效的文件类型:' . $ext;
			return false;
		}
		//检测文件大小
		$size = $file->getClientSize();
		
		return true;
	}
	
    public function __get($name)
	{
		return $this->_config[$name];
	}
	
	public function __isset($name)
	{
		return isset($this->_config[$name]);
	}
	
    /**
	 * 获取子目录名
	 */
	protected function getSubPath()
	{
		$name = '';
		$rule = $this->subDirNameRule;
	    if($this->autoSubDirs===true && $rule){
			if(is_array($rule)){
				$fun = $rule[0];
				$param = (array)$rule[1];
				$name = call_user_func_array($fun,$param);
			}elseif(is_string($rule)){
				if(function_exists($rule)){
	                $name = call_user_func($rule);
	            }
			}
		}
		return rtrim($this->savePath,'/') . '/' . ltrim($name,'/');
	}
	
	/**
	 * 获取新文件名
	 */
	protected function getFileName($file)
	{
		$name = $file->getClientOriginalName();
		$ext = $file->getClientOriginalExtension();
		$rule = $this->saveName;
		if($rule){
			if(is_array($rule)){
				$fun = $rule[0];
				$param = (array)$rule[1];
				$name = call_user_func_array($fun,$param);
				$name = $name . '.' . $ext;
			}elseif(is_string($rule)){
				if(function_exists($rule)){
	                $name = call_user_func($rule);
	                $name = $name . '.' . $ext;
	            }
			}
		}
		return $name;
	}
	
	public function getError()
	{
		return $this->error;
	}
}

/**
 * 上传接口类
 */
interface IUploader
{
	public function save($file,$subpath,$filename);
}

/**
 * 本地上传类
 */
class LocalUploader implements IUploader
{
	protected $config = array(	   
	    'rootPath'=>'',	    
	    'autoType'=>'auto',//图片自动缩略方式
	    'autoSize'=>array(620,320,240,160,120,100,80),//图片自动缩略规格
	);
	
	protected $error;
	
	public function __construct($config)
	{
		$this->config['rootPath'] = Config::get('app.image_storage_path',storage_path());
		$this->config = array_merge($this->config,$config);
	}	
	
	public function __set($name,$value)
	{
		if(isset($this->config[$name])){
			$this->config[$name] = $value;
		}
	}
	
	public function __get($name)
	{
		return $this->config[$name];
	}
	
	public function __isset($name)
	{
		return isset($this->config[$name]);
	}
	
	public function save($file,$subpath,$filename)
	{
		$ext = $file->getClientOriginalExtension();		
		if(!$this->makeDirs($subpath)){			
			return false;
		}
		$serverpath = $this->rootPath . $subpath;
		$file->move($serverpath,$filename);
		//自动生成缩略图
		if($this->autoType && $this->autoSize && is_array($this->autoSize)){
		    $layer = ImageWorkshop::initFromPath($serverpath . '/' . $filename);
		    $autoSize = $this->autoSize; 
		    rsort($autoSize,SORT_NUMERIC);
		    foreach($autoSize as $size){
		    	$name = str_replace('.'.$ext,'_'.$size.'.'.$ext,$filename);
		    	$layer->resizeInPixel($size,null,true);
				$layer->save($serverpath,$name,true,null,95);
		    }
		}
		return true;
	}
	
	protected function makeDirs($path)
	{
		$dir = $this->rootPath  . $path;
		if(is_dir($dir)) return true;
		if(mkdir($dir,0777,true)){
			return true;
		}
		$this->error = '目录创建失败';
		return false;
	}
	
	public function getError()
	{
		return $this->error;
	}
}