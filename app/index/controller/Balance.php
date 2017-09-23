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
       
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'用户中心', 'template'=>$config['mall_template']['value'] 
            ]);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function opWithdraw(){
        $id = session(config('USER_ID'));
        $money['value'] = input('value',0,'floatval');
        // dump($money['value']);die;
        $data['pay_code'] = input('pay_code',0,'intval');

        $user = decodeCookie('user');
        
        $old_pwd = cryptCode($data['pay_code'], 'ENCODE', substr(md5($data['pay_code']), 0, 4));
        $money['userid'] = $user['id'];
        $money['name'] = $user['realname'];
        $mon = db('users', [], false) ->where(array('id'=>$id)) ->field('money') ->find();
        
        if($money['value'] == 0){
            return $this->error('金额错误');
        }
        if($money['value'] > $mon['money']){
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
                return $this->success('申请提现成功', 'Balance/index');
                
            }else{
                return $this->error('申请提现失败');
            }   
        }else{
            return $this->error('密码错误');
        }
        
    }

}
