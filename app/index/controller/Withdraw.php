<?php
namespace app\index\controller;
vendor('wxpay.WxPay#JsApiPay');
use app\common\controller\Common; 
use app\admin\controller\Wechat as Wechat;
// use app\extend\controller\Mall as Mall;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;
# 调用接口提现
class Withdraw extends Common
{

    public function index(){

        $uid = session(config('USER_ID'));

    }

	# 企业付款到银行卡
	public function payToBank($order_id='', $order=[], $desc='六耳猕猴微信提现到银行卡'){
		if(empty($order_id)){
			$order_id = input('id', '', 'htmlspecialchars,trim');
		}

		if(empty($order)){
			$order = Db::name('withdraw') -> where(['order_id'=>$order_id, 'status'=>1, 'pay_status'=>0]) -> find();
		}
		// return dump($order);
		// $uid = session(config('USER_ID'));
		// $wxconf = getWxConf();
		
		$input = new \PayToUser();
		$input -> SetValues('partner_trade_no', $order['order_id']); // 企业付款单号
		$input -> SetValues('enc_bank_no', ''); //收款方银行卡号
		$input -> SetValues('enc_true_name', ''); //开户人姓名
		$input -> SetValues('bank_code', $order['bank_code']); // 开户行
		$input -> SetValues('amount', $order['value']*100); // 付款金额 (单位： 分)
		$input -> SetValues('desc', $desc); //付款说明

		$order = \WxPayApi::payToBank($input);

		return dump($order);
	}


	public function getRSAKey(){
		$input = new \GetRSAKEY();
		$wxconf = getWxConf();
		$input -> SetValues('mch_id', $wxconf['MCHID']['value']);
		$res = $this->getSign();
		$input -> SetValues('nonce_str', $res['noncestr']);
		$input -> SetValues('sign', $res['sign']);
		$input -> SetValues('sign_type', 'MD5');

		$response = \WxPayApi::getRSAKey($input);
	}

	/**
	 * 生成签名
	 */
     public function getSign(){
		$wxconf = getWxConf();
        $nonce_str = $this->getNonceStr();
		echo $nonce_str.'<br>';
        $stringA = 'mch_id='.$wxconf['MCHID']['value'].'&nonce_str='.$nonce_str;
		echo $stringA.'<br>';
        $stringSignTemp = $stringA."&key=".$wxconf['PAYSECRET']['value'];
		echo $stringSignTemp.'<br>';
        $sign = strtoupper(MD5($stringSignTemp));
        return ['noncestr'=>$nonce_str, 'sign'=>$sign];
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
