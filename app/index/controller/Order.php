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
    #生成订单预览
    public function preview(){
        
        $cart_list = input('id_list', '', 'htmlspecialchars,trim');
        if($cart_list == ''){
            return $this->error('请选择商品'); exit;
        }

        $user = decodeCookie('user');
        $mallObj = new Mall();
        // 查出订单预览信息，包括
        // 查出购物车信息，包括
        // 商品具体信息、规格、图片、促销活动、买家信息
        $field = ['a.id as cart_id', 'a.goods_id', 'a.buyer_id', 'a.num', 'a.spec as spec_id', 'a.price', 'b.promotion as promotion_id', 
            'c.spec', 'b.name', 'b.sub_name', 'b.description', 'b.key_words', 'b.brand', 'b.bait', 'b.point','d.pic'];
        $cart = Db::name('cart') ->alias('a') 
             -> join('goods b', 'a.goods_id=b.id', 'LEFT') 
             -> join('goods_spec c', 'a.spec=c.id', 'LEFT')  
             -> join('goods_picture d', 'a.goods_id=d.gid', 'LEFT') 
             -> field($field)    
             -> group('b.id, a.spec') 
             -> order('a.addtime desc') 
             -> where('a.id in ('.$cart_list.')') 
             -> select(); 

        if(!empty($cart)){
            # 查询促销
            $promotion = Db::name('mall_promotion') 
                -> where('status=1 and begin_time<='.time().' and end_time>='.time()) -> select();
            $promotion = getField($promotion, 'id');
            foreach($cart as $k=>$v){
                if($v['promotion_id'] != 0){
                    $cart[$k]['promotion'] = $promotion[$v['promotion_id']]['title'];
                    if($promotion[$v['promotion_id']]['type'] == 1){
                        $cart[$k]['price'] = $v['price']*$promotion[$v['promotion_id']]['percent']/100;
                    }
                }else{
                    $cart[$k]['promotion'] = '';
                }  
            }
            # 收货地址
            // return dump($this->getAddress());
            $this->assign('address', $this->getAddress());
            # 支付方式
            $this->assign('pay_way', $this->getPayWay());
        }else{
            return '错误'; die;
        }
        $this->assign('carts', $cart);
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'订单预览', 'template'=>$config['mall_template']['value'] ]);



        return $this->fetch();
    }

    public function getAddress(){

        $address = Db::name('user_address') -> where(['userid'=>session(config('USER_ID'))]) -> select();
        // $address = Db::name('user_address') -> where(['userid'=>2]) -> select();
        return $address;
    }

    public function getPayWay(){

        return $pay = [
            ['id'=>1, 'name'=>'微信支付'],
            ['id'=>2, 'name'=>'支付宝支付'],
            ['id'=>3, 'name'=>'银联支付'],
            ['id'=>4, 'name'=>'货到付款']
        ];

    }
    #创建订单
    public function add(){

    }


    
}
