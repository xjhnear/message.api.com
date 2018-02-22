<?php
namespace Youxiduo\Base;
use Youxiduo\Base\Model;

class MyBaseModel extends Model
{
	private  static $table='';
	private  static $database='';


	public static function setTable($table_='')
	{	
		self::$table=$table_;
	}

	public static function getTable()
	{
		return self::$table;
	}

	public static function setDatabase($database_='')
	{
		self::$database=$database_;
	}

	public static function getDatabase()
	{
		return self::$database;
	}

	/***
		 添加数据
	***/
	public static function insert($datainfo=array())
	{
		return parent::db()->insertGetId($datainfo);
	}

	/***
		更新数据
	***/
	public static function update($datainfo=array(),$id=0,$wherekey='id')
	{	
		return parent::db()->where($wherekey, $id)->update($datainfo);
	}
	/***
		删除数据
	***/
	public static function delete($key='', $where='', $value='')
	{	
		return parent::db()->where($key, $where, $value)->delete();
	}
	/**
	 * 获取记录
	 * @param string $id, boolean $transaction = true
	 * @return array $JsonResult
	 */
	public static function getInfo($key,$id=0)
	{
		return 	parent::db()->where($key, $id)->first();
	}

	/**
	 * 获取记录集群
	 * @param string $id, boolean $transaction = true
	 * @return array $JsonResult
	 */
	public static function getInfos($key,$id=0)
	{
		return 	parent::db()->where($key, $id)->get();
	}
	
	/**
	 * 获取多条记录多WHERE条件
	 * @param string $id, boolean $transaction = true
	 * @return array $JsonResult
	 */
	public static function getInfos_($wheres=array())
	{	
		if(!empty($wheres)){
			$sql=parent::db();
			foreach($wheres as $key=>$value)
			{
				$sql->where(!empty($value['0'])?$value['0']:'id',!empty($value['1'])?$value['1']:'=',!empty($value['2'])?$value['2']:'0');
			}
		}
		return 	$sql->get();
	}
	/**
	 * 查询
	 * @param string $sql,boolean $transaction = true
	 * @return array $JsonResult
	 */
	public static function find($where='')
	{
		return 	parent::db()->whereRaw($where)->get();
	}

	/**
	 * 查询(只返回一条数据 而且排序)
	 * @param string $sql,boolean $transaction = true
	 * @return array $JsonResult
	 */
	public static function find_($where='',$orderbykey='',$orderby='desc')
	{
		return 	parent::db()->whereRaw($where)->orderBy($orderbykey, $orderby)->first();
	}
    	


    public static function setsearch($arr=array()){
		$sql=$search=array();
		foreach($arr as $key=>$value){
			$keys=substr($key,1);
			$key_=$key{0};
			if(($key_=="=")&&($value != "" )){
				$sql[]=$keys." = '$value'";
			}
			if(($key_ =="%")&&($value != "" )){
				$sql[]=$keys." like '%%".$value."%%' ";
			}
			if(($key_ ==">")&&($value != "" )){
				$sql[]=$keys." >= '".$value."' ";
			}
			if(($key_ =="<")&&($value != "" )){
				$sql[]=$keys." <= '".$value."' ";
			}
			if(($key_=="!")&&($value != "" )){
				$sql[]=$keys." != '".$value."' ";
			}
				
		}
		return join(' and ',$sql);
	}
	/***
	*
	**/
	public static function  pagelist($page=1,$pageSize=10,$where='',$select=array(),$orderbykey='',$orderby='desc'){
		 
		  $sql=parent::db();
		  $datalist=array();
		  /***
		  if(!empty($joins)){
		  	 foreach($joins as $key => $value){
		  	    $sql=$sql->leftJoin($value['jointables'],$value['key_1'],$value['fuhao'], $value['key_2']);
		     }
		  }
		  ***/
		  if(!empty($where)){
		  		$sql=$sql->whereRaw($where);
		  }
		  $datalist['totalCount']=$sql->count();
		  if(!empty($select)){
		  	$sql=$sql->select($select);	
		  }
		  if($datalist['totalCount'] != null){
		  		if(empty($orderbykey)){
		  			$orderbykey='Id';
		  		}
		  		$datalist['result']=$sql->orderBy($orderbykey, $orderby)->forPage($page,$pageSize)->get();
		  }
		  return $datalist;
	}
	
	protected static function buildSearch()
	{	
		return parent::DB(self::getDatabase())->table(self::getTable());
	}

	

}