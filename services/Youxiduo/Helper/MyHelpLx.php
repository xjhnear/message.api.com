<?php
/**
 * @package Youxiduo
 * @category Android 
 * @link http://dev.youxiduo.com
 * @copyright Copyright (c) 2008 Youxiduo.com 
 * @license http://www.youxiduo.com/license
 * @since 4.0.0
 *
 */
namespace Youxiduo\Helper;
use Illuminate\Support\Facades\Log;
use Youxiduo\V4\Game\GameService;
use Illuminate\Support\Facades\Paginator;
use Youxiduo\V4\User\UserService;
use Youxiduo\Helper\Utility;
use Config;
class MyHelpLx
{		
        /***
		public static function setList($str,$input,$params){
            echo __CLASS__;
            $backtrace = debug_backtrace();
            array_shift($backtrace);
            var_dump($backtrace);
            exit;

            $inputinfo=self::get_Input_value($input,$params);
            $result = LabelService::get_tag_relation_list($inputinfo);
            if($result['errorCode']==0){
                $data=self::processingInterface($result,$data,15);
                $data=parent::superGetData($data);
            }
            //return $this->display('label-list',$data);
        }
        ***/
        public static function getdata($url,$inputInfo){
             return Utility::loadByHttp($url,$inputInfo,'GET');
        }

        public static function postdata($url,$inputInfo){
            return Utility::loadByHttp($url,$inputInfo,'POST');
        }


		//获取列表查询数据
		public static function get_Input_value($input=array(),$params=array(),$is_page=1){
			if(empty($params)) return $input; 
			$datainfo=array();
			foreach($params as $val){
				if(!empty($input[$val])) $datainfo[$val]=$input[$val];
			}
			if($is_page != 1) return $datainfo; 
			$datainfo['pageIndex'] = !empty($input['page'])?$input['page'] : 1;
		    $datainfo['pageSize'] =15;
		    $datainfo['isActive']='true';
	    	return $datainfo;
		}

		//获取逗号分割的字符串
		public static function get_Ids($arr=array(),$key='')
		{
			if(empty($arr) || !is_array($arr)) return '';
			$array=array();

			foreach ($arr as $value) {
				 $array[]=!empty($key)?$value[$key]:$value;
			}
			return join(',',$array);
 		}
 		//列表UID获取用户
 		public static function getUser($datalist,$key1){
 			 $params=array();
 			 foreach($datalist as $val){
                $params[]=$val[$key1]; 
             }
             if(!empty($params)){
                $params=UserService::getMultiUserInfoByUids(array_flip(array_flip($params)),'full');
                if(!empty($params)){
                    $userinfo=array();
                    foreach($params as $val_){
                        $userinfo[$val_['uid']]=array('nickname'=>$val_['nickname'],'mobile'=>$val_['mobile']);
                    }   
                }
                return $userinfo;
             }
             return array();
 		}

 		 //用于生成符合前台页面SELECT标签的数组
	    public static function array_select($result,$id,$val)
	    {
	        if($result){
	            $selectInfo=array();
	            foreach($result as $key=>$value){
	                $selectInfo[$value[$id]]=$value[$val];
	            }
	            return $selectInfo;
	        }
	        return $result;
	    }

        //将KEY的图片值换成有前缀的在返回列表
        public static function getImgUrlforlist($datalist=array(),$key='')
        {
            if(empty($datalist)) return ;
            foreach($datalist as &$row){
                $row[$key] = Utility::getImageUrl($row[$key]);
            }
            return $datalist;
        }

 		//根据ID/CODE为KEY获取游戏ID 
 		//根据游戏ID查询
 		//在将原数据循环遍历到列表集合中
 		public static  function  getGameNameByCode($datalist)
 		{
 			
            foreach($datalist as $key=>&$value)
            {
                if(!empty($value['gid'])){
                    $gameName=GameService::getOneInfoById($value['gid'],'ios');
                    $value['gname']=!empty($gameName['gname']) && $gameName['gname']!='g'  ? $gameName['gname'] : '';
                }
            }
            return $datalist;
 		} 
 		//根据关联接口列表数据获取游戏属性
 		//$result 为接口返回的数据集合
 		//KEY为 对应视图列表中的ID (KEY=KEY2 数值相等)
 		//$datalist  为查询的视图列表
 		//$key2视图列表中的ID
 		public static function getGameInfoByInterfaceList($result,$key,$datalist=array(),$key2='',$gameType='ios')
 		{	
 			if($result['errorCode']==0 && !empty($result['result'])){
 				$arr=array();
                $arr_id=array();
 			    foreach ($result['result'] as $value) {
 			    	$arr[$value[$key]]=$value['gid'];
                    $arr_id[$value[$key]]=$value['id'];
 			    }
 			    //如果无需集合到视图列表中就返回
 			    if(empty($datalist) && empty($key2)) return $arr;
 			    foreach ($datalist as $key => &$value){
 			    	if(!empty($arr[$value[$key2]]) ){
 			    		$value['gid']=$arr[$value[$key2]];
                        $value['other_id']=$arr_id[$value[$key2]];
 			    		$gameName=GameService::getOneInfoById($value['gid'],$gameType);
                    	$value['gname']=!empty($gameName['gname']) && $gameName['gname']!='g'  ? $gameName['gname'] : '';
 			    	}
 			    }
 			    return $datalist;
 			}
 			return array();	
 		}


