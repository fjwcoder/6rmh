<?php
namespace app\index\controller;
vendor('wxpay.WxPay#JsApiPay');
use app\admin\controller\Wechat as Wechat;
use app\extend\controller\Mall as Mall;
use app\index\controller\Wxpay as Wxpay;
use app\index\controller\Paysuccess as Paysuccess;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Payresult extends Controller
{

    public function orderResult(){
        $postStr = file_get_contents('php://input');
        file_put_contents('orderresylt.txt', $poststr);
        Db::name('payresult_step') -> insert(['order_id'=>$order_id, 'content'=>$postStr, 'step'=>1]); 
        if (!empty($postStr) ){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);//把XML载入对象中
            $resJson = json_encode($postObj);
            $resArr = json_decode($resJson, true);
            if( ($resArr['return_code'] == 'SUCCESS') && ($resArr['result_code'] == 'SUCCESS') ) { // 支付成功
                $wxconf = getWxConf();
                if($resArr['mch_id'] != $wxconf['MCHID']['value']){
                    return '商家账号错误'; // 插入一条记录，支付失败原因“商家账号错误”；
                }
                $order_id = substr($resArr['out_trade_no'], 1); //订单号
                // $wxpay = new Wxpay();
                $check = $this->orderCheck($order_id, 'order', $wxconf);
                Db::name('payresult_step') -> insert(['order_id'=>$order_id, 'content'=>json_encode($check), 'step'=>2]); 
                if($check['status']){ //订单查询成功
                    $success = new Paysuccess();
                    #调用相关的方法 $check['type']可以是'order'， 'charge'， 'trade'
                    $result = $success->order($resArr, $check['order']);
                    
                    echo 'success';
                }else{
                    // return $this->error('支付成功，订单错误');
                }

            }

        }
    }

    public function chargeResult(){
        $postStr = file_get_contents('php://input');
        if (!empty($postStr) ){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);//把XML载入对象中
            $resJson = json_encode($postObj);
            $resArr = json_decode($resJson, true);
            if( ($resArr['return_code'] == 'SUCCESS') && ($resArr['result_code'] == 'SUCCESS') ) { // 支付成功
                $wxconf = getWxConf();
                if($resArr['mch_id'] != $wxconf['MCHID']['value']){
                    return '商家账号错误'; // 插入一条记录，支付失败原因“商家账号错误”；
                }
                $order_id = substr($resArr['out_trade_no'], 1); //订单号
                $wxpay = new Wxpay();
                $check = $wxpay->orderCheck($order_id, 'order', $wxconf);
                if($check['status']){ //订单查询成功
                    $success = new Paysuccess();
                    #调用相关的方法 $check['type']可以是'order'， 'charge'， 'trade'
                    $result = $success->charge($resArr, $check['order']);

                    echo 'success';
                }else{
                    // return $this->error('支付成功，订单错误');
                }

            }

        }
    }

    public function tradeResult(){
        $postStr = file_get_contents('php://input');
        if (!empty($postStr) ){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);//把XML载入对象中
            $resJson = json_encode($postObj);
            $resArr = json_decode($resJson, true);
            if( ($resArr['return_code'] == 'SUCCESS') && ($resArr['result_code'] == 'SUCCESS') ) { // 支付成功
                $wxconf = getWxConf();
                if($resArr['mch_id'] != $wxconf['MCHID']['value']){
                    return '商家账号错误'; // 插入一条记录，支付失败原因“商家账号错误”；
                }
                $order_id = substr($resArr['out_trade_no'], 1); //订单号
                $wxpay = new Wxpay();
                $check = $wxpay->orderCheck($order_id, 'order', $wxconf);
                if($check['status']){ //订单查询成功
                    $success = new Paysuccess();
                    #调用相关的方法 $check['type']可以是'order'， 'charge'， 'trade'
                    $result = $success->trade($resArr, $check['order']);

                    echo 'success';
                }else{
                    // return $this->error('支付成功，订单错误');
                }

            }

        }
    }


    # 支付回调地址
    public function wxPayResult(){
        $postStr = file_get_contents('php://input');
        file_put_contents('wxpayresult.txt', $postStr); die;
        if (!empty($postStr) ){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);//把XML载入对象中
            $resJson = json_encode($postObj);
            $resArr = json_decode($resJson, true);

            Db::name('payresult_step') -> insert(['order_id'=>'123', 'content'=>$resJson, 'step'=>1]); 

            if( ($resArr['return_code'] == 'SUCCESS') && ($resArr['result_code'] == 'SUCCESS') ) { // 支付成功

                // dump($resArr);
                $wxconf = getWxConf();
                if($resArr['mch_id'] != $wxconf['MCHID']['value']){
                    return '商家账号错误'; // 插入一条记录，支付失败原因“商家账号错误”；
                }
                $order_id = substr($resArr['out_trade_no'], 1); //订单号
        
                $order_type = Session::get(config('ORDER_TYPE')); //支付订单类型

                Db::name('payresult_step') -> insert(['order_id'=>$order_id, 'content'=>$order_type, 'step'=>2]);
                $check = $this->orderCheck($order_id, $order_type, $wxconf);
                
                Db::name('payresult_step') -> insert(['order_id'=>$order_id, 'content'=>json_encode($check), 'step'=>4]);
                // return dump($check);

                if($check['status']){ //订单查询成功
                    $success = new Paysuccess();
                    #调用相关的方法 $check['type']可以是'order'， 'charge'， 'trade'
                    $result = $success->$check['type']($resArr, $check['order']);

                    // if($result){ //成功
                    //     echo '支付成功'; die;
                    //     // return $this->success('支付成功', $check['type'].'/index');
                    // }else{
                    //     echo '支付失败'; die;
                    //     // return $this->error('支付失败');
                    // }
                    echo 'success';
                }else{
                    // return $this->error('支付成功，订单错误');
                }
            }
        }else{
            Db::name('payresult_step') -> insert(['order_id'=>'123', 'content'=>'$poststr 为空？？？', 'step'=>9]);
        }
    }

    #查询订单信息并进行验证
    # $id 订单号
    public function orderCheck($id=0, $type='order', $wxconf= []){
        
        if($id===0){
            return ['status'=>false, 'content'=>'订单错误']; exit;
        }
        if(empty($wxconf)){
            $wxconf = getWxConf();
        }
        
        switch($type){
            case 'order':
                
                $order = Db::name('order') -> where(['order_id'=>strval($id), 'status'=>1, 'pay_status'=>0]) -> find();
                
                if(isset($order)){
                    if($order['money']<=0){
                        return ['status'=>false, 'content'=>'金额为0，不需支付' ]; exit;
                    }
                    return ['status'=>true, 'order'=>$order, 'type'=>$type];
                }else{
                    return ['status'=>false, 'content'=>'购物订单不存在'];
                }
            break;
            case 'charge':
                $order = Db::name('charge') -> where(['order_id'=>$id, 'status'=>1]) -> find();
                if(isset($order)){
                    if($order['money'] <= 0){
                        return ['status'=>false, 'content'=>'金额错误' ]; exit;
                    }
                    return ['status'=>true, 'order'=>$order, 'type'=>$type];
                }else{
                    return ['status'=>false, 'content'=>'充值订单不存在'];
                }
            break;
            case 'trade':
                $order = Db::name('inner_shop') -> where(['order_id'=>$id, 'status'=>1]) -> find();
                // 余额支付多少还需支付多少（假数据测试）
                // $order["yuepay"] = 3;
                // $order["haixupay"] = 6;
                
                if(isset($order)){
                    if($order['money'] <= 0){
                        return ['status'=>false, 'content'=>'金额错误' ]; exit;
                    }
                    return ['status'=>true, 'order'=>$order, 'type'=>$type];
                }else{
                    return ['status'=>false, 'content'=>'购买订单不存在'];
                }
            break;
            default:
                return ['status'=>false, 'content'=>'支付状态错误'];
            break;
        }
        
    }


    #扫码支付模式一 平台配置回调地址
    public function scanPay($postStr=''){

        $postStr = file_get_contents('php://input');
        if (!empty($postStr)){
            
            // file_put_contents('response.txt', $postStr);
			$resObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $resJson = json_encode($resObj);
            $resArr = json_decode($resJson, true);
            // file_put_contents('payresult.txt', $resJson);
            $wxpay = new Wxpay();
            $wxconf = getWxConf();

            $check = $wxpay->check($resArr['product_id'], $wxconf);
            if($check['status']){ //验证成功
                $user = Db::name('users') -> where(['id'=>$check['order']['userid'], 'status'=>1]) ->find();
                $user['subscribe'] = $resArr['is_subscribe'];
                // if($user['subscribe'] == 1){
                //     $user['subscribe'] = 'Y';
                // }else{
                //     $user['subscribe'] = 'N';
                // }
                
                $jsApiParameters = $wxpay->orderPay($check['order'], $user);

                file_put_contents('payresults.txt', $jsApiParameters);


            }
        }
        

    }



}
