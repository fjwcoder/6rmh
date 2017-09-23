<?php
# +-------------------------------------------------------------
# | CREATE by ZMX IN 2017-9-17.
# | 后台Delivery控制器
# | 后台配送方式控制器
# | 
# +-------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Manage;
use app\common\controller\Common;
use think\Controller;
use think\Session;
use think\Cookie;
use think\Config;
use think\Request;
use think\Db;
use think\Cache;



class Balance extends Manage
{
    public function index()
    {   
        $navid = input('navid', 43, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('withdraw', [], false) ->order('id desc') -> paginate();
        $this->assign('list', $list);  
        $header =  ['title'=>'订单管理->余额管理->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }
    
    #处理提现成功
    public function status(){
        $navid = input('navid', 0, 'intval');
        $id = input('id', 0, 'intval');
        $data['optime'] = Date('Y-m-d H:i:s');
        $data['status'] = 2;
        $data['opname'] = session(config('ADMIN_AUTH_NAME'));

        // $status = db('withdraw', [], false) -> where(array('id'=>$id)) -> find();
        // if($status['status'] == 1){
        $sta = db('withdraw', [], false) -> where(array('id'=>$id)) -> update($data); 
        // }
        if($sta){
            return $this->success('提现成功', "Balance/index");
        }else{
            return $this->error('失败');
        }
    }

    public function add(){
        return $this->error('不能添加');
    }

    #驳回处理（添加驳回理由）
    public function rejection(){
        $id = input('id', 0, 'intval');
        $navid = input('navid', 43, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $header =  ['title'=>'订单管理->驳回理由', 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        
        $this->assign('header', $header);
        $this->assign('id', $id);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }

    public function rejectionreason(){
        $id = input('id', 0, 'intval');
        $data['opreason'] = input('opreason', '', 'htmlspecialchars,trim');
        $data['optime'] = Date('Y-m-d H:i:s');
        $data['status'] = 3;
        $data['opname'] = session(config('ADMIN_AUTH_NAME'));
        
        $sta = db('withdraw', [], false) -> where(array('id'=>$id)) -> update($data); 
        
        if($sta){
            return $this->success('驳回成功', "Balance/index");
        }else{
            return $this->error('失败');
        }
    }

}
