<?php
namespace app\index\controller;
use app\common\controller\Common; 
// use app\common\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Paginator;
use think\Db;

class Announce extends controller
{

    public function index(){

        $announce = Db::name('announce')->order('addtime DESC') ->select();
        $default = Db::name('announce')->where(array('type'=>1,'status'=>1))->order('addtime DESC') ->limit(1)->find();
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'系统公告', 'template'=>$config['mall_template']['value'] 
            ]);
        $this->assign('announce', $announce);
        $this->assign('default', $default);
        return $this->fetch();

    }

    public function detail(){

        $id = input('id', 0, 'intval');

        $detail = Db::name('announce')->field('title,content') ->where(['id' => $id]) ->select();

        echo json_encode($detail, JSON_UNESCAPED_UNICODE);

    }

}