 		/**处理接口返回数据**/
    	public static function processingInterface($result,$data,$pagesize=10){ //echo $result['totalCount'];exit;
	        $pager = Paginator::make(array(),!empty($result['totalCount'])?$result['totalCount']:0,$pagesize);
	        unset($data['pageIndex']);
	        $pager->appends($data);
			$data['pagelinks'] = $pager->links();
	        $data['datalist'] = !empty($result['result'])?$result['result']:array();
	        return $data;
    	}

    	 private static function createFolder($path)
	    {
	      if (!file_exists($path))
	      {
	        self::createFolder(dirname($path));
	        mkdir($path, 0777);
	      }
	    }

        public static function save_img($img){
            $titlePic = false;
            if($img) {
                if (!isset($dir)) {
                    $dir = '/userdirs/' . date('Y') . '/' . date('m') . '/';
                    $path = storage_path() . $dir;
                }
                $file = $img;
                $new_filename = date('YmdHis') . str_random(4);
                $mime = $file->getClientOriginalExtension();
                $file->move($path, $new_filename . '.' . $mime);
                $titlePic = $dir . $new_filename . '.' . $mime;
                $titlePic = Utility::getImageUrl($titlePic);
            }
            return $titlePic;
        }

    public static function save_imgs($imgs){
        $pic_arr = array();
        if($imgs){
            foreach($imgs as $k=>$v){
                $pic_arr[] = self::save_img($v);
            }
        }
        return $pic_arr;
    }
    public static function pager($data,$totle,$size,$search){
        $pager = Paginator::make($data,$totle,$size);
        $pager->appends($search);
         return $pager->links();
    }
    public static function pager_new($data,$totle,$size,&$search){
        unset($search['page']);unset($search['pageIndex']);//pager不能有‘page'参数
        $pager = Paginator::make($data,$totle,$size);
        $pager->appends($search);
        return $pager->links();
    }

    public static function baidu_weburl($urls,$api){
        $ch = curl_init();
        $options =  array(
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => implode("\n", $urls),
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $result = json_decode($result,true);
        return $result;
    }

    //添加错误日志
    public static function error_log($path,$str){
        $myfile = fopen($path, "a");
        fwrite($myfile, $str);
        fclose($myfile);
    }

    public function postAjaxUploadImg()
    {
        if(Input::file('pic')){
            $dir = '/userdirs/' . date('Y') . '/' . date('m') . '/';
            $path = storage_path() . $dir;
            $file_arr = Input::file('prize_pic');
            $file = $file_arr[0];
            $new_filename = date('YmdHis') . str_random(4);
            $mime = $file->getClientOriginalExtension();
            $file->move($path,$new_filename . '.' . $mime );
            $icon = $dir . $new_filename . '.' . $mime;
            echo json_encode(array('success'=>"true",'mess'=>'修改成功','data'=>$icon));
        }else{
            echo json_encode(array('success'=>"false",'mess'=>'修改失败','data'=>"图片丢失"));
        }
    }

    //给特定模块加参数
    public static function add_keys_for_modules($url="",$param=array(),$platform='android',$filter=array()){
        //默认的徐毅迅需求模块
        if(!$filter){
            $filter = array('module_mall','module_account','module_virtual','module_material');
        }
        foreach($filter as $v){
            if(strpos($url,$v)){
                if(!array_key_exists('platform',$param)){
                    $param['platform'] = $platform;
                }
            }
        }
        return $param;
    }

    //在结果中插入用户信息，结果中要带用户的id暂时支持accountId、userId、uid、luckyUserId四种key
    public static function insertUserhtmlIntoRes($res){
        foreach($res as &$row){
            $row['uid'] = isset($row['uid'])?$row['uid']:"";
            isset($row['accountId']) && $row['uid'] = $row['accountId'];
            isset($row['userId']) && $row['uid'] = $row['userId'];
            isset($row['luckyUserId']) && $row['uid'] = $row['luckyUserId'];
            $user = $row['uid'] ? UserService::getUserInfoByUid($row['uid']) : array();
            if($user&&isset($user['uid'])){
                $html = '<span class="badge badge-info">'.$user['uid'].'</span><br/><a href="'.url('v4user/users/edit',array('id'=>$user['uid'])).'" target="_blank">'.$user['nickname'].'<br>'.$user['mobile'].'</a>';
                $row['userhtml'] = $html;
                $row['nickname'] = $user['nickname'];
                $row['mobile'] = $user['mobile'];
            }
        }
        return $res;
    }

    //时间转换（毫秒）
    public static function microtime_format($tag,$time)
    {
        if (!$tag || !$time) return '';
        $timeArr = explode(".", $time);
        $date = date($tag, $timeArr[0]);
        $timeArrCount = count($timeArr);
        if ($timeArrCount == 2) return str_replace('x', $timeArr[1], $date);
        return str_replace(' x', '', $date) . ' 000';
    }

    //获取ip地址
    public static function get_real_ip(){
        $realip = "";
        if(isset($_SERVER)){
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
                $realip=$_SERVER['HTTP_X_FORWARDED_FOR'];
            }else if(isset($_SERVER['HTTP_CLIENT_IP'])){
                $realip=$_SERVER['HTTP_CLIENT_IP'];
            }else{
                $realip=$_SERVER['REMOTE_ADDR'];
            }
        }else{
            if(getenv('HTTP_X_FORWARDED_FOR')){
                $realip=getenv('HTTP_X_FORWARDED_FOR');
            }else if(getenv('HTTP_CLIENT_IP')){
                $realip=getenv('HTTP_CLIENT_IP');
            }else{
                $realip=getenv('REMOTE_ADDR');
            }
        }
        return $realip;
    }

}