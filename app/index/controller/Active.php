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

    public function regIsActive(){
        $active = Db::name('active') -> where('begin_time<'.time().' and end_time>'.time().' and status=1') -> select();
        if(!empty($active)){
            return true;
        }else{
            return false;
        }
    }

    # 一元秒杀活动
    public function isActive($gid){

        $goods = Db::name('active') -> alias('a') 
        -> join('active_detail b', 'a.id=b.aid', 'LEFT')
        -> field(['a.id', 'a.name as active_name', 'a.begin_time', 'a.end_time', 'a.type', 'a.description', 
            'b.gid', 'b.gbegin_time', 'b.gend_time', 'b.price', 'b.bait', 'b.point'])  
        ->where('id=1 and status=1 and begin_time<'.time().' and end_time>'.time().' and b.gid='.$gid) 
        -> find();
        
        if(isset($goods)){
            // return ['status'=>true, 'goods'=>$goods];
            $inactive = $this->inActive($goods['gbegin_time'], $goods['gend_time']);
            if($inactive){
                return ['status'=>true, 'goods'=>$goods];
            }else{
                return ['status'=>false];
            }
            
        }else{
            return ['status'=>false];
        }

    }

    # 单个商品是否在活动时间内
    public function inActive($begin='', $end=''){
        // $flag = false;
        if(($begin == '') && ($end == '')){
            return true; exit;
        }else{ // 不全为空
            if(($begin < time()) && ($end > time())){
                return true; exit;
            }
            if( (empty($begin)) && ($end>time()) ){
                return true; exit;
            }
            if( (empty($end)) && ($begin<time()) ){
                return true; exit;
            }
        }
    }

    

}
