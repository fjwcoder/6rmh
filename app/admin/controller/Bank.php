<?php
# +-------------------------------------------------------------
# | CREATE by ZMX IN 2017-11-14.
# | 后台Bank控制器
# | 后台银行管理控制器
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



class Bank extends Manage
{
    public function index()
    {   
        $navid = input('navid', 58, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('bank', [], false) ->order('id desc') -> paginate();
        $this->assign('list', $list);  
        $header =  ['title'=>'数据统计->银行管理->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }
    


    public function add(){
        if(request()->post()){
            return $this->dataPost('add');
        }
        $navid = input('navid', 58, 'intval');
        $nav = adminNav();
        $this->assign('header', ['title'=>'增加银行', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('bank');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $navid = input('navid', 0, 'intval');
        $nav = adminNav();
        $id = input('id', 0, 'intval');
        $result = db('bank', [], false) -> where(array('id'=>$id)) -> find();
        $this->assign('result', $result);
        $this->assign('header', ['title'=>'编辑银行:  【'.$result['bankname'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('bank');
    }

    public function del(){
        $id = Request::instance()->param('id');
        $rs = db('bank')->where(array('id'=>$id))->delete();
        if ($rs) {
            $this->success('删除成功', "Bank/index");
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

            $result = db('bank', [], false) -> insert($data);
        }else{
            $id = $data['id'];
            unset($data['id']);
            $result = db('bank', [], false) -> where(array('id'=>$id)) ->update($data);
        }

        
        if($result){
            // return $this->success('成功', "request()->controller()/index");
            return $this->success('成功', "Bank/index");
        }else{
            return $this->error('失败');
        }
    }




}
