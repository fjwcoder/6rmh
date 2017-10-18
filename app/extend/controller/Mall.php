<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-19.
# | 前台控制器基类
# |
# |
# +-------------------------------------------------------------
namespace app\extend\controller;
use think\Controller;
use think\Config;
use think\Session;
use think\Cache;
use think\Db;

class Mall extends controller //需要继承该类，否则无法使用
{

    // public function getGoods($in){
        

    //     $data = Db::name('goods') ->alias('a') 
    //         -> join('mall_brand b', 'a.brand=b.id', 'LEFT') 
    //         // -> join('shop s', 'a.userid=s.userid', 'LEFT') //商家
    //         -> field(['a.id', 'a.name', 'a.price'])
    //         -> where('a.id in ('.$in.')') -> select();
    //     // if($data){
    //     //     return ['status'=>true, 'data'=>$data, 'content'=>'数据正常'];
    //     // }else{
    //     //     return ['status'=>false, 'content'=>'未查到数据'];
    //     // }

    //     return $data;
    // }

    // public function getGoodsInfo(){
    //     $goods = Db::name('goods') ->alias('a') 
    //         -> join('goods_picture b', 'a.id=b.gid', 'LEFT') 
    //         -> join('goods_spec c', 'a.id=c.gid', 'LEFT') 
    //         -> field(['a.id as id', 'a.name', 'a.description', 'a.weight', 'a.price as gprice', 'a.userid', 'a.status',
    //             'b.pic', 'c.spec', 'c.num', 'c.price as sprice'])  
    //         -> where(['a.id'=>$id, 'c.id'=>$sid]) 
    //         -> group('b.gid')
    //         -> find();
    //     return $goods;
    // }

    # 获取商品详情(前台商品详情页用到该方法)
    public function getGoodsDetail($id){
        $field = ['a.id', 'a.userid', 'a.catid', 'a.catid_list', 'a.name', 'a.sub_name', 'a.key_words', 'a.service', 'a.img',
            'a.price', 'a.cost_price', 'a.sell_price', 'a.amount', 'a.sell_amount', 'a.weight', 'a.bait', 'a.promotion',
            'a.point', 'a.free_shipping', 'a.description', 'a.high_comm', 'a.low_comm', 'a.low_comm', 
            'a.remark', 'b.detail', 'c.title as brand_title', 'c.logo as brand_logo', 'c.description as brand_description'
        ];
        // 以后加上关联商家
        $goods = Db::name('goods') -> alias('a') 
            -> join('goods_detail b', 'a.id=b.gid', 'LEFT')  //商品详情
            -> join('mall_brand c', 'a.brand=c.id', 'LEFT') //品牌
            -> field($field) 
            -> where(['a.id'=>$id, 'a.status'=>1, 'c.status'=>1 ]) 
            -> find();
        
        if(!empty($goods)){
            #查出图片
            $picture = $this->getGoodsImg($id);
            $goods['pic'] = empty($picture)?[]:$picture;

            #查出规格
            $spec = Db::name('goods_spec') -> where(['gid'=>$id]) -> select();
            $goods['spec'] = empty($spec)?[]:$spec;


            #查出服务
            if(strlen($goods['service'])>1){
                $in = substr($goods['service'], 2); //去掉前两个字符  0,
                $service = Db::name('mall_service') -> where('id in ('.$in.')') -> select();
                
            }else{
                $service = $this->getCategoryService($goods['catid_list']);
            }
            $goods['service'] = empty($service)?[]:$service;
            
            // return dump($goods);

            #查出促销 （还没写好，没考虑好怎么样继承父级促销）
            if($goods['promotion']>0){
                $promotion = Db::name('mall_promotion') 
                    // -> field([ 'name as prom_name', 'title as prom_title', 'type as prom_type', 'percent as prom_percent', 'money_limit',
                    // 'money_sub', 'bait as prom_bait', 'point as prom_point', 'description as prom_description'])
                    -> where('status=1 and begin_time<='.time().' and end_time>='.time().' and id='.$goods['promotion']) -> find();
                $goods['promotion'] = empty($promotion)?[]:$promotion;
            }  
        }else{
            return ['status'=>false, 'error'=>'未查到商品或商品已经下架']; exit;
        }
        return ['status'=>true, 'data'=>$goods, 'success'=>'商品查询正常'];

    }




    # param => $id_list: 种类ID链 （上溯到上级分类）
    public function getCategoryService($id_list){
        $list = db('mall_category') -> where('id in ('.$id_list.')') -> column('service');
        $id_list = [];
        foreach($list as $k=>$v){
            $temp = explode(';', $v);
            $id_list = array_merge($id_list, $temp); //合并数组
        }
        $id_list = array_filter(array_unique($id_list)); //去重、去空
        $in = '(0';
        foreach($id_list as $k=>$v){
            $in .= ','.$v;
        }
        $in .= ')';
        $service = db('mall_service', [], false) -> where('id in '.$in) -> select();
        return $service;

    }

    #获取某商品的所有图片
    public function getGoodsImg($id){
        $picture = db('goods_picture') -> where(['gid'=>$id]) -> select();
        return $picture;
    }
    #获取某商品的所有规格
    public function getGoodsSpec($id){
        $spec = db('goods_spec') -> where(['gid'=>$id]) -> select();
        return $spec;
    }
    
    public function getCartInfo($id){


    }

    public function getOrderInfo($id){

    }

    #获取全部品牌
    public function getBrand(){
        if(cache('MALL_BRAND')){
            $brand = cache('MALL_BRAND');
        }else{
            $brand = db('mall_brand', [], false) -> where(['status'=>1]) -> select();
            // cache('MALL_BRAND', $brand);  //缓存注释
        }
        
        return $brand;
    }

    #获取全部服务
    public function getService(){
        if(cache('MALL_SERVICE')){
            $service = cache('MALL_SERVICE');
        }else{
            $service = db('mall_service', [], false) -> where(['status'=>1]) -> select();
            // cache('MALL_SERVICE', $service);  //缓存注释
        }
        return $service;
    }

    #获取全部促销
    public function getPromotion(){
        if(cache('MALL_PROMOTION')){
            $promotion = cache('MALL_PROMOTION');
        }else{
            $promotion = db('mall_promotion', [], false) -> where(['status'=>1]) -> select();
            // cache('MALL_PROMOTION', $promotion);  //缓存注释
        }
        return $promotion;
    }

    #获取全部种类
    public function getCatetory($type="all"){
        if($type === 'all'){
            if(cache('MALL_CATEGORY')){
                $category = cache('MALL_CATEGORY');
            }else{
                // $category = db('mall_category', [], false) -> where(array('status'=>1)) ->order('id_list, sort') -> select();
                $category = db('mall_category', [], false) ->order('id_list, sort') -> select();
                
                // cache('MALL_CATEGORY', $category);  //缓存注释
            }
        }else{
            $category = db('mall_category', [], false) -> where(array('status'=>1)) ->order('id_list, sort') -> select();
        }
        
        return $category;
    }

    #获取全部规格
    public function getSpec(){
        if(cache('MALL_SPEC')){
            $spec = cache('MALL_SPEC');
        }else{
            $spec = Db::name('mall_spec') -> where(['status'=>1]) -> select();
            // cache('MALL_SPEC', $spec); //缓存注释
        }

        return $spec;
    }


}