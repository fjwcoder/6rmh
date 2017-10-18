<?php
namespace app\index\controller;
vendor('wxpay.WxPay#JsApiPay');
// use app\common\controller\Common; 
use app\admin\controller\Wechat as Wechat;
use app\extend\controller\Mall as Mall;
use app\index\controller\Wxpay as Wxpay;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Payresult extends Controller
{
    public function test(){
        
        $str = '<xml>
                    <appid><![CDATA[wxc7db069fb239b27e]]></appid>
                    <openid><![CDATA[ooktzs1xmFTMcPQ8__S45Z49ZUgs]]></openid>
                    <mch_id><![CDATA[1265602401]]></mch_id>
                    <is_subscribe><![CDATA[Y]]></is_subscribe>
                    <nonce_str><![CDATA[ntAm8mJLSNtUgO7C]]></nonce_str>
                    <product_id><![CDATA[G919838545019229]]></product_id>
                    <sign><![CDATA[DFE186FD39172005B1CE49891F6B78F9]]></sign>
                </xml>';
        // die;
        $this->wxPay($str);
    }
    #扫码支付模式一 平台配置回调地址
    public function wxPay($postStr=''){

        $postStr = file_get_contents('php://input');
        if (!empty($postStr)){
            
            // file_put_contents('response.txt', $postStr);
			$resObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $resJson = json_encode($resObj);
            $resArr = json_decode($resJson, true);
            
            $wxconf = getWxConf();
            $check = $this->resCheck($wxconf, $resArr);
            if($check['status']){ //验证成功
                $user = Db::name('users') -> where(['id'=>$check['order']['userid'], 'status'=>1]) ->find();
                if($user['subscribe'] == 1){
                    $subscribe = 'Y';
                }else{
                    $subscribe = 'N';
                }
                
                #拼凑信息
                $money = floatval($check['order']['money'])*100;
                $tools = new \JsApiPay();
                $input = new \WxPayUnifiedOrder();
                $input -> SetAppid($wxconf['APPID']['value']);//公众账号ID
                $input -> SetMch_id($wxconf['MCHID']['value']);//商户号
                $input -> SetOpenid($user['openid']);
                $input -> SetBody('六耳猕猴订单支付');
                $input -> SetAttach('六耳猕猴订单支付');
                $input -> SetOut_trade_no(strval($check['order']['order_id']));
                $input -> SetProduct_id(strval($check['order']['order_id']));
                // $input -> SetPrepay_id(strval($check['order']['order_id']));
                $input -> SetIs_subscribe($subscribe);
                $input -> SetTotal_fee(strval($money));
                $input -> SetTime_start(date('YmdHis'));
                $input -> SetTime_expire(date('YmdHis', time() + 1000));
                $input -> SetNotify_url('http://www.6rmh.com/Index/Payresult/wxPayResult');
                $input -> SetTrade_type('NATIVE');

                $order = \WxPayApi::unifiedOrder($input); 

                $jsApiParameters = $tools->GetJsApiParameters($order);
                if( ($order['result_code'] === 'SUCCESS') && ($order['return_code'] === 'SUCCESS') ){
                    return $order;
                }
                // file_put_contents('fjw.txt', var_export($order, true));
                // $jsApiParameters = $tools->GetJsApiParameters($order);
                // var_dump($jsApiParameters);

            }
        }
        

    }

    #回调信息参数验证
    public function resCheck($wxconf, $resArr){
        
        if($resArr['appid'] !== $wxconf['APPID']['value']){
            return ['status'=>false];
        }
        if($resArr['mch_id'] !== $wxconf['MCHID']['value']){
           return ['status'=>false]; 
        }
        #查看订单
        $order = Db::name('order') -> where(['order_id'=>$resArr['product_id'], 'status'=>1, 'pay_status'=>0]) -> find();
        if(isset($order)){
            if($order['money']<=0){
                return ['status'=>false, 'content'=>'金额为0，不需支付' ]; exit;
            }
            return ['status'=>true, 'order'=>$order];
        }

    }


    # 支付回调地址
    public function wxPayResult(){
        $wxpay = new Wxpay();
        file_put_contents('result.txt', '走到这里');
    }


}
