<?php
namespace app\index\controller;
use think\Controller;
use think\Config;
use think\Session;
use think\Cache;

class Pay extends controller
{

    public function index(){


        $this->assign('header', ['title'=>'用户注册', 'loginbg'=>'']);
        return $this->fetch();
    }

    
    
}
