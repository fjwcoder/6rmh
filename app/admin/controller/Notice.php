<?php
# +-------------------------------------------------------------
# | CREATE by ZMX IN 2017-10-20.
# | 后台Notice控制器
# | 后台配送方式控制器
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



class Notice extends Manage
{
    public function index()
    {   
        $navid = input('navid', 14, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('notice', [], false) ->order('id desc') -> paginate();
        $this->assign('list', $list);  
        $header =  ['title'=>'欢迎登录->系统公告->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }
    


    public function add(){
        if(request()->post()){
            return $this->dataPost('add');
        }
        $navid = input('navid', 14, 'intval');
        $nav = adminNav();
        $this->assign('header', ['title'=>'增加公告', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('notice');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $navid = input('navid', 0, 'intval');
        $nav = adminNav();
        $id = input('id', 0, 'intval');
        $result = db('notice', [], false) -> where(array('id'=>$id)) -> find();
        $this->assign('result', $result);
        $this->assign('header', ['title'=>'编辑公告:  【'.$result['title'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('notice');
    }

    public function del(){
        $id = Request::instance()->param('id');

        $rs = db('notice')->where(array('id'=>$id))->delete();
        if ($rs) {
            $this->success('删除成功', "Notice/index");
        } else {
            $this->error('删除失败');
        }
    }

    public function dataPost($type=''){
        $post = request()->post();
        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        $data['addtime'] = time();
        unset($data['navid']);
        if($type=='add'){
            // return dump($data);
            $result = db('notice', [], false) -> insert($data);
        }else{
            $id = $data['id'];
            unset($data['id']);
            unset($data['addtime']);
            $result = db('notice', [], false) -> where(array('id'=>$id)) ->update($data);
        }

        
        if($result){
            // return $this->success('成功', "request()->controller()/index");
            return $this->success('成功', "Notice/index");
        }else{
            return $this->error('失败');
        }
    }




}
