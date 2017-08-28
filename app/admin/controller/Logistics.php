<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 后台Logistics控制器
# | 后台物流公司控制器
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



class Logistics extends Manage
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
        $navid = input('navid', 36, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('mall_logistics', [], false) ->order('id desc') -> paginate();
        $this->assign('list', $list);  
        $header =  ['title'=>'商品管理->配送管理->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }
    


    public function add(){
        if(request()->post()){
            return $this->dataPost('add');
        }
        $navid = input('navid', 36, 'intval');
        $nav = adminNav();
        $this->assign('header', ['title'=>'增加物流', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('logistics');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $navid = input('navid', 0, 'intval');
        $nav = adminNav();
        $id = input('id', 0, 'intval');
        $result = db('mall_logistics', [], false) -> where(array('id'=>$id)) -> find();
        $this->assign('result', $result);
        $this->assign('header', ['title'=>'编辑物流:  【'.$result['title'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('logistics');
    }

    public function del(){
        $id = Request::instance()->param('id');
        $rs = db('mall_logistics')->where(array('id'=>$id))->delete();
        if ($rs) {
            $this->success('删除成功', "Logistics/index");
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
        if($type=='add'){

            $result = db('mall_logistics', [], false) -> insert($data);
        }else{
            $id = $data['id'];
            unset($data['id']);
            $result = db('mall_logistics', [], false) -> where(array('id'=>$id)) ->update($data);
        }

        
        if($result){
            // return $this->success('成功', "request()->controller()/index");
            return $this->success('成功', "Logistics/index");
        }else{
            return $this->error('失败');
        }
    }




}
