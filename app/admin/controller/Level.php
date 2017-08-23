<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 后台Level控制器
# | 后台级别管理控制器
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



class Level extends Manage
{
    public function index()
    {   
        $navid = input('navid', 13, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('admin_level', [], false)  ->order('level desc') -> paginate(15);;
        $this->assign('list', $list);   
        $header = ['title'=>'扩展管理->用户归属->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'],
         'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }
    


    public function add(){
        if(request()->post()){
            return $this->dataPost('add');
        }
        $navid = input('navid', 13, 'intval');
        $nav = adminNav();
        $max = db('admin_level', [], false) ->max('level');
        $this->assign('action', ['max'=>$max, 'level'=>intval($max+1)]);
        $this->assign('header', ['title'=>'增加级别', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('level');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $navid = input('navid', 0, 'intval');
        $nav = adminNav();
        $id = input('id', 0, 'intval');
        $result = db('admin_level', [], false) -> where(array('id'=>$id)) -> find();
        $max = db('admin_level', [], false) ->max('level');
        $this->assign('result', $result);
        $this->assign('already', ['max'=>$max, 'level'=>$result['level']]);
        $this->assign('header', ['title'=>'编辑级别:  【'.$result['title'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('level');
    }

    public function dataPost($type=''){
        $post = request()->post();
        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        unset($data['navid'], $data['max']);
        if($type=='add'){
            $data['name'] = 'level'.$data['level'];
            $result = db('admin_level', [], false) -> insert($data);
        }else{
            $id = $data['id'];
            unset($data['level'], $data['id']);
            $result = db('admin_level', [], false) -> where(array('id'=>$id)) ->update($data);
        }
     
        if($result){
            return $this->success('成功', 'level/index');
        }else{
            return $this->error('失败');
        }
    }




}
