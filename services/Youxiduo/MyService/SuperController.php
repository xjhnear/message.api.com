<?php
/**
 * Created by PhpStorm.
 * User: fujiajun
 * Date: 15/12/3
 * Time: 下午5:03
 */

namespace Youxiduo\MyService;



use Youxiduo\Helper\MyHelp;
use Yxd\Modules\Core\BackendController;
use Youxiduo\MyService\QueryService;
use Youxiduo\Cache\CacheService;
use Validator;
use Input;
use Cache;
use Log,App,Request;
use modules\v4_adv\models\Core;
//如果继承该类

class  SuperController extends BackendController
{
    //请求URL地址
    protected $url_array=array(
        'list_url'=>'',//列表
        'post_add'=>'',//添加
        'post_edit'=>'',//修改
        'set'=>array(),//为SET方法中type为KEY跳转如‘top’＝》URL地址
    );

    protected $_config=array(
        'date'=>'',//设置当前时间用于记录日志或者其他用途
        'obj'=>'',//对象子类变量
        'lookLog'=>false,//是否查看当前执行log日志
        'isFirePHP'=>false,//是否在FirePHP查看LOG
        'curlType'=>'POST',//CURL 请求类型
        'controller'=>'',//设置调用Controller
        'isSql'=>false,//是否读取数据库
        'databas_table'=>'table',
        'isSqlListen'=>false,

    );

    //请求URL地址 添加 修改返回的URL地址
    protected $callback_url=array(
         'add'=>'',
         'edit'=>'',

    );

    //设置缓存
    protected $Cache=array(
        'is_cache'=>false,//是否开启缓存
        'minutes'=>5,//缓存时间
        'key'=>'',
    );



    public function __construct(&$this_,$config=false)
    {
        $this->_config['obj']=$this_;
        $this->_config['date']=date('Y-m-d H:i:s',time());
        $this->_config['controller']=strtolower(self::_getClassName());
        parent::__construct();
        if($this->_config['isSqlListen'])
            self::boot();
    }

    //列表查询
    public function getList($restId=0){
        //http://test.open.youxiduo.com/doc/interface-info/649
        $inputinfo=Input::all();
        if($restId){
            $inputinfo['restId']=$restId;
        }
        if(!empty($inputinfo['page'])) $inputinfo['pageIndex']=Input::get('page',1);
        $inputinfo=self::_setCallBack('BeforeList',$inputinfo);
        $result=self::getResult($inputinfo);
        //var_dump($result['result'][2]['items']);die;
        self::_looklog($result,$this->_config['isFirePHP']);
        if($result['errorCode'] == 0){
            if($this->_config['isSql']){
                $inputinfo=$inputinfo['where'];
            }
            $pageSize = isset($inputinfo['pageSize'])?$inputinfo['pageSize']:10;
            list($result['inputinfo'],$result)=array($inputinfo,MyHelp::processingInterface($result, $inputinfo,$pageSize));
            $result=self::_setCallBack('AfterList',$result);
           // var_dump($result['datalist'][2]['items']);die;
            return $this->display($this->_config['controller'].'/'.$this->_config['controller'].'-list',$result);
        }
        return $this->back()->with('global_tips',$result['errorDescription']);
    }


    //添加View
    public function getAdd(){
        $result=self::_setCallBack('AfterViewAdd',Input::all());
        self::_looklog($result,$this->_config['isFirePHP']);
        return $this->display($this->_config['controller'].'/'.$this->_config['controller'].'-add',$result);
    }

    //修改View
    public function getEdit($id=0,$key='')
    {
        $inputinfo[empty($key)?'id':$key]=$id;
        $inputinfo=self::_setCallBack('BeforeViewEdit',$inputinfo);
        self::_looklog($inputinfo,$this->_config['isFirePHP']);
        $result=self::getResult($inputinfo);
        self::_looklog($result,$this->_config['isFirePHP']);
        if($result['errorCode'] == 0){
            $result=self::_setCallBack('AfterViewEdit',current($result['result']));
            return $this->display($this->_config['controller'].'/'.$this->_config['controller'].'-edit',$result);
        }else{
            return $this->back()->with('global_tips',$result['errorDescription']);
        }
    }

    /**
     * @param $inputinfo
     * @return mixed rule = array('product_code'=>'required');
     * rule = array('product_code'=>'required');
     */
    //验证
    private function _ByValidator($inputinfo)
    {
        $rule_prompt['rule']=$rule_prompt['prompt']=array();//最简单的验证
        //如果有自定义相应验证方法请实现  rule_prompt 方法 返回数组KEY 为 rule，prompt，extend（可空 根据自身需求）
        if(method_exists($this->_config['obj'],'rule_prompt')){
            $rule_prompt=$this->rule_prompt($inputinfo);
        }
        $valid = Validator::make($inputinfo,$rule_prompt['rule'],$rule_prompt['prompt']);
        //验证回调
        if(!empty($rule_prompt['extend']) and is_array($rule_prompt['extend'])){
            foreach($rule_prompt['extend'] as $key=>$val){
                Validator::extend($key,$val);
            }
        }
        if($valid->fails())
            return $this->back()->withInput()->with('global_tips',$valid->messages()->first());
        return true;
    }

