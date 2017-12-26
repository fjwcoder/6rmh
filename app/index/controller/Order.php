<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use app\index\controller\Address as Address; 
use app\index\controller\Cart as Cart; 
use app\extend\controller\Shipping as Shipping;
use app\index\controller\Active as Active;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Order extends Common
{
    public static function orderStatus($status){
        $array = ['全部订单', '待支付订单', '待发货订单', '待收货订单', '待评价订单', '已完成订单'];
        return $array[$status];
    }

    #订单列表页
    public function index(){
        $status = input('status', 0, 'intval');
        // echo $status;
        
        $uid = session(config('USER_ID'));
        $where = 'userid='.$uid;
        if($status !== 0 ){
            $where .= ' and status='.$status;
        }
        $where .= ' and active=0 or pay_status=1';
        
        $order = $this->getOrder($where, 0, 16); //获取订单信息

        $this->assign('order', $order);
        $this->assign('status', $status);
        $page_title = $this->orderStatus($status);
        
        $config = mallConfig();

        $this->assign('config', ['page_title'=>$page_title, 'template'=>$config['mall_template']['value'] ]);
        return $this->fetch();
    }

    

    public function getOrder($where , $form=0, $to=4){
        $order = [];
        $data = Db::name('order') -> where($where) 
        -> field(['order_id', 'userid', 'status', 'pay_status', 'balance', 'money', 'baits', 'points', 'payment_id', 'payment_name', 
            'shipping_id', 'shipping_name', 'add_time as addtime', 'user_name', 'user_address', 'user_mobile']) 
        -> order('add_time desc') -> limit($form, $to) -> select();
        if(!empty($data)){
            $id_list = " ('' ";

            foreach($data as $k=>$v){
                // return dump($v);
                $order[$v['order_id']]['islong'] = $this->isTooLong($v);
                $order[$v['order_id']]['order'] = $v;
                $order[$v['order_id']]['detail'] = [];
                $id_list .= ", '$v[order_id]' ";
                
            }
            $id_list .=")";
            $data = Db::name('order_detail')-> where("order_id in $id_list") 
                -> field(['order_id', 'gid', 'catid_list', 'name as goods_name', 'pic', 'price', 
                    'num', 'bait', 'point', 'promotion_id', 'promotion', 'service', 'spec']) 
                -> select();

            foreach($data as $k => $v){
                $order[$v['order_id']]['detail'][] = $v;
            }

            return $order;
        }else{
            return [];
        }
        
    }

    public function dealOrder($list, $order_id = ''){
        $count = ['baits'=>0, 'points'=>0, 'prices'=>0, 'cost_prices'=>0];
        $promotion = Db::name('mall_promotion') 
            -> where('status=1 and begin_time<='.time().' and end_time>='.time()) -> select();
        $promotion = getField($promotion, 'id');
        // return dump($list);
        foreach($list as $k=>$v){
            if($v['promotion_id'] != 0){
                $list[$k]['promotion'] = $promotion[$v['promotion_id']]['title'];
                if($promotion[$v['promotion_id']]['type'] == 1){
                    $list[$k]['price'] = $v['price']*$promotion[$v['promotion_id']]['percent']/100;//单价
                }
            }else{
                $list[$k]['promotion'] = '';
            }
            $list[$k]['order_id'] = $order_id;
            #计算订单总额们
            $count['baits'] += intval($list[$k]['bait']*$list[$k]['num']);  
            $count['points'] += intval($list[$k]['point']*$list[$k]['num']);
            $count['prices'] += floatval($list[$k]['price']*$list[$k]['num']); 
            $count['cost_prices'] += floatval($list[$k]['cost_price']*$list[$k]['num']); 
        }

        return ['list'=>$list, 'count'=>$count];
    }


    #生成订单预览
    public function preview($cart_list=''){

        $cart_list = empty($cart_list)?input('id_list', '', 'htmlspecialchars,trim'):$cart_list; //$cart_list：购物车ID列表
        if($cart_list == ''){
            return msg('-1', '请选择商品'); exit;
        }
        
        $addrID = input('addrid', 0, 'intval'); 
        $user = Db::name('users') -> where(['id'=>session(config('USER_ID')), 'status'=>1]) -> find();
        $mallObj = new Mall();
        // 查出订单预览信息，包括
        // 查出购物车信息，包括
        // 商品具体信息、规格、图片、促销活动、买家信息
        $field = ['a.id as cart_id', 'a.goods_id', 'a.buyer_id', 'a.num', 'a.spec as spec_id', 'a.price', 'a.active', 'b.promotion as promotion_id', 
            'c.spec', 'b.name', 'b.sub_name', 'b.description', 'b.key_words', 
            'b.cost_price', // 17.12.26 by fjw 增加查询成本价
            'b.brand', 'b.bait', 'b.point','d.pic'];
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
        $no_active = true; // 不是活动商品
        if(!empty($cart)){
            if(count($cart) == 1){ // 判断是否活动商品
                if($user['isactive'] == 1){ // 查询活动
                    
                    $activeObj = new Active();
                    $g_active = $activeObj->isActive($cart[0]['goods_id']);
                    
                    if($g_active['status']){ // 存在活动


                        $no_active = false;
                        # 查看是否存在活动商品
                        $cart[0]['promotion'] = 0;
                        if(count($cart) > 1){
                            return msg('-1', '一元抢购，限量一件'); exit;
                        }

                        if($cart[0]['num'] > 1){
                            return msg('-1', '一元抢购，限量一件'); exit;
                        }

                        $cart[0]['bait'] = $g_active['goods']['bait'];
                        $cart[0]['point'] = $g_active['goods']['point'];

                        $count['baits'] = intval($cart[0]['bait']*$cart[0]['num']);  
                        $count['points'] = intval($cart[0]['point']*$cart[0]['num']);
                        $count['prices'] = floatval($cart[0]['price']*$cart[0]['num']); 
                        $count['cost_prices'] = 0; // 一元抢购不参与生成红包
                    }
                    // else{
                    //     $no_active = false;
                    //     return msg('-1', '该产品抢购已结束'); exit;
                    // }
                }
            }

            if($no_active){
                $deal_order = $this->dealOrder($cart);
                $cart = $deal_order['list'];
                $count = $deal_order['count'];
            }

// return dump($deal_order);

            # 收货地址
            if($addrID === 0){
                $curr_address = $this->getAddress();
            }else{
                $curr_address = $this->choseAddr($addrID);
            }
            $this->assign('addcount', count($curr_address));
            $this->assign('address', $curr_address);
            # 省份
            $addrObj = new Address();
            $this->assign('province', $addrObj->getProvince());
            # 支付方式
            $this->assign('pay_way', $this->getPayWay());
            # 配送方式
            $this->assign('delivery', $this->getDelivery());
        }else{
            return msg('-1', '商品不存在或订单错误');
        }
        
        // return dump($cart);
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
            'b.point', 'b.bait', 'b.promotion as promotion_id', 'b.service', 
            'b.cost_price', // 17.12.26 by fjw 增加查询成本价
            'b.catid_list', 'b.name', 'c.spec', 'd.pic'];
        $goods = Db::name('cart') ->alias('a') 
             -> join('goods b', 'a.goods_id=b.id', 'LEFT') 
             -> join('goods_spec c', 'a.spec=c.id', 'LEFT')  
             -> join('goods_picture d', 'a.goods_id=d.gid', 'LEFT') 
             -> field($field)    
             -> group('b.id, a.spec') 
             -> order('a.addtime desc') 
             -> where('a.id in ('.$id_list.')') 
             -> select(); 
        // return dump($goods);
        if(!empty($goods)){
            #生成订单ID
            $order_id = getOrderID();
            $user = Db::name('users') -> where(['id'=>session(config('USER_ID'))]) -> find();
            $no_active = true; // 不是活动商品
            $active_id = 0;
            if(count($goods) == 1){ // 
                if($user['isactive'] == 1){ // 查询活动
                    $activeObj = new Active();
                    $g_active = $activeObj->isActive($goods[0]['gid']);
                    if($g_active['status']){ // 存在活动
                        $no_active = false;
                        $active_id = $g_active['goods']['id'];
                        $goods[0]['promotion'] = '';
                        if(count($goods) > 1){
                            return msg('-1', '一元抢购，限量一种商品'); exit;
                        }
                        if($goods[0]['num'] > 1){
                            return msg('-1', '一元抢购，单品限量一件'); exit;
                        }
                        
                        $goods[0]['order_id'] = $order_id;
                        $goods[0]['bait'] = $g_active['goods']['bait'];
                        $goods[0]['point'] = $g_active['goods']['point'];
                        $count['baits'] = intval($goods[0]['bait']*$goods[0]['num']);  
                        $count['points'] = intval($goods[0]['point']*$goods[0]['num']);
                        $count['prices'] = floatval($goods[0]['price']*$goods[0]['num']); 
                        $count['cost_prices'] = 0;  // 活动商品不参与红包生成
                    }
                    // else{
                    //     $no_active = false;
                    //     return msg('-1', '该产品抢购已结束'); exit;
                    // } 
                }
            }

            if($no_active){
                $deal_order = $this->dealOrder($goods, $order_id);
                $goods = $deal_order['list'];
                $count = $deal_order['count'];
            }  

// return dump($deal_order);

            $data = [];
            #如果余额优先
            
            // if($money_first === 'on'){
            //     $user = Db::name('users') -> where(['id'=>session(config('USER_ID'))]) -> find();
            //     if($user['money']>=$count['money']){ //余额足够
                    
            //     }else{//余额不够的时候

            //     }
             
            // }
            # 处理收货地址
            $region = getRegion();
            $real_addr = '';
            if($address['province'] != 0){
                $real_addr .= $region[$address['province']]['name'];
            }
            if($address['city'] != 0){
                $real_addr .= $region[$address['city']]['name'];
            }
            if($address['area'] != 0){
                $real_addr .= $region[$address['area']]['name'];
            }
            
            $data = ['userid'=>session(config('USER_iD')), 'order_id'=>$order_id, 
                'status'=>1, 'pay_status'=>0, 'money'=>$count['prices'], 'baits'=>$count['baits'], 'points'=>$count['points'],
                'payment_id'=>$pay, 'payment_name'=>$payment[$pay]['name'], 
                'active'=>$active_id, // 17.12.25 by fjw 增加订单是否活动价格
                'cost_price'=>floatval($count['cost_prices']), // 17.12.26 by fjw 增加订单中商品成本价格
                'shipping_id'=>$delivery[$ship]['id'], 'shipping_name'=>$delivery[$ship]['title'],
                'user_name'=>$address['name'], 
                'user_address'=>$real_addr.$address['address'],
                'user_mobile'=>$address['mobile']  ];
            // return dump($data);
            #生成细表记录
            $detail = Db::name('order_detail') -> insertAll($goods);

            if($detail){
                $create = Db::name('order') -> insert($data);
                if($create){ //订单生成成功 
                    # 删除购物车信息
                    $cartObj = new Cart();
                    $cartObj->delete($id_list, 'order'); //注意这个方法有两个参数的时候
                    if($active_id > 0){
                        return $this->redirect('/index/wxpay/index?type=order&id='.$order_id);
                    }else{
                        return $this->redirect('index');
                    }
                    
                }
            }else{
                return $this->error('订单生成失败'); exit;
            }

            
        }else{
            return $this->error('订单生成失败'); exit;
        }

    }

    // 订单 或 订单详情页 查看是否超出售后时间
    public function isTooLong($order){
        $config = mallConfig();
        $addtime = strtotime(isset($order['addtime'])?$order['addtime']:$order['add_time']);
        $endtime = $addtime + $config['AFTER_GOODS_TIME']['value']*3600*24;
        if( $endtime < time() ){

            return true;
        }else{

            return false;
        }
    }


    # 订单详情页
    public function orderDetail(){
        $order_id = input('id', '', 'htmlspecialchars,trim');
        $user = decodeCookie('user');
        $order = db('order', [], false) ->where(array('order_id'=>$order_id)) ->find();
        
        $this->assign('islong', $this->isTooLong($order));
        #商品详情
        $orderdetail = Db::name('order_detail') ->alias('a') 
            -> join('goods b', 'a.gid=b.id', 'LEFT') 
            -> join('goods_after c', 'a.order_id=c.order_id and a.gid=c.gid', 'LEFT')
            -> field('a.*,b.description,b.sub_name, c.opreason')    
            -> where(array('a.order_id'=>$order_id)) 
            -> select(); 

        $today = strtotime(date('Y-m-d', time()));
        if( ($order['trace_time'] == 0) || ($order['trace_time'] < $today) ){ //为空或者昨天查询的
            $result = $this->getShipper($order, $order['shipping_no']);
            if($result['status']){
                $traces['shipping_trace'] = json_encode(array_reverse($result['trace']['Traces']));
                $traces['trace_time'] = time();
                Db::name('order') -> where(['order_id'=>$order_id]) -> update($traces);
                $this->assign('trace', array_reverse($result['trace']['Traces']));
            }else{
                $this->assign('trace', json_decode($order['shipping_trace'], true));
            }

        }else{
            $this->assign('trace', json_decode($order['shipping_trace'], true));
        }

        $config = mallConfig();
        $this->assign('order', $order);
        $this->assign('orderdetail', $orderdetail);
        $this->assign('config', ['page_title'=>'订单详情', 'template'=>$config['mall_template']['value'] ]);
        return $this->fetch('detail');
    }

    #|======================================================
    #| 获取物流信息
    #| 1.更新shipping_name（快递名称）
    #| 
    #|
    #|
    #|======================================================
    public function getShipper($order, $shipping_no=''){
        $shipObj = new Shipping();
        if($order['shipping_name'] === "默认物流"){
            $ship_type = $this->getShipperType($shipping_no);
        }else{
            $ship_type['Shippers'][0]['ShipperCode'] = $order['shipping_code'];
            $ship_type['Shippers'][0]['ShipperName'] = $order['shipping_name'];
        }
        // return dump($ship_type);
        if(empty($ship_type['Shippers'])){ //没有快递类别
            return ['status'=>false];
        }else{
            $trace = $shipObj->index($order['order_id'], $ship_type['Shippers'][0]['ShipperCode'], $shipping_no);
        }
        
        return ['status'=>true, 'trace'=>$trace];
    }

    // 识别单号
    public function getShipperType($shipping_no){
        $shipObj = new Shipping();
        $ship_type = $shipObj -> getOrderLogisticByJson($shipping_no); //返回string 类型
        if(empty($ship_type)){ //识别失败，返回空
            $ship_type['Shippers'][0]['ShipperCode'] = '';
            $ship_type['Shippers'][0]['ShipperName'] = '默认物流';
        }else{
            $ship_type = json_decode($ship_type, true);
        }

        return $ship_type;
    }


    #退货
    public function returnGoods(){
        $order_id = input('id', '', 'htmlspecialchars,trim');
        $gid = input('gid', 0, 'intval');
        // 商品信息
        $orderinfo = db('order', [], false) -> where(array('order_id'=>$order_id)) ->find();
        if(!isset($orderinfo)){
            return msg('-1', '未找到订单信息'); exit;
        }
        if($orderinfo['status'] < 3){
            return msg('-1', '尚未确认收货'); exit;
        }

        $islong = $this->isTooLong($orderinfo);
        if($islong){
            return $this->redirect('/index/order/index'); exit;
        }
        if($gid == 0){
            $comment = db('order_detail', [], false) -> where(array('order_id'=>$order_id)) ->select();
        }else{
            $spec = input('spec', '', 'htmlspecialchars,trim');
            $comment = db('order_detail', [], false) -> where(array('order_id'=>$order_id, 'gid'=>$gid, 'spec'=>$spec)) ->select();
        }
        $this->assign('orderinfo', $orderinfo);
        $this->assign('comment', $comment);

        $config = mallConfig();
        $this->assign('config', ['page_title'=>'售后服务', 'template'=>$config['mall_template']['value'] ]);
        return $this->fetch();
    }

    #退货原因
    public function returnreason(){
        $order_id = input('order_id', '', 'htmlspecialchars,trim');
        $reason = input('comment', '', 'htmlspecialchars,trim');
        $gid = input('gid', 0, 'intval');
        $num = input('num', 0, 'intval');
        $number = input('number', 0, 'intval');
        $type = input('type', 0, 'intval');//1退货 2 换货
        if($number > $num){
            return msg('-1', '输入数量超过购买数量');
        }
        $data = Db::name('order_detail') ->field('order_id,gid,name,addtime,pic,price,spec')->where(array('order_id'=>$order_id,'gid'=>$gid))->find();
        $islong = $this->isTooLong($data);
        if($islong){
            return msg('-1', '订单超出售后服务时间'); exit;
        }
        $data['addtime'] = time();
        $data['type'] = $type;
        $data['num'] = $number;
        $lis['type'] = 2;
        $lis['number'] = $number;
        if($reason != ''){
            $data['reason'] = $reason;
            $res = Db::name('goods_after') ->insert($data);
        }      
        if($res){
            $result = db('order_detail', [], false) -> where(array('order_id'=>$order_id,'gid'=>$gid)) ->update($lis);            
            return $this->success('退货申请提交成功', 'Order/index');
        }else{
            return msg('-1', '提交申请失败');
        }
        
    }

    # 预览页添加地址
    public function addAddr(){
        $userid = session(config('USER_ID'));
        $id_list = input('id_list', 0, 'intval');
        $data['name'] = input('name','','htmlspecialchars,trim');
        $data["province"] = input('province',0,'intval');
        $data["city"] = input('city',0,'intval');
        $data["area"] = input('area',0,'intval');
        $data['address'] = input('address','','htmlspecialchars,trim');
        $data['mobile'] = input('mobile','','htmlspecialchars,trim');
        $data['zipcode'] = input('zipcode','','htmlspecialchars,trim');
        $data['userid'] = session(config('USER_ID'));

        $count = Db::name('user_address') -> where(['userid'=>$userid])-> count();
        if($count >=10){
            return msg('-1', '地址数量已达上限');
        }else{
            $a = Db::name('user_address') ->insert($data);

            if($a){  
                return $this->redirect('preview', ['id_list'=>$id_list]);  
            }else{
                return msg('-1', '添加失败');
            }
        }  
    }
    

    # 预览页设置默认地址
    public function defAddr(){
        $cart_list = input('id_list', '', 'htmlspecialchars,trim');
        $id = input('id', 0, 'intval');
        $addr = new Address();
        if($addr->defAddr($id, true)){
            return $this->redirect('preview', ['id_list'=>$cart_list]);
        }else{
            return '修改失败';
        }

    }

    # 选择该地址
    public function choseAddr($addrID){
        $address = Db::name('user_address') -> where(['userid'=>session(config('USER_ID'))]) ->order('type desc') -> select();
        $temp_arr = [];
        $region = getRegion();
        foreach($address as $k=>$v){
            if($v['id'] == $addrID){ //找到选择的地址
                $temp_arr = $v; //当前地址
                $address[$k] = $address[0]; //默认地址
                $address[0] = $temp_arr;
            }
        }
        foreach($address as $k=>$v){
            $address[$k]['province'] = empty($region[$v['province']]['name'])?'':$region[$v['province']]['name'];
            $address[$k]['city'] = empty($region[$v['city']]['name'])?'':$region[$v['city']]['name'];
            $address[$k]['area'] = empty($region[$v['area']]['name'])?'':$region[$v['area']]['name'];
        }
        return $address;
    }

    #删除地址
    public function delAddr(){
        $cart_list = input('id_list', '', 'htmlspecialchars,trim');
        $id = input('id', 0, 'intval');
        $addr = new Address();
        if($addr->delAddr($id)){
            return $this->redirect('preview', ['id_list'=>$cart_list]);
        }else{
            return msg('-1', '删除失败');
        }
    }

    # 获取用户地址
    public function getAddress(){
        $region = getRegion();
        $address = Db::name('user_address') -> where(['userid'=>session(config('USER_ID'))]) ->order('type desc') -> select();
        foreach($address as $k=>$v){
            $address[$k]['province'] = empty($region[$v['province']]['name'])?'':$region[$v['province']]['name'];
            $address[$k]['city'] = empty($region[$v['city']]['name'])?'':$region[$v['city']]['name'];
            $address[$k]['area'] = empty($region[$v['area']]['name'])?'':$region[$v['area']]['name'];

        }
        // return dump($address);
        return $address;
    }

    #获取配送方式
    public function getDelivery(){
        if(cache('MALL_DELIVERY')){
            $delivery = cache('MALL_DELIVERY');
        }else{
            $delivery = Db::name('mall_delivery') -> where('status=1') -> select();
            $delivery = getField($delivery, 'id');
            cache('MALL_DELIVERY', $delivery);   //缓存注释
        }

        return $delivery;
    }

    #获取支付方式
    public function getPayWay(){
        $pay = [
            ['id'=>1, 'name'=>'微信支付'],
            // ['id'=>2, 'name'=>'支付宝支付'],
            // ['id'=>3, 'name'=>'银联支付'],
            // ['id'=>4, 'name'=>'货到付款']
        ];

        return getField($pay, 'id');
    }

    # 确认收货
    public function getGoods(){
        $order_id = input('id', '', 'htmlspecialchars,trim');
        $result = Db::name('order') -> where(['order_id'=>$order_id, 'status'=>3,'pay_status'=>1]) -> update(['status'=>4]);
        if($result){
            return $this->redirect('Order/index');
        }else{
            return msg('-1', '尚未支付');
        }

    }

    # 提醒发货
    public function remand(){
        $order_id = input('id', '', 'htmlspecialchars,trim');
        $check = Db::name('order') -> where(['order_id'=>$order_id])-> find();
        $today = strtotime(date("Y-m-d"), time()); 
        if((empty($check['remand_time'])) || ($check['remand_time'] < $today) ){ //如果提醒发货的时间为空 或者 早于今天凌晨0点
            $remand = Db::name('order') -> where(['order_id'=>$order_id]) -> update(['remand_time'=>time()]); //更新时间
            if($remand){
                return msg('-1', '提醒发货成功');
            }
        }else{
            return msg('-1', '请等待发货');
        }

    } 

}
