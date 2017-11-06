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
    # 支付回调地址
    public function wxPayResult(){
        return session('PAY_TYPE');
        $postStr = file_get_contents('php://input');

        file_put_contents('step0.txt', $postStr);

        if (!empty($postStr) || (1==1)){
			$resObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $resJson = json_encode($resObj);
            $resArr = json_decode($resJson, true);

            $resArr=['mch_id'=>'1446652202', 'return_code'=>'SUCCESS', 'result_code'=>'SUCCESS', 'out_trade_no'=>'JGB06367414700025'];
            
            if( ($resArr['return_code'] == 'SUCCESS') && ($resArr['result_code'] == 'SUCCESS') ) { // 支付成功
                $wxconf = getWxConf();
                if($resArr['mch_id'] != $wxconf['MCHID']['value']){
                    return '商家账号错误';
                }
                $order_id = substr($resArr['out_trade_no'], 1); //订单号
                file_put_contents('step1.txt', $order_id);
                
                $pay_type = session('PAY_TYPE');
                // return $pay_type;
file_put_contents('step2.txt', $pay_type);
                $wxpay = new Wxpay();

                $check = $wxpay->orderCheck($order_id, $pay_type, $wxconf);
                file_put_contents('step3.txt', var_export($check));

                if($check['status']){ //订单查询成功
                    $success = new Paysuccess();
                    
                    #调用相关的方法 $check['type']可以是'order'， 'charge'， 'trade'
                    $result = $success->$check['type']($resArr, $check['order']);
                    if($result){ //成功
                        return $this->success('支付成功', $check['type'].'/index');
                    }else{
                        return $this->error('支付失败');
                    }
                }else{
                    return $this->error('支付成功，订单错误');
                }
            }
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
