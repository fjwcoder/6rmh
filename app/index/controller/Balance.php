<?php
namespace app\index\controller;
use app\common\controller\Common; 
// use app\index\controller\Wxpay as Wxpay;
use think\Controller;
use think\Config;
use think\Session;
use think\Paginator;
use think\Db;

class Balance extends Common
{
    //提现
    public function withdraw(){
        $id = session(config('USER_ID'));
        $list = db('withdraw', [], false) -> where(array('userid'=>$id)) ->order('addtime', 'DESC') ->paginate();
        
        //查出银行信息
        $bank = db('user_bank', [], false) -> where(['userid'=>$id, 'status'=>1]) ->select();
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'余额提现', 'template'=>$config['mall_template']['value'] ]);
        $this->assign('list', $list);
        $this->assign('bank', $bank);
        return $this->fetch();
    }

    public function opWithdraw(){
        $id = session(config('USER_ID'));
        


        $money['withdrawstatus'] = input('withdrawstatus',0,'intval');
        $money['value'] = input('money',0,'floatval');
        if($money['withdrawstatus'] == 1){//1 选中银行卡
            $bankid = input('bankid', 0, 'intval');//银行ID
            if($bankid == 0){
                return $this->error('请选择银行');
            }
        }
        // return dump($money);
        
        $pay_code = input('pay_code', 0,'intval');

        // $user = decodeCookie('user');
        $user = db('users', [], false) ->where(array('id'=>$id)) ->find();
        $old_pwd = cryptCode($pay_code, 'ENCODE', substr(md5($pay_code), 0, 4));
        $money['userid'] = $user['id'];
        $money['name'] = $user['realname'];
        
        
        if($money['value'] <= 0){
            return $this->error('金额错误');
        }
        if($money['value'] > $user['money']){
            return $this->error('余额不足');
        }
        if(isMobile()){
            $money['type'] = 2;
        }else{
            $money['type'] = 1;
        }
        $money['terminal'] = clientIP();
        if($old_pwd == $user['pay_code']){
            $result = db('withdraw', [], false) -> insert($money);
            if($result){
                $widthdraw = new Widthdraw();
                $pay_status = $widthdraw->payToBank();
                if($pay_status){
                    return $this->success('提现成功', 'Balance/withdraw');
                }else{
                    return $this->error('申请提现成功');
                }
                
                
            }else{
                
            }   
        }else{
            return $this->error('密码错误');
        }
        
    }


    //充值
    public function recharge(){
        $id = session(config('USER_ID'));
        $list = db('recharge', [], false) -> where(array('userid'=>$id)) ->order('addtime', 'DESC') ->paginate();
       
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'余额充值', 'template'=>$config['mall_template']['value']  ]);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function opRecharge(){
        $id = session(config('USER_ID'));
        $money['money'] = input('money', 0,'floatval');
        $user = decodeCookie('user');

        $money['order_id'] = 'C'.strval(time());

        $money['userid'] = $id;
        $money['name'] = $user['nickname'];
        
        if($money['money'] <= 0){
            return $this->error('金额错误');
        }

        if(isMobile()){
            $money['type'] = 2;
        }else{
            $money['type'] = 1;
        }
        $money['terminal'] = clientIP();
        $money['addtime'] = time();

        $result = db('recharge', [], false) -> insert($money);
        if($result){

            return $this->redirect('Index/Wxpay/index',  ['id'=>$money['order_id'], 
            'type'=>'charge']);
            // return $this->success('申请充值成功', 'Balance/recharge');
            
        }else{
            return $this->error('申请充值失败');
        }   

        
    }

}
