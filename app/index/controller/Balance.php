<?php
namespace app\index\controller;
use app\common\controller\Common; 
// use app\common\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Paginator;
use think\Db;

class Balance extends Common
{
    public function withdraw(){
        $id = session(config('USER_ID'));
        $list = db('withdraw', [], false) -> where(array('userid'=>$id)) ->order('addtime', 'DESC') ->paginate();
        
        //查出银行信息
        $bank = db('user_bank', [], false) ->select();
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'余额提现', 'template'=>$config['mall_template']['value'] 
            ]);
            // return dump($bank);
        $this->assign('list', $list);
        $this->assign('bank', $bank);
        return $this->fetch();
    }

    public function opWithdraw(){
        $id = session(config('USER_ID'));
        $money['withdrawstatus'] = input('withdrawstatus',0,'intval');
        
        if($money['withdrawstatus'] == 1){//1 选中银行卡
            $bankid = input('bankid', 0, 'intval');//银行ID
            if($bankid == ''){
                return $this->error('请选择银行');
            }
        }
        // return dump($bankid);
        $money['value'] = input('money',0,'floatval');
        $data['pay_code'] = input('pay_code',0,'intval');

        $user = decodeCookie('user');
        $old_pwd = cryptCode($data['pay_code'], 'ENCODE', substr(md5($data['pay_code']), 0, 4));
        $money['userid'] = $user['id'];
        $money['name'] = $user['realname'];
        $userinfo = db('users', [], false) ->where(array('id'=>$id)) ->find();
        
        if($money['value'] == 0){
            return $this->error('金额错误');
        }
        if($money['value'] > $userinfo['money']){
            return $this->error('余额不足');
        }
        if(isMobile()){
            $money['type'] = 2;
        }else{
            $money['type'] = 1;
        }
        $money['terminal'] = clientIP();
        if($old_pwd == $userinfo['pay_code']){
            $result = db('withdraw', [], false) -> insert($money);
            if($result){
                return $this->success('申请提现成功', 'Balance/withdraw');
                
            }else{
                return $this->error('申请提现失败');
            }   
        }else{
            return $this->error('密码错误');
        }
        
    }

    public function recharge(){
        $id = session(config('USER_ID'));
        $list = db('recharge', [], false) -> where(array('userid'=>$id)) ->order('addtime', 'DESC') ->paginate();
       
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'余额充值', 'template'=>$config['mall_template']['value'] 
            ]);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function opRecharge(){
        $id = session(config('USER_ID'));
        $money['money'] = input('money',0,'floatval');
        // dump($money['money']);die;
        // $data['pay_code'] = input('pay_code',0,'intval');

        $user = decodeCookie('user');
        
        // $old_pwd = cryptCode($data['pay_code'], 'ENCODE', substr(md5($data['pay_code']), 0, 4));
        $money['order_id'] = time();
        $money['userid'] = $user['id'];
        $money['name'] = $user['realname'];
        // $mon = db('users', [], false) ->where(array('id'=>$id)) ->field('money') ->find();
        
        if($money['money'] == 0){
            return $this->error('金额错误');
        }
        // if($money['money'] > $mon['money']){
        //     return $this->error('余额不足');
        // }
        if(isMobile()){
            $money['type'] = 2;
        }else{
            $money['type'] = 1;
        }
        $money['terminal'] = clientIP();
        $money['addtime'] = time();
        // if($old_pwd == $user['pay_code']){
        $result = db('recharge', [], false) -> insert($money);
        if($result){
            return $this->success('申请充值成功', 'Balance/recharge');
            
        }else{
            return $this->error('申请充值失败');
        }   
        // }else{
        //     return $this->error('密码错误');
        // }
        
    }

}
