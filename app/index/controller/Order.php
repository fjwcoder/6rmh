<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use app\index\controller\Address as Address; 
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Order extends Common
{
    #生成订单预览
    public function preview($cart_list=''){
        if(empty($cart_list)){
            $cart_list = input('id_list', '', 'htmlspecialchars,trim');
            if($cart_list == ''){
                return $this->error('请选择商品'); exit;
            }
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
            $count = ['baits'=>0, 'points'=>0, 'prices'=>0];
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
                #计算订单总额们
                $count['baits'] += floatval($cart[$k]['bait']*$cart[$k]['num']);  
                $count['points'] += floatval($cart[$k]['point']*$cart[$k]['num']);
                $count['prices'] += floatval($cart[$k]['price']*$cart[$k]['num']); 
            }

            # 收货地址
            $this->assign('address', $this->getAddress());
            # 支付方式
            $this->assign('pay_way', $this->getPayWay());
            # 配送方式
            // return dump($this->getDelivery());
            $this->assign('delivery', $this->getDelivery());
        }else{
            return '错误'; die;
        }
        $this->assign('id_list', $cart_list);
        $this->assign('carts', $cart);
        $this->assign('count', $count);
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'订单预览', 'template'=>$config['mall_template']['value'] ]);



        return $this->fetch();
    }



    #创建订单
    public function create(){
        $id_list = input('id_list', '', 'htmlspecialchars,trim');
        $pay = input('pay', 0, 'intval');
        $addr = input('addr', 0, 'intval');
        $ship = input('delivery', 0, 'intval');

        if(empty($id_list))
            return $this->error('商品参数错误');
        if($pay === 0)
            return $this->error('支付方式错误');
        if($addr === 0)
            return $this->error('收货地址错误');
        if($ship === 0)
            return $this->error('请选择配送方式');

        #获取支付方式
        $payment = $this->getPayWay();
        #获取收货地址
        $address = Db::name('user_address') -> where(['userid'=>session(config('USER_iD')), 'id'=>$addr]) -> find();
        #获取配送方式
        $delivery = $this->getDelivery();
        #获取商品信息
        // return $id_list;
        $goods = Db::name('goods') -> alias('a') 
            -> join('goods_spec b', 'a.id=b.gid', 'LEFT') 
            -> join('goods_picture c', 'a.id=c.gid', 'LEFT') 
            -> field()
            // -> group('a.id, b.spec') 
            -> where('a.id in ('.$id_list.')') 
            -> select(); 
        return dump($goods);
        $data = ['userid'=>session(config('USER_iD')), 'order_id'=>getOrderID(), 
            'status'=>0, 'pay_status'=>0,// 'currency'=>0, 'money'=>0, 
            'payment_id'=>$pay, 'payment_name'=>$payment[$pay]['name'],
            'shipping_id'=>$delivery[$ship]['id'], 'shipping_name'=>$delivery[$ship]['title'],
            'user_name'=>$address['name'], 'user_address'=>$address['province'].$address['city'].$address['area'].$address['address'],
            'user_mobile'=>$address['mobile']
            ];
        return dump($data);

    }


    #设置默认地址
    public function defAddr(){
        $cart_list = input('id_list', '', 'htmlspecialchars,trim');
        $id = input('id', 0, 'intval');
        $addr = new Address();
        if($addr->defAddr($id)){
            return $this->redirect('preview', ['id_list'=>$cart_list]);
        }else{
            return '修改失败';
        }

    }

    #删除地址
    public function delAddr(){
        $cart_list = input('id_list', '', 'htmlspecialchars,trim');
        $id = input('id', 0, 'intval');
        $addr = new Address();
        if($addr->delAddr($id)){
            return $this->redirect('preview', ['id_list'=>$cart_list]);
        }else{
            return '修改失败';
        }
    }

        public function getAddress(){
        $address = Db::name('user_address') -> where(['userid'=>session(config('USER_ID'))]) ->order('type desc') -> select();
        // $address = Db::name('user_address') -> where(['userid'=>2]) -> select();
        return $address;
    }

    #获取配送方式
    public function getDelivery(){
        if(cache('MALL_DELIVERY')){
            $delivery = cache('MALL_DELIVERY');
        }else{
            $delivery = Db::name('mall_delivery') -> where('status=1') -> select();
            $delivery = getField($delivery, 'id');
            // cache('MALL_DELIVERY', $delivery);   //缓存注释
        }

        return $delivery;
    }

    #获取支付方式
    public function getPayWay(){
        $pay = [
            ['id'=>1, 'name'=>'微信支付'],
            ['id'=>2, 'name'=>'支付宝支付'],
            ['id'=>3, 'name'=>'银联支付'],
            ['id'=>4, 'name'=>'货到付款']
        ];

        return getField($pay, 'id');
    }



}
