<?php
use Yxd\Modules\Core\CacheService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;
use Youxiduo\System\MessageService;
use Youxiduo\System\Model\MessageDetail;
use Youxiduo\System\Model\MessageList;
use Youxiduo\System\Model\MessageListDetail;
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
		$pageSize = 10000;
		$input_l = array();
		$input_l['message_id'] = $search['message_id'];
		$input_l['status'] = 5;
		MessageList::save($input_l);
		$data_list = MessageList::getInfoById($search['message_id']);
		$data_list_detail = MessageListDetail::getInfoById($search['message_id']);
		$rate_dark = DB::select('select rate_dark from yii2_admin where uid ='.$data_list['create_uid']);
		$rate_dark_v = 1;
		if ($rate_dark) {
			if ($rate_dark[0]['rate_dark']>0 && $rate_dark[0]['rate_dark']<1) {
				$rate_dark_v = $rate_dark[0]['rate_dark'];
			}
		}
		$dark_arr = array();$dark_normal = $dark_do = 0;
		if ($data_list['count'] >= 5000 && $rate_dark_v <> 1) {
			$dark_normal = round($data_list['count'] * $rate_dark_v);
			$dark_do = $data_list['count'] - $dark_normal;
			$dark_arr = array_pad($dark_arr,$dark_do,1);
			$dark_arr = array_pad($dark_arr,$data_list['count'],0);
			shuffle($dark_arr);
		}
		$data_list['content'] = json_decode($data_list_detail['content_json'],true);
		$content_arr['1'] =  $data_list['content']['unicom'];
		$content_arr['2'] =  ($data_list['content']['mobile']<>'')?$data_list['content']['mobile']:$data_list['content']['unicom'];
		$content_arr['3'] =  ($data_list['content']['telecom']<>'')?$data_list['content']['telecom']:$data_list['content']['unicom'];
		$sendTime = $data_list['send_time'];
        $message_code= $data_list['message_code'];
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
					$sql="INSERT INTO yii2_message_send (message_id,message_did,phonenumber,task_id,operator,channel_id,create_time,uid,is_dark) VALUES ";
					foreach ($data_detail as $item) {
						$is_dark = array_pop($dark_arr);
						if ($is_dark == NULL) $is_dark = 0;
						$channel_id = $item['channel_id'];
						$message_did_arr[] = $item['message_did'];
						if ($is_dark == 0) {
							$phonenumber_arr[] = $item['phonenumber'];
						}
						$input_d = array();
						$input_d['message_did'] = $item['message_did'];
						$input_d['status'] = 5;
						MessageDetail::save($input_d);
						$tmpstr = "'". $search['message_id'] ."','". $item['message_did'] ."','". $item['phonenumber'] ."','[TASKID]','". $operator ."','". $item['channel_id'] ."','". time() ."','". $item['create_uid'] ."','". $is_dark ."'";
						$sql .= "(".$tmpstr."),";
					}
					$message_dids = implode(',',$message_did_arr);
					$mobile = implode(',',$phonenumber_arr);
					$content = $content_arr[$operator];
					$params = array(
						'mobile'=>$mobile,
						'content'=>$content,
						'sendTime'=>$sendTime,
                        'message_code'=>$message_code,
					);
					$channel_item = Channel::getInfo($channel_id);
					if (!$channel_item) {
						DB::update('update yii2_message_detail set status=4, errmsg="通道信息错误" where message_did in ('.$message_dids.')');
						continue;
					}
                    $params = $this->make_params($params, 'sms', $channel_item);
					$r = $this->unifySend($params['arr'], $params['xml'], $channel_item);
                    $r = $this->make_return($r, 'sms', $channel_item);
					if ($r['returnstatus'] == 'Faild') {
						DB::update('update yii2_message_detail set status=4, errmsg="'.$r['message'].'" where message_did in ('.$message_dids.')');
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
//		$sql="UPDATE yii2_message_detail SET status=4 WHERE status<>5 AND message_id=".$search['message_id'];
//		DB::update($sql);

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
	 * 状态报告查询 (已废弃)
	 */
	public function status()
	{
		$channel_list = Channel::getList();
		foreach ($channel_list as $channel_item) {
			$params = array();
            $params = $this->make_params($params, 'status', $channel_item);
            $r = $this->unifySend($params['arr'], $params['xml'], $channel_item);
            $r = $this->make_return($r, 'status', $channel_item);
//		$r = array('statusbox'=> array('0' => array('mobile' => '18301376919', 'taskid' => 8235059, 'status' => 20, 'receivetime' => '2018-02-23 15:36:05', 'errorcode' => '终止', 'extno' => 8710 ) ,'1' => array ( 'mobile' => '18301376919', 'taskid' => 8235032 ,'status' => 20, 'receivetime' => '2018-02-23 15:36:05', 'errorcode' => '终止', 'extno' => 8710 ) ) );
//		$r = array('statusbox'=> array( 'mobile' => '13329050908', 'taskid' => 8235060, 'status' => 20, 'receivetime' => '2018-02-23 15:37:16', 'errorcode' => '终止', 'extno' => Array ( ) ) );

			if (isset($r['statusbox'])) {
			    $balance_arr = array();
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
//                            $detail_now = DB::select('select content,create_uid from yii2_message_detail where message_did ='.$data_send['message_did']);
//                            $message_count = mb_strlen($detail_now[0]['content']);
//                            $power = 1;
//                            if ($message_count > 130) {
//                                $power = 3;
//                            } elseif ($message_count > 70) {
//                                $power = 2;
//                            } else {
//                                $power = 1;
//                            }
//                            if (isset($balance_arr[$detail_now[0]['create_uid']])) {
//                                $balance_arr[$detail_now[0]['create_uid']] += $power;
//                            } else {
//                                $balance_arr[$detail_now[0]['create_uid']] = $power;
//                            }
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
//                                $detail_now = DB::select('select content,create_uid from yii2_message_detail where message_did ='.$data_send['message_did']);
//                                $message_count = mb_strlen($detail_now[0]['content']);
//                                $power = 1;
//                                if ($message_count > 130) {
//                                    $power = 3;
//                                } elseif ($message_count > 70) {
//                                    $power = 2;
//                                } else {
//                                    $power = 1;
//                                }
//                                if (isset($balance_arr[$detail_now[0]['create_uid']])) {
//                                    $balance_arr[$detail_now[0]['create_uid']] += $power;
//                                } else {
//                                    $balance_arr[$detail_now[0]['create_uid']] = $power;
//                                }
							}
							MessageDetail::save($input_d);
						}
					}
				}
