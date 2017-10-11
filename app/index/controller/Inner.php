<?php
namespace app\index\controller;
use app\common\controller\Common; 
// use app\common\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Paginator;
use think\Db;

class Inner extends Common
{
    // public function index0(){

    // }

    public function index(){
        $id = session(config('USER_ID'));
        $list = db('inner_goods', [], false) -> where(['status'=>1]) ->select(); //增加where过滤条件 by fjw
        $lis = db('users', [], false) ->field('bait,point') ->where(array('id'=>$id)) ->find();    
        foreach($list as $k=>$v){  //修改 by fjw
            $list[$k]['value'] = $lis[$v['name']];
        }    
// return dump($list);
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'个人资产', 'template'=>$config['mall_template']['value'] 
            ]);
        $this->assign('list', $list);
        // $this->assign('lis', $lis);
        return $this->fetch();
    }

    public function sellgoods(){
        return dump($_POST);
        $id = session(config('USER_ID'));
        $list = db('users', [], false) ->field('realname,bait,point') ->where(array('id'=>$id)) ->find();
        
        $data['value'] = input('value', 0, 'intval');
        $data['money'] = input('money', 0, 'intval');
        $data['selltime'] = input('selltime', 0, 'intval');
        $data['orderid'] = time();
        $data['userid'] = $id;
        $data['type'] = input('type', 0, 'intval');  //1积分  2 鱼饵
        $data['username'] = $list['realname'];
        $data['addtime'] = date('Y-m-d H:i:s',time());

        if($data['value'] > $list['point']){
            return $this->error('积分不足');
        }else{
            $res = Db::name('inner_shop') ->insert($data);
        }

        if($res){  
            return $this->success('出售成功', 'Inner/index');  
        }else{
            return $this->error('出售失败');
        } 
        return $this->fetch('sell');
    }
 
    // public function sell(){
        
    //     $id = session(config('USER_ID'));
    //     // $list = Db::name('inner_shop') ->alias('a')
    //     //     ->join('users b', 'a.userid=b.id', 'LEFT')
    //     //     ->field('a.*, b.bait, b.point, b.realname')
    //     //     ->where(['userid' =>$id])
    //     //     ->select();
    //     $list = db('users', [], false) ->field('realname,bait,point') ->where(array('id'=>$id)) ->find();
    //     // return dump($list);
    //     $config = mallConfig();
    //     $this->assign('config', ['page_title'=>'交易平台', 'template'=>$config['mall_template']['value'] 
    //         ]);
    //     $this->assign('time', ['status'=>false]);
    //     $this->assign('list', $list);
    //     return $this->fetch();
    // }

    public function purchase(){
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'交易平台', 'template'=>$config['mall_template']['value'] ]);
        
        return $this->fetch();
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
