<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\common\controller\Web;
use think\Controller;
use think\Config;
use think\Session;

class Index extends Common
{

    public function index(){
        // return dump(config()); //打印一下框架配置
        $mall_config = mallConfig();
        $this->assign('config', ['template'=>$mall_config['index_template']['value']
            ]);
        // $this->assign('user', ['']);
        return $this->fetch($mall_config['index_template']['value']);
    }


}
