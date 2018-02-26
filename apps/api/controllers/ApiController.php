<?php
use Yxd\Modules\Core\CacheService;
use Illuminate\Support\Facades\Input;
use Youxiduo\Api\AdminService;
use Youxiduo\Api\model\Admin;
use Youxiduo\System\model\MessageSend;
use Youxiduo\System\Model\MessageDetail;
use Youxiduo\System\Model\MessageList;
use Youxiduo\System\Model\AccountDetail;

class ApiController extends BaseController
{
	/**
	 * 非法关键词查询
	 */
	public function checkkeyword()
	{
        $account = Input::get('account');
        $password = Input::get('password');
        $info = $this->checkPassword($account,$password);
        if (!$info) {
            $r['error'] = 100;
            $r['remark'] = '用户名密码错误';
            return Response::json($r);
        }
		$content = Input::get('content');
		$params = array(
			'action'=>'checkkeyword',
			'content'=>$content
		);
		$r = $this->unifySend('sms', $params);
        $out = array();
        if ($r['returnstatus'] == 'Faild') {
            $out['error'] = 100;
            $out['message'] = $r['message'];
            $out['content'] = $r['checkCounts'];
        } else {
            $out['error'] = 0;
            $out['message'] = $r['message'];
            $out['content'] = $r['checkCounts'];
        }

		return Response::json($out);
	}

    /**
     * 发送
     */
    public function sms()
    {

    }

    /**
     * 余额及已发送量查询
     */
    public function overage()
    {
        $account = Input::get('account');
        $password = Input::get('password');
        $info = $this->checkPassword($account,$password);
        if (!$info) {
            $r['error'] = 100;
            $r['remark'] = '用户名密码错误';
            return Response::json($r);
        }
        $out['error'] = 0;
        $out['message'] = 'success';
        $out['balance'] = $info['balance'];
        $out['count'] = MessageSend::getCount($info['uid']);

        return Response::json($out);
    }

    /**
     * 状态报告
     */
    public function status()
    {
        $account = Input::get('account');
        $password = Input::get('password');
        $info = $this->checkPassword($account,$password);
        if (!$info) {
            $r['error'] = 100;
            $r['remark'] = '用户名密码错误';
            return Response::json($r);
        }
        $task_id = Input::get('task_id');
        $data_list = MessageSend::getList($task_id);
        $status_arr = array();
        foreach ($data_list as $item) {
            $status_arr = array();
            $status_arr['phonenumber'] = $item['phonenumber'];
            $status_arr['status'] = $item['status'];
            $status[] = $status_arr;
        }
        $out['error'] = 0;
        $out['message'] = 'success';
        $out['status'] = $status;

        return Response::json($out);
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

    protected function checkPassword($account,$password){

        $info = Admin::getInfo($account);
        $check = password_verify($password,$info['password']);
        if (!$check) {
            return false;
        }
        return $info;
    }

}