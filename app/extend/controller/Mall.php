<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-19.
# | 前台控制器基类
# |
# |
# +-------------------------------------------------------------
namespace app\extend\controller;
use app\common\controller\Authority;
use think\Controller;
use think\Config;
use think\Session;
use think\Cache;
use think\Db;

class Mall extends controller //需要继承该类，否则无法使用
{

    public function getGoodsInfo($in){
        $data = Db::name('goods') ->alias('a') 
            -> join('mall_brand b', 'a.brand=b.id', 'LEFT') 
            // -> join('shop s', 'a.userid=s.userid', 'LEFT') //商家
            -> field(['a.id', 'a.name', 'a.price'])
            -> where('a.id in '.$in) -> select();
        return dump($data);
        if($data){
            return ['status'=>true, 'data'=>$data, 'content'=>'数据正常'];
        }else{
            return ['status'=>false, 'content'=>'未查到数据'];
        }
    }

    public function getGoodsDetail($id){

    }

    public function getCartInfo($id){

    }

    public function getOrderInfo($id){

    }

    public function getBrand(){
        if(cache('MALL_BRAND')){
            $brand = cache('MALL_BRAND');
        }else{
            $brand = db('mall_brand', [], false) -> where(['status'=>1]) -> select();
            // cache('MALL_BRAND', $brand);  //缓存注释
        }
        
        return $brand;
    }

    public function getService(){
        if(cache('MALL_SERVICE')){
            $service = cache('MALL_SERVICE');
        }else{
            $service = db('mall_service', [], false) -> where(['status'=>1]) -> select();
            // cache('MALL_SERVICE', $service);  //缓存注释
        }
        return $service;
    }

    public function getPromotion(){
        if(cache('MALL_PROMOTION')){
            $promotion = cache('MALL_PROMOTION');
        }else{
            $promotion = db('mall_promotion', [], false) -> where(['status'=>1]) -> select();
            // cache('MALL_PROMOTION', $promotion);  //缓存注释
        }
        return $promotion;
    }

    public function getCatetory(){
        if(cache('MALL_CATEGORY')){
            $category = cache('MALL_CATEGORY');
        }else{
            $category = db('mall_category', [], false) -> where(array('status'=>1)) ->order('id_list, sort') -> select();
            // cache('MALL_CATEGORY', $category);  //缓存注释
        }
        return $category;
    }

    public function getGoodsImg($id){
        $picture = db('goods_picture') -> where(['gid'=>$id]) -> select();
        return $picture;
    }


}