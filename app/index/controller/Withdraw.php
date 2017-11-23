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
	public function payToBank(){
		$uid = session(config('USER_ID'));
		$wxconf = getWxConf();
		
		$input = new \PayToUser();
		$input -> SetValues('partner_trade_no', ''); // 企业付款单号
		$input -> SetValues('enc_bank_no', ''); //收款方银行卡号
		$input -> SetValues('enc_true_name', ''); //开户人姓名
		$input -> SetValues('bank_code', ''); // 开户行
		$input -> SetValues('amount', ''); // 付款金额
		$input -> SetValues('desc', ''); //付款说明

		$order = \WxPayApi::payToBank($input);

		return dump($order);
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
