<?php
# +-------------------------------------------------------------
# | CREATE by ZMX IN 2017-9-17.
# | 后台Userlist控制器
# | 后台用户列表控制器
# | 
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



class Userlist extends Manage
{
    public function index()
    {   
        $navid = input('navid', 54, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('users', [], false) ->field('id,pid,name,nickname,realname,status') -> paginate();
        $this->assign('list', $list);  
        $header =  ['title'=>'用户管理->用户管理->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }

    #锁定启用用户
    public function status(){
        $navid = input('navid', 0, 'intval');
        $id = input('id', 0, 'intval');
        $user = db('users', [], false) -> where(array('id'=>$id)) -> find();
        if($user['status'] == 1){
            $sta = Db::name('users') 
                -> where(array('id'=>$id)) 
                -> update(['status'=>2]);
            if($sta){
                return $this->success('用户锁定成功', "Userlist/index");
            }else{
                return $this->error('用户锁定失败');
            }            
        }else{
            $stas = Db::name('users') 
            -> where(array('id'=>$id)) 
            -> update(['status'=>1]);
            if($stas){
                return $this->success('用户启用成功', "Userlist/index");
            }else{
                return $this->error('用户启用失败');
            }
        }
    }

    #用户详情显示
    public function userdetail(){
        $navid = input('navid', 0, 'intval');
        $id = input('id', 0, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $user = db('users', [], false) -> where(['id'=>$id]) -> find();
        $header =  ['title'=>'用户管理->用户管理->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'edit', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        $this->assign('user', $user);
        return $this->fetch();
    }

    public function add(){
        return $this->error('不允许后台添加');
    }

}
