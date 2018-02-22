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
use Log;
use Youxiduo\V4\Game\GameService;
use Paginator;
use Youxiduo\V4\User\UserService;
use Youxiduo\Helper\Utility;
use Youxiduo\V4\Game\Model\IosGame;
use Youxiduo\Imall\ProductService;
use Youxiduo\MyService\QueryService;
use Youxiduo\V4\Cms\Model\News;
use Youxiduo\V4\Cms\Model\NewGame;
use Youxiduo\Cms\Model\Videos;
use Youxiduo\Task\TaskV3Service;
use Youxiduo\V4\Activity\ActivityService;
class MyHelp
{		
       public static function getdata($url='',$inputInfo){
             return Utility::loadByHttp($url,$inputInfo,'GET');
        }

        public static function postdata($url,$inputInfo){
            return Utility::loadByHttp($url,$inputInfo,'POST');
        }

        public static function html_form_data($url,$inputInfo){
            return Utility::loadByHttp($url,$inputInfo,'HTMLFROM');
        }
        public static function curldata($url='',$inputInfo,$type='POST',$isLog=false)
        {
            return Utility::SuperLoadByHttp($url,$inputInfo,$type,$isLog);
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
		    $datainfo['pageSize'] =14;
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

 		//使用用户名查询IDs
        public static function searchUser($name=''){
            $result=UserService::searchByUserName($name);
            if(empty($result)) return '';
            $arr=array();
            foreach($result as $key=>$value){
                $arr[]=$value['uid'];
            }
            return join(',',$arr);
        }

 		//列表UID获取用户
 		public static function getUser($datalist,$key1){
 			 $params=array();
 			 foreach($datalist as $val){
                 if(!empty($val[$key1])) $params[]=$val[$key1];
             }
             if(empty($params)) return array();
             $params=UserService::getMultiUserInfoByUids(array_flip(array_flip($params)),'full');
             if(empty($params) || $params=='user_not_exists') return array();
             $userinfo=array();
             foreach($params as $val_){
                   $userinfo[$val_['uid']]=array('nickname'=>$val_['nickname'],'mobile'=>$val_['mobile']);
             }
             return $userinfo;


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
                $row[$key] = self::getImageUrl($row[$key]);
            }
            return $datalist;
        }

