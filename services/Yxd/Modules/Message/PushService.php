<?php
namespace Yxd\Modules\Message;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

use Yxd\Modules\Core\BaseService;

use Yxd\Services\Models\Account;

class PushService extends BaseService
{
	public static function sendOne($apple_token,$content,$params=array())
	{
		return self::send($apple_token,$content,$params);		
	}
	
	public static function sendMulit($apple_token_list,$content,$params=array())
	{
	    return self::send($apple_token_list,$content,$params);
	}
	
	protected static function send($token,$content,$params=array())
	{
	    $apples = Config::get('app.apple_push');
		foreach($apples as $apple){
			$pem = $apple['apple_push_pem'];
			$passphrase = $apple['apple_push_passphrase'];
			self::_send($token, $content,$params, $pem, $passphrase);
		}
		return true;
	}
	
	protected static function _send($token,$content,$params=array(),$pem,$passphrase)
	{
		require_once  base_path() . '/vendor/apple/2.0/ApnsPHP/' .'/Autoload.php';
				
		//$pem = Config::get('app.apple_push_pem');
		//$passphrase = Config::get('app.apple_push_passphrase');
		$env = Config::get('app.apple_push_debug')==true ? \ApnsPHP_Abstract::ENVIRONMENT_SANDBOX : \ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION;	
		$push = new \ApnsPHP_Push($env, $pem);		
		$passphrase && $push->setProviderCertificatePassphrase($passphrase);
				
		$push->connect();		
		$apple_token_list = is_array($token) ? $token : array($token);
		$message = new \ApnsPHP_Message();
		foreach($apple_token_list as $apple_token){
			$apple_token = trim($apple_token);
			if($apple_token && strlen($apple_token)==64){
				$message->addRecipient($apple_token);
			}												
		}
		$message->setCustomIdentifier("Message-Badge-1");
		$message->setBadge(1);
		$message->setText($content);
		$message->setSound();
		if($params && is_array($params) && count($params)){
		    foreach($params as $key=>$val){
			    $message->setCustomProperty($key,$val);
		    }
		}
		$message->setExpiry(30);
		$push->add($message);
		$push->send();
		$push->disconnect();
		
		$aErrorQueue = $push->getErrors();		
		$content =json_encode(array('token'=>$token,'content'=>$content,'params'=>$params)) . "\r\n";
		$file = storage_path() . '/logs/' . 'push-apache2handler-' . date('Y-m-d') . '.txt';
		file_put_contents($file,$content,FILE_APPEND);
		
		if (!empty($aErrorQueue)) {
			Log::error($aErrorQueue);
			return false;
		}
		
		return true;
	}
	
	public static function sendSubscribeGiftbagUpdate($uids,$giftbag_id,$params)
	{
		$tpl = NoticeService::AUTO_NOTICE_SUBSCRIBE_GIFTBAG_UPDATE;		
		$apple_token_list = Account::db()->where('apple_token','!=','')->whereIn('uid',$uids)->distinct()->select('apple_token')->lists('apple_token');
		if(!$apple_token_list) return false;
		$content = NoticeService::parseTpl($tpl, $params);
		if($content===false) return false;
		$args = array();
		$args['type'] = '12';
		$args['linkid'] = $giftbag_id;
		return self::sendMulit($apple_token_list, $content,$args);
		//return self::sendPassiveMessage($uid,self::SYSTEM_REDIRECT_METHOD_APP,self::SYSTEM_REDIRECT_TYPE_GIFT_DETAIL,$giftbag_id,$tpl,$params);
	}
	
	public static function sendSystemMessage($uids,$content,$type,$linkid)
	{
		if(!$uids){
			$apple_token_list = Account::db()->where('apple_token','!=','')->distinct()->select('apple_token')->lists('apple_token');
		}else{
			$apple_token_list = Account::db()->where('apple_token','!=','')->distinct()->select('apple_token')->whereIn('uid',$uids)->lists('apple_token');
		}
		$data = array('content'=>$content,'type'=>$type,'linkid'=>$linkid);
		self::redis()->set('queue::apple_push::content',serialize($data));
		//$queue_data = array_fill_keys($apple_token_list,serialize($data));
		$queue_data = array_values($apple_token_list);
		$today = date('Ymd');
		$queue_name = 'queue::apple_push:'.$today;
		if($queue_data){
			self::queue()->rpush($queue_name,$queue_data);
		}
	}
	
	/**
	 * 分发Apple消息推送
	 */
	public static function distributePush()
	{
		$today = date('Ymd');
		$queue_name = 'queue::apple_push:'.$today;
		$data = self::queue()->lpop($queue_name);
		$push_list = array();
		while($data){
			$push_list[] = $data;
		    $data = self::redis()->lpop($queue_name);
		    if(count($push_list)>100 || !$data){
		    	$push_data = unserialize(self::redis()->get('queue::apple_push::content'));
		    	$content = $push_data['content'];
		    	$params = array('type'=>$push_data['type'],'linkid'=>$push_data['linkid']);
		    	self::sendMulit($push_list,$content,$params);
		    	$push_list = array();
		    }
		}
	}		
}