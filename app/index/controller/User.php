<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\common\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;

class User extends Common
{

    public function index(){

        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
        }
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'用户中心', 'template'=>$config['mall_template']['value'] 
            ]);

        return $this->fetch();
    }

    
}
