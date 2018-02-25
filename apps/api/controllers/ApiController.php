<?php
use Yxd\Modules\Core\CacheService;
use Illuminate\Support\Facades\Input;
use Youxiduo\Api\AdminService;
use Youxiduo\Api\model\Admin;

class ApiController extends BaseController
{
	/**
	 * 非法关键词查询
	 */
	public function checkkeyword()
	{
	    print_r(crypt('123456', 'SbSY36BLw3V2lU-GB7ZAzCVJKDFx82IJ'));exit;
        $account = Input::get('account');
        $password = Input::get('password');
        $user = Admin::checkPassword($account,'username',$password);
        if (!$user) {
            $r['error'] = 100;
            $r['remark'] = '用户名密码错误';
            return Response::json($r);
        }
        print_r($user);exit;
		$content = Input::get('content');
		$params = array(
			'action'=>'checkkeyword',
			'content'=>$content
		);
		$r = $this->unifySend('sms', $params);

		return Response::json($r);
	}

	protected function unifySend($action,$params)
	{
		$url = 'http://139.196.58.248:5577/'.$action.'.aspx';
		$userid = '8710';
		$account = '借鸿移动贷款';
		$password = 'a123456';

		$params['userid'] = $userid;
		$params['account'] = $account;
		$params['password'] = $password;
		$o = "";
		foreach ( $params as $k => $v )
		{
//            $o.= "$k=" . urlencode(iconv('UTF-8', 'GB2312', $v)). "&" ;
			$o.= "$k=" . urlencode($v). "&" ;
		}
		$post_data = substr($o,0,-1);
		$re = $this->request_post($url, $post_data);

		return $this->xmlToArray($re);
	}

	protected function request_post($url = '', $param = '') {
		if (empty($url) || empty($param)) {
			return false;
		}

		$postUrl = $url;
		$curlPost = $param;
		$ch = curl_init();//初始化curl
		curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
		curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
		$data = curl_exec($ch);//运行curl
		curl_close($ch);

		return $data;
	}

	protected function xmlToArray($xml){

		//禁止引用外部xml实体
		libxml_disable_entity_loader(true);
		$xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		$val = json_decode(json_encode($xmlstring),true);
		return $val;
	}

}