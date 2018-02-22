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
namespace Yxd\Utility;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App;
class Utility
{
	public static function formatContent($content,$video,array $config=array())
	{
		$_config = array('ishtml5'=>false);
		$config = array_merge($_config,$config);
		
		$header = '<html><header><meta charset="utf-8"></header>';
		$style = '<style>'
		          .'body { line-height: 16pt;font-family:"宋体";font-size: 15px;color:#3d3d3d;} h3{margin:10px 0;font-weight: bold;}'
				  .'.video{width:290px; height:200px; padding:2px;text-align:center; background:#e9eaeb; margin:10px auto 2px; box-shadow:0px 0px 1px #666;}'
				  .'.article{ margin:3px auto;padding:0px 1px;font-size:14px;color:#666;}'
				  .'.article p{margin:10px 0;line-height:20px;}.article p strong{color:#333;font-size:14px;}'
				  .'.article img {max-width:100%;border:0;}</style>';
		$body = '<body>';
		$footer = '</body></html>';
		$video_str = '';
		if ($video) {
			$video = self::formatVideo($video);
			$video_str	=	"<div class='video'><iframe src=\"".$video."\" height=\"200\" width=\"290\" frameborder=\"0\" allowfullscreen></iframe></div>";
		}
		$content_str = '<div class="article" id="content">'.$content.'</div>';
		if($config['ishtml5']==true){
			return $video_str.$content_str;
		}
		return $header.$style.$body.$video_str.$content_str.$footer;
	}
	
	public static function formatVideo($video)
	{
		$pattern = '/http:\/\/v.youku.com\/player\/getRealM3U8\/vid\/(.*)/is';
		preg_match($pattern, $video, $match);
		if (!empty($match[1])) {
			$str = 'http://player.youku.com/embed/';
			$video = $str.$match[1];
		}
		return $video;
	}
	
	public static function validateMobile($mobile)
	{
		if(!$mobile) return false;
		if(preg_match("/^13[0-9]{1}[0-9]{8}$|14[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|16[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$/",$mobile)){
			return true;
		}
		return false;
	}
	
	public static function validateEmail($email)
	{
		if(!$email) return false;
		if(preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/",$email)){
			return true;
		}
		return false;
	}
	
	public static function random($length,$type='alnum',$cunstom='')
	{
		$alnum = '0123456789';
		$alphr = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$all   = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$pool = '';
		switch($type){
			case 'custom':
				$pool = $cunstom ? : $alnum;
				break;
			case 'all':
				$pool = $all;
				break;
			case 'alnum':
				$pool = $alnum;
				break;
			case 'alphr':
				$pool = $alphr;
				break;
			default:
				$pool = $all;
				break;
		}
		
		return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
	}
	
    /**
	 * 密码的加密算法
	 */
	public static function cryptPwd($password)
	{
		if(strlen($password)==32) return $password;
		$salt = md5(substr($password,-1));
		$password = md5($password . $salt);
		return $password;
	}
	
	/**
	 * 生成邀请码
	 */
    public static function makeInvitationCode()
	{
	    $chars_array = array(
	        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
	        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
	        'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
	        'w', 'x', 'y',
	    );
	    $charsLen = count($chars_array) - 1;
	    $outputstr = "";
	    for ($i=0; $i<10; $i++)
	    {
	    $outputstr .= $chars_array[mt_rand(0, $charsLen)];
	    }
	    $out = array();
	    if(in_array($outputstr, $out)){
	    	self::makeInvitationCode();
	    }else{
	    	$out[] = $outputstr;
	    }
	    return $outputstr;
	}

