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

    #扫码支付模式一 平台配置回调地址
    public function scanPay($postStr=''){

        $postStr = file_get_contents('php://input');
        if (!empty($postStr)){
            
            // file_put_contents('response.txt', $postStr);
			$resObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $resJson = json_encode($resObj);
            $resArr = json_decode($resJson, true);
            file_put_contents('payresult.txt', $resJson);
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


    # 支付回调地址
    public function wxPayResult(){
        $postStr = file_get_contents('php://input');
        if (!empty($postStr)){
            
            // file_put_contents('response.txt', $postStr);
			$resObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $resJson = json_encode($resObj);
            $resArr = json_decode($resJson, true);

            file_put_contents('payresult.txt', $resJson);

            if($resArr['result_code'] === 'SUCCESS'){ // 支付成功
                $order_id = $resArr['product_id'];
                $wxpay = new Wxpay();
                $wxconf = getWxConf();
                $check = $wxpay->check($order_id, $wxconf);
                if($check['status']){ //订单查询成功
                    $order = $check['order'];

                    # 上线的物品增加
                    
                    # 自己的物品更改
                    
                    # 修改订单状态
                }
            }else{

            }
                

        }
    }


}
