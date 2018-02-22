<?php
namespace Yxd\Services;

use Yxd\Modules\Core\BaseService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis as Redis;

class Service extends BaseService
{		
	public static function send($status=200,$data=null,$error_code='',$error_description='')
	{
		return array('status'=>$status,'data'=>$data,'error_code'=>$error_code,'error_description'=>$error_description);
	}
	public static function joinImgUrl($img,$size=0)
	{
		if(!$img) return '';
		if(strpos($img,'http')===0) return $img;
		$last = '';
		if($size>0 && in_array($size,array(50,60,90,120,240,320))){
			$file = $tmp = str_replace('.','_' . $size . '.',$img);
			$a = explode('?',$tmp);
			if(is_readable(storage_path() . $a[0])){
				$img = $file;
			}			
		}
		return Config::get('app.img_url') . $img . $last;
	}
	
    /**
     * 
     */
    public static function loadByJson($url,$params=array(),$method='GET',$multi = false, $extheaders = array())
    {
    	$url = 'http://121.40.78.19:18080/' . $url;
    	if(!function_exists('curl_init')) exit('Need to open the curl extension');
        $method = strtoupper($method);
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, 'PHP-SDK OAuth2.0');
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
                        curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
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
	    if(!curl_errno($ci)){
		    $code = curl_getinfo($ci,CURLINFO_HTTP_CODE);
		    curl_close ($ci);
			if($code==200){
			    return json_decode($response,true);
			}elseif($code==404){
				throw new \Exception($url . ' is not exists!');
			}elseif($code==500){
			    throw new \Exception('java service error');
			}
		}else{
			throw new \Exception(curl_error($ci));
		}
        curl_close ($ci);
    }
}