    //post 添加请求
    public function postAdd(){
        $inputinfo=self::_setCallBack('BeforePostAdd',Input::all());
        self::_ByValidator($inputinfo);//验证
        $arr_controller = array( 'popup','carousel','video','recommend','banner','vicebanner','indexbanner','gameinfo','gameinfotop','startup','task');
        if(in_array($this->_config['controller'],$arr_controller)){
            if($inputinfo['adv_href_type'] != '外部url' && $inputinfo['adv_href_type'] != '内部safari'){
                $inputinfo['isAutoLogin'] = 2;
            }
        }
        self::_looklog($inputinfo,$this->_config['isFirePHP']);
        if (isset($this->_config['except'])) {
            $except = array();
            foreach ($this->_config['except'] as $k) {
                if (isset($inputinfo[$k])) {
                    $except[$k] = $inputinfo[$k];
                    unset($inputinfo[$k]);
                }
            }
        }
        if($this->_config['isSql']){
            QueryService::$databas_table=$this->_config['databas_table'];
            $result=QueryService::addData($inputinfo);
        }else{
            $result=MyHelp::curldata($this->url_array['post_add'],$inputinfo,'POST',$this->_config['lookLog']);
        }
        $result['url']=$this->url_array['post_add'];
        if (isset($except)) {
            $inputinfo = array_merge($inputinfo,$except);
        }
        $result['inputinfo']=$inputinfo;
        self::_looklog($result,$this->_config['isFirePHP']);
        if($result['errorCode']==0){
            $arr_adv_controller = array( 'popup','carousel','video','recommend','banner','vicebanner','indexbanner','gameinfo','webgame','pcgame');
            if (isset($inputinfo['platform']) && $inputinfo['platform'] == 'iosyn') {
                $appname = 'youxiduojiu3';
            } else {
                $appname = 'yxdjpb';
            }
            if(in_array($this->_config['controller'],$arr_adv_controller)) $data_del_cache = Core::delcache(array('type'=>1,'appname'=>$appname));
            if (isset($data_del_cache)&&$data_del_cache==false) {
                $this->callback_url['add']=$this->redirect(str_replace('_','',$this->current_module).'/'.$this->_config['controller'].'/list')->with('global_tips','添加成功,缓存失败');
                self::_setCallBack('AfterPostAdd',$result);
                return  $this->callback_url['add'];
            }
            $this->callback_url['add']=$this->redirect(str_replace('_','',$this->current_module).'/'.$this->_config['controller'].'/list')->with('global_tips','添加成功');
            self::_setCallBack('AfterPostAdd',$result);
            return  $this->callback_url['add'];
        }else{
            return $this->back()->withInput()->with('global_tips',$result['errorDescription']);
        }
    }

    public function postEdit()
    {
        $inputinfo=self::_setCallBack('BeforePostEdit',Input::all());
        if (isset($inputinfo['id'])) {
            $id = $inputinfo['id'];
        }
        self::_ByValidator($inputinfo);//验证
        $arr_controller = array( 'popup','carousel','video','recommend','banner','vicebanner','indexbanner','gameinfo','gameinfotop','startup','task');
        if(in_array($this->_config['controller'],$arr_controller)){
            if($inputinfo['adv_href_type'] != '外部url' && $inputinfo['adv_href_type'] != '内部safari'){
                $inputinfo['isAutoLogin'] = 2;
            }
        }
        self::_looklog($inputinfo,$this->_config['isFirePHP']);
        if (isset($this->_config['except'])) {
            $except = array();
            foreach ($this->_config['except'] as $k) {
                if (isset($inputinfo[$k])) {
                    $except[$k] = $inputinfo[$k];
                    unset($inputinfo[$k]);
                }
            }
        }
        if($this->_config['isSql']){
            QueryService::$databas_table=$this->_config['databas_table'];
            if(!empty($inputinfo['id'])){
                $where[]='id';
                $where[]=$inputinfo['id'];
                unset($inputinfo['id']);
            }
            $result=QueryService::editData($inputinfo,$where);
        }else{
            $result=MyHelp::curldata($this->url_array['post_edit'],$inputinfo,'POST',$this->_config['lookLog']);
        }
        $result['url']=$this->url_array['post_add'];
        if (isset($except)) {
            $inputinfo = array_merge($inputinfo,$except);
        }
        if (isset($id)) {
            $inputinfo['id'] = $id;
        }
        $result['inputinfo']=$inputinfo;
        self::_looklog($result,$this->_config['isFirePHP']);
        if($result['errorCode']==0){
            //2V4商城福利管理界面
            $data =array();
            if($this->_config['controller'] == 'welfare') $data = CacheService::cache_del_key(4);
            if(isset($data['errorCode'])&&$data['errorCode']!=0){
                $this->callback_url['edit']=$this->redirect(str_replace('_','',$this->current_module).'/'.strtolower($this->_config['controller']).'/list')->with('global_tips','修改成功,缓存失败');
                self::_setCallBack('AfterPostEdit',$inputinfo);
                return $this->callback_url['edit'];
            }
            //v4广告缓存
            $arr_adv_controller = array( 'popup','carousel','video','recommend','banner','vicebanner','indexbanner','gameinfo','webgame','pcgame');
            if (isset($inputinfo['platform']) && $inputinfo['platform'] == 'iosyn') {
                $appname = 'youxiduojiu3';
            } else {
                $appname = 'yxdjpb';
            }
            if(in_array($this->_config['controller'],$arr_adv_controller)) $data_del_cache = Core::delcache(array('type'=>1,'appname'=>$appname));
            if (isset($data_del_cache)&&$data_del_cache == false) {
                $this->callback_url['edit']=$this->redirect(str_replace('_','',$this->current_module).'/'.strtolower($this->_config['controller']).'/list')->with('global_tips','修改成功,缓存失败');
                self::_setCallBack('AfterPostEdit',$inputinfo);
                return $this->callback_url['edit'];
            }
            $this->callback_url['edit']=$this->redirect(str_replace('_','',$this->current_module).'/'.strtolower($this->_config['controller']).'/list')->with('global_tips','修改成功');
            self::_setCallBack('AfterPostEdit',$inputinfo);
            return $this->callback_url['edit'];
        }else{
            return $this->back()->withInput()->with('global_tips',$result['errorDescription']);
        }
    }





