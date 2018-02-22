<?php
namespace Youxiduo\Helper;

use Illuminate\Support\Facades\Log;

class BaseHttp
{
    public static function http($url,$params=array(),$method='GET',$format='text',$multi = false, $extheaders = array())
	{
        //print_r($multi);
        $platform = '';
        if($_SERVER['REDIRECT_URL']){
            $server = explode('/',$_SERVER['REDIRECT_URL']);
            $arr = array('v4giftbag','v4product','v4lotteryproduct','v4message');
            if(in_array($server[1],$arr)){
                $platform = "ios";
                $params['platform'] = $platform;
            }
        }
        $params = MyHelpLx::add_keys_for_modules($url,$params,$platform);//添加paltform参数

    	if(!function_exists('curl_init')) exit('Need to open the curl extension');
        $method = strtoupper($method);        
        $ci = curl_init();
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
                        $params_str = $format=='json' ? json_encode($params) : self::buildHttpQuery($params);
                        //$headers[] = 'Content-Type: application/json; charset=utf-8';
                        if($format=='json'){
                            $headers[] = 'Content-Type: application/json; charset=utf-8';
                        }
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
                        . (is_array($params) ? self::buildHttpQuery($params) : $params);
                }
                break;
            case 'UTF8':
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
                        $params_str = $format=='json' ? json_encode($params) : self::buildHttpQuery($params);
                        //$headers[] = 'Content-Type: application/json; charset=utf-8';
                        $headers[] = 'content-type: application/x-www-form-urlencoded;charset=UTF-8';
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params_str);
                    }
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
//         echo $url;print_r($params);print_r($response);
        $status_code = curl_getinfo($ci,CURLINFO_HTTP_CODE);
        curl_close ($ci);
        if($status_code==200) return json_decode($response,true);
        Log::error($response);
        //var_dump($response);exit();
        return false;
	}	
	
	public static function buildHttpQuery($params)
	{
		$query_attr = array();
		foreach($params as $key=>$val){
			if(is_array($val)){
				foreach($val as $one){
					$query_attr[] = $key . '=' . urlencode($one);
				}
			}else{
				$query_attr[] = $key . '=' . urlencode($val);
			}
		}
		return implode('&',$query_attr);
	}
}