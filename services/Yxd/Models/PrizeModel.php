<?php
namespace Yxd\Models;

use Yxd\Modules\Core\BaseModel;
use Yxd\Services\Models\ActivityPrize;

class PrizeModel extends BaseModel
{
	public static function getList($ids)
	{
		if(!$ids) return array();
		$list = ActivityPrize::db()->whereIn('id',$ids)->get();
		$data = array();
		foreach($list as $row){
			$data[$row['id']] = $row;
		}
		return $data;
	}
}