    public function getSet($type='',$method='get')
    {
        if(empty($type) || empty($this->url_array['set'][$type])){
            echo  json_encode(array('errorCode'=>1,'msg'=>'操作失败 接口地址丢失'));
            exit;
        }
        $inputinfo= Input::all();
        if(method_exists($this->_config['obj'],'set_inputinfo')){
            $inputinfo=$this->set_inputinfo($inputinfo,$type);
        }
        $inputinfo['modifier'] = parent::getSessionUserName();
        $result=MyHelp::curldata($this->url_array['set'][$type],$inputinfo,$method,$this->_config['lookLog']);
        if(method_exists($this->_config['obj'],'set_result')){
            $result=$this->set_result($result,$type,$inputinfo);
        }
        $result=$result['errorCode'] == 0 ? array('errorCode'=>0,'msg'=>'操作成功','val'=>$inputinfo,'result'=>!empty($result['result'])?$result['result']:array()) : array('errorCode'=>1,'msg'=>$result['errorDescription'],'val'=>$inputinfo,'result'=>!empty($result['result'])?$result['result']:array());
        echo  json_encode($result);
        exit;
    }

    /**
     * @param $inputinfo
     * @ret   urn mixed
     */
    protected function getResult($inputinfo=array())
    {
        $this->Cache['key']='list_'.$this->_config['controller'].'_'.Input::get('page',1);
        if($this->Cache['is_cache'] and Cache::has($this->Cache['key'])){
            $result=Cache::get($this->Cache['key']);
        }else{
            $result=self::_cacheCallBack($inputinfo);
            if(!Cache::has($this->Cache['key']))
                Cache::add($this->Cache['key'], $result, $this->Cache['minutes']);
        }
        return $result;
    }

    private function _getClassName(){
        $str=(string)get_class($this->_config['obj']);
        return str_replace('Controller','',substr(strrchr($str,'\\'),1));
    }

    private  function _setCallBack($key,$data=array()){

        if(method_exists($this->_config['obj'],$key)){
            return call_user_func_array(array($this->_config['obj'],$key),array($data,$key));
        }
        return $data;
    }

    private function _cacheCallBack($inputinfo){
        if(!$this->_config['isSql']){
            return MyHelp::curldata($this->url_array['list_url'], $inputinfo,'GET',$this->_config['lookLog']);
        }else{
            QueryService::$databas_table=$this->_config['databas_table'];
            return QueryService::getDatalistByPage($inputinfo);
        }

    }

    private function _looklog($result=array(),$stop=false){

        if($stop){
            //Log::debug(json_encode($result));
            $monolog= Log::getMonolog();
            $monolog->pushHandler(new \Monolog\Handler\FirePHPHandler());
            $monolog->addInfo('Log Message', array('items' => json_encode($result)));

        }
    }

    public function __cell(){
        return $this->back()->with('global_tips','方法缺失');
    }

    private function boot()
    {

        \DB::listen(function($sql, $bindings, $time)
        {
            $monolog= Log::getMonolog();
            $monolog->pushHandler(new \Monolog\Handler\FirePHPHandler());
            header("Content-type: text/html; charset=utf-8");
            $monolog->addInfo('Log Message', array('sql' =>$sql,'bindings'=>json_encode($bindings),'time'=>$time,'date'=>date('Y-m-d H:i:s',time())));
        });
    }

    public function Log_listen()
    {
        Log::listen(function($level, $message, $context)
        {
            //
           // echo 1;exit;
        });

    }
}