 		//根据ID/CODE为KEY获取游戏ID 
 		//根据游戏ID查询
 		//在将原数据循环遍历到列表集合中
 		public static  function  getGameNameByCode($datalist,$type='ios')
 		{
 			
            foreach($datalist as $key=>&$value)
            {
                if(!empty($value['gid'])){
                    $gameName=GameService::getOneInfoById($value['gid'],$type);
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

        public static function getGameInfoByGid($result){
            if($result['errorCode']==0 && !empty($result['result'])){
                foreach($result['result'] as &$item){
                    $gameName=GameService::getOneInfoById($item['gid'],"ios");
                    $item['gname'] = $gameName['gname'];
                }
            }
            return $result['result'];
        }

    /**处理接口返回数据*
     * @param $result
     * @param $data
     * @param int $pagesize
     * @return
     */
    	public static function processingInterface($result,$data,$pagesize=10){ //echo $result['totalCount'];exit;
	        $pager = Paginator::make(array(),!empty($result['totalCount'])?$result['totalCount']:0,$pagesize);
            unset($data['pageIndex'],$data['page']);
            $pager->appends($data);
			$data['pagelinks'] = $pager->links();
	        $data['datalist'] = !empty($result['result'])?$result['result']:array();
            $data['totalCount'] = isset($result['totalCount']) ?  $result['totalCount'] : 0;
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

    public static function setJson($inputinfo=array(),$keys=array()){
           return json_encode(array_intersect_key($inputinfo,$keys));
    }

    public static function save_img($img){
        $titlePic ="";
        if($img) {
            if (!isset($dir)) {
                $dir = '/userdirs/' . date('Y') . '/' . date('m') . '/';
                $path = storage_path() . $dir;
            }
            self::createFolder($path);
            $file = $img;
            $new_filename = date('YmdHis') . str_random(4);
            $mime = $file->getClientOriginalExtension();
            $file->move($path, $new_filename . '.' . $mime);
            $titlePic = $dir . $new_filename . '.' . $mime;
            $titlePic = Utility::getImageUrl($titlePic);
        }
        return $titlePic;
    }

    public static function save_img_no_url($img,$dir_='user'){
        $titlePic ="";
        if($img) {
            if (!isset($dir)) {
                $dir = '/userdirs/'.$dir_.'/' . date('Y') . '/' . date('m') . '/';
                $path = base_path() . $dir;
            }
            self::createFolder($path);
            $file = $img;
            $new_filename = date('YmdHis') . str_random(4);
            $mime = $file->getClientOriginalExtension();
            $file->move($path, $new_filename . '.' . $mime);
            $titlePic = $dir . $new_filename . '.' . $mime;
            //$titlePic = Utility::getImageUrl($titlePic);
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

    public static function set_adv_inputinfo($inputinfo,$date,$keys)
    {
        $inputinfo['thirdParty']=json_encode(array_intersect_key($inputinfo,$keys));
        if(!empty($inputinfo['thirdParty_']) and $inputinfo['thirdParty_']==$inputinfo['thirdParty']){
            unset($inputinfo['thirdParty'],$inputinfo['thirdParty_']);
        }
        $inputinfo=array_diff_key($inputinfo,$keys);
        /***
        if($inputinfo['attribute'] == 2 and ($date < $inputinfo['startTime'])){
            $inputinfo['is_show']=0;
        }
        if($inputinfo['attribute'] == 3 and ($date > $inputinfo['endTime'])){
            $inputinfo['is_show']=0;
        }
         * ***/
        return $inputinfo;
    }

    public static function getAdv_Type()
    {
        return array(
            "外部url"=>"Safari浏览器",
            "内部safari"=>"内置浏览器",
            "游戏详情"=>"游戏详情",
            "专题"=>"专题",
            "新游预告"=>"新游预告",
            "新闻文章"=>"新闻文章",
            "帖子详情"=>"帖子详情",
            "礼包详情"=>"礼包详情",
            "视频详情"=>"视频详情",
            "任务详情"=>"任务详情",
            "商品详情"=>"商品详情",
            "活动详情"=>"活动详情",
            "礼包列表"=>"礼包列表",
            "游币商城列表"=>"游币商城列表",
            "钻石商城列表"=>"钻石商城列表",
            "任务列表"=>"任务列表",
            "指定聊天室"=>"指定聊天室",
            "大转盘"=>"大转盘",
            "天天彩"=>"天天彩",
            "新手任务列表"=>"新手任务列表",
            "账号共享"=>"账号共享"
        );
    }

    public static function getAutoSearch($key,$value='')
    {
        $arr=$params=array();
        switch($key){
            case '游戏详情':
                $arr=IosGame::db()->select('id', 'shortgname as value ')->where('shortgname','like','%'.$value.'%')->where('isdel','=',0)->get();
                break;
            case '专题':
                //$datalist=TopicService::FindTopic(' apptype=1 and  ztitle like "%%'.$value.'%%"');
                $inputinfo['list']=0;
                QueryService::$databas_table='yxd_www.m_zt';
                $inputinfo['where']=array('apptype'=>1,'ztitle'=>"%{$value}%");
                $inputinfo['sqlWhere']=array( 'apptype'=>' apptype = ? ','ztitle'=>" ztitle like ? ");
                $arr=QueryService::getDatalistByPage($inputinfo);
                if($arr['errorCode'] == 0){
                    $arr=array_map(function($val){
                        $arr_['id']=$val['id'];
                        $arr_['value']=$val['ztitle'];
                        return $arr_;
                    },$arr['result']);
                }
                break;
            case '新游预告':
                $arr=NewGame::getAutoSearch($value);
                break;
            case '新闻文章':
                $arr=News::getAutoSearch($value);
                break;
            case '礼包详情':
                $params['productName']=$value;
                $params['productType']=2;
                $arr = ProductService::searchProductList($params,1,'gift');
                if($arr['errorCode'] == 0){
                    $arr=array_map(function($val){
                        $arr_['id']=$val['productCode'];
                        $arr_['value']=$val['productName'];
                        $arr_['gameId']=$val['gid'];
                        return $arr_;
                    },$arr['result']);
                }
                break;
            case '视频详情':
                $arr=Videos::getAutoSearch($value);
                break;
            case '任务详情':
                $params['taskName']=$value;
                $params['platformType']='I';
                $arr=TaskV3Service::task_list($params);
                if($arr['errorCode'] == 0){
                    $arr=array_map(function($val){
                        $arr_['id']=$val['taskId'];
                        $arr_['value']=$val['taskName'];
                        return $arr_;
                    },$arr['result']);
                }
                break;
            case '商品详情':
                $params['productName']=$value;
                $params['productType']='0,1,3,4';
                $arr=ProductService::searchProductList($params);
                if($arr['errorCode'] == 0){
                    $arr=array_map(function($val){
                        $arr_['id']=$val['productCode'];
                        $arr_['value']=$val['productName'];
                        $arr_['gameId']=$val['gid'];
                        return $arr_;
                    },$arr['result']);
                }
                break;
            case '商城详情':
                $params['productName']=$value;
                $params['productType']='0,1,3,4';
                $arr=ProductService::searchProductList($params);
                if($arr['errorCode'] == 0){
                    $arr=array_map(function($val){
                        $arr_['id']=$val['productCode'];
                        $arr_['value']=$val['productName'];
                        return $arr_;
                    },$arr['result']);
                }
                break;
            case '指定聊天室':
                $arr=IosGame::db()->select('id', 'shortgname as value ')->where('shortgname','like','%'.$value.'%')->where('isdel','=',0)->get();
                break;
            case '活动详情':
                $params['name']=$value;
                $params['activityType']='3,4';
                $arr = ActivityService::get_activity_info_list_back_end($params,array('name','activityType'));
                if($arr['errorCode'] == 0){
                    $arr=array_map(function($val){
                        $arr_['id']=$val['id'];
                        $arr_['value']=$val['name'];
                        $arr_['gameId']=$val['gid'];
                        $arr_['other']=json_encode(array('linkValue'=>$val['linkValue'],'linkType'=>isset($val['linkType'])?:"",'linkId'=>isset($val['linkId'])?:""));
                        return $arr_;
                    },$arr['result']);
                }
                break;
        }

        return $arr;
    }




    public static function getImageUrl($img,$isTest=0)
    {
       if($isTest==1){
          return  'http://test.img.youxiduo.com'.$img;
       }
       return  Utility::getImageUrl($img);
    }
}