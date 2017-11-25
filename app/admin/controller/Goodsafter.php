<?php
# +-------------------------------------------------------------
# | CREATE by ZMX IN 2017-11-24.
# | 后台Goodsafter控制器
# | 后台余额提现控制器
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



class Goodsafter extends Manage
{
    public function index()
    {   
        $navid = input('navid', 60, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('goods_after', [], false) ->order('id desc') -> paginate();
        $this->assign('list', $list);  
        $header =  ['title'=>'系统设置->系统设置->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }
    
    #处理审核通过
    public function status(){
        $navid = input('navid', 0, 'intval');
        $id = input('id', 0, 'intval');
        $data['optime'] = Date('Y-m-d H:i:s');
        $data['status'] = 3;
        $data['opname'] = session(config('ADMIN_AUTH_NAME'));

        // $status = db('goods_after', [], false) -> where(array('id'=>$id)) -> find();
        // if($status['status'] == 1){
        $sta = db('goods_after', [], false) -> where(array('id'=>$id)) -> update($data); 
        // }
        if($sta){
            $after = db('goods_after', [], false) ->field('order_id,gid,status') -> where(array('id'=>$id)) -> find();
            $da['type'] = $after['status'];
            $detail = db('order_detail', [], false) ->where(array('order_id'=>$after['order_id'],'gid'=>$after['gid'])) ->update($da);
            return $this->success('审核通过', "Goodsafter/index");
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
        $navid = input('navid', 60, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $header =  ['title'=>'系统设置->驳回理由', 'icon'=>$nav[$navid]['icon'], 
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
        $data['status'] = 4;
        $data['opname'] = session(config('ADMIN_AUTH_NAME'));
        
        $sta = db('goods_after', [], false) -> where(array('id'=>$id)) -> update($data); 
        
        if($sta){
            $after = db('goods_after', [], false) ->field('order_id,gid,status') -> where(array('id'=>$id)) -> find();
            $da['type'] = $after['status'];
            $detail = db('order_detail', [], false) ->where(array('order_id'=>$after['order_id'],'gid'=>$after['gid'])) ->update($da);
            return $this->success('驳回成功', "Goodsafter/index");
        }else{
            return $this->error('失败');
        }
    }

}
