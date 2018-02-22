<?php

namespace libraries;

use libraries\UploadHandler;
use Illuminate\Support\Facades\Config;

class Helpers {
	/**
	 * 上传单个图片
	 * @param string $dir
	 * @param file $file
	 * @return boolean|string
	 */
	public static function uploadPic($dir,$file){
		if(!$dir || empty($file)) return false;
		$path = storage_path() . $dir;
		$new_filename = date('YmdHis') . str_random(4);
		$mime = $file->getClientOriginalExtension();
		$result = $file->move($path,$new_filename . '.' . $mime );
		if($result){
			return $dir . $new_filename . '.' . $mime;
		}
	}
	
	public static function jqueryFileUpload($path,$file,$filename,$suffix,$del_url){
		$dir = storage_path().$path;
		$url = Config::get('ueditor.imageUrlPrefix').$path;
		$option = array('upload_dir'=>$dir,'upload_url'=>$url,'script_url'=>$del_url.base64_encode($path.$filename.'.'.$suffix),'file_names'=>$filename,'delete_type'=>'DELETE');
		return new UploadHandler($option);
	}
	
	/**
	 * 删除目录
	 * @param unknown $dir
	 * @return boolean
	 */
	public static function delTree($dir,$cur_dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir."/".$object) == "dir") Helpers::delTree($dir."/".$object,$cur_dir); else unlink($dir."/".$object);
				}
			}
			$files = array_diff($objects, array('.','..'));
			reset($objects);
			if($dir != $cur_dir){
				rmdir($dir);
			}
		}
	}
	
	/**
	 * CURL GET
	 * @param String $url
	 * @return mixed
	 */
	public static function curlGet($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; 
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	
	/**
	 * CURL POST
	 * @param String $url
	 * @param Array $data
	 * @return mixed
	 */
	public static function curlPost($url,$data){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_PORT, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	
	/**
	* Name: time_ago
	* Purpose: 将时间戳转换为距离当前时间有多久的表现形式
	* 1分钟内按秒
	* 1小时内按分钟显示
	* 1天内按时分显示
	* 3天内以昨天，前天显示
	* 超过3天显示具体日期
	*/
	
	public static function smarty_modifier_time_ago($time) {
		$time_deff = time () - $time;
		$retrun = '';
		if ($time_deff >= 259200) {
			$retrun = date ( 'm/d H:i', $time );
		} else if ($time_deff >= 172800) {
			$retrun = "前天 " . date ( 'H:i', $time );
		} else if ($time_deff >= 86400) {
			$retrun = "昨天" . date ( 'H:i', $time );
		} else if ($time_deff >= 3600) {
			$hour = intval ( $time_deff / 3600 );
			$minute = intval ( ($time_deff % 3600) / 60 );
			$retrun = $hour . '小时';
// 			if ($minute > 0) {
// 				$retrun .= $minute . '分钟';
// 			}
			$retrun .= '前';
		} else if ($time_deff >= 60) {
			$minute = intval ( $time_deff / 60 );
			$second = $time_deff % 60;
			$retrun = $minute . '分';
			if ($second > 0) {
				$retrun .= $second . '秒';
			}
			$retrun .= '前';
		} else {
			$retrun = $time_deff . '秒前';
		}
		return $retrun;
	}
}