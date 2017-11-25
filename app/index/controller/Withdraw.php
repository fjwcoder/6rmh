<?php
namespace app\index\controller;
vendor('wxpay.WxPay#JsApiPay');
use app\common\controller\Common; 
use app\admin\controller\Wechat as Wechat;
use app\index\controller\Paysuccess as Paysuccess;

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
	public function payToBank($order_id='', $opname = '', $desc='六耳猕猴微信提现到银行卡' ){
		if(empty($order_id)){
			$order_id = input('id', '', 'htmlspecialchars,trim');
		}
		
		if(empty($opname)){
			$opname = input('opname', '', 'htmlspecialchars,trim');
		}

		$order = Db::name('withdraw') -> where(['order_id'=>$order_id, 'status'=>1, 'pay_status'=>0]) -> find();
		
		if(empty($order)){
			return $this->success('提现申请成功', 'Balance/withdraw'); exit;
		}else{
			$userinfo = ['enc_bank_no'=>$order['cartnumber'], 'enc_true_name'=>$order['accountholder']];
		
			$encrypt = $this->encryptRSA($userinfo);

			if($encrypt['status']){
				$input = new \PayToUser();
				$input -> SetValues('partner_trade_no', $order['order_id']); // 企业付款单号
				$input -> SetValues('enc_bank_no', $encrypt['data']['enc_bank_no']); //收款方银行卡号
				$input -> SetValues('enc_true_name', $encrypt['data']['enc_true_name']); //开户人姓名
				$input -> SetValues('bank_code', $order['bank_code']); // 开户行
				$input -> SetValues('amount', $order['value']*100); // 付款金额 (单位： 分)
				$input -> SetValues('desc', $desc); //付款说明

				$resArr = \WxPayApi::payToBank($input);
				// file_put_contents('p2b.txt', var_export($resArr, true));
				if( ($resArr['return_code'] === 'SUCCESS') && ($resArr['result_code'] === 'SUCCESS') ){ //微信侧受理成功

					$success = new Paysuccess();
					$res = $success->payToBank($resArr, $opname);
					if(!$res['status']){
						Db::name('withdraw_log') -> insert(['userid'=>$id, 'remark'=>$res['content'].'订单：'.$res['id']]); 
					}
					return ['status'=>true, 'content'=>$res['content']];
					
				}else{
					Db::name('withdraw_log') -> insert(['userid'=>$id, 'remark'=>'提现付款失败']); 
					return ['status'=>false, 'content'=>'提现付款失败'];
				}

			}
		}

	}

	public function encryptRSA($data, $padding = OPENSSL_PKCS1_OAEP_PADDING){
		$result = [];
		$publicstr = file_get_contents(\WxPayConfig::RSAPUBLIC_PATH);
		$publickey = openssl_pkey_get_public($publicstr);
		foreach($data as $k=>$v){
			$encrypt = openssl_public_encrypt($v, $encrypted, $publickey, $padding);
			if($encrypt){
				$result[$k] = base64_encode($encrypted);
			}else{
				return ['status'=>false]; exit;
			}
		}

		return ['status'=>true, 'data'=>$result];
	}


	public function getRSAKey(){
		$input = new \GetRSAKEY();
		$wxconf = getWxConf();
		$input -> SetValues('mch_id', $wxconf['MCHID']['value']);
		$input -> SetValues('sign_type', 'MD5');
		$response = \WxPayApi::getRSAKey($input);
		// var_export($response); die;
		file_put_contents('H:/PHP_Develop/WWW/sixer/vendor/wxcert/rsa_public_cert.pem', $response['pub_key']);
		echo 'success';
	}

	public function getMyRSAKEY(){
		$public_cert = file_get_contents(\WxPayConfig::RSAPUBLIC_PATH);
		echo $public_cert;
	}



}
