<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 后台规格管理
# | 后台服务管理控制器
# | email: fjwcoder@gmail.com
# +-------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Manage;
use app\common\controller\Common;
use think\Controller;
use think\Session;
use think\Cookie;
use think\Config;
use think\Request;
use think\Db;
use think\Cache;



class Specific extends Manage
{
    // dump(request()->module());//模块
    // dump(request()->controller()); //控制器
    // dump(request()->action()); //方法
    // private $module = '';
    // private $controller = '';

    // public function _initialize(){
    //     $this->module = request()->module();
    //     request()->controller() = request()->controller();
    // }

    public function index()
    {   
        $navid = input('navid', 33, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('mall_spec', [], false) ->order('id') -> paginate();
        $this->assign('list', $list);  
        $header =  ['title'=>'商品管理->商品配置->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }
    


    public function add(){
        if(request()->post()){
            return $this->dataPost('add');
        }
        $navid = input('navid', 33, 'intval');
        $nav = adminNav();
        $this->assign('header', ['title'=>'添加规格', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('specific');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $navid = input('navid', 33, 'intval');
        $nav = adminNav();
        $id = input('id', 0, 'intval');
        $result = db('mall_spec', [], false) -> where(array('id'=>$id)) -> find();
        $specs = explode(';', $result['value']);
        foreach($specs as $k=>$v){
            $specs[$k] = explode('|', $v);
        }
        $result['value'] = $specs;

        $this->assign('result', $result);
        $this->assign('header', ['title'=>'编辑规格:  【'.$result['title'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('specific');
    }

    public function del(){
        $id = Request::instance()->param('id');
        $rs = db('mall_service')->where(array('id'=>$id))->delete();
        if ($rs) {
            $this->success('删除成功', "Service/index");
        } else {
            $this->error('删除失败');
        }
    }

    public function dataPost($type=''){
        $post = request()->post();

        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        
        unset($data['navid']);
        
        #处理规格
        $spec = [];
        foreach($data['spec'] as $k=>$v){
            $spec[$k] = $v['sort'].'|'.$v['value']; 
        }
        $data['value'] = implode(';', $spec);
        unset($data['spec']);
        // return dump($data);
        if($type=='add'){
            
            $result = db('mall_spec', [], false) -> insert($data);
        }else{
            $id = $data['id'];

            unset($data['id']);
            // return dump($data);
            $result = db('mall_spec', [], false) -> where(array('id'=>$id)) ->update($data);
        }

        
        if($result){
            // return $this->success('成功', "request()->controller()/index");
            return $this->success('成功', "Specific/index");
        }else{
            return $this->error('失败');
        }
    }




}
