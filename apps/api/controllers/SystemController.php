<?php
use Yxd\Modules\Core\CacheService;
use Illuminate\Support\Facades\Input;
use Youxiduo\System\MessageService;
use Youxiduo\System\Model\MessageDetail;
use Youxiduo\System\Model\MessageList;
use Youxiduo\System\Model\MessageSend;
use Youxiduo\System\Model\Channel;
use Youxiduo\System\Model\Account;
use Youxiduo\System\Model\AccountDetail;
use Youxiduo\System\Model\Report;

class SystemController extends BaseController
{
	/**
	 * 短信发送
	 */
	public function sms()
	{
		$search['message_id'] = Input::get('message_id');
		if (!$search['message_id']) return Response::json(array());
		$pageSize = 20000;
		$input_l = array();
		$input_l['message_id'] = $search['message_id'];
		$input_l['status'] = 5;
		MessageList::save($input_l);
		$data_list = MessageList::getInfoById($search['message_id']);
		$data_list['content'] = json_decode($data_list['content_json'],true);
		$content_arr['1'] =  $data_list['content']['unicom'];
		$content_arr['2'] =  ($data_list['content']['mobile']<>'')?$data_list['content']['mobile']:$data_list['content']['unicom'];
		$content_arr['3'] =  ($data_list['content']['telecom']<>'')?$data_list['content']['telecom']:$data_list['content']['unicom'];
		$sendTime = date('Y-m-d H:i:s', $data_list['send_time']);
		$extno = '';
		$count_all = 0;
		for ($operator=1; $operator<=3; $operator++) {
			$search['operator'] = $operator;
			$search['status'] = 1;
			$count = MessageDetail::getCount($search);
			if ($count>0) {
				$page = ceil($count/$pageSize);
				for ($pageIndex=1; $pageIndex<=$page; $pageIndex++) {
					$data_detail = array();
					$data_detail = MessageDetail::getList($search,1,$pageSize);
					$message_did_arr = $phonenumber_arr = array();
					$sql="INSERT INTO yii2_message_send (message_id,message_did,phonenumber,task_id,operator,channel_id,create_time,uid) VALUES";
					foreach ($data_detail as $item) {
						$channel_id = $item['channel_id'];
						$message_did_arr[] = $item['message_did'];
						$phonenumber_arr[] = $item['phonenumber'];
						$input_d = array();
						$input_d['message_did'] = $item['message_did'];
						$input_d['status'] = 5;
						MessageDetail::save($input_d);
						$tmpstr = "'". $search['message_id'] ."','". $item['message_did'] ."','". $item['phonenumber'] ."','[TASKID]','". $operator ."','". $item['channel_id'] ."','". time() ."','". $item['create_uid'] ."'";
						$sql .= "(".$tmpstr."),";
					}
					$message_dids = implode(',',$message_did_arr);
					$mobile = implode(',',$phonenumber_arr);
					$content = $content_arr[$operator];
					$params = array(
						'action'=>'send',
						'mobile'=>$mobile,
						'content'=>$content,
						'sendTime'=>$sendTime,
						'extno'=>$extno
					);
					$channel_item = Channel::getInfo($channel_id);
					if (!$channel_item) {
						continue;
					}
					$r = $this->unifySend('sms', $params, $channel_item);
					if ($r['returnstatus'] == 'Faild') {
						continue;
					}
					$count_all += $count;
					$task_id = $r['taskID'];
					$sql = str_replace('[TASKID]', $task_id, $sql);
					$sql = substr($sql,0,-1);   //去除最后的逗号
					DB::insert($sql);
				}
			}
		}
		$sql="UPDATE yii2_message_detail SET status=4 WHERE status=5 AND message_id=".$search['message_id'];
		DB::update($sql);

		$input_l = array();
		$input_l['message_id'] = $search['message_id'];
		if ($count_all > 0) {
			$input_l['status'] = 3;
		} else {
			$input_l['status'] = 4;
		}
		MessageList::save($input_l);

		return Response::json($r);
	}

