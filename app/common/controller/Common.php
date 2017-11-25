<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | 公共文件
# |
# |
# +-------------------------------------------------------------


namespace app\common\controller;
use app\common\controller\Authority as Authority;
use app\extend\controller\Gaode as Gaode;
use think\Controller;
use think\Config;
use think\Session;
use think\Cache;

class Common extends Controller
{
    protected function _initialize(){
        
        if(empty(session('LOCATION'))){
            $gaode = new Gaode();
            $gaode->IPLocation();
        }

        #是否登录
        if( Session::get(Config::get('USER_ID')) ){
            //登陆后，每次跳转，都设置一下session，保持登录状态
            Session::set(Config::get('USER_ID'), Session::get(Config::get('USER_ID')));
            
            $this->assign('cookie', decodecookie('user'));
        }else{
            session(null);
            return $this->redirect('/index/login/index');
            exit;
        }
    }
    
}