//                foreach ($balance_arr as $k=>$v) {
//                    DB::update('update yii2_admin set balance=balance+'.$v.' where uid ='.$k);
//                    $balance_now = DB::select('select balance from yii2_admin where uid ='.$k);
//                    DB::insert('INSERT INTO yii2_account_detail (uid,change_count,change_type,balance,remark,op_uid,create_time) VALUES ("'.$k.'","'.$v.'","1","'.$balance_now[0]['balance'].'","返还","0","'.time().'")');
//                }
			}
		}
		return Response::json($r);
	}

    /**
     * 获取状态报告
     */
    public function getstatus()
    {
		$channel_url_arr = array();
        $channel_list = Channel::getList();
        foreach ($channel_list as $channel_item) {
			if (in_array($channel_item['url'],$channel_url_arr)) {
				continue;
			}
			$channel_url_arr[] = $channel_item['url'];
            $params = array();
            $params = $this->make_params($params, 'status', $channel_item);
            $r = $this->unifySend($params['arr'], $params['xml'], $channel_item);
            $r = $this->make_return($r, 'status', $channel_item);

            if (isset($r['statusbox'])) {
                $balance_arr = array();
                if (isset($r['statusbox']['mobile'])) {
					$sql="INSERT INTO yii2_message_return (phone,taskid,status,retime,errorcode,extno,create_time) VALUES ";
					$extno = is_array($r['statusbox']['extno'])?'':$r['statusbox']['extno'];
					$tmpstr = "'". $r['statusbox']['mobile'] ."','". $r['statusbox']['taskid'] ."','". $r['statusbox']['status'] ."','". strtotime($r['statusbox']['receivetime']) ."','". $r['statusbox']['errorcode'] ."','". $extno ."','". time() ."'";
					$sql .= "(".$tmpstr.")";
					$sql = substr($sql,0,-1);   //去除最后的逗号
					DB::insert($sql);
                } else {
                    $sql="INSERT INTO yii2_message_return (phone,taskid,status,retime,errorcode,extno,create_time) VALUES ";
                    foreach ($r['statusbox'] as $item) {
                        $extno = is_array($item['extno'])?'':$item['extno'];
                        $tmpstr = "'". $item['mobile'] ."','". $item['taskid'] ."','". $item['status'] ."','". strtotime($item['receivetime']) ."','". $item['errorcode'] ."','". $extno ."','". time() ."'";
                        $sql .= "(".$tmpstr."),";
                    }
                    $sql = substr($sql,0,-1);   //去除最后的逗号
                    DB::insert($sql);
                }
            }
			sleep(2);
        }
        return Response::json($r);

    }

    /**
     * 处理状态报告
     */
    public function dostatus()
    {
        $sql = 'SELECT MAX(rid) as max_rid FROM yii2_message_return WHERE is_do = 0';
        $max_rid = DB::select($sql);
		if ($max_rid) {
			$max_rid = $max_rid[0]['max_rid'];

			$sql2 = 'UPDATE yii2_message_send a
INNER JOIN yii2_message_return b
ON a.task_id = b.taskid AND a.phonenumber=b.phone
SET a.errorcode = b.errorcode,
a.status = b.status,
a.extno = b.extno,
a.return_time = b.retime,
a.is_get = 0
WHERE b.is_do = 0 AND b.rid <= '.$max_rid;
			DB::update($sql2);

			$sql3 = 'UPDATE yii2_message_detail aa
INNER JOIN
(SELECT a.message_did,b.status FROM yii2_message_send a
INNER JOIN yii2_message_return b
ON a.task_id = b.taskid AND a.phonenumber=b.phone
WHERE b.is_do = 0 AND b.rid <= '.$max_rid.' ) bb
ON aa.message_did = bb.message_did
SET aa.status = case when (bb.status<>10) then 4 else 3 end
';
			DB::update($sql3);

			$sql4 = 'UPDATE yii2_message_return SET is_do = 1 WHERE is_do = 0 AND rid <= '.$max_rid;
			DB::update($sql4);
		}

        Response::json(array('true'));
    }

	/**
	 * 处理状态报告(暗改)
	 */
	public function dostatusdark()
	{
		$sql = 'SELECT MAX(message_sid) as max_sid,MIN(message_sid) as min_sid FROM yii2_message_send WHERE is_dark = 1 AND `status`=0  AND DATE_SUB(CURDATE(), INTERVAL 1 DAY) > date(from_unixtime(create_time)) ';
		$max_min_sid = DB::select($sql);
		if ($max_min_sid) {
			$max_sid = $max_min_sid[0]['max_sid'];
			$min_sid = $max_min_sid[0]['min_sid'];

			$sql2 = 'UPDATE yii2_message_send a
SET a.status = 10,
a.is_get = 0,
a.return_time = '.time().'
WHERE a.`status`=0 AND a.is_dark = 1 AND a.message_sid <= '.$max_sid.' AND a.message_sid >= '.$min_sid;
			DB::update($sql2);

			$sql3 = 'UPDATE yii2_message_detail aa
INNER JOIN
(SELECT a.message_did,a.status FROM yii2_message_send a
WHERE a.is_dark = 1 AND a.message_sid <= '.$max_sid.' AND a.message_sid >= '.$min_sid.' ) bb
ON aa.message_did = bb.message_did
SET aa.status = case when (bb.status<>10) then 4 else 3 end
WHERE aa.`status`=5';
			DB::update($sql3);
		}

		Response::json(array('true'));
	}

    /**
     * 手动处理文件状态报告
     */
	public function statushand()
	{
		$sql="SELECT message_id,create_time FROM yii2_message_list";
		$message_id = DB::select($sql);
		if ($message_id) {
			foreach ($message_id as $item) {
				$sql4 = 'UPDATE yii2_message_detail SET create_time = '.$item['create_time'].' WHERE message_id= '.$item['message_id'];
				DB::update($sql4);
			}
		}
		Response::json(array('true'));
	}


	/**
	 * 上行查询
	 */
	public function call()
	{
		$channel_list = Channel::getList();
		foreach ($channel_list as $channel_item) {
            $params = array();
            $params = $this->make_params($params, 'call', $channel_item);
            $r = $this->unifySend($params['arr'], $params['xml'], $channel_item);
            $r = $this->make_return($r, 'call', $channel_item);

			if (isset($r['callbox'])) {
				if (isset($r['callbox']['mobile'])) {
					$data_send = MessageSend::getInfo2($r['callbox']['mobile'],$r['callbox']['taskid']);
					if ($data_send) {
						$uid = $data_send['uid'];
					} else {
						$uid = 0;
					}
					$sql="INSERT INTO yii2_message_call (phonenumber,task_id,content,return_time,create_time,uid) VALUES ";
					$tmpstr = "'". $r['callbox']['mobile'] ."','". $r['callbox']['taskid'] ."','". $r['callbox']['content'] ."','". strtotime($r['callbox']['receivetime']) ."','". time() ."','". $uid ."'";
					$sql .= "(".$tmpstr.")";
					$sql = substr($sql,0,-1);   //去除最后的逗号
					DB::insert($sql);
				} else {
					$sql="INSERT INTO yii2_message_call (phonenumber,task_id,content,return_time,create_time,uid) VALUES ";
					foreach ($r['callbox'] as $item) {
						$data_send = MessageSend::getInfo2($item['mobile'],$item['taskid']);
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

	protected function unifySend($params, $xml='', $channel_item)
	{
//		$url = 'http://139.196.58.248:5577/'.$action.'.aspx';
//		$userid = '8710';
//		$account = '借鸿移动贷款';
//		$password = 'a123456';
        $url = $params['url'];unset($params['url']);
        $o = "";
        foreach ( $params as $k => $v )
        {
//            $o.= "$k=" . urlencode(iconv('UTF-8', 'GB2312', $v)). "&" ;
			if ($channel_item['type'] == 3 && $k == 'Account') {
				$o.= "$k=" . iconv("UTF-8","GB2312",$v). "&" ;
			} elseif ($v == '[XML]') {
                $o.= "$k=" . $xml. "&" ;
            } else {
                $o.= "$k=" . urlencode($v). "&" ;
            }
        }
        $post_data = substr($o,0,-1);
//        echo $post_data;exit;
        $re = $this->request_post($url, $post_data);

        return $re;

	}

    protected function make_params($params, $action, $channel_item)
    {
        $params_new = array();
        $params_xml = '';
        switch ($channel_item['type']) {
            case 1:
                $action_arr = Config::get('sms.action_arr');
                $params_new['url'] = $channel_item['url'].'/'.$action_arr[$channel_item['type']][$action];
                $params_new['userid'] = $channel_item['userid'];
                $params_new['account'] = $channel_item['account'];
                $params_new['password'] = $channel_item['password'];
                switch ($action) {
                    case 'sms':
                        $params_new['action'] = 'send';
                        $params_new['extno'] = '';
                        $params_new['mobile'] = $params['mobile'];
                        $params_new['content'] = $params['content'];
                        $params_new['sendTime'] = date('Y-m-d H:i:s', $params['sendTime']);
                        break;
                    case 'status':
                        $params_new['action'] = 'query';
                        break;
                    case 'call':
                        $params_new['action'] = 'query';
                        break;
                }
                break;
            case 2:
                $action_arr = Config::get('sms.action_arr');
                $params_new['url'] = $channel_item['url'].'/'.$action_arr[$channel_item['type']][$action];
                $params_new['account'] = $channel_item['account'];
                $params_new['password'] = md5($channel_item['password']);
                switch ($action) {
                    case 'sms':
                        $params_new['smsType'] = $channel_item['userid'];
                        $params_new['message'] = '[XML]';
                        $params_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><MtMessage>';
                        $params_xml .= '<content>'.$params['content'].'</content>';
                        $mobile_arr = explode(',',$params['mobile']);
                        foreach ($mobile_arr as $item_mobile) {
                            $params_xml .= '<phoneNumber>'.$item_mobile.'</phoneNumber>';
                        }
                        $params_xml .= '<sendTime>'.date('Y-m-d H:i:s', $params['sendTime']).'</sendTime>';
                        $params_xml .= '<smsId>'.$params['message_code'].'</smsId>';
                        $params_xml .= '<subCode></subCode><templateId></templateId></MtMessage>';
                        break;
                    case 'status':
                        break;
                    case 'call':
                        break;
                }
                break;
            case 3:
                $action_arr = Config::get('sms.action_arr');
                $params_new['url'] = $channel_item['url'].'/'.$action_arr[$channel_item['type']][$action];
                $params_new['Account'] = $channel_item['account'];
                $params_new['Password'] = $channel_item['password'];
                switch ($action) {
                    case 'sms':
                        $params_new['Phones'] = $params['mobile'];
                        $params_new['Content'] = iconv("UTF-8","GB2312",$params['content']);
                        $params_new['Channel'] = 1;
                        $params_new['SendTime'] = date('YmdHis', $params['sendTime']);
                        break;
                    case 'status':
                        break;
                    case 'call':
                        break;
                }
                break;
        }
        return array('arr'=>$params_new, 'xml'=>$params_xml);
    }

	protected function xmlSave($r,$action='other'){

		$filePath = '/downloads/'.$action.'/'.date('Ymd').'/'.date('H').'/';
		if(!is_dir(public_path() . $filePath)) {
			mkdir(public_path() . $filePath,0777,true);
		}
		$fp = fopen(public_path() . $filePath . $action . '_' . date('YmdHis') .'.xml','a');
		fwrite($fp, $r);
		fclose($fp);
	}

	protected function xmlRead($name,$path1,$path2,$action='other'){

		error_reporting(7);
		$filePath = '/downloads/'.$action.'/'.$path1.'/'.$path2.'/';
		if(!is_dir(public_path() . $filePath)) {
			mkdir(public_path() . $filePath,0777,true);
		}
		$fp = fopen(public_path() . $filePath . $name,'r');
		$r = fread($fp,filesize(public_path() . $filePath . $name));
		fclose($fp);

		return $r;
	}

    protected function make_return($r, $action, $channel_item)
    {
		$return_new = array();
        switch ($channel_item['type']) {
            case 1:
				$this->xmlSave($r, $action);
                $return_new = $this->xmlToArray($r);
                break;
            case 2:
//                $r = '<ReportMessageRes><resDetail><revTime>2018-03-04 21:55:41</revTime><phoneNumber>13917438216</phoneNumber><smsId>M1519630974</smsId><stat>r:000</stat><statDes>DELIVRD</statDes></resDetail><subStat>r:000</subStat><subStatDes>获取状态报告记录数:1</subStatDes></ReportMessageRes>';
				$this->xmlSave($r, $action);
				$return = $this->xmlToArray($r);

                switch ($action) {
                    case 'sms':
                        if ($return['subStat'] == 'r:000') {
                            $return_new['returnstatus'] = 'Success';
                            $return_new['taskID'] = $return['smsId'];
                        } else {
                            $return_new['returnstatus'] = 'Faild';
                            $return_new['message'] = $return['subStatDes'];
                        }
                        break;
                    case 'status':
                        if (isset($return['subStat']) && isset($return['resDetail'])) {
                            if (isset($return['resDetail']['phoneNumber'])) {
                                $resDetail = $return['resDetail'];
                                $return['resDetail'] = array(0=>$resDetail);
                            }
                            $return_new['statusbox'] = array();
                            foreach ($return['resDetail'] as $item) {
                                $statusbox = array();
                                $statusbox['mobile'] = $item['phoneNumber'];
                                $statusbox['taskid'] = $item['smsId'];
                                $statusbox['receivetime'] = date("Y-m-d H:i:s",strtotime($item['revTime']));
                                $statusbox['errorcode'] = $item['statDes'];
                                $statusbox['extno'] = '';
                                if ($item['stat'] == 'r:000') {
                                    $statusbox['status'] = 10;
                                } else {
                                    $statusbox['status'] = 20;
                                }
                                $return_new['statusbox'][] = $statusbox;
                            }
                        }
                        break;
                    case 'call':
                        if (isset($return['resDetail'])) {
                            if (isset($return['resDetail']['phoneNumber'])) {
                                $resDetail = $return['resDetail'];
                                $return['resDetail'] = array(0=>$resDetail);
                            }
                            $return_new['callbox'] = array();
                            foreach ($return['resDetail'] as $item) {
                                $callbox = array();
                                $callbox['mobile'] = $item['phoneNumber'];
                                $callbox['taskid'] = '';
                                $callbox['content'] = $item['content'];
                                $callbox['receivetime'] = date("Y-m-d H:i:s",strtotime($item['revTime']));
                                $return_new['callbox'][] = $callbox;
                            }
                        }
                        break;
                }
                break;
            case 3:
//				$r = '1532690$$$$13579910573$$$$2018/3/20 14:56:00$$$$失败$$$$DB00108||||1532690$$$$13916058395$$$$2018/3/20 13:41:00$$$$失败$$$$MK:1008||||1532690$$$$13579915886$$$$2018/3/20 13:58:00$$$$失败$$$$DB00108||||1532690$$$$13579923622$$$$2018/3/20 15:05:00$$$$失败$$$$DB00108||||1532699$$$$18838818710$$$$2018/3/20 15:06:00$$$$失败$$$$MK:1008||||1532699$$$$18753920284$$$$2018/3/20 15:06:00$$$$失败$$$$MK:100D||||1532699$$$$18705725030$$$$2018/3/20 15:37:00$$$$失败$$$$DB00108||||1532699$$$$18722384318$$$$2018/3/20 15:37:00$$$$失败$$$$DB00108||||1532699$$$$18784590699$$$$2018/3/20 15:06:00$$$$失败$$$$MK:1008||||1532699$$$$18769552289$$$$2018/3/20 15:06:00$$$$失败$$$$MK:1008||||1532699$$$$18728332290$$$$2018/3/20 15:06:00$$$$失败$$$$MK:1008||||1532699$$$$18781482482$$$$2018/3/20 15:06:00$$$$失败$$$$MK:1008||||1532699$$$$18750823234$$$$2018/3/20 15:06:00$$$$失败$$$$MK:1008||||1532699$$$$18779428785$$$$2018/3/20 16:08:00$$$$失败$$$$DB00108||||1532699$$$$13916058395$$$$2018/3/20 15:06:00$$$$失败$$$$MK:1008';
				$r = mb_convert_encoding($r, "utf-8", "gb2312");
				$this->xmlSave($r, $action);
                switch ($action) {
                    case 'sms':
                        if ($r>0) {
                            $return_new['returnstatus'] = 'Success';
                            $return_new['taskID'] = $r;
                        } else {
                            $return_new['returnstatus'] = 'Faild';
                            $return_new['message'] = $r;
                        }
                        break;
                    case 'status':
                        if ($r>0) {
                            $return['resDetail'] = explode('||||', $r);
                            foreach ($return['resDetail'] as $item_str) {
                                $item = explode('$$$$', $item_str);
                                $statusbox = array();
                                $statusbox['mobile'] = $item[1];
                                $statusbox['taskid'] = $item[0];
                                $statusbox['receivetime'] = date("Y-m-d H:i:s",strtotime($item[2]));
                                $statusbox['errorcode'] = $item[4];
                                $statusbox['extno'] = '';
                                if ($item[3] == '成功') {
                                    $statusbox['status'] = 10;
                                } else {
                                    $statusbox['status'] = 20;
                                }
                                $return_new['statusbox'][] = $statusbox;
                            }
                        }
                        break;
                    case 'call':
                        if ($r>0) {
                            $return['resDetail'] = explode('||||', $r);
                            foreach ($return['resDetail'] as $item_str) {
                                $item = explode('$$$$', $item_str);
                                $callbox = array();
                                $callbox['mobile'] = $item[0];
                                $callbox['taskid'] = '';
                                $callbox['content'] = $item[1];
                                $callbox['receivetime'] = date("Y-m-d H:i:s",strtotime($item[2]));
                                $return_new['callbox'][] = $callbox;
                            }
                        }
                        break;
                }
                break;
        }
        return $return_new;
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
	 * 超时处理
	 */
	public function timeout()
	{
		$sql_config="select `value` from yii2_config where `name`='AUTO_TIMEOUT_API'";
		$item_config = DB::select($sql_config);
		if (!$item_config || $item_config[0]['value']==0) {
			Response::json(array('false'));
		} else {
			$start = mktime(0,0,0,date("m"),date("d")-4,date("Y"));
			$end = mktime(0,0,0,date("m"),date("d")-3,date("Y"));
			$sql="SELECT DISTINCT message_id
FROM yii2_message_detail WHERE `status`=4 AND is_return = 0 AND create_time<".$end;
			$message_id = DB::select($sql);
			if ($message_id) {
				foreach ($message_id as $item) {
					$sql_count="select count(*) as num,create_uid,content from yii2_message_detail where status=4 AND is_return = 0 and message_id =".$item['message_id']." group by content,create_uid";
					$item_count = DB::select($sql_count);
					$balance = $create_uid = 0;
					foreach ($item_count as $item_c) {
						$message_count = mb_strlen($item_c['content']);
						$power = 1;
						if ($message_count > 130) {
							$power = 3;
						} elseif ($message_count > 70) {
							$power = 2;
						} else {
							$power = 1;
						}
						$create_uid = $item_c['create_uid'];
						$balance += $item_c['num'] * $power;
					}
					DB::update('update yii2_message_detail set is_return=1 where message_id ='.$item['message_id']);
					DB::update('update yii2_admin set balance=balance+'.$balance.' where uid ='.$create_uid);
					$balance_now = DB::select('select balance from yii2_admin where uid ='.$create_uid);
					DB::insert('INSERT INTO yii2_account_detail (uid,change_count,change_type,balance,remark,op_uid,create_time) VALUES ("'.$create_uid.'","'.$balance.'","1","'.$balance_now[0]['balance'].'","返还","0","'.time().'")');
				}
			}
			Response::json(array('true'));
		}
	}

    /**
     * 状态报告查询
     */
    public function report()
    {

        $start = mktime(0,0,0,date("m"),date("d")-1,date("Y"));
		$start3 = mktime(0,0,0,date("m"),date("d")-3,date("Y"));
        $end = mktime(0,0,0,date("m"),date("d"),date("Y"));
        $c_date = date("Y-m-d",strtotime("-1 day"));
		$sql = 'SELECT * FROM yii2_report WHERE c_date = "'.$c_date.'"';
		$info = DB::select($sql);
		if (!$info) {
			$sql="select a.uid,IFNULL(count,0) as count,IFNULL(count_success,0) as count_success from yii2_admin as a 
LEFT JOIN 
(
select count(*) as count,create_uid as uid from yii2_message_detail where create_time between ".$start." and ".$end." group by create_uid
) as b on a.uid=b.uid
LEFT JOIN 
(
select count(*) as count_success,create_uid as uid from yii2_message_detail where status=3 and create_time between ".$start." and ".$end." group by create_uid
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
select sum(change_count) as count_recharge,uid from yii2_account_detail where remark='充值' and create_time between ".$start." and ".$end." group by uid 
) as b on a.uid=b.uid 
LEFT JOIN 
(
select sum(change_count) as count_consume,uid from yii2_account_detail where change_type=2 and create_time between ".$start." and ".$end." group by uid 
) as c on a.uid=c.uid 
LEFT JOIN 
(
select sum(change_count) as count_fail,uid from yii2_account_detail where remark='返还' and create_time between ".$start." and ".$end." group by uid 
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


			$sql_1 = "update yii2_report b
INNER JOIN
(select FROM_UNIXTIME(create_time,'%Y-%m-%d') as c_date,count(*) as count_success,create_uid as uid from yii2_message_detail where status=3 and (create_time between ".$start3." and ".$end.") group by c_date,create_uid) a
ON a.c_date=b.c_date AND a.uid = b.uid
SET b.success_count = a.count_success
where UNIX_TIMESTAMP(b.c_date) between ".$start3." and ".$end."";
			DB::update($sql_1);

			Response::json(array('true'));
		}
    }

}