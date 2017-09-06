<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Sale extends Common
{

    #立即够买
    public function buy(){
        $action = input('action', '', 'htmlspecialchars,trim');

        
        return $id.'=>'.$spec.'=>'.$num;
        $mallObj = new Mall();
        

    }

    #加入购物车
    public function cart(){
        $id = input('id', 0, 'intval');
        $sid = input('spec', 0, 'intval'); //spec  id
        $num = input('num', 0, 'intval');

        #检查商品状态
        $goods = db('goods', [], false) -> where(['id'=>$id]) -> find();
        if($goods['status'] != 1){
            return $this->error('商品已下架'); exit;
        }
        $spec = db('goods_spec', [], false) -> where(['id'=>$sid]) -> find();
        if($spec['num'] < $num){
            return $this->error('商品数量不足'); exit;
        }
  
        $data = ['buyer_id'=>Session::get(Config::get('USER_ID')), 
            'seller_id'=>$goods['userid'], 'goods_id'=>$id, 
            'price'=>$spec['price']?$spec['price']:$goods['price'],
            'num'=>$num, 'addtime'=>time(), 'spec'=>$sid
            ];
        
        $add = Db::name('cart') -> insert($data);
        if($add){
            return '添加购物车成功';
        }else{
            return '添加购物车失败';
        }
        

    }


    public function collect(){
        $id = input('id', 0, 'intval');
        $spec = input('spec', 0, 'intval');
        $num = input('num', 0, 'intval');

        
        
        return $id.'=>'.$spec.'=>'.$num;
        $mallObj = new Mall();
    }




}
