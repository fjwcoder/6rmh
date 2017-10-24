<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | 红包发放
# | 
# |
# +-------------------------------------------------------------
namespace app\index\controller;
vendor('wxpay.WxPay#JsApiPay');
use app\common\controller\Common; 
use app\index\controller\Reward as Reward;
use app\admin\controller\Wechat as Wechat;
use think\Controller;
use think\Config;
use think\Session;
use think\Request;
use think\Db;

class Redpacket extends Common
{
    public function index(){
        $res = request()->server();
        $re = \WxPayConfig::SSLCERT_PATH;
        return $re;
        return dump($res);
    }

    # 红包发放（普通红包）
    # 默认1元
    public function redpacket($money = 1){
        $uid = session(config('USER_ID'));

        $user = decodecookie('user'); //获取用户信息
        $wxconf = getWxConf();
        // $mch_billno = strval($this->set_mch_billno());
        $input = new \WxPayUnifiedOrder();
        $input -> SetWxappid($wxconf['APPID']['value']);
        // $input -> SetMch_billno(strval($this->set_mch_billno()));
        $input -> SetMch_billno(getOrderID());
        $input -> SetSend_name('六耳猕猴红包来袭');
        $input -> SetRe_openid($user['openid']);
        $input -> SetTotal_amount($money*100);
        $input -> SetTotal_num(1);
        $input -> SetWishing("六耳猕猴，恭喜发财！");
        $input -> SetAct_name("钓鱼送红包");
        $input -> SetRemark("六耳猕猴红包来袭");
        $order = \WxPayApi::redPack($input); 
        return dump($order);
        if(strtoupper($order['return_code']) == 'SUCCESS'){
            if(strtoupper($order['result_code']) == 'SUCCESS'){
                return 'true';
            }
        }


    }

    private function set_mch_billno(){
        $mch_id = \WxPayConfig::MCHID;
        $mid = strval(date('Ymd'));
        $ten = strval(time());
        return strval($mch_id.$mid.$ten);
    }



#=============模拟部分==============================================================================
    public function test(){

        $mallConf = mallConfig();
        return dump($mallConf); die;

        $num = 0;
        for($i=1; $i<=1000; $i++){
            $rand = rand(1, 2000);
            if($rand == 1000){
                $num += 1;
            }
        }
        echo $num;
    }
    #数量模拟
    public function numMoni(){
        $arr = ['1'=>0, '2'=>0, '3'=>0, '4'=>0, '5'=>0, '6'=>0];
        for($i=0; $i<1000; $i++){
            $level = $this->getNum();
            $arr[$level] += 1;
        }

        return dump($arr);
        
    }

    public function getNum(){
        # 计算比率
        $rand = rand(1, 1000);
        if($rand <= 841){ //1-10
            echo '1-10   档次一<br>';
            return 1;
        }elseif( ($rand>841) && ($rand<=943) ){ // 10-30
            echo '10-30   档次二<br>';
            return 2;
        }elseif( ($rand>943) && ($rand<=983) ){ //30-50
            echo '30-50   档次三<br>'; 
            return 3;
        }elseif( ($rand>983) && ($rand<=993) ){ //50-100
            echo '50-100   档次四<br>';
            return 4;
        }elseif( ($rand>993) && ($rand<=998) ){ //100-150
            echo '100-150   档次五<br>'; 
            return 5;
        }else{ // 150-200
            echo '150-200   档次六<br>';
            return 6;
        }

    }

    # 金额模拟
    public function moneyMoni(){
        $wxconf = getWxConf();
        $total = 0;
        $num = 0;
        $redpacket = Db::name('redpacket') -> where(['type'=>'level']) -> select();
        for($i=1; $i<=10; $i++){
            $data = $this-> data($redpacket);
            $total += $data['money'];
            $num += $data['number'];
            echo '第'.$i.'次： '.$data['money'].'<br>';
        }
        echo '总共：'.$total.'<br>';
        echo '次数：'.$num.'<br>';
    }

    public function data($redpacket=[]){
        if(empty($redpacket)){
            $redpacket = Db::name('redpacket') -> where(['type'=>'level']) -> select();
        
        }
        $money = 0;
        $number = 0;
        foreach($redpacket as $k => $v){
            for($i=1; $i<=$v['value']; $i++){
                $rand = rand(1, 2);
                if($rand === 2){
                    $fee = floatval(rand( intval($v['min_val']), intval($v['max_val']))/100 );
                }else{
                    $fee = floatval(rand( intval($v['min_val']), intval($v['max_val'])*0.98 )/100 );
                }
                
                $money += $fee;
                $number += 1;
            }
        }

        return ['money'=>$money, 'number'=>$number];
    }

    

}