	/**
	 * 状态报告查询
	 */
	public function status()
	{
		$channel_list = Channel::getList();
		foreach ($channel_list as $channel_item) {
			$params = array(
				'action'=>'query'
			);
			$r = $this->unifySend('statusApi', $params, $channel_item);
//		$r = array('statusbox'=> array('0' => array('mobile' => '18301376919', 'taskid' => 8235059, 'status' => 20, 'receivetime' => '2018-02-23 15:36:05', 'errorcode' => '终止', 'extno' => 8710 ) ,'1' => array ( 'mobile' => '18301376919', 'taskid' => 8235032 ,'status' => 20, 'receivetime' => '2018-02-23 15:36:05', 'errorcode' => '终止', 'extno' => 8710 ) ) );
//		$r = array('statusbox'=> array( 'mobile' => '13329050908', 'taskid' => 8235060, 'status' => 20, 'receivetime' => '2018-02-23 15:37:16', 'errorcode' => '终止', 'extno' => Array ( ) ) );

			if (isset($r['statusbox'])) {
				if (isset($r['statusbox']['mobile'])) {
					$data_send = MessageSend::getInfo($r['statusbox']['mobile'],$r['statusbox']['taskid']);
					if ($data_send) {
						$input = array();
						$input['message_sid'] = $data_send['message_sid'];
						$input['status'] = $r['statusbox']['status'];
						$input['return_time'] = strtotime($r['statusbox']['receivetime']);
						$input['errorcode'] = $r['statusbox']['errorcode'];
						$input['extno'] = is_array($r['statusbox']['extno'])?'':$r['statusbox']['extno'];
						MessageSend::save($input);
						$input_d = array();
						$input_d['message_did'] = $data_send['message_did'];
						$input_d['return_time'] = time();
						if ($r['statusbox']['status'] == 10) {
							$input_d['status'] = 3;
						} else {
							$input_d['status'] = 4;
						}
						MessageDetail::save($input_d);
					}
				} else {
					foreach ($r['statusbox'] as $item) {
						$data_send = MessageSend::getInfo($item['mobile'],$item['taskid']);
						if ($data_send) {
							$input = array();
							$input['message_sid'] = $data_send['message_sid'];
							$input['status'] = $item['status'];
							$input['return_time'] = strtotime($item['receivetime']);
							$input['errorcode'] = $item['errorcode'];
							$input['extno'] = is_array($item['extno'])?'':$item['extno'];
							MessageSend::save($input);
							$input_d = array();
							$input_d['message_did'] = $data_send['message_did'];
							$input_d['return_time'] = time();
							if ($item['status'] == 10) {
								$input_d['status'] = 3;
							} else {
								$input_d['status'] = 4;
							}
							MessageDetail::save($input_d);
						}
					}
				}
			}
		}
		return Response::json($r);
	}

	/**
	 * 上行查询
	 */
	public function call()
	{
		$channel_list = Channel::getList();
		foreach ($channel_list as $channel_item) {
			$params = array(
				'action'=>'query'
			);
			$r = $this->unifySend('callApi', $params, $channel_item);

			if (isset($r['callbox'])) {
				if (isset($r['callbox']['mobile'])) {
					$data_send = MessageSend::getInfo($r['callbox']['mobile'],$r['callbox']['taskid']);
					if ($data_send) {
						$uid = $data_send['uid'];
					} else {
						$uid = 0;
					}
					$sql="INSERT INTO yii2_message_call (phonenumber,task_id,content,return_time,create_time,uid) VALUES";
					$tmpstr = "'". $r['callbox']['mobile'] ."','". $r['callbox']['taskid'] ."','". $r['callbox']['content'] ."','". strtotime($r['callbox']['receivetime']) ."','". time() ."','". $uid ."'";
					$sql .= "(".$tmpstr.")";
					$sql = substr($sql,0,-1);   //去除最后的逗号
					DB::insert($sql);
				} else {
					$sql="INSERT INTO yii2_message_call (phonenumber,task_id,content,return_time,create_time,uid) VALUES";
					foreach ($r['callbox'] as $item) {
						$data_send = MessageSend::getInfo($item['mobile'],$item['taskid']);
						if ($data_send) {
							$uid = $data_send['uid'];
						} else {
							$uid = 0;
						}
						$tmpstr = "'". $item['mobile'] ."','". $item['taskid'] ."','". $item['content'] ."','". strtotime($item['receivetime']) ."','". time() ."','". $uid ."'";
						$sql .= "(".$tmpstr."),";
					}
					$sql = substr($sql,0,-1);   //去除最后的逗号
					DB::insert($sql);
				}
			}
		}
		return Response::json($r);
	}

