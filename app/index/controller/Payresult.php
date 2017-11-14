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
        
        $postStr = file_get_contents('php://input');

        $postStr = '<xml><appid><![CDATA[wx6daa65cc6fc26c29]]></appid>
<attach><![CDATA[六耳猕猴购物订单支付]]></attach>
<bank_type><![CDATA[CFT]]></bank_type>
<cash_fee><![CDATA[1]]></cash_fee>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[Y]]></is_subscribe>
<mch_id><![CDATA[1446652202]]></mch_id>
<nonce_str><![CDATA[6qvaeye7s9x2bb25rivjmyq46o3yd3q3]]></nonce_str>
<openid><![CDATA[onHIb0kOB02RVCLcrQolopmNLdHM]]></openid>
<out_trade_no><![CDATA[JGB13563097602713]]></out_trade_no>
<result_code><![CDATA[SUCCESS]]></result_code>
<return_code><![CDATA[SUCCESS]]></return_code>
<sign><![CDATA[CFF52FC4CFD2A091A5AD9E0B88DA4893]]></sign>
<time_end><![CDATA[20171113145842]]></time_end>
<total_fee>1</total_fee>
<trade_type><![CDATA[JSAPI]]></trade_type>
<transaction_id><![CDATA[4200000007201711134426739940]]></transaction_id>
</xml>';
        if (!empty($postStr) ){
			$resObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $resJson = json_encode($resObj);
            $resArr = json_decode($resJson, true);
            
            // $resArr = ['mch_id'=>'1446652202', 'out_trade_no'=>'JGB13545290023884', 'return_code'=>'SUCCESS', 'result_code'=>'SUCCESS'];



            // dump($resArr);
            if( ($resArr['return_code'] == 'SUCCESS') && ($resArr['result_code'] == 'SUCCESS') ) { // 支付成功
                
                $wxconf = getWxConf();
                if($resArr['mch_id'] != $wxconf['MCHID']['value']){
                    return '商家账号错误'; // 插入一条记录，支付失败原因“商家账号错误”；
                }
                $order_id = substr($resArr['out_trade_no'], 1); //订单号
        
                $pay_type = session('PAY_TYPE'); //支付订单类型
                // echo $pay_type; die;
                $wxpay = new Wxpay();

                $check = $wxpay->orderCheck($order_id, $pay_type, $wxconf);

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
