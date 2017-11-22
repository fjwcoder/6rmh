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
        file_put_contents('orderresult.txt', $postStr);
        try{
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
                    $check = $this->orderCheck($order_id, 'order', $wxconf);

                    Db::name('payresult_step') -> insert(['order_id'=>$order_id, 'content'=>json_encode($check), 'step'=>2]); 

                    if($check['status']){ //订单查询成功
                        $success = new Paysuccess();
                        #调用相关的方法 
                        $result = $success->order($resArr, $check['order']);
                        echo 'success';
                    }

                }else{
                    Db::name('pay_log') -> insert(['type'=>'order', 'order_id'=>substr($resArr['out_trade_no'], 1), 'content'=>$postStr, 'status'=>2]); 
                }

            }
        }catch(Exception $e){
            Db::name('payresult_exception') -> insert(['type'=>'order', 'order'=>$postStr,'exception'=>$e->getMessage()]); 
        }
        
    }

    public function chargeResult(){
        $postStr = file_get_contents('php://input');
        file_put_contents('chargeResult.txt', $postStr);

        try{
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
                    $check = $this->orderCheck($order_id, 'charge', $wxconf);

                    Db::name('payresult_step') -> insert(['order_id'=>$order_id, 'content'=>json_encode($check), 'step'=>2]); 

                    if($check['status']){ //订单查询成功
                        $success = new Paysuccess();
                        #调用相关的方法 
                        $result = $success->charge($resArr, $check['order']);
                        echo 'success';
                    }

                }else{
                    Db::name('pay_log') -> insert(['type'=>'charge', 'order_id'=>substr($resArr['out_trade_no'], 1), 'content'=>$postStr, 'status'=>2]); 
                }

            }
        }catch(Exception $e){
            Db::name('payresult_exception') -> insert(['type'=>'charge', 'order'=>$postStr,'exception'=>$e->getMessage()]); 
        }
    }

    public function tradeResult(){
        $postStr = file_get_contents('php://input');
        file_put_contents('tradeResult.txt', $postStr);
        try{
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
                    $check = $this->orderCheck($order_id, 'trade', $wxconf);

                    Db::name('payresult_step') -> insert(['order_id'=>$order_id, 'content'=>json_encode($check), 'step'=>2]); 

                    if($check['status']){ //订单查询成功
                        $success = new Paysuccess();
                        #调用相关的方法 
                        $result = $success->trade($resArr, $check['order']);
                        echo 'success';
                    }

                }else{
                    Db::name('pay_log') -> insert(['type'=>'trade', 'order_id'=>substr($resArr['out_trade_no'], 1), 'content'=>$postStr, 'status'=>2]); 
                }

            }
        }catch(Exception $e){
            Db::name('payresult_exception') -> insert(['type'=>'trade', 'order'=>$postStr,'exception'=>$e->getMessage()]); 
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
                $order = Db::name('recharge') -> where(['order_id'=>$id, 'status'=>1]) -> find();
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
                $order = Db::name('inner_order') -> where(['order_id'=>$id, 'status'=>1]) -> find();

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
