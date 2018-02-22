<?php
namespace Yxd\Modules\Core;
use Youxiduo\Helper\MyHelp;
use libraries\Helpers;
class SuperService
{
    protected  $url_array;
    protected  $obj;
    public function __construct()
    {
    }
    //block_
    public function _setObj(&$obj)
    {
        $this->obj=$obj;
        $this->url_array=!empty($this->obj->url_array)?$this->obj->url_array:false;
        if(!empty($this->obj->current_module) and empty($this->url_array)){
            $file =app_path() . '/modules/'. $this->obj->current_module . '/config.php';
            if(is_file($file) && is_readable($file)){
                $arr=require $file;
                $str=(string)get_class($this->obj);
                $this_=str_replace('Controller','',substr(strrchr($str,'\\'),1));
                $this->url_array=!empty($arr[$this_])?$arr[$this_]:array();
            }

        }
    }


    public function _setUrlarray($url_array=''){
         $this->url_array=$url_array;
    }


    //列表
    public function _ListByView($inputinfo=array(),$url=''){
        $inputinfo['pageIndex']=!empty($inputinfo['page'])?$inputinfo['page']:1;
        $result=MyHelp::getdata(!empty($this->url_array['list'])?$this->url_array['list']:$url,$inputinfo);
        if($result['errorCode'] == 0){
            $data = MyHelp::processingInterface($result, $inputinfo, 15);
            $data['inputinfo']=$inputinfo;
            return $data;
        }//$this->obj->block_('list',$result['errorDescription'])
        return array();
    }

    public function _getData($key='',$inputInfo=array())
    {
        return MyHelp::getdata(!empty($this->url_array[$key])?$this->url_array[$key]:false,$inputInfo);
    }

    //删除
    public function _getDel($inputinfo)
    {
        $result=MyHelp::getdata(!empty($this->url_array['delect'])?$this->url_array['delect']:'',$inputinfo);
        if($result['errorCode'] != 0){
            return $this->obj->block_('del',$result['errorDescription']);
        }
        return $this->obj->block_('del','删除成功');//->with('global_tips',)
    }


    public function _EditByView($inputinfo)
    {
        $result=MyHelp::getdata($this->url_array['EditByView'],$inputinfo);
        if($result['errorCode'] != 0){
            return $this->obj->block_('ByView',$result['errorDescription']);
        }
        return $result['result']['0'];
    }

    //验证
    public function _ByValidator($inputinfo,$rule=array(),$prompt=array(),$extend)
    {
        $valid = Validator::make($inputinfo,$rule,$prompt);
        //验证回调
        if(!empty($extend) and is_array($extend)){
            foreach($extend as $key=>$val){
                Validator::extend($key,$val);
            }
        }
        if($valid->fails())
            return $this->obj->block_('ByView',$valid->messages()->first());
        return $inputinfo;
    }

    //post 添加
    public function _PostByAdd($inputinfo){
        $result=MyHelp::postdata($this->url_array['AddPostUrl'],$inputinfo);
        if($result['errorCode']!=0){
            return $this->obj->block_('ByView',$result['errorDescription']);
        }
        return $this->obj->block_('Bypost','添加成功',$this->url_array['callListUrl']);
    }

    //post 修改6
    public function _PostByEdit($input)
    {
        $result=MyHelp::postdata($this->url_array['EditPostUrl'],$input);
        if($result['errorCode']!=0){
            return $this->obj->block_('ByView',$result['errorDescription']);
        }
        return $this->obj->block_('Bypost','修改成功',$this->url_array['callListUrl']);
    }

    public function  _AjaxBy($input,$method='get',$type=''){
        $url=$this->url_array['AjaxUrl'.$type];
        $result=$method=='post'?MyHelp::postdata($url,$input):MyHelp::getdata($url,$input);
        return json_encode($result['errorCode'] == 0 ? array('errorCode'=>0,'msg'=>'操作成功','val'=>$input,'result'=>!empty($result['result'])?$result['result']:array()) : array('errorCode'=>1,'msg'=>$result['errorDescription'],'val'=>$input,'result'=>!empty($result['result'])?$result['result']:array()));

    }

    public function _getChecksbox()
    {

    }

}