<?php
namespace app\index\controller;
vendor('wxpay.WxPay#JsApiPay');
// vendor('phpqrcode.phpqrcode');
use app\common\controller\Common; 
use app\admin\controller\Wechat as Wechat;
use app\extend\controller\Mall as Mall;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Wxpay extends Common
{

    public function index(){
        
        $id = input('id', '', 'htmlspecialchars,trim');
        $uid = session(config('USER_ID'));
        $action = input('type', '', 'htmlspecialchars,trim');

        $check = $this->check($id);

        if($check['status']){
            #获取用户信息
            $user = decodecookie("user");
            if(empty($user)){
                $user = Db::name('users') -> where(['id'=>$uid, 'status'=>1]) ->find();
            }

            # 扫码支付 模式一
            // $qrcode = $this->scanPay($check['order']);
            // return '<img src="'.$qrcode.'"/>';
            
            # 扫码支付 模式二
            $jsApiParameters = $this->orderPay($check['order'], $user, 'NATIVE');
            $qrcode = "http://paysdk.weixin.qq.com/example/qrcode.php?data=".urlencode($jsApiParameters['code_url']);
            $this->assign('result', ['status'=>true, 'qrcode'=>$qrcode]);
            $this->assign('order', $check['order']);

        }else{
            $this->assign('result', ['status'=>false, 'content'=>$check['content']]);
        }
        return $this->fetch();



        
    }

    # 统一下单支付
    public function orderPay($order, $user, $trade_type='NATIVE' ,$attach='六耳猕猴购物订单支付'){
        
        $wxconf = getWxConf();
        #拼凑信息
        $money = floatval($order['money'])*100;
        $tools = new \JsApiPay();
        $input = new \WxPayUnifiedOrder();
        $input -> SetAppid($wxconf['APPID']['value']);//公众账号ID
        $input -> SetMch_id($wxconf['MCHID']['value']);//商户号
        $input -> SetOpenid($user['openid']);
        $input -> SetBody('六耳猕猴订单支付');
        $input -> SetAttach($attach);
        $input -> SetOut_trade_no(strval($order['order_id']));
        $input -> SetProduct_id(strval($order['order_id']));
        if($trade_type==='NATIVE'){
            if( ($user['subscribe'] == 'Y') || ($user['subscribe'] == 1) ){
                $subscribe = 'Y';
            }else{
                $subscribe = 'N';
            }
            $input -> SetIs_subscribe($subscribe);
        }
        $input -> SetTotal_fee(strval($money));
        $input -> SetTime_start(date('YmdHis'));
        $input -> SetTime_expire(date('YmdHis', time() + 1000));
        $input -> SetNotify_url('http://www.6rmh.com/Index/Payresult/wxPayResult');
        $input -> SetTrade_type($trade_type);
        $order = \WxPayApi::unifiedOrder($input); 
        return $order;
        // $jsApiParameters = $tools->GetJsApiParameters($order);
        // return $jsApiParameters;
        
    }


    #查询订单信息并进行验证
    # $id 订单号
    public function check($id=0, $wxconf= []){
        if($id===0){
            return ['status'=>false, 'content'=>'订单错误']; exit;
        }
        if(empty($wxconf)){
            $wxconf = getWxConf();
        }
        $number = ['1', '2','3','4','5','6','7','8','9','0'];
        $first = substr($id, 0, 1);
        if(!in_array($first, $number)){ //购物订单
        
            $order = Db::name('order') -> where(['order_id'=>$id, 'status'=>1, 'pay_status'=>0]) -> find();
            
            if(isset($order)){
                if($order['money']<=0){
                    return ['status'=>false, 'content'=>'金额为0，不需支付' ]; exit;
                }
                return ['status'=>true, 'order'=>$order];
            }else{
                return ['status'=>false, 'content'=>'订单不存在'];
            }
        }else{ //内部交易订单
            //$order = Db::name('order') -> where(['order_id'=>$id, 'status'=>1, 'pay_status'=>0]) -> find();
        }

        
    }

    # 扫码支付
    public function scanPay($order=[]){

        
        if(!empty($order)){

            $wxconf = getWxConf();
            $str_appid = "appid=".$wxconf['APPID']['value']; //公众账号ID String(32)
            $str_mch_id = "&mch_id=".$wxconf['MCHID']['value']; // 商户号 String(32)

            $str_product = "&product_id=".$order['order_id']; //商品ID String(32)
            $str_stamp = "&time_stamp=".strval(time()); // 时间戳 String(10)
            
            $sign = $this->getSign($str_appid, $str_mch_id, $str_product, $str_stamp, $wxconf['PAYSECRET']['value']); 
            
            $qr_str = "weixin://wxpay/bizpayurl?";
            $qr_str .= "sign=".$sign['sigh']."&"; //签名 String(32)
            $qr_str .= $str_appid.$str_mch_id.$str_product.$str_stamp;
            $qr_str .= "&nonce_str=".$sign['noncestr']; // 随机字符串 String(32)

            return "http://paysdk.weixin.qq.com/example/qrcode.php?data=".urlencode($qr_str);


        }else{
            return $check['content'];
        }

    }

    /**
	 * 生成签名
	 */
     public function getSign($str_appid, $str_mch_id, $str_product, $str_stamp, $key){
        $nonce_str = $this->getNonceStr();
        $stringA = $str_appid.$str_mch_id.'&nonce_str='.$nonce_str.$str_product.$str_stamp;
        $stringSignTemp = $stringA."&key=".$key;
        $sign = strtoupper(MD5($stringSignTemp));
        return ['noncestr'=>$nonce_str, 'sigh'=>$sign];
     }



    /**
	 * 
	 * 产生随机字符串，不长于32位
	 * @param int $length
	 * @return 产生的随机字符串
	 */
	public static function getNonceStr($length = 32) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}


    


}
