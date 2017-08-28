<?php
namespace app\index\controller;
use think\Controller;
use think\Config;
use think\Session;
use think\Cache;

class Login extends controller
{

    public function index(){

        
        // return dump($footer);
        $this->assign('header', ['title'=>'用户登录', 'loginbg'=>'']);
        return $this->fetch();
    }

    public function footerInfo(){
        $footer = db('web_info', [], false) -> where(array('name'=>'common_footer_info')) -> select();
        echo json_encode(array('footers'=>$footer), JSON_UNESCAPED_UNICODE);
    }

    public function login(){
        
    }
}
