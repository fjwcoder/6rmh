<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Cart extends Common
{
    public function index(){
        
        $user = decodeCookie('user');
        $mallObj = new Mall();
        // 查出购物车信息，包括
        // 商品具体信息、促销活动、买家信息
        
        

    }






}
