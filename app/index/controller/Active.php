<?php
namespace app\index\controller;
// use app\common\controller\Common; 
// use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Request;
use think\Db;

class Active extends controller
{
    public function index(){

        
    }

    # 一元秒杀活动
    public function isActive($gid){

        $goods = Db::name('active') -> alias('a') 
        -> join('active_detail b', 'a.id=b.aid', 'LEFT')
        -> field(['a.id', 'a.name as active_name', 'a.begin_time', 'a.end_time', 'a.type', 'a.description', 
            'b.gid', 'b.gbegin_time', 'b.gend_time', 'b.price', 'b.bait', 'b.point'])  
        ->where('id=1 and status=1 and begin_time<'.time().' and end_time>'.time().' and b.gid='.$gid) -> find();
        if(isset($goods)){
            return ['status'=>true, 'goods'=>$goods];
        }else{
            return ['status'=>false];
        }

    }

    

}
