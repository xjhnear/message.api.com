<?php
namespace Yxd\Modules\System;
use Yxd\Modules\Core\BaseService;

class ProfileService extends BaseService
{
	protected static $_profiles = array();
	
	public static function add($line)
	{
		self::$_profiles[] = $line;
	}
	
	public static function save()
	{
		if(self::$_profiles){
			$content = '';
			foreach(self::$_profiles as $line){
				$content .= $line;	
			}
			$sql_file = storage_path() . '/logs/' . 'sql-apache2handler-' . date('Y-m-d-H') . '.txt';
			file_put_contents($sql_file,$content,FILE_APPEND);
		}
	}
}