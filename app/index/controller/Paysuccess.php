<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | 支付成功，逻辑问题
# |
# |
# +-------------------------------------------------------------
namespace app\index\controller;
use app\common\controller\Common; 
use app\admin\controller\Wechat as Wechat;
use app\extend\controller\Mall as Mall;
use app\index\controller\Wxpay as Wxpay;
use app\index\controller\Payresult as Payresult;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Paysuccess extends Common
{

    #购物支付成功

    public function buy($order=[]){
        if(!empty($order)){
            
        }else{
            return false;
        }
    }

    # 交易支付成功
    public function trade($order=[]){
        if(!empty($order)){

        }else{
            return false;
        }
    }


    #充值支付成功
    public function charge($order=[]){
        if(!empty($order)){
            # 用户余额增加
            $money = Db::name('users') -> where(['id'=>$order['userid']]) -> setInc('money', $order['value']);
            if($money){
                # 修改订单状态
                $update = Db::name('charge') -> where(['order_id'=>$order['order_id'], 'status'=>1]) -> update(['status'=>2, 'paytime'=>time() ]);
                if($update){
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }




}
