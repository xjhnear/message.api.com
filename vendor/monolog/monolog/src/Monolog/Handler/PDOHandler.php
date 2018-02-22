<?php
namespace Monolog\Handler;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class PDOHandler extends AbstractProcessingHandler
{
	private $initialized = false;
	private $pdo;
	private $statement;

	public function __construct(\PDO $pdo, $level = Logger::DEBUG, $bubble = true)
	{
		$this->pdo = $pdo;
		parent::__construct($level, $bubble);
	}

	protected function write(array $record)
	{
		if (!$this->initialized) {
			$this->initialize();
		}
		
		$this->statement->execute(array(
				'channel' => $record['channel'],
				'operate' => $record['message'],
				'op_name' => $record['context']['name'],
				'op_group' => $record['context']['group'],
				'related_data' => serialize($record['extra']),
				'time' => $record['datetime']->format('Y-m-d H:i:s'),
				'level' => $record['level_name']
		));
	}

	private function initialize()
	{
		$this->pdo->exec(
				"CREATE TABLE IF NOT EXISTS `yxd_monolog` ( `monolog_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, 
				`channel` VARCHAR(255) NOT NULL COMMENT '日志标示名', 
				`operate` VARCHAR(255) NOT NULL COMMENT '操作描述', 
				`op_name` VARCHAR(20) NOT NULL COMMENT '操作员姓名', 
				`op_group` VARCHAR(20) NOT NULL COMMENT '操作员权限组', 
				`related_data` VARCHAR(500) NOT NULL COMMENT '操作内容', 
				`time` DATETIME NOT NULL COMMENT '操作时间', 
				`level` VARCHAR(20) NOT NULL COMMENT '日志级别', PRIMARY KEY (`monolog_id`) ) ENGINE=INNODB 
				CHARSET=utf8 COLLATE=utf8_general_ci"
		);
		
		$this->statement = $this->pdo->prepare(
				'SET CHARACTER SET UTF8;INSERT INTO yxd_monolog (channel, operate, op_name, op_group, related_data, time, level) VALUES (:channel, :operate, :op_name, :op_group, :related_data, :time, :level)'
		);
		
		$this->initialized = true;
	}
}