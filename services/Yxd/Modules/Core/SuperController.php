<?php
namespace Yxd\Modules\Core;
use Youxiduo\Helper\MyHelp;
use Yxd\Modules\Core\BackendController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
class SuperController extends BackendController
{

    //列表
    public function getList(){
        echo 1;exit;
        $data = $params = array();
        $inputinfo=Input::all();
        if(!empty($inputinfo['page'])) $inputinfo['pageIndex']=Input::get('page',1);
        $inputinfo=$this->_setInputinfo($inputinfo);
        $result=MyHelp::getdata($this->getListurl,$inputinfo);
        if($result['errorCode'] == 0){
            $data = MyHelp::processingInterface($result, $inputinfo, 15);
            $data=$this->_getGlobalData($data);
            $data['inputinfo']=$inputinfo;
            return $this->display($this->controller.'/'.$this->controller.'-list',$data);
        }
        return $this->back()->with('global_tips',$result['errorDescription']);
    }



    public function getAdd(){
        $data=array('add'=>1);
        $data=$this->_getGlobalData($data);
        return $this->display($this->controller.'/'.$this->controller.'-add',$data);
    }


    public function getEdit($id=0,$key='')
    {
        $inputinfo[empty($key)?'id':$key]=$id;
        $result=MyHelp::getdata($this->getListurl,$inputinfo);
        if($result['errorCode'] == 0){
            $result['result']=$result['result']['0'];
            $result=$this->_getGlobalData($result);//print_r($result);exit;
            return $this->display($this->controller.'/'.$this->controller.'-edit',$result);
        }else{
            return $this->back()->with('global_tips',$result['errorDescription']);
        }
        return $this->display($this->controller.'/'.$this->controller.'-edit',array());
    }


    public function postAdd(){
        $input = Input::all();
        $v=$this->_setInputinfo($input);
        if(!empty($v['rule'])){
            $valid = Validator::make($v['input'],$v['rule'],$v['prompt']);
        }else{
            $valid = Validator::make($input,array(),array());
        }
        if($valid->fails())
            return $this->back()->withInput()->with('global_tips',$valid->messages()->first());
        $input=$this->_setAddModifyData($input);//print_r($input);
        $result=MyHelp::postdata($this->getAddurl,$input);
        if($result['errorCode']==0){
            $result=$this->_afterDBAct($result);
            return $this->redirect(str_replace('_','',$this->current_module).'/'.$this->controller.'/list')->with('global_tips','添加成功');
        }else{
            return $this->back()->withInput()->with('global_tips',$result['errorDescription']);
        }
    }

    public function postEdit()
    {
        $input = Input::all();
        $v=$this->_setInputinfo($input);
        if(!empty($v['rule'])){
            $valid = Validator::make($v['input'],$v['rule'],$v['prompt']);
        }else{
            $valid = Validator::make($input,array(),array());
        }
        if($valid->fails())
            return $this->back()->withInput()->with('global_tips',$valid->messages()->first());
        $input=$this->_setAddModifyData($input);
        $result=MyHelp::postdata($this->getEditurl,$input);//print_r($result);exit;
        if($result['errorCode']==0){
            $result=$this->_afterDBAct($result);//;
            return $this->redirect(str_replace('_','',$this->current_module).'/'.$this->controller.'/list')->with('global_tips','修改成功');
        }else{
            return $this->back()->withInput()->with('global_tips',$result['errorDescription']);
        }
    }



    public function getSet($type='',$method='get')
    {
        if(empty($type) || empty($this->url[$type])){
            echo  json_encode(array('errorCode'=>1,'msg'=>'操作失败 接口地址丢失'));exit;
        }
        $input = Input::all();
        $url=$this->url[$type];
        $input['type']=$type;
        $input=$this->_setParams($input);
        unset($input['type']);
        $result=$method=='post'?MyHelp::postdata($url,$input):MyHelp::getdata($url,$input);
        //print_r($result);exit;
        $result=$result['errorCode'] == 0 ? array('errorCode'=>0,'msg'=>'操作成功','val'=>$input,'result'=>!empty($result['result'])?$result['result']:array()) : array('errorCode'=>1,'msg'=>$result['errorDescription'],'val'=>$input,'result'=>!empty($result['result'])?$result['result']:array());
        echo  json_encode($result);
        exit;
    }


}