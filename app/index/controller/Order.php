<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Order extends Common
{
    public function index(){
        
        $cart_list = input('id_list', '', 'htmlspecialchars,trim');
        if($cart_list == ''){
            return $this->error('请选择商品'); exit;
        }

        // return $cart_list;
        $user = decodeCookie('user');
        $mallObj = new Mall();
        // 查出购物车信息，包括

        $config = mallConfig();
        $this->assign('config', ['page_title'=>'订单', 'template'=>$config['mall_template']['value'] ]);



        return $this->fetch();
    }




    #删除购物车商品
    public function del(){
        $id = input('id', 0, 'intval');
        return $id;
    }

    #清除购物车
    public function delate(){
        return '清除购物车';
        $result = Db::name('cart')->where(['buyer_id'=>session(config('USER_ID'))]) -> delete();
        if($result){
            return $this->success('购物车清除成功', 'Cart/index');
        }else{
            return $this->error('购物车清除失败');
        }
    }

}
