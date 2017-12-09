<?php

namespace app\admin\controller;
use app\common\controller\Manage;
use app\common\controller\Common;
use app\extend\controller\Shipping as Shipping;
use app\extend\controller\Gaode as Gaode;
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

        $navid = input('navid', 60, 'intval');
        $nav = adminNav();
        $header =  ['title'=>'订单管理->用户订单->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $this->assign('keyword', $key?$key:'');


        $orders = $this->getOrders();
        $this->assign('order', $orders);

        return $this->fetch();
    }

    public function getOrders(){
        
        $order = Db::name('order')-> order('add_time desc')-> paginate();
        return $order;
    }

    public function detail(){
        $id = input('id', '', 'htmlspecialchars,trim');
        $navid = input('navid', 60, 'intval');
        if(!empty(request()->post())){
            return $this->detailPost(); exit;
        }

        $nav = adminNav();
        $header =  ['title'=>'订单管理->用户订单->订单详情', 'icon'=>$nav[$navid]['icon'], 
        'form'=>'detail', 'navid'=>$navid];
        $this->assign('header', $header);


        $order = Db::name('order') -> where(['order_id'=>$id]) -> find();
        $detail = Db::name('order_detail') -> where(['order_id'=>$id]) -> select();

        # 物流信息，最好是弄成异步
        if($order['shipping_no'] != ''){
            $result = $this-> getShipper($order, $order['shipping_no']);
            $this->assign('trace', array_reverse($result['trace']['Traces']));
        }

        $this->assign('order', $order);
        $this->assign('detail', $detail);

        
        return $this->fetch();
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
        
        if($ship_type['Shippers'][0]['ShipperCode'] == ''){ //没有快递类别
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

    #|======================================================
    #| 发货
    #| 1.更新shipping_name（快递名称）
    #| 
    #|
    #|
    #|======================================================
    public function detailPost(){
        $order_id = input('order_id', '', 'htmlspecialchars,trim');
        $shipping_no = input('shipping_no', '', 'htmlspecialchars,trim');
        
        # 检查是否已经发货
        $check = Db::name('order') -> where(['order_id'=>$order_id]) -> find();

        if(($check['status'] == 1) && ($check['pay_status']==0)){
            return $this->error('尚未支付'); exit;
        }
        if( ($check['status']>=3) || ($check['shipping_no'] != '') || ($check['ship_time'] > 0) ){
            return $this->error('已经发货'); exit;
        }

        if($check['shipping_name'] === "默认物流"){
            $ship_type = $this->getShipperType($shipping_no);
            
        }
        $shipping = Db::name('order') -> where(['order_id'=>$order_id]) -> update(['shipping_no'=>$shipping_no, 
            'ship_time'=>time(), 'shipping_code'=>$ship_type['Shippers'][0]['ShipperCode'], 
            'shipping_name'=>$ship_type['Shippers'][0]['ShipperName']]);
        if($shipping){
            return $this->success('发货成功', 'Order/index');
        }else{
            return $this->error('发货失败');
        }
    }

    

}