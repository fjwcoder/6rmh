<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | 权限验证
# |
# |
# +-------------------------------------------------------------
namespace app\common\controller;
use think\Controller;
use think\Config;
use think\Session;

class Authority extends Controller
{
    // 是否登录
    public function isLogin(){
        if(Session::get(Config::get('USER_KEY'))){
            return true;
        }else{
            return false;
        }
    }


    /* 验证权限
     * $type: 是否设置cookie
     * 设置好 配置中心里的用户ID和用户名； 验证标志设置为true
     */
    public function setAdminInfo($type = false){
        Session::set(Config::get('USER_KEY'), Session::get(Config::get('USER_KEY')));
        $id = Session::get(Config::get('USER_KEY'));
        if(!empty(Session::get(Config::get('ADMIN_AUTH_KEY')))){
            Session::set(Config::get('ADMIN_AUTH_KEY'), Session::get(Config::get('ADMIN_AUTH_KEY')));
            Session::set(Config::get('ADMIN_AUTH_NAME'), Session::get(Config::get('ADMIN_AUTH_NAME')));
            Session::set(Config::get('ADMIN_AUTH_LEVEL'), Session::get(Config::get('ADMIN_AUTH_LEVEL')));
        }else{
            //从数据库中查询数据
            $admin = db('admin_member',[], false) -> field(array('id', 'name', 'title', 'level', 'status')) -> where(array('id'=>$id, 'status'=>1)) -> find();
            Session::set(Config::get('ADMIN_AUTH_KEY'), $admin['id']);
            Session::set(Config::get('ADMIN_AUTH_NAME'), $admin['name']);
            Session::set(Config::get('ADMIN_AUTH_LEVEL'), $admin['level']);
        }
        
        // if($type){
        //     ksetcookie([$id, $admin['name']]); //设置cooke
        // }     
        
    }

}