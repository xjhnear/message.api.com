<?php
/**
 * @package Youxiduo
 * @category Base 
 * @link http://dev.youxiduo.com
 * @copyright Copyright (c) 2008 Youxiduo.com 
 * @license http://www.youxiduo.com/license
 * @since 4.0.0
 *
 */
namespace Youxiduo\User;

use Illuminate\Support\Facades\Config;
use Youxiduo\Base\BaseService;


class UploaderService extends BaseService
{

	// 上传配置信息
	public $upconfig = array(
		'maxSize'    =>    314572800,         //314572800B（字节） = 300M
		'exts'       =>    array('mp4', 'flv', 'png', 'jpg', 'jpeg'),
        'rootPath'   =>    './Public/Uploads/info/',
	);

	/**
	 * @param $string_video_content - 所要上传视频的字符串资源
	 * @param $new_videoname - 视频的名称，如：57c14e197e2d1744.jpg
	 * @return mixed
	 */
	public function upload($string_video_content,$new_videoname) {

		$res['result'] = 1;
		$res['videourl'] = '';
		$res['comment'] = '';

		do {
			$ret = true;
			$fullPath = $this->upconfig['rootPath'] . $this->upconfig['savePath'];
			if(!file_exists($fullPath)){
				$ret = mkdir($fullPath, 0777, true);
			}
			if(!$ret) {
				// 上传错误提示错误信息
				$res['result'] = 12;
				$res['comment'] = "创建保存文件的路径失败！";
				return $res;
				break;
			}

			//开始上传
			if (file_put_contents($fullPath.$new_videoname, $string_video_content)){
				// 上传成功 获取上传文件信息
				$res['result'] = 0;
				$res['comment'] = "上传成功！";
				$res['videoname'] = $new_videoname;
			}else {
				// 上传错误提示错误信息
				$res['result'] = 11;
				$res['comment'] = "上传失败！";
			}


		} while(0);

		return $res;
	}

}