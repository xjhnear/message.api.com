<?php
/**
 * Created by PhpStorm.
 * User: fujiajun
 * Date: 15/12/10
 * Time: 下午2:06
 */

namespace Youxiduo\MyService;
use Illuminate\Support\Facades\DB;
use Youxiduo\Base\Model;
class QueryService extends Model
{
    public static $databas_table='';
    public static $pagesize=15;
    public function __construct()
    {

    }



    public static function getListByApi($inputinfo)
    {
        $where=' WHERE 1=1 ';
        if(!empty($inputinfo['adv_logo'])){
            $where.='and adv_logo="'.$inputinfo['adv_logo'].'" and attribute = 1 ';
        }
        if(!empty($inputinfo['game_id'])){
            $where.='and game_id="'.$inputinfo['game_id'].'"';
        }
        if(!empty($inputinfo['appname'])){
            if ($inputinfo['appname'] == 'youxiduojiu3') {
                $where.='and platform="iosyn"';
            } else {
                $where.='and platform="ios"';
            }
        }
        $sqlWhereValue=self::getWheredata(!empty($inputinfo['where'])?$inputinfo['where']:array(),!empty($inputinfo['sqlWhere'])?$inputinfo['sqlWhere']:array());
        if(!empty($sqlWhereValue['0'])){
            $where .=' and '. $sqlWhereValue['0'];
        }
        if(!empty($inputinfo['orderby']))
        {
            $where.=$inputinfo['orderby'];
        }
        self::updata_data($inputinfo);
        return DB::select('SELECT id,adv_logo,attribute,name,adv_name,adv_img,adv_content,adv_href_type,adv_type,game_id,urlId,urlAddress,startTime,endTime,sort,isAutoLogin FROM '.self::$databas_table.$where,$sqlWhereValue['1']);

    }



    //SELECT * FROM `t1` INNER JOIN ( SELECT id FROM `t1` WHERE ftype=1 ORDER BY id DESC LIMIT 935500,10) t2 USING (id)
    //SELECT * FROM (SELECT * FROM `t1` WHERE id > ( SELECT id FROM `t1` WHERE ftype=1 ORDER BY id DESC LIMIT 935510, 1) LIMIT 10) T ORDER BY id DESC;
    public static  function getDatalistByPage($inputinfo)
    {
        $list=0;
        if(!empty($inputinfo['list'])){
            $list=1;
            unset($inputinfo['list']);
        }
        $where=' WHERE 1=1 ';
        if(!empty($inputinfo['adv_logo'])){
            $where.='and adv_logo="'.$inputinfo['adv_logo'].'"';
        }
        $sqlWhereValue=self::getWheredata(!empty($inputinfo['where'])?$inputinfo['where']:array(),!empty($inputinfo['sqlWhere'])?$inputinfo['sqlWhere']:array());
        if(!empty($sqlWhereValue['0'])){
            $where .=' and '. $sqlWhereValue['0'];
        }

        $limit='';
        if($list == 1){
            $page=1;
            if(!empty($inputinfo['page']) and $inputinfo['page']>1){
                $page=$inputinfo['page'];
            }

            if(!empty($inputinfo['setWhere']))
            {
                $where.=$inputinfo['setWhere'];
            }
            if(!empty($inputinfo['orderby']))
            {
                $where.=$inputinfo['orderby'];
            }
            /***
            $result=DB::select('SELECT id,startTime,endTime,attribute FROM '.self::$databas_table.'  WHERE adv_logo="'.$inputinfo['adv_logo'].'" and attribute in (2,3) ');

            if(!empty($result)){
               $attribute2=$attribute3='';
                $date=date('Y-m-d H:i:s',time());
               foreach($result as $key=>$value){
                   if($value['attribute']==2 and $value['startTime'] < $date ) {
                        $attribute2.='"'.$value['id'].'"'.',';
                   }
                   if($value['attribute']==3 and $value['endTime'] < $date ){
                        $attribute3.='"'.$value['id'].'"'.',';
                   }
               }

               if($attribute2 != ''){
                   $attribute2=rtrim($attribute2,',');
                   DB::update('update '.self::$databas_table.' set is_show = 1,attribute=1  where id in ('.$attribute2.')');
               }
               if($attribute3 != ''){
                   $attribute3=rtrim($attribute3,',');
                   DB::update('update '.self::$databas_table.' set is_show = 0,attribute=4  where id in ('.$attribute3.')');
               }
            }
            ***/
            self::updata_data($inputinfo);

            if($inputinfo['adv_logo'] == '首页弹窗')
            {
                $limit = ' LIMIT  10 ';
            }else{
                $limit = ' LIMIT '.($page-1)*self::$pagesize.','.self::$pagesize;
            }

            $where.=$limit;

            //echo 'SELECT SQL_CALC_FOUND_ROWS *  FROM '.self::$databas_table.$where;
            $data=DB::select('SELECT SQL_CALC_FOUND_ROWS *  FROM '.self::$databas_table.$where,$sqlWhereValue['1']);
            $count=DB::select('SELECT FOUND_ROWS() as count');
            $count=current($count);
            //$totalCount=ceil($count['count'] / self::$pagesize);
        }else{
            if(!empty($inputinfo['limit'])){
                $limit = $inputinfo['limit'];
            }
            $where.=$limit;
            $data=DB::select('SELECT * FROM '.self::$databas_table.$where,$sqlWhereValue['1']);
            $count=count($data);
        }
        if(count($data) < 1){
            return array('errorCode'=>0,'result'=>array());
        }else{
            $data= array('errorCode'=>0,'result'=>$data,'inputinfo'=>$inputinfo['where'],'totalCount'=>$count['count']);
            return $data;
        }
    }

