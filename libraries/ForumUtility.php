<?php

class ForumUtility
{
	/**
	 * 格式化URL
	 */
	public static function parse_url($url)
	{
		$str = '<div class="url">';
	    if ( preg_match("/(youku.com|youtube.com|ku6.com|sohu.com|mofile.com|sina.com.cn|tudou.com|yinyuetai.com)/i", $url[0] , $hosts) ){
	        $str .= '<a href="'.$url[0].'" target="_blank" event-node="show_url_detail" class="ico-url-video"></a>';
	    } else if ( strpos( $url[0] , 'taobao.com') ){
	        $str .= '<a href="'.$url[0].'" target="_blank" event-node="show_url_detail" class="ico-url-taobao"></a>';
	    } else {
	        $str .= '<a href="'.$url[0].'" target="_blank" event-node="show_url_detail" class="ico-url-web">'.$url[0].'</a>';
	    }
	    $str .= '<div class="url-detail" style="display:none;">'.$url[0].'</div></div>';
	    return $str;		
	}
	
	/**
	 * 格式化@好友
	 */
	public static function parse_at()
	{
		
	}
	
	/**
	 * 格式化图片
	 */
	public static function parse_image($data)
	{		
		if(preg_match("/\[img:(.+?)\]/is",$data[0],$img)){
			//print_r($img[1]);die;
		    $data[0] = preg_replace("/(\[img:.+?\])/is",'<img src="' . $img[1] . '" />',$data[0]);
		}
		return $data[0];
	}
	
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
	        return preg_replace("/\[.+?\]/i","<img src='/static/img/expression/miniblog/".$info['filename']."' />",$data[0]);
	    }else {
	        return $data[0];
	    }
	}
	
	public static function parseForWeb($text)
	{
		$text = htmlspecialchars_decode($text);
		//表情
		$text = preg_replace_callback("/(\[.+?\])/is",function($data){return self::parse_expression($data);},$text);
		//@好友
		$text = preg_replace_callback("/@([\w\x{2e80}-\x{9fff}\-]+)/u", function($data){return self::parse_at($data);},$text);
		//图片
		$text = preg_replace_callback('/(\[img:.+?\])/is',function($data){return self::parse_image($data);},$text);
		//网址
		//$text = preg_replace_callback('/((?:https?|mailto|ftp):\/\/([^\x{2e80}-\x{9fff}\s<\'\"“”‘’，。}]*)?)/u', function($data){return self::parse_url($data);}, $text);
		
		
		
		return $text;
	}
	
	public static function formatDate($time,$type='normal')
	{
		$current = (int)microtime(true);
		$diff = $time - $current;
		$diffDay = (int)date('z',$time) - (int)date('z',$current);
		
		//if($type=='mohu'){
			if($diff<60){
				return $diff . '秒前';
			}elseif($diff<3600){
				return intval($diff/60) . '分钟前';
			}elseif($diff>=3600 && $diffDay ==0){
				return intval($diff/3600) . '小时前';
			}elseif($diffDay>0 && $diffDay<=30){
				return intval($diffDay) . '天前';
			}else{
				return date('m月d日 H:i',$time);
			}
		//}
	}	
}