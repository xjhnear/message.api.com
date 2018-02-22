<?php
namespace Yxd\Utility;
use Yxd\Modules\System\SettingService;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
class ForumUtility
{
    /**
	 * 格式化表情
	 */
	public static function parse_expression($data)
	{
		if(preg_match("/#.+#/i",$data[0])){
	        return $data[0];
	    }
	    $allexpression = Config::get('expression.minbbs');
	    $info = isset($allexpression[$data[0]]) ? $allexpression[$data[0]] : null;
	    if($info){
	        return preg_replace("/\[.+?\]/i","<img src='/public/img/expression/miniblog/".$info['filename']."' />",$data[0]);
	    }else {
	        return $data[0];
	    }
	}
	
    /**
	 * 格式化@好友
	 */
	public static function parse_at($content)
	{
		
	}
	
	public static function find_at($content)
	{
		$at_regex = "/@(.+?)([\s|:]|$)/is";
		preg_match_all($at_regex, $content, $matches); 
		$nicknames = $matches[1];
		return $nicknames;
	}
	
    /**
	 * 格式化图片
	 */
	public static function parse_image($data)
	{		
		if(preg_match("/\[img:(.+?)\]/is",$data[0],$img)){
		    $data[0] = preg_replace("/(\[img:.+?\])/is",'<img src="' . $img[1] . '" />',$data[0]);
		}
		return $data[0];
	}
	
    public static function parseForWeb($text)
	{
		$text = htmlspecialchars_decode($text);
		//表情
		$text = preg_replace_callback("/(\[.+?\])/is",function($data){return self::parse_expression($data);},$text);
		//@好友
		//$text = preg_replace_callback("/@([\w\x{2e80}-\x{9fff}\-]+)/u", function($data){return self::parse_at($data);},$text);
		//图片
		$text = preg_replace_callback('/(\[img:.+?\])/is',function($data){return self::parse_image($data);},$text);
		//网址
		//$text = preg_replace_callback('/((?:https?|mailto|ftp):\/\/([^\x{2e80}-\x{9fff}\s<\'\"“”‘’，。}]*)?)/u', function($data){return self::parse_url($data);}, $text);
		
		
		
		return $text;
	}
	
	//过滤上传文件类型
	public static function filterUploadImg($file)
	{
		$ext = $file->guessClientExtension();
		$maxsize = $file->getMaxFilesize();
		$filesize = $file->getClientSize();
		if(!in_array($ext,array('png','jpeg','gif','jpg' || $filesize >= $maxsize))){
			return false;
		}
		return true;
	}
	
	public static function filterWords($content)
	{
		$config = SettingService::getConfig('pcweb_setting');
		$filter_words = isset($config['data']['filter_words']) ? $config['data']['filter_words'] : '';
		$keywords = explode('|',$filter_words);
		$num = 0;
		foreach($keywords as $keyword)
		{
			if(!$keyword) continue;
			$num = stristr($content,$keyword) ? $num+1 : $num;
		}			
		return $num>0 ? true : $content;
	}
	
	/**
	 * 文章详情页内容处理
	 */
    public static function doDetailContent($content)
	{
		$data = array();
		$data['content'] = self::processContentImage($content);
		$view = View::make('article_detail',$data);
		return $view->render();
	}
	
	public static function processContentImage($content)
	{
		//去掉图片宽度
		$search = '/(<img.*?)width=(["\'])?.*?(?(2)\2|\s)([^>]+>)/is';
		//去掉图片高度
		$search1 = '/(<img.*?)height=(["\'])?.*?(?(2)\2|\s)([^>]+>)/is';
		$content = preg_replace($search,'$1$3',$content);
		$content = preg_replace($search1,'$1$3',$content);
		return $content;
	}
    public static function processContentVideo($content,$width='',$height='')
    {
        //去掉video宽度
        $search = '/(<video.*?)width=(["\'])?.*?(?(2)\2|\s)([^>]+>)/is';
        //去掉video高度
        $search1 = '/(<video.*?)height=(["\'])?.*?(?(2)\2|\s)([^>]+>)/is';
        $content = $width ? preg_replace($search,'$1 width="'.$width.'" $3',$content) : preg_replace($search,'$1$3',$content);
        $content = $height ? preg_replace($search1,'$1 height="'.$height.'" $3',$content) : preg_replace($search1,'$1$3',$content);
        return $content;
    }

    public static function processContentIframe($content,$width='',$height='')
    {
        //去掉iframe宽度
        $search = '/(<iframe.*?)width=(["\'])?.*?(?(2)\2|\s)([^>]+>)/is';
        //去掉iframe高度
        $search1 = '/(<iframe.*?)height=(["\'])?.*?(?(2)\2|\s)([^>]+>)/is';
        $content = $width ? preg_replace($search,'$1 width="'.$width.'" $3',$content) : preg_replace($search,'$1$3',$content);
        $content = $height ? preg_replace($search1,'$1 height="'.$height.'" $3',$content) : preg_replace($search1,'$1$3',$content);
        return $content;
    }
	
    public static function processContentTables($content) 
	{		
		$pattern = "/<table[\s\S]*?>([\s\S]*?)<\/table>/is";
		preg_match_all($pattern, $content, $match);
		if (!empty($match[0])) {
			/*
			$patterns = array();
			$patterns[0] = '/width="(\d+)"/';
			$patterns[1] = '/cellpadding="(\d+)"/';
			$patterns[2] = '/cellspacing="(\d+)"/';			
			$replacements = array();
			$replacements[0] = 'width="100%"';
			$replacements[1] = 'cellpadding="5"';
			$replacements[2] = 'cellspacing="0"';
			$table_str = preg_replace($patterns, $replacements, $match[0]);
			$content = preg_replace($pattern, $table_str, $content);
			*/
			$content = preg_replace('/<table([^>]*)width=".*?"([^>]*)>/is', '<table$1width="100%"$2>', $content);
		}
		return $content;
	}
	
	/**
	 * 
	 */
	public static function formatTopicMessage($message)
	{
		$format_message = '';
	    foreach($message as $val){

			if($val['img']){
				$format_message .= '<p class="topic-img"><img src="' . 'http://img.youxiduo.com' . $val['img'] . '" /></p>';
			}	
			if($val['text']){
				$format_message .= '<p class="topic-text">' . $val['text'] . '</p>';
			}										    
		}
		return $format_message;
	}
}