    public static function addData($inputinfo)
    {
        $inputinfo['id'] = self ::com_create_guid();
        $key=array_keys($inputinfo);
        $fuHao=array();
        foreach($key as $val){
            $fuHao[]='?';
        }
        $result=DB::insert('insert into '.self::$databas_table.'('.join(',',array_keys($inputinfo)).') values ('.join(',',$fuHao).')',array_values($inputinfo));
        if($result==1){
           return array('errorCode'=>0,'result'=>$inputinfo['id'],'inputinfo'=>$inputinfo);
        }
        return array('errorCode'=>1,'errorDescription'=>'添加失败','inputinfo'=>$inputinfo);
    }


    public static function editData($inputinfo,$where)
    {
        $result=0;
        if(!empty(self::$databas_table)){
           $result=DB::connection('adv')->table('v4appadv')->where($where['0'],$where['1'])->update($inputinfo);
        }
        if($result==1){
            return array('errorCode'=>0,'result'=>$result,'inputinfo'=>$inputinfo);
        }
        return array('errorCode'=>1,'errorDescription'=>'修改失败','inputinfo'=>$inputinfo);
    }

    public static function delectData($where)
    {
       return  DB::delete('delete from '.self::$databas_table.' where id = ?', $where);
    }



    public static function SetSort($inputinfo,$where)
    {
        $database=self::$databas_table;
        $result=DB::select('SELECT id FROM '.$database.' WHERE adv_logo=? and sort=? LIMIT   1',array($where['adv_logo'],$where['old_sort']));
        $result=current($result);
        DB::transaction(function() use($inputinfo,$result,$where,$database) {
                if(!empty($result['id'])) {
                    DB::update('update ' .$database. ' set sort = ' . $inputinfo['old_sort'] . ' where id = ?', array($result['id']));
                }
                DB::update('update '.$database.' set sort = '.$inputinfo['sort'].' where id = ?', array($where['id']));
        });
            return array('errorCode'=>0,'result'=>'执行成功','inputinfo'=>$inputinfo);
    }





    private static  function com_create_guid()
    {

        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }


    private static function getWheredata($inputinfo=array(),$sqlWhere=array())
    {
        $sqlWhereValue = $value = $where= array();
        if(count($inputinfo)<1 or count($sqlWhere)<1){
            $sqlWhereValue['0']='';
            $sqlWhereValue['1']=array();
            return $sqlWhereValue;
        }
        foreach($inputinfo as $key=>$val){
            if( !empty($sqlWhere[$key]) and strlen($val) > 0 ){
                $where[]=$sqlWhere[$key];
                if($key=='name'){
                    $value[]='%'.$val.'%';
                }else{
                    $value[]=$val;
                }

            }
        }
        $sqlWhereValue['0']=join(' and ',$where);
        $sqlWhereValue['1']=$value;
        return $sqlWhereValue;
    }


    private static function updata_data($inputinfo)
    {
        $date=date('Y-m-d H:i:s',time());
        $result=DB::select('SELECT id FROM '.self::$databas_table.'  WHERE adv_logo="'.$inputinfo['adv_logo'].'"  and startTime < "'.$date.'" and endTime  > "'.$date.'" and  attribute = 4 and is_show=0 ');
        if(!empty($result)) {
            $arr=array();
            foreach($result as $key=>$value1){
                $arr[]="'".$value1['id']."'";
            }
            if(count($arr) > 0 ){
                DB::update('update '.self::$databas_table.' set is_show = 1,attribute=1  where id in ('.join(',',$arr).')');
            }

        }
        $result=DB::select('SELECT id FROM '.self::$databas_table.'  WHERE adv_logo="'.$inputinfo['adv_logo'].'"  and (startTime > "'.$date.'" or endTime  < "'.$date.'") and  attribute = 1 and is_show = 1 ');
        if(!empty($result)) {
            $arr=array();
            foreach($result as $key=>$value2){
                $arr[]="'".$value2['id']."'";
            }
            if(count($arr) > 0){
                DB::update('update '.self::$databas_table.' set is_show = 0,attribute=4  where id in ('.join(',',$arr).')');
            }
        }
        return true;
    }

    protected static function buildSearch()
    {
        return DB::connection(self::$databas)->table(self::$table);
    }
}
