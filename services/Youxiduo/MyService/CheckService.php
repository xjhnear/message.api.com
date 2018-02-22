<?php
/**
 * Created by PhpStorm.
 * User: fujiajun
 * Date: 15/11/25
 * Time: 上午10:20
 */

namespace Youxiduo\MyService;

use modules\user\models\UserModel;
class CheckService
{
    protected static $pagesize=7;
    protected static $order=array('dateline'=>'desc');
    protected static $_th=array(
        'user'=>array('ID','用户名','头像','注册时间','来源','状态')
    );
    protected static $_td=array(
        'user'=>array('uid'=>'need','nickname'=>'input','avatar'=>'img','dateline'=>'input','userstype'=>'input','bans'=>'input')
    );

    public static function NeedUserDataBylayer($search=array())
    {
        $data=$search;
        $page=empty($search['page'])?1:$search['page'];
        if(!empty($search['keyword'])){
            $search['keytype']='nickname';

        }

        $totalCount=UserModel::searchCount($search);
        $data['totalCount']=ceil($totalCount / self::$pagesize);
        $result = UserModel::searchList($search,$page,self::$pagesize,self::$order);
        if(empty($result['users'])){
            echo '抱歉，没有数据';
            exit;
        }
        $data['datalist'] = $result['users'];
        $data['bans'] = UserModel::getBanList();
        foreach($data['datalist'] as &$val){
            if(in_array($val['uid'],$data['bans'])){
                $val['bans']='<span class="label">禁用中</span>';
            }else if($val['vuser']==1){
                $val['bans']='robot';
            }else{
                $val['bans']='无状态';
            }
            if($val['client'] == 'android'){
                $val['userstype']='<span class="cus-android"></span>';
            }else{
                $val['userstype']='<span class="cus-ios"></span>';
            }
        }

        $data['thread_th']=self::$_th['user'];
        $data['tbody_td']=self::$_td['user'];
        $data['primarykey']='uid';
        $data['html_th']=self::setTableTdByData($data);
        return $data;
    }
    private static function  setTableTdByData($data=array())
    {

        $html_td=array();
        if(!empty($data['is_check'])){
            $is_check=json_decode($data['is_check'],true);

        }
        foreach($data['datalist'] as $key=>$val){
            $html_td[$key]="<tr><input type='hidden' id='totalCount' value='".$data['totalCount']."'>";
            foreach($data['tbody_td'] as $key_td=>$tbody_td){
                $td_val=$val[$key_td];
                if($tbody_td == 'img'){
                    $html_td[$key].='<td class="user-avatar"><img src="'.$td_val.'"></td>';
                }elseif($tbody_td == 'need'){
                    $html_td[$key].='<td id='.$key_td.'_'.$val[$data['primarykey']].' dataValue="'.$td_val.'">'.$td_val.'</td>';
                }else{
                    $html_td[$key].='<td >'.$td_val.'</td>';
                }
            }
            $xz=empty($is_check[$val[$data['primarykey']]])?'选择':'已选择';
            $html_td[$key].='<td><a href="javascript:void(0);" data-id='.$val[$data['primarykey']].' class="btn btn-primary select-id">'.$xz.'</a></td></tr>';
        }
        return join('',$html_td);
    }

    /***
     * <!--{% for key,item in datalist %}-->
            <tr  id="tr_<!--{{item[primarykey]}}-->">
            <!--{% for key,item_key in tbody_td %}-->
            <!--{% if item_key == 'img'%}-->
            <td class="user-avatar"><img src="<!--{{item[key]}}-->"></td>
            <!--{% elseif item_key == 'need' %}-->
            <td id="<!--{{key}}-->_<!--{{item[primarykey]}}-->"><!--{{item[key]}}--></td>
            <!--{% else %}-->
            <td ><!--{{item[key]}}--></td>
            <!--{% endif %}-->
            <!--{% endfor %}-->
            <td>
            <a href="javascript:void(0);" data-id="<!--{{item[primarykey]}}-->"  class="btn btn-primary select-id">选择</a>
            </td>
            </tr>
       <!--{% endfor %}-->
     */
}