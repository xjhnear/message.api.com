<?php
use Yxd\Modules\Core\BackendController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Paginator;

use Youxiduo\System\Model\Admin;
use Youxiduo\System\Model\MessageSend;
use Youxiduo\System\Model\MessageDetail;
use Youxiduo\System\Model\MessageList;
use Youxiduo\System\Model\AccountDetail;
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
        $phone_number_arr = $phone_number_show = array();
        $phone_number_arr['unicom'] = $phone_number_arr['mobile'] = $phone_number_arr['telecom'] = $phone_number_arr['other'] = array();

        foreach ($mobile_arr as $item_phonenumber) {
            $phone_number_7 =  substr($item_phonenumber,0,7);
//            if (Redis::exists("isp_".$phone_number_7)) {
//                $operator = Redis::get("isp_".$phone_number_7);
//            } else {
//                $operator = '';
//            }
            $sql = 'SELECT isp FROM yii2_phone_model WHERE phone = "'.$phone_number_7.'"';
            $info_pn = DB::select($sql);
            if (!$info_pn) {
                $operator = '';
            } else {
                $operator = $info_pn[0]['isp'];
            }
            switch ($operator) {
                case "联通":
                    $phone_number_arr['unicom'][] = $item_phonenumber;
                    break;
                case "移动":
                    $phone_number_arr['mobile'][] = $item_phonenumber;
                    break;
                case "电信":
                    $phone_number_arr['telecom'][] = $item_phonenumber;
                    break;
                case "虚拟/联通":
                    $phone_number_arr['unicom'][] = $item_phonenumber;
                    break;
                case "虚拟/移动":
                    $phone_number_arr['mobile'][] = $item_phonenumber;
                    break;
                case "虚拟/电信":
                    $phone_number_arr['telecom'][] = $item_phonenumber;
                    break;
                default:
                    $phone_number_arr['other'][] = $item_phonenumber;
                    break;
            }
            $phone_number_show = array_merge($phone_number_arr['unicom'],$phone_number_arr['mobile'],$phone_number_arr['telecom'],$phone_number_arr['other']);
        }

        $count = count($mobile_arr);
        $rest = floor($info['balance']/$info['coefficient']);
        if ($count > $rest) {
            $r['error'] = 100;
            $r['remark'] = '您目前的余额只能发送'.$rest.'个号码';
            return Response::json($r);
        }

        $input = array();
        $input['message_code'] = 'M'.time();
        $input['phonenumbers'] = $mobile;
        $input['phonenumbers_json'] = json_encode($phone_number_arr);
        $input['count'] = $count;
        $input['content'] = $content;
        $input['content_json'] = json_encode(array('unicom'=>$content,'mobile'=>'','telecom'=>''));
        $input['create_time'] = time();
        $input['send_time'] = $sendTime;
        $input['create_name'] = $info['username'];
        $input['create_uid'] = $info['uid'];
        $message_id = MessageList::save($input);

        foreach ($mobile_arr as $item_phonenumber) {
            $phone_number_7 =  substr($item_phonenumber,0,7);
//            if (Redis::exists("isp_".$phone_number_7)) {
//                $operator = Redis::get("isp_".$phone_number_7);
//            } else {
//                $operator = '';
//            }
            $sql = 'SELECT isp FROM yii2_phone_model WHERE phone = "'.$phone_number_7.'"';
            $info_pn = DB::select($sql);
            if (!$info_pn) {
                $operator = '';
            } else {
                $operator = $info_pn[0]['isp'];
            }
            switch ($operator) {
                case "联通":
                    $operator_code = 1;
                    break;
                case "移动":
                    $operator_code = 2;
                    break;
                case "电信":
                    $operator_code = 3;
                    break;
                case "虚拟/联通":
                    $operator_code = 1;
                    break;
                case "虚拟/移动":
                    $operator_code = 2;
                    break;
                case "虚拟/电信":
                    $operator_code = 3;
                    break;
                default:
                    $operator_code = 4;
                    break;
            }
            $input_d = array();
            $input_d['phonenumber'] = $item_phonenumber;
            $input_d['message_id'] = $message_id;
            $input_d['message_code'] = $input['message_code'];
            $input_d['content'] = $content;
            $input_d['send_time'] = $sendTime;
            $input_d['operator'] = $operator_code;
            $input_d['create_uid'] = $info['uid'];
            MessageDetail::save($input_d);

            $cost = $count * $info['coefficient'];
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

        }

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
    public function status()
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