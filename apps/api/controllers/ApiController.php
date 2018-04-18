<?php
use Yxd\Modules\Core\BackendController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Paginator;

use Youxiduo\System\Model\Admin;
use Youxiduo\System\Model\MessageSend;
use Youxiduo\System\Model\MessageDetail;
use Youxiduo\System\Model\MessageList;
use Youxiduo\System\Model\MessageListDetail;
use Youxiduo\System\Model\AccountDetail;
use Youxiduo\System\Model\MessageCall;
//use Youxiduo\Api\AdminService;
//use Redis;
use Illuminate\Support\Facades\DB;

class ApiController extends BaseController
{
	/**
	 * 非法关键词查询
	 */
	public function checkkeyword()
	{
        $account = Input::get('account');
        $password = Input::get('password');
        if ($account=="" || $password=="") {
            $r['error'] = 100;
            $r['remark'] = '用户名或密码不能为空';
            return Response::json($r);
        }
        $info = $this->checkPassword($account,$password);
        if (!$info) {
            $r['error'] = 100;
            $r['remark'] = '用户名或密码错误';
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
        $account = Input::get('account');
        $password = Input::get('password');
        if ($account=="" || $password=="") {
            $r['error'] = 100;
            $r['remark'] = '用户名或密码不能为空';
            return Response::json($r);
        }
        $info = $this->checkPassword($account,$password);
        if (!$info) {
            $r['error'] = 100;
            $r['remark'] = '用户名或密码错误';
            return Response::json($r);
        }
        $mobile = Input::get('mobile');
        $content = Input::get('content');
        $sendTime = Input::get('sendTime');
        if ($mobile=="") {
            $r['error'] = 100;
            $r['remark'] = '短信号码不能为空';
            return Response::json($r);
        }
        if ($content=="") {
            $r['error'] = 100;
            $r['remark'] = '短信内容不能为空';
            return Response::json($r);
        }
        if ($sendTime=="") {
            $sendTime = time();
        }
        $params = array(
            'action'=>'checkkeyword',
            'content'=>$content
        );
        $r = $this->unifySend('sms', $params);
        $out = array();
        if ($r['returnstatus'] == 'Faild') {
            $r['error'] = 100;
            $r['remark'] = '包含非法字符';
            return Response::json($r);
        }
        $mobile_arr = explode(',', $mobile);
        $count = count($mobile_arr);
        $rest = $info['balance'];
        if ($count > $rest) {
            $r['error'] = 100;
            $r['remark'] = '您目前的余额只能发送'.$rest.'个号码';
            return Response::json($r);
        }

        $input = array();
        $input['message_code'] = 'M'.time() . rand(0,9);
        $input_ld['phonenumbers'] = $mobile;
        $input_ld['phonenumbers_json'] = '';
        $input['count'] = $count;
        $input['content'] = $content;
        $message_count = mb_strlen($content);
        $power = 1;
        if ($message_count > 130) {
            $power = 3;
        } elseif ($message_count > 70) {
            $power = 2;
        } else {
            $power = 1;
        }
        $input_ld['content_json'] = json_encode(array('unicom'=>$content,'mobile'=>'','telecom'=>''));
        $input['create_time'] = time();
        $input['send_time'] = $sendTime;
        $input['create_name'] = $info['username'];
        $input['create_uid'] = $info['uid'];
        $message_id = MessageList::save($input);
        $input_ld['message_id'] = $message_id;
        MessageListDetail::save($input_ld);

        $cost = $count * $power;
        $input_u = array();
        $input_u['uid'] = $info['uid'];
        $input_u['balance'] = $info['balance'] - $cost;
        Admin::save($input_u);

        $input_ad = array();
        $input_ad['uid'] = $info['uid'];
        $input_ad['change_count'] = $cost;
        $input_ad['change_type'] = 2;
        $input_ad['balance'] = $input_u['balance'];
        $input_ad['remark'] = '消耗';
        $input_ad['op_uid'] = $info['uid'];
        $input_ad['create_time'] = time();
        AccountDetail::save($input_ad);

        $out['error'] = 0;
        $out['message'] = 'success';
        $out['message_id'] = $message_id;
        $out['message_code'] = $input['message_code'];

        return Response::json($out);
    }

    /**
     * 余额及已发送量查询
     */
    public function overage()
    {
        $account = Input::get('account');
        $password = Input::get('password');
        if ($account=="" || $password=="") {
            $r['error'] = 100;
            $r['remark'] = '用户名或密码不能为空';
            return Response::json($r);
        }
        $info = $this->checkPassword($account,$password);
        if (!$info) {
            $r['error'] = 100;
            $r['remark'] = '用户名或密码错误';
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
    public function status1()
    {
        $account = Input::get('account');
        $password = Input::get('password');
        if ($account=="" || $password=="") {
            $r['error'] = 100;
            $r['remark'] = '用户名或密码不能为空';
            return Response::json($r);
        }
        $info = $this->checkPassword($account,$password);
        if (!$info) {
            $r['error'] = 100;
            $r['remark'] = '用户名或密码错误';
            return Response::json($r);
        }
        $message_id = Input::get('message_id');
        if ($message_id=="") {
            $r['error'] = 100;
            $r['remark'] = '短信ID不能为空';
            return Response::json($r);
        }
        $data_list = MessageSend::getList($message_id);
        if (!$data_list) {
            $r['error'] = 100;
            $r['remark'] = '当前没有待查询的短信状态';
            return Response::json($r);
        }
        $status_arr = $status = array();
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

    /**
     * 上行
     */
    public function call()
    {
        $account = Input::get('account');
        $password = Input::get('password');
        if ($account=="" || $password=="") {
            $r['error'] = 100;
            $r['remark'] = '用户名或密码不能为空';
            return Response::json($r);
        }
        $info = $this->checkPassword($account,$password);
        if (!$info) {
            $r['error'] = 100;
            $r['remark'] = '用户名或密码错误';
            return Response::json($r);
        }
        $mobile = Input::get('mobile');
        if ($mobile=="") {
            $r['error'] = 100;
            $r['remark'] = '手机号码不能为空';
            return Response::json($r);
        }
        $data_list = MessageCall::getList($mobile,$info['uid']);
        if (!$data_list) {
            $r['error'] = 100;
            $r['remark'] = '当前没有待查询的上行信息';
            return Response::json($r);
        }
        $call = $call_arr = array();
        foreach ($data_list as $item) {
            $call_arr = array();
            $call_arr['mobile'] = $item['phonenumber'];
            $call_arr['content'] = $item['content'];
            $call_arr['receivetime'] = date('Y-m-d H:i:s', $item['return_time']);
            $call[] = $call_arr;
        }
        $out['error'] = 0;
        $out['message'] = 'success';
        $out['call'] = $call;

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

        $sql = 'SELECT * FROM yii2_admin WHERE username = "'.$account.'" AND status=1 AND is_del=0';
        $info = DB::select($sql);
        if (!$info) {
            return false;
        }
        $info = $info[0];
        $check = password_verify($password,$info['password']);
        if (!$check) {
            return false;
        }
        return $info;
    }

}