<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Fishing extends Common
{
    public function index(){
        
 
        $id = session(config('USER_ID'));
        
        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
        }
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'钓鱼页面', 'template'=>$config['mall_template']['value'] 
            ]);

        return '钓鱼页面';


        return $this->fetch();
    }



}
