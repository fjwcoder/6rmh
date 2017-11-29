<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | 后台控制器基类
# |
# |
# +-------------------------------------------------------------
namespace app\common\controller;
use app\common\controller\Authority;
use think\Controller;
use think\Config;
use think\Session;

class Manage extends Authority
{

    //权限验证
    public function _initialize(){ //构造函数内无法返回值，无法die/exit；

        // Session::set(Config::get('USER_KEY'), 1);//测试账号
        #是否登录
        // if(!Authority::isLogin()){
        if( Session::get(Config::get('USER_KEY')) ){
            //登陆后，每次跳转，都设置一下session，保持登录状态
            Authority::setAdminInfo(false);
            
        }else{
            session(null);
            return msg('/admin/login/index', '', 'iframe');
            // return $this->redirect('/admin/login/index');
            exit;
        }
        
    }

    //加载后台配置
    public function loadConfig($name){
        $config = cache(Config::get('ADMIN_CONFIG'));
        if(empty($config)){
            $result = db('admin_config', [], false) 
                -> field(array('id', 'name', 'value', 'tvalue')) 
                -> where(array('status'=>1)) 
                -> select(); 
            $common = new Common();
            $config = $common->cacheForeach($result, 'name');
            cache(Config::get('ADMIN_CONFIG'), $config);
        }
        return $config[$name];
    }


}