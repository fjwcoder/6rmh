<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Goods extends controller
{

    public function detail(){
        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
        }
        $id = input('id', 0, 'intval');

        $config = mallConfig();
        $this->assign('config', ['page_title'=>'商品详情', 'template'=>$config['mall_template']['value'] ]);

        $mallObj = new Mall();
        $goods = $mallObj->getGoodsDetail($id); //获取商品详情
        if($goods['status']){
            // return dump($goods['data']);
            $this->assign('goods', $goods['data']);
            return $this->fetch('detail');
        }else{

        }
        
    }





}