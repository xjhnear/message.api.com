<?php
use Yxd\Modules\Core\CacheService;
use Illuminate\Support\Facades\Input;
use Youxiduo\System\MessageService;
use Youxiduo\System\Model\MessageDetail;
use Youxiduo\System\Model\MessageList;
use Youxiduo\System\Model\MessageSend;

class SystemController extends BaseController
{
	/**
	 * 短信发送
	 */
	public function sms()
	{
		$search['message_id'] = Input::get('message_id');
		if (!$search['message_id']) return Response::json(array());
		$pageSize = 2000;
		$data_list = MessageList::getInfoById($search['message_id']);
		$data_list['content'] = json_decode($data_list['content'],true);
		$content_arr['1'] =  $data_list['content']['unicom'];
		$content_arr['2'] =  ($data_list['content']['mobile']<>'')?$data_list['content']['mobile']:$data_list['content']['unicom'];
		$content_arr['3'] =  ($data_list['content']['telecom']<>'')?$data_list['content']['telecom']:$data_list['content']['unicom'];
		$sendTime = date('Y-m-d H:i:s', $data_list['send_time']);
		$extno = '';
		for ($operator=1; $operator<=3; $operator++) {
			$search['operator'] = $operator;
			$count = MessageDetail::getCount($search);
			if ($count>0) {
				$page = ceil($count/$pageSize);
				for ($pageIndex=1; $pageIndex<=$page; $pageIndex++) {
					$data_detail = array();
					$data_detail = MessageDetail::getList($search,$pageIndex,$pageSize);
					$message_did_arr = $phonenumber_arr = array();
					$sql="INSERT INTO yii2_message_send (message_id,message_did,phonenumber,task_id,operator,channel_id) VALUES";
					foreach ($data_detail as $item) {
						$message_did_arr[] = $item['message_did'];
						$phonenumber_arr[] = $item['phonenumber'];
						$tmpstr = "'". $search['message_id'] ."','". $item['message_did'] ."','". $item['phonenumber'] ."','[TASKID]','". $operator ."','". $item['channel_id'] ."'";
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
					$r = $this->unifySend('sms', $params);
					$task_id = $r['taskID'];
					$sql = str_replace('[TASKID]', $task_id, $sql);
					$sql = substr($sql,0,-1);   //去除最后的逗号
					DB::insert($sql);
				}
			}
		}

		return Response::json($r);
	}

	/**
	 * 状态报告查询
	 */
	public function status()
	{
		$params = array(
			'action'=>'query'
		);
//		$r = $this->unifySend('statusApi', $params);
		$r = array('statusbox'=> array('0' => array('mobile' => '18301376919', 'taskid' => 8235059, 'status' => 20, 'receivetime' => '2018-02-23 15:36:05', 'errorcode' => '终止', 'extno' => 8710 ) ,'1' => array ( 'mobile' => '18301376919', 'taskid' => 8235032 ,'status' => 20, 'receivetime' => '2018-02-23 15:36:05', 'errorcode' => '终止', 'extno' => 8710 ) ) );
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