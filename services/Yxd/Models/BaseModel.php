<?php
namespace Yxd\Models;

use Illuminate\Support\Facades\DB;

class BaseModel
{
	protected static function dbCmsMaster()
	{
		return DB::connection('cms');
	} 
	
	protected static function dbClubMaster()
	{
		return DB::connection('mysql');
	}
	
	protected static function dbCmsSlave()
	{
		return DB::connection('cms');
	}
	
    protected static function dbClubSlave()
	{
		return DB::connection('mysql');
	}
}