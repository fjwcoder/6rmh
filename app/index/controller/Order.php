<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use app\index\controller\Address as Address; 
use app\index\controller\Cart as Cart; 
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Order extends Common
{

    #订单详情页
    public function index(){
        $status = input('status', 0, 'intval');
        // echo $status;
        $order = [];
        $where['a.userid'] = session(config('USER_ID'));
        if($status !== 0 ){
            $where['status'] = $status;
        }
        $data = Db::name('order') ->alias('a')
            ->join('order_detail b', 'a.order_id=b.order_id') 
            ->field(['a.*', 'b.gid', 'b.catid_list', 'b.name as goods_name', 'b.pic', 'b.price', 'b.num', 'b.bait', 
                'b.point', 'b.promotion_id', 'b.promotion', 'b.service', 'b.spec'])
            ->where($where) ->order('a.add_time desc') -> paginate();

        if(!empty($data)){
            foreach($data as $k=>$v){
                if(!array_key_exists($v['order_id'], $order)){
                    $order[$v['order_id']]['order'] = ['order_id'=>$v['order_id'], 'userid'=>$v['userid'], 'status'=>$v['status'], 
                        'pay_status'=>$v['pay_status'], 'balance'=>$v['balance'], 'money'=>$v['money'], 'baits'=>$v['baits'], 
                        'points'=>$v['points'], 'payment_id'=>$v['payment_id'], 'payment_name'=>$v['payment_name'], 
                        'shipping_id'=>$v['shipping_id'], 'shipping_name'=>$v['shipping_name'], 'addtime'=>$v['add_time'],
                        'user_name'=>$v['user_name'], 'user_address'=>$v['user_address'], 'user_mobile'=>$v['user_mobile']];
                }
                $order[$v['order_id']]['detail'][] = ['gid'=>$v['gid'], 'goods_name'=>$v['goods_name'], 'pic'=>$v['pic'], 'price'=>$v['price'], 
                    'num'=>$v['num'], 'bait'=>$v['bait'], 'point'=>$v['point'], 'promotion'=>$v['promotion'], 'spec'=>$v['spec']
                ];

            }
            // return dump($order);
            $this->assign('order', $order);
        }else{
            $this->assign('order', []);
        }
        

        $config = mallConfig();
        $this->assign('config', ['page_title'=>'我的订单', 'template'=>$config['mall_template']['value'] ]);
        return $this->fetch();
    }

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
        // return dump($cart);
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
                        $cart[$k]['price'] = $v['price']*$promotion[$v['promotion_id']]['percent']/100;//单价
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
        $money_first = input('money_first', '', 'htmlspecialchars,trim');



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
        #获取商品信息 (跟预览方法里的一样，应该封装方法)
        $field = ['a.buyer_id', 'a.goods_id as gid', 'a.price', 'a.num', 
            'b.point', 'b.bait', 'b.promotion as promotion_id', 'b.service', 'b.catid_list', 'b.name', 'c.spec', 'd.pic'];
        $goods = Db::name('cart') ->alias('a') 
             -> join('goods b', 'a.goods_id=b.id', 'LEFT') 
             -> join('goods_spec c', 'a.spec=c.id', 'LEFT')  
             -> join('goods_picture d', 'a.goods_id=d.gid', 'LEFT') 
             -> field($field)    
             -> group('b.id, a.spec') 
             -> order('a.addtime desc') 
             -> where('a.id in ('.$id_list.')') 
             -> select(); 
        if(!empty($goods)){
            #生成订单ID
            $order_id = getOrderID();
            $count = ['baits'=>0, 'points'=>0, 'money'=>0];
            # 查询促销
            $promotion = Db::name('mall_promotion') 
                -> where('status=1 and begin_time<='.time().' and end_time>='.time()) -> select();
            $promotion = getField($promotion, 'id');
            foreach($goods as $k=>$v){
                $goods[$k]['order_id'] = $order_id;
                if($v['promotion_id'] != 0){
                    $goods[$k]['promotion'] = $promotion[$v['promotion_id']]['title'];
                    if($promotion[$v['promotion_id']]['type'] == 1){ 
                        $goods[$k]['price'] = $v['price']*$promotion[$v['promotion_id']]['percent']/100; 
                    }
                    #还需要添加更多东西促销活动
                }else{
                    $goods[$k]['promotion'] = '';
                }
                #计算订单总额们
                $count['baits'] += floatval($goods[$k]['bait']*$goods[$k]['num']);  
                $count['points'] += floatval($goods[$k]['point']*$goods[$k]['num']);
                $count['money'] += floatval($goods[$k]['price']*$goods[$k]['num']); 
            }
            // return dump($goods);
            $data = [];
            #如果余额优先
            if($money_first === 'on'){
                $user = Db::name('users') -> where(['id'=>session(config('USER_ID'))]) -> find();
                if($user['money']>=$count['money']){ //余额足够
                    
                }else{//余额不够的时候

                }
                return dump($user);
            }

            $data = ['userid'=>session(config('USER_iD')), 'order_id'=>$order_id, 
                'status'=>0, 'pay_status'=>0, 'money'=>$count['money'], 'baits'=>$count['baits'], 'points'=>$count['points'],
                'payment_id'=>$pay, 'payment_name'=>$payment[$pay]['name'],
                'shipping_id'=>$delivery[$ship]['id'], 'shipping_name'=>$delivery[$ship]['title'],
                'user_name'=>$address['name'], 'user_address'=>$address['province'].$address['city'].$address['area'].$address['address'],
                'user_mobile'=>$address['mobile']  ];

            #生成细表记录
            $detail = Db::name('order_detail') -> insertAll($goods);

            if($detail){
                $create = Db::name('order') -> insert($data);
                if($create){ //订单生成成功 
                    # 删除购物车信息
                    $cartObj = new Cart();
                    $cartObj->delete($id_list, 'order'); //注意这个方法有两个参数的时候

                    return $this->redirect('index');
                    // return '订单生成成功'; exit;
                }
            }else{
                return '订单生成失败'; exit;
            }

            
        }else{
            return '订单生成失败'; exit;
        }

    }

    // public function

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
        $region = getRegion();
        $address = Db::name('user_address') -> where(['userid'=>session(config('USER_ID'))]) ->order('type desc') -> select();
        foreach($address as $k=>$v){
            $address[$k]['province'] = $region[$v['province']]['name'];
            $address[$k]['city'] = $region[$v['city']]['name'];
            $address[$k]['area'] = $region[$v['area']]['name'];

        }
        
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
