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

class Redpacket extends controller
{

    public function getNum(){

        $money = 0;
        for($i=0; $i<100; $i++){
            $money += $this->getPacketMoney();

        }

        echo '总额：'.$money;
    }

    public function getPacketMoney(){
        $clear = 1; //红包是否存在
        # 计算比率
        $rand = rand(1, 1000);
        if($rand <= 841){ //1-10 841个
            $where['level'] = '1';
        }elseif( ($rand>841) && ($rand<=943) ){ // 10-30  102个
            $where['level'] = '2';
        }elseif( ($rand>943) && ($rand<=983) ){ //30-50 40个
            $where['level'] = '3';
        }elseif( ($rand>983) && ($rand<=993) ){ //50-100 10个
            $where['level'] = '4';
        }elseif( ($rand>993) && ($rand<=998) ){ //100-150  5个
            $where['level'] = '5';
        }else{ // 150-200 2个
            $where['level'] = '6';
        }

        # 优化查询红包  
        $redpacket = Db::name('redpacket') -> where("type='level' and num>0 and level<=".$where['level']) -> order('level desc') -> find();
        
        # 以备不时之需
        if(empty($redpacket)){
            return ['status'=>false, 'money'=>1 ]; exit;
        }
        
        $rand = rand(1, 2); // 概率设置红包档的最大值
        if($rand === 2){
            $fee = floatval(rand( intval($redpacket['min_val']), intval($redpacket['max_val']))/100 );
        }else{
            $fee = floatval(rand( intval($redpacket['min_val']), intval($redpacket['max_val'])*0.7 )/100 );
        }
        
        $sub_total_num = Db::name('redpacket') -> where(['name'=>'TOTAL_MONEY']) -> setDec('num', $fee); // 总金额减少
        
        if($redpacket['level'] != 1){
            $sub_num = Db::name('redpacket') -> where(['type'=>'level', 'level'=>$redpacket['level']]) -> setDec('num', 1); // 该档红包数量-1
            $sub_total_money = Db::name('redpacket') -> where(['name'=>'TOTAL_NUM']) -> setDec('num', 1); // 总数量-1
        }else{
            if($redpacket['num'] > 1){
                $sub_num = Db::name('redpacket') -> where(['type'=>'level', 'level'=>$redpacket['level']]) -> setDec('num', 1); // 该档红包数量-1
                
                $sub_total_money = Db::name('redpacket') -> where(['name'=>'TOTAL_NUM']) -> setDec('num', 1); // 总数量-1
                
            }else{
                $clear = 2;
            }
        }

        return ['status'=>true, 'money'=>$fee, 'clear'=>$clear];
    }



    # 红包发放（普通红包）
    # 默认1元
    public function sendRedPacket($money = 1){
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

    #在红包池中生成10000元的红包
    public function createRedPacket(){
        $money = [
            ['num'=>10000, 'name'=>'TOTAL_MONEY'], ['num'=>1000, 'name'=>'TOTAL_NUM']
        ];
        $num = [
            ['num'=>841, 'level'=>1],
            ['num'=>102, 'level'=>2],
            ['num'=>40, 'level'=>3],
            ['num'=>10, 'level'=>4],
            ['num'=>5, 'level'=>5],
            ['num'=>2, 'level'=>6],
        ];

        foreach($money as $k => $v){
            Db::name('redpacket') -> where(['name'=>$v['name']]) -> setInc('num', $v['num']);
        }
        foreach($num as $k => $v){
            Db::name('redpacket')-> where(['level'=>$v['level']]) -> setInc('num', $v['num']);
        }
        
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

    

    

}
