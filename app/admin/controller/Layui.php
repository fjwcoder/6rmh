<?php

namespace app\admin\controller;
use app\common\controller\Manage;
use app\common\controller\Common;
use think\Controller;
use think\Session;
use think\Cookie;
use think\Config;
use think\Request;
use think\Db;
use think\Cache;
class Order extends Manage
{   
    public function index(){

        return $this->fetch();
    }

    public function getOrders(){
        
        $order = Db::name('order')-> order('add_time desc')-> paginate();
        
        
        return $order;
    }

}