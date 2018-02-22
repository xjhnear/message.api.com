<?php
namespace Yxd\Modules\System;
use Yxd\Modules\Core\BaseService;

use PDO;
use PDOException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\PDOHandler;
use Monolog\Formatter\LineFormatter;
use Illuminate\Support\Facades\Config;

class OperatorService extends BaseService
{
    public static function log($admin_id,$action,$info)
	{
		$content = date('Y-m-d H:i:s') . ' ' . $admin_id . ' ' . $action . ' ' . $info . "\r\n";
		$file = storage_path() . '/logs/' . 'admin-operator-log-' . date('Y-m-d') . '.txt';
		file_put_contents($file,$content,FILE_APPEND);
	}
	
	public static function operationLog($admin_name,$admin_group,$action,$info,$data){
		// create a log channel
// 		$log = new Logger('name');
// 		$log = Log::getMonolog();
// 		$log->pushHandler(new StreamHandler(storage_path().'/logs/'.'admin-operator-log-'.date('Y-m-d').'.log','admin',Logger::INFO));
		
// 		// add records to the log
// 		$log->addInfo('Log message', array('context' => 'Other helpful information'));
		// Create the logger
		$dateFormat = "Y n j, g:i a";
		$output = "%datetime% > %level_name% > %message% %context% %extra%\n";
		$formatter = new LineFormatter($output, $dateFormat);
		
		$stream = new StreamHandler(storage_path().'/logs/'.'operator-log-'.date('Y-m-d').'.log',Logger::INFO);
		$stream->setFormatter($formatter);
		
		$logger = new Logger('my_logger');
		
		// Now add some handlers
		$logger->pushHandler($stream);
// 		$logger->pushHandler(new StreamHandler(storage_path().'/logs/'.'admin-operator-log-'.date('Y-m-d').'.log','admin',Logger::INFO));
// 		$logger->pushHandler(new FirePHPHandler());
		
		// You can now use your logger
		$logger->pushProcessor(function ($record) use ($action,$data) {
			$record['extra']['action'] = $action;
			$record['extra']['data'] = serialize($data);
		
			return $record;
		});
		$logger->addInfo($info,array('name'=>$admin_name,'group'=>$admin_group));
	}
	
	public static function operationPdoLog($channel,$admin_name,$admin_group,$action,$info,$data){
		$logger = new Logger($channel);
		$db_conf = Config::get('database.connections.mysql');
		try {
			$db = new PDO('mysql:host='.$db_conf['host'].';dbname='.$db_conf['database'].';charset=UTF8', $db_conf['username'], $db_conf['password'],array(
    		PDO::ATTR_PERSISTENT => true
		));
		}catch(PDOException $e) {
			echo $e->getMessage();
		}
		
// 		$rs=$db->query("SELECT * FROM yxd_admin");
// 		$rs->setFetchMode(\PDO::FETCH_ASSOC);
// 		$result_arr=$rs->fetchAll();
// 		print_r($result_arr);
// 		var_dump($db);exit;
		$pdo = new PDOHandler($db);
// 		echo $channel;exit;
		$logger->pushHandler($pdo);
		$logger->pushProcessor(function ($record) use ($action,$data) {
			$record['extra']['action'] = $action;
			$record['extra']['data'] = serialize($data);
		
			return $record;
		});
// 		// You can now use your logger
		$logger->addInfo($info,array('name'=>$admin_name,'group'=>$admin_group));
	}
}