	public static function loadByHttp2($url , $params = array(), $method = 'GET' ,$format='json',$platform='android' ,$multi = false, $extheaders = array()){
		 if(!function_exists('curl_init')) exit('Need to open the curl extension');
        $method = strtoupper($method);
        $keyname = $platform=='android' ? 'app.android_core_api_url' : 'app.ios_core_api_url';
        $url = Config::get($keyname) .$url;
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, 'PHP-SDK API');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);
        $headers = (array)$extheaders;
	 	$method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params))
                {
                    $url = $url . (strpos($url, '?') ? '&' : '?')
                        . (is_array($params) ? http_build_query($params) : $params);
                }
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
        curl_setopt($ci, CURLOPT_URL, $url);
        if($headers)
        {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
        }

        $response = curl_exec($ci);
        curl_close ($ci);
        if($format=='json'){
            $response = preg_replace('/[^\x20-\xff]*/', "", $response); //清除不可见字符
            $response = iconv("utf-8", "utf-8//ignore", $response); //UTF-8转码
            $response = json_decode($response,true);
        }
        //header('Content-type:application/json');
        //print_r($params_str);
        return $response;
	}

    public static function preParamsOrCurlProcess($params,$params_,$url,$method='GET')
    {
        if(!$params_) return NULL;
        $datainfo=array();
        //params数组定义我需要给接口什么参数
        //params_为接口中有什么参数
        if($params){
        	foreach ($params as $key => $value) {
        		# code... 
        		if(in_array($key,$params_,true) && (is_numeric($value) || !empty($value))){
                     //if($key == 'productImgpath')
                    if(is_array($value)) {
                        $datainfo[$key]=$value;
                    }elseif(is_numeric($value)){
                        if($value{0} == 0){
                            $datainfo[$key]=$value;
                        }else{
                            $datainfo[$key]=intval($value);
                        }
                        
                    } else{
                        $datainfo[$key]=trim($value);
                    }

                }

        	}
        }
        if($url){//echo $url;print_r($datainfo);
            return Utility::loadByHttp($url,$datainfo,$method);
        }else{
            return $datainfo;
        }
    }
	
    public static function loadByHttp($url , $params = array(), $method = 'GET' ,$format='json',$platform='android' ,$multi = false, $extheaders = array())
    {   //print_r($multi);
        if(!function_exists('curl_init')) exit('Need to open the curl extension');
        $method = strtoupper($method);
        if(strpos($url,'http://')!==0 && strpos($url,'https://')!==0){
	        $keyname = $platform=='android' ? 'app.android_core_api_url' : 'app.ios_core_api_url';
	        $url = Config::get($keyname) .$url;
        }
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, 'PHP-SDK API');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);
        $headers = (array)$extheaders;
        switch ($method)
        {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($params))
                {
                    if($multi)
                    {
                       foreach($multi as $key => $file)
                        {
                            if (class_exists('\CURLFile')) {
                                //echo file_get_contents($file['tmp_name']);exit;
                                $params[$key] = curl_file_create(realpath($file['tmp_name']),$file['type'],$file['name']);
                            }else{
                                $params[$key] = '@'.realpath($file['tmp_name']).";type=".$file['type'].";filename=".$file['name'];
                            }
                        }
                        if(sizeof($params) > 0){
                            foreach($params as $key => $val){
                                   $params[$key]= $val;
                            }
                        }
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    }
                    else
                    {
                    	if($format == 'json'){
                            $params_str = json_encode($params);
                            $headers[] = 'Content-Type: application/json; charset=utf-8';
                    	}else{
                    		$params_str = $params;
                    	}
                       
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params_str);
                    }
                }
                break;
            case 'HTMLFROM':
               curl_setopt($ci, CURLOPT_POST, TRUE);
               curl_setopt($ci, CURLOPT_POSTFIELDS,!empty($params) && is_array($params) ? http_build_query($params) : '');

               $headers[] = 'Accept: text/html,application/xhtml+xml';
            break;
            case 'DELETE':
            case 'GET':
                $method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params))
                {  
                    $url = $url . (strpos($url, '?') ? '&' : '?')
                        . (is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }

        //echo '<br/><br/><br/><br/><br/><br/><br/>'.$url.'<br/>';exit;
        //curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
        curl_setopt($ci, CURLOPT_URL, $url);
        if($headers)
        {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
        }
        $response = curl_exec($ci);
        $result = $response;
        $status_code = curl_getinfo($ci,CURLINFO_HTTP_CODE);
        curl_close ($ci);
        if($status_code!=200){
        	Log::info($response);
        }
        //
        if($format=='json'){
           $response = preg_replace('/[^\x20-\xff]*/', "", $response); //清除不可见字符
           $response = iconv("utf-8", "utf-8//ignore", $response); //UTF-8转码
           $response = json_decode($response,true);
           if($response===null){
           	   Log::error($result);
           }
        }
        
        return $response;
    }
	
	public static function SuperLoadByHttp($url , $params = array(), $method = 'GET' ,$isLog=false,$format='json',$platform='android' ,$multi = false, $extheaders = array())
    {   //print_r($multi);
        if(!function_exists('curl_init')) exit('Need to open the curl extension');
        $method = strtoupper($method);
        if(strpos($url,'http://')!==0 && strpos($url,'https://')!==0){
            $keyname = $platform=='android' ? 'app.android_core_api_url' : 'app.ios_core_api_url';
            $url = Config::get($keyname) .$url;
        }
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, 'PHP-SDK API');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);
        $headers = (array)$extheaders;
        switch ($method)
        {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($params))
                {
                    if($multi)
                    {
                        foreach($multi as $key => $file)
                        {
                            if (class_exists('\CURLFile')) {
                                $params[$key] = curl_file_create(realpath($file['tmp_name']),$file['type'],$file['name']);
                            } else {
                                $params[$key] = '@'.realpath($file['tmp_name']).";type=".$file['type'].";filename=".$file['name'];
                            }

                        }
                        if(sizeof($params) > 0){
                            foreach($params as $key => $val){
                                $params[$key]= $val;
                            }
                        }

                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    }
                    else
                    {
                        if($format == 'json'){
                            $params_str = json_encode($params);
                            $headers[] = 'Content-Type: application/json; charset=utf-8';
                        }else{
                            $params_str = $params;
                        }

                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params_str);
                    }
                }
                break;
            case 'HTMLFROM':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                curl_setopt($ci, CURLOPT_POSTFIELDS,!empty($params) && is_array($params) ? http_build_query($params) : '');
                $boundary = '---------------------------'.substr(md5(rand(0,32000)),0,10);
                $headers[] = "content-type: multipart/form-data; boundary=".$boundary."\r\n";
                //$headers[] = 'Accept: text/html,application/xhtml+xml';
                $headers[] = "content-type: application/x-www-form-urlencoded\r\n";
                //$headers[] = "content-length: ".strlen($data)."\r\n";
                $headers[] = "connection: close\r\n\r\n";
                break;
            case 'DELETE':
            case 'GET':
                $method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params))
                {
                    $url = $url . (strpos($url, '?') ? '&' : '?')
                        . (is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }

        //echo '<br/><br/><br/><br/><br/><br/><br/>'.$url.'<br/>';exit;
        //curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
        curl_setopt($ci, CURLOPT_URL, $url);
        if($headers)
        {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
        }
        $response = curl_exec($ci);

        $result = $response;
        Log::info($result);
        $result = json_decode($result,true);
        $httpcode_=curl_getinfo($ci,CURLINFO_HTTP_CODE);
        if($isLog){
            $error=array();
            if($httpcode_!=''){
                $error[]=' HTTP传输编码－> '.$httpcode_;
            }
            if(!empty($params_str) and $method=='POST' ){
                $error[]=' POST传输数据－> '.$params_str;
            }
            if(!empty($params) and $method=='GET'){
                $error[]=' GET传输数据－> '.http_build_query($params);
            }
            if(!empty($url)){
                $error[]=' 传输URL－> '.$url;
            }
            if(!empty($result['errorDescription'])){
                $error[]=' 返回的错误信息－> '.$result['errorDescription'];
            }
            if($httpcode_ == 200 and !empty($result['result']))
            {
                $error[]= !is_array($result['result']) ?'':'<br>'.json_encode(current($result['result']));
            }

            App::abort('200',join('',$error));
        }
        curl_close ($ci);

        if($format=='json'){
            $response = preg_replace('/[^\x20-\xff]*/', "", $response); //清除不可见字符
            $response = iconv("utf-8", "utf-8//ignore", $response); //UTF-8转码
            $response = json_decode($response,true);
            if($response===null){
                Log::error($result);
            }
        }

        return $response;
    }
    
    public static function sendVerifySMS($mobile,$code,$sms=true)
    {    	
    	$to = $mobile;
    	if($sms==true){
    	    $text = str_replace('{code}',$code,Config::get('sms.template',''));
    	}else{
    		$text = $code;
    	}
    	$from = 'test';
    	return self::sendSMS($to,$text,$from,$sms);
    }
    
    public static function sendSMS($to,$text,$from,$sms=true)
    {
    	$url = Config::get('sms.gateway');	
		$key = Config::get('sms.secret');;
		$sign = strtoupper(md5($from.$to.$text.$key));
		
		$params = array(
		    'from'=>$from,
		    'to'=>$to,
		    'txt'=>$text,
		    'sign'=>$sign,
		    'sendBy'=>$sms==true ? 'SMS':'IVR'			    
		);		
		return Utility::httpByJson($url,$params,'POST');
    }
    
    public static function httpByJson($url,$params = array(), $method = 'GET',$platform='',$multi = false, $extheaders = array())
    {
    	$format = 'json';
    	if(!function_exists('curl_init')) exit('Need to open the curl extension');
        $method = strtoupper($method);
        if(strpos($url,'http://')!==0){
	        $keyname = $platform=='android' ? 'app.android_core_api_url' : 'app.ios_core_api_url';
	        $url = Config::get($keyname) .$url;
        }
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, 'PHP-SDK API');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);
        $headers = (array)$extheaders;
        switch ($method)
        {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($params))
                {
                    if($multi)
                    {
                        foreach($multi as $key => $file)
                        {
                            $params[$key] = '@' . $file;
                        }
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    }
                    else
                    {
                        $params_str = $format=='json' ? json_encode($params) : http_build_query($params);
                        $headers[] = 'Content-Type: application/json; charset=utf-8';
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params_str);
                    }
                }
                break;
            case 'DELETE':
            case 'GET':
                $method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params))
                {
                    $url = $url . (strpos($url, '?') ? '&' : '?')
                        . (is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
        curl_setopt($ci, CURLOPT_URL, $url);
        if($headers)
        {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
        }

        $response = curl_exec($ci);
        curl_close ($ci);        
        
        return $response;
    }

    /**
     * 临时过滤文章图片
     */
    public static function _getArticleContent($title, $editor, $date, $content, $showimg = 0){
        // 临时过滤图片
        if ($showimg == 1) {
            $patterns = array();
            $patterns[0] = '/<img (.*?)src="(.*?)"(.*?) \/>/is';
            $replacements[0] = '';
            $content = preg_replace($patterns, $replacements, $content);
        }
        //替换内容中的分页标签
        $content = str_replace('_baidu_page_break_tag_', '', $content);
        return self::_getAppHtml($title, $editor, $date, $content);
        //$content = processContentTables($content);
        //return $this->_app_html_header($title, $editor, $date, $content);
    }

    public static function _processVideoContent($content) {
        $pattern = '/<video (.*?)src="(.*?)"(.*?)><\/video>/is';

        preg_match($pattern, $content, $match);
        if ($match && $match[2]) {
            $video = self::_processVideo($match[2]);
            $video	=	"<div class='video'><iframe src=\"".$video."\" height=\"140\" width=\"290\" frameborder=\"0\" allowfullscreen></iframe></div>";
            $content = preg_replace($pattern, $video, $content);
        }
        return $content;
    }

    public static function _processVideo($video) {
        $pattern = '/http:\/\/v.youku.com\/player\/getRealM3U8\/vid\/(.*)/is';
        preg_match($pattern, $video, $match);
        if (!empty($match[1])) {
            $str = 'http://player.youku.com/embed/';
            $video = $str.$match[1];
        }
        return $video;
    }

    public static function _convertFormat($content) {
        $returnText = '';
        $sections   = array();
        $pattern1   = '%^(<(\w++)[^>]*+>.*?</\2>)$%smx';
        $pattern2 = '%(?:\s*\n\s*+|^)(.+?)(?:\s+$)?(?=\s*\n\s*|$)%x';

        $sections = preg_split($pattern1, $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

        for ($i = 0; $i < count($sections); $i++) {
            if (preg_match($pattern1, $sections[$i])) {
                $returnText .= "\n" . $sections[$i] . "\n";
                $i++;
            } else {
                $returnText .= preg_replace($pattern2, "<p>$1</p>", $sections[$i]);
            }
        }
        $returnText = preg_replace('/^\s+/', '', $returnText);
        $returnText = preg_replace('/\s+$/', '', $returnText);

        return $returnText;
    }

    public static function _getAppHtml($title, $editor, $date, $content){
        require_once base_path().'/libraries/HTMLPurifier/HTMLPurifier.auto.php';
        $htmlPurifier = new \HTMLPurifier(array(
            'AutoFormat.AutoParagraph' => TRUE,
            'HTML.TidyLevel' => 'medium',
            'Cache.DefinitionImpl' => NULL,
            'HTML.SafeIframe' => TRUE,
            'URI.SafeIframeRegexp' => '%^http://(player.youku.)%'
        ));

        $content = $htmlPurifier->purify($content);

        $content =  str_replace("　", "", $content);
// 		$content =  preg_replace('/(<[^>]*>)([^>]*)<br \/>/i', "\r\n$1\r\n$2\r\n", $content);

        $content = strip_tags($content, '<video><iframe><a><img><table><tr><th><td><span><br>');

        $content =  self::_convertFormat($content);

        $content = preg_replace('/<img[^>]*src="(.*?)"[^>]*>/is', '<div class="img_box" style="overflow:hidden;"><img src=\\1></div>', $content);
        $content = preg_replace('/<table([^>]*)width=".*?"([^>]*)>/is', '<table$1width="100%"$2>', $content);

        #$html = '$header = "<html><header><meta charset="utf-8"></header>"';
        $html = <<<EOF
        <style>
            body{line-height:1.5em;font-family:"宋体";font-size:15px;color:#3d3d3d; margin:0; word-wrap: break-word; word-break:break-word; }
            .art_head h3{margin:10px auto;font-weight: bold;} .art_head p{ margin:10px auto;}
            img{max-width:100%;} a{color:#000;text-decoration:underline;}
            .img_box{width:100%; min-height:100px; text-align:center; margin-bottom:5px;}
            .img_box img{vertical-align: middle;}
            #content {margin:0 0.8em;}
            #content p{padding:0px; margin:0.6em 0;text-indent:2em;}
            #content iframe {width:290px; margin:0 auto 15px auto; display:block; }
        </style>
EOF;
        $html .='<div align="center" class="art_head"><h3>'.$title.'</h3><p>小编:'.$editor.'&nbsp;&nbsp;'.$date.'</p></div>';
        $html .= '<div id="content">'.$content.'</div>';

        return $html;
    }

    //生成新游预告详情内容
    public static function _noticeDetailOld($data) {
        $br_img		=	Config::get('app.img_url') . "/res/images/bg_new.png";
        require_once base_path().'/libraries/HTMLPurifier/HTMLPurifier.auto.php';
        $htmlPurifier = new \HTMLPurifier(array(
            'AutoFormat.AutoParagraph' => TRUE,
            'HTML.TidyLevel' => 'medium',
            'Cache.DefinitionImpl' => NULL,
            'HTML.SafeIframe' => TRUE,
            'URI.SafeIframeRegexp' => '%^http://(player.youku.)%'
        ));

        $video_str = '';
        if ($data['video_url']) {
            $video = self::_processVideo($data['video_url']);
            $video_str	=	"<div class='video'><iframe src=\"".$video."\" height=\"140\" width=\"290\" frameborder=\"0\" allowfullscreen></iframe></div>";
        }
        $content = self::_processVideoContent($data['art_content']);

        $content = $htmlPurifier->purify($content);
        $content =  str_replace("　", "", $content);
        $content =  preg_replace('/(<[^>]*>)([^>]*)<br \/>/i', "\r\n$1\r\n$2\r\n", $content);
        $content = strip_tags($content, '<video><iframe><a><img><table><tr><th><td>');
        $content =  self::_convertFormat($content);

        $content = preg_replace('/<img[^>]*src="(.*?)"[^>]*>/is', '<div class="img_box" style="overflow:hidden;"><img src=\\1></div>', $content);
        $content = preg_replace('/<table([^>]*)width=".*?"([^>]*)>/is', '<table$1width="100%"$2>', $content);
        $imgsrc		=	Config::get('app.img_url') . $data['pic'];
        $html  = "<html>
			<head>
				<meta charset='UTF-8'>
				<title></title>
				<style>
					p {margin:0;padding:0;border:0;}
					html,body{margin:0; padding:0;line-height: 16pt;font-family:'宋体';font-size: 15px;color:#3d3d3d;}
					#head{width:96%; height:75px; padding:6px; background:#f5f5f5; border-bottom:1px solid #c3c2c2; position:relative;}
					#head div.img{display:block; width:70px; height:70px; overflow:hidden; float:left; border:none;border-radius:10px}
					#head .txt{float: left;height: 71px;overflow: hidden;padding-left: 12px;line-height: 20px;text-align: left;width: 46%;}
					#head .txt h3{font-size:18px;height:22px;margin: 4px 0;overflow:hidden}
					#head .txt h3 a{color:#373737; text-decoration:none;}
					#head .txt p{color:#535151; font-size:13px; line-height:20px;}
					#head .span{width:70px; height:70px;line-height:70px;overflow:hidden;font-weight:bold;text-align:center;position:absolute; right:10px; top:10px; background:url('".$br_img."') no-repeat; background-size:70px 70px; font-size:13px; color:#f88906;}
					.video{width:290px; height:140px; padding:2px; background:#e9eaeb; margin:10px auto 2px; box-shadow:0px 0px 1px #666;}
					.video img{width:290px; height:140px;}
					img{max-width:100%;}
					.img_box{width:100%; min-height:100px; text-align:center; margin-bottom:5px;}
					.img_box img{vertical-align: middle;}
					iframe {width:290px; margin:0 auto; display:block;}
					.article {margin:0 0.8em;}
					.article p{padding:0px; margin:0.6em 0;text-indent:2em;line-height:20px;}
				</style>
			</head>
			<body>
				<div id='head'>
					<div class='img'><img style='border:none;width:70px;height:70px;' src='".$imgsrc."' alt='' /></div>
					<div class='txt'>
						<h3>".$data['gname']."</h3>
						<p>游戏类型：".$data['type']."</p>
						<p>开发商：".$data['company']."</p>
					</div>
					<div class='span'>".$data['date']."</div>
				</div>
				".$video_str."
				<div class='article' id='content'>".$content."</div>
			</body>
			</html>";
        return $html;
    }

    public static function get_real_ip(){
        $ip=false;
        if(!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
            for ($i = 0; $i < count($ips); $i++) {
                if (!eregi ("^(10|172\.16|192\.168)\.", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    public static function getIp()
    {
        static $realip = NULL;

        if ($realip !== NULL)
        {
            return $realip;
        }

        if (isset($_SERVER))
        {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                foreach ($arr AS $ip)
                {
                    $ip = trim($ip);

                    if ($ip != 'unknown')
                    {
                        $realip = $ip;

                        break;
                    }
                }
            }
            elseif (isset($_SERVER['HTTP_CLIENT_IP']))
            {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            }
            else
            {
                if (isset($_SERVER['REMOTE_ADDR']))
                {
                    $realip = $_SERVER['REMOTE_ADDR'];
                }
                else
                {
                    $realip = '0.0.0.0';
                }
            }
        }
        else
        {
            if (getenv('HTTP_X_FORWARDED_FOR'))
            {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            }
            elseif (getenv('HTTP_CLIENT_IP'))
            {
                $realip = getenv('HTTP_CLIENT_IP');
            }
            else
            {
                $realip = getenv('REMOTE_ADDR');
            }
        }

        preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
        $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

        return $realip;
    }

    /**
     * 	过滤APP评论中的屏蔽词语
     * 	@param	$encode_str	string	转码过的评论内容
     *
     * */
    public static function _filterCommentStr($encode_str)
    {
        $content = $encode_str;
        //先将内容解码回UTF-8可读文字
        $content = self::decode_comment_info($content);
        //再对转码回来的内容进行屏蔽词语过滤
        $content = self::comment_filter($content);
        //再将过滤后的内容转码回UTF_16编码
        $plcontent = self::string_convert_encode($content);
        return $plcontent;
    }

    /**
     * 对转码过的评论内容进行解码
     * 评论转码规则：先unicode再base64_encode
     * @param	$encode_str		string	经过转码需要解码的文本字符
     * @param	解码说明：先base64_decode解码，再unicode decode解码
     */
    public static function decode_comment_info($encode_str)
    {
        if(empty($encode_str)){
            return '';
        }
        $encode_str = str_replace("\n", '', str_replace("\r", '', $encode_str));
        #//6xlABnDlT9kCh1DU6JY4ZO
        #先检测内容是否经过转码的,如果没有转码直接返回当前内容
        //检测当前内容是否经过base64_encode的编码
        if ($encode_str != base64_encode(base64_decode($encode_str))) {
            return $encode_str;
        }
        $decode_str	=	'';
        $base64_decode_str	=	'';
        //先对转码的字符进行base64_decode解码
        $base64_decode_str	=	base64_decode($encode_str);
        //再进行unicode解码
        $decode_str =	mb_convert_encoding($base64_decode_str,'UTF-8','UTF-16');

        return stripslashes($decode_str);
    }

    /**
     * 对评论中敏感关键字进行过滤替换
     * @param	$content	string	需要过滤的内容
     * @param	$finalremove	return 过滤后的内容
     */
    public static function comment_filter($content)
    {
        // 使用这个插件可以将评论中敏感关键字（也就是传说中的“有害信息”）进行过滤（分隔符,），将敏感文字替换为×。
        // 修改yxd.php配置文件中‘filter_chars’的内容，增加你需要的过滤关键字，关键字之间使用分隔符,进行分割。
        $banned_contents = Config::get('yxd.filter_chars');

        $patterns = explode(",", $banned_contents);
        $finalremove = $content;
        $piece_front="";
        $piece_back="";
        $piece_replace = Config::get('yxd.replace_chars'); //将关键字替换后的字符
        $_filter_count = count($patterns);
        for ($x=0; $x < $_filter_count; $x++) {
            $safety=0;
            while(strstr(strtolower($finalremove),strtolower($patterns[$x]))) {
                # find & remove all occurrence
                $safety=$safety+1;
                if ($safety >= 100000) { break; }

                $occ=strpos(strtolower($finalremove),strtolower($patterns[$x]));
                $piece_front=substr($finalremove,0,$occ);
                $piece_back=substr($finalremove,($occ+strlen($patterns[$x])));
                $finalremove=$piece_front . $piece_replace . $piece_back;
            } # while

        }
        return $finalremove;
    }

    /**
     * 对内容先进行unicode编码再base64_encode编码
     * @param	$input_str	string	需要编码的内容
     * @param	$base64_encode_str	return	返回经过编码后的内容
     * @param	编码说明:unicode 编码时需要将当前UTF-8编码的内容转码为UTF-16LE编码
     *
     */
    public static function string_convert_encode($input_str)
    {
        if($input_str == ''){
            return null;
        }
        $source_str = $input_str;
        //unicode转码到UTF-16
        $unicode_encode_str	= mb_convert_encoding($source_str,'UTF-16','auto');//iconv('UTF-8', 'UTF-16', $source_str);
        //再base64_encode
        $base64_encode_str	= base64_encode($unicode_encode_str);
        return $base64_encode_str;
    }
    
    public static function getImageUrl($img,$size=0)
    {
    	if(!$img) return '';
		if(strpos($img,'http')===0) return $img;
		if($size>0 && in_array($size,array(50,60,90,120,240,320))){
			$file = $tmp = str_replace('.','_' . $size . '.',$img);
			$a = explode('?',$tmp);
			if(is_readable(storage_path() . $a[0])){
				$img = $file;
			}			
		}
        if(strpos($img,'/')===false){
        	$base_img_url = 'http://test.youxiduo.com:8080/module_file_system/file/';
        }else{
            $first_path = explode('/',$img);        
            $base_img_url = isset($first_path[1]) && $first_path[1] == 'uploads' ? 'http://www.youxiduo.com' : Config::get('app.image_url');
        }
        return $base_img_url . $img;
    }

    public static function getPageStart($total, $offset, $num) {
        if ($total == 0) {
            return 0;
        }
        $start = $offset - 1 < 0 ? $num : $offset * $num;
        if ($start >= $total) {
            if ($total % $num == 0) {
                return ($total - $num)/$num;
            } else {
                return ($total - ($total % $num))/$num + 1;
            }
        } else {
            return $start/$num;
        }
    }
}
