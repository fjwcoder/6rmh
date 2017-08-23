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
        $auth = new Authority();
        Config::set('IS_WEB_CLOSE', false);
        // 1.查看网站是否关停
        if(Config::get('IS_WEB_CLOSE') == true){
            return $this->redirect('/index/close/index');
            die;
        }

        // Session::set(Config::get('USER_KEY'), 1); //测试
        //2.查看是否登录
        // if(!$auth->isLogin()){
        //     return $this->redirect('/index/login/index');
        //     die;
        // }
    }
    
}