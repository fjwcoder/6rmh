<?php
# +-------------------------------------------------------------
# | CREATE by ZMX IN 2017-11-27.
# | 后台Redpacketlog控制器
# | 后台红包明细控制器
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



class Redpacketlog extends Manage
{
    public function index()
    {   
        $navid = input('navid', 59, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('redpacket_log', [], false) -> paginate();
        $this->assign('list', $list);  
        $header =  ['title'=>'订单管理->明细管理->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }
    
}
