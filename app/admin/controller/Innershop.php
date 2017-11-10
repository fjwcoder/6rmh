<?php
# +-------------------------------------------------------------
# | CREATE by ZMX IN 2017-9-17.
# | 后台Innershop控制器
# | 后台出售列表控制器
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



class Innershop extends Manage
{
    public function index()
    {   
        $navid = input('navid', 51, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('inner_shop', [], false) -> paginate();
        $this->assign('list', $list);  
        $header =  ['title'=>'订单管理->内部交易->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }

    #处理下架商品
    public function status(){
        $navid = input('navid', 0, 'intval');
        $order_id = input('order_id', 0, 'intval');
        $data['status'] = 2;

        $sta = db('inner_shop', [], false) -> where(array('order_id'=>$order_id)) -> update($data); 
        
        if($sta){
            return $this->success('商品已成功下架', "Innershop/index");
        }else{
            return $this->error('失败');
        }
    }
	
	public function innerlog()
    {   
        $navid = input('navid', 52, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('inner_log', [], false) -> paginate();
        $this->assign('list', $list);  
        $header =  ['title'=>'订单管理->内部交易->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch('innerlog');
    }
	


    
}
