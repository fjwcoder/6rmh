<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | 公共文件
# |
# |
# +-------------------------------------------------------------


namespace app\common\controller;
use app\common\controller\Authority as Authority;
use think\Controller;
use think\Config;
use think\Session;
use think\Cache;

class Common extends Controller
{
    protected function _initialize(){
        // Session::set(Config::get('USER_KEY'), 1);//测试账号
        
        #是否登录
        // if(!Authority::isLogin()){
        if( Session::get(Config::get('USER_KEY')) ){
            //登陆后，每次跳转，都设置一下session，保持登录状态
            Session::set(Config::get('USER_KEY'), Session::get(Config::get('USER_KEY')));
            
        }else{
            session(null);
            return $this->redirect('/index/login/index');
            exit;
        }
    }
    
}