	protected function checkListStatus($message_id)
	{
		$count = MessageDetail::getAllCount($message_id);
		$count_success =  MessageDetail::getSuccessCount($message_id);
		$count_fail =  MessageDetail::getfailCount($message_id);
		if ($count_success + $count_fail = $count) {
			$input_l = array();
			$input_l['message_id'] = $message_id;
			if ($count_success > 0) {
				$input_l['status'] = 3;
			} else {
				$input_l['status'] = 4;
			}
			MessageList::save($input_l);
		}
	}

	protected function unifySend($action,$params, $channel_item)
	{
//		$url = 'http://139.196.58.248:5577/'.$action.'.aspx';
//		$userid = '8710';
//		$account = '借鸿移动贷款';
//		$password = 'a123456';
		$url = $channel_item['url'].'/'.$action.'.aspx';
		$userid = $channel_item['userid'];
		$account = $channel_item['account'];
		$password = $channel_item['password'];

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

    /**
     * 状态报告查询
     */
    public function report()
    {
        $start = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
        $end = mktime(0,0,0,date("m"),date("d"),date("Y"));
        $c_date = date('Y-m-d');
        $sql="select a.uid,IFNULL(count,0) as count,IFNULL(count_success,0) as count_success from yii2_admin as a 
LEFT JOIN
(
select count(*) as count,uid from yii2_message_send where create_time between ".$start." and ".$end." group by uid
) as b on a.uid=b.uid
LEFT JOIN
(
select count(*) as count_success,uid from yii2_message_send where status=10 and create_time between ".$start." and ".$end." group by uid
) as c on a.uid=c.uid
where a.role = 1";
        $send = DB::select($sql);
        foreach ($send as $item) {
            $input = array();
            $input['uid'] = $item['uid'];
            $input['c_date'] = $c_date;
            $input['send_count'] = $item['count'];
            $input['success_count'] = $item['count_success'];
            $input['create_time'] = time();
            Report::save($input);
        }

    $sql="select a.uid,IFNULL(count_recharge,0) as count_recharge,IFNULL(count_consume,0) as count_consume,IFNULL(count_fail,0) as count_fail from yii2_admin as a 
LEFT JOIN
(
select count(*) as count_recharge,uid from yii2_account_detail where remark='充值' and create_time between ".$start." and ".$end." group by uid
) as b on a.uid=b.uid
LEFT JOIN
(
select count(*) as count_consume,uid from yii2_account_detail where change_type=2 and create_time between ".$start." and ".$end." group by uid
) as c on a.uid=c.uid
LEFT JOIN
(
select count(*) as count_fail,uid from yii2_account_detail where remark='返还' and create_time between ".$start." and ".$end." group by uid
) as d on a.uid=d.uid
where a.role = 1";
        $send = DB::select($sql);
        foreach ($send as $item) {
            $input = array();
            $input['uid'] = $item['uid'];
            $input['c_date'] = $c_date;
            $input['recharge_count'] = $item['count_recharge'];
            $input['consume_count'] = $item['count_consume'];
            $input['fail_count'] = $item['count_fail'];
            $input['create_time'] = time();
            Account::save($input);
        }

        return true;
    }

}