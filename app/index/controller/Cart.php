<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Cart extends Common
{
    public function index(){
        
        $user = decodeCookie('user');
        $mallObj = new Mall();
        // 查出购物车信息，包括
        // 商品具体信息、促销活动、买家信息
        $where = ['a.id as cart_id', 'a.goods_id', 'a.buyer_id', 'a.num', 'a.spec as spec_id', 'a.price', 'b.promotion as promotion_id', 
            'c.spec', 'b.name', 'b.sub_name', 'b.description', 'b.key_words', 'b.brand', 'b.bait', 'b.point','d.pic'];
        $cart = Db::name('cart') ->alias('a') 
             -> join('goods b', 'a.goods_id=b.id', 'LEFT') 
             -> join('goods_spec c', 'a.spec=c.id', 'LEFT')  
             -> join('goods_picture d', 'a.goods_id=d.gid', 'LEFT') 
             -> field($where)    
             -> group('b.id, a.spec') 
             -> order('a.addtime desc') 
             -> where(['a.buyer_id'=>session(config('USER_ID')), 'a.status'=>1, 'b.status'=>1]) -> select(); 
        if(!empty($cart)){
            # 查询促销
            $promotion = Db::name('mall_promotion') 
                -> where('status=1 and begin_time<='.time().' and end_time>='.time()) -> select();
            $promotion = getField($promotion, 'id');

            // return dump($cart);
            foreach($cart as $k=>$v){
                $cart_list[] = $v['cart_id'];
                if($v['promotion_id'] != 0){
                    $cart[$k]['promotion'] = $promotion[$v['promotion_id']]['title'];
                    if($promotion[$v['promotion_id']]['type'] == 1){
                        $cart[$k]['price'] = $v['price']*$promotion[$v['promotion_id']]['percent']/100;
                    }
                }else{
                    $cart[$k]['promotion'] = '';
                }
                    
            }

            // return dump($cart); //打印

            $this->assign('all_cart', json_encode($cart_list)); //把全部购物车ID
        }
        
        $this->assign('carts', $cart);
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'购物车', 'template'=>$config['mall_template']['value'] ]);



        return $this->fetch();
    }

    #数量增加
    public function setInc(){

    }

    #数量减少
    public function setDec(){

    }


    #加入购物车
    public function add(){
        $id = input('id', 0, 'intval'); //商品id
        $sid = input('spec', 0, 'intval'); //规格  id
        $num = input('num', 0, 'intval'); //数量

        #检查商品状态
        $goods = Db::name('goods') ->alias('a') 
            -> join('goods_picture b', 'a.id=b.gid', 'LEFT') 
            -> join('goods_spec c', 'a.id=c.gid', 'LEFT') 
            -> field(['a.id as id', 'a.name', 'a.description', 'a.weight', 'a.price as gprice', 'a.userid', 'a.status',
                'b.pic', 'c.spec', 'c.num', 'c.price as sprice'])  
            -> where(['a.id'=>$id, 'c.id'=>$sid]) 
            -> group('b.gid')
            -> find();
        
        if($goods['status'] != 1){
            return $this->error('商品已下架'); exit;
        }

        #检查商品数量
        // $spec = db('goods_spec', [], false) -> where(['id'=>$sid]) -> find();
        if($goods['num'] < $num){
            return $this->error('商品数量不足'); exit;
        }
  
        #查询是否存在 同商品 同规格
        #有则数量相加、没有则新建一条
        $cart = Db::name('cart') -> where(['buyer_id'=>session(config('USER_ID')), 
            'goods_id'=>$id, 'spec'=>$sid]) -> find();
        if(empty($cart)){ //空的，新加
            $data = ['buyer_id'=>Session::get(Config::get('USER_ID')), 
                'seller_id'=>$goods['userid'], 'goods_id'=>$id, 
                'price'=>$goods['sprice']?$goods['sprice']:$goods['gprice'],
                'num'=>$num, 'addtime'=>time(), 'spec'=>$sid
                ];
            
            $result = Db::name('cart') -> insert($data);
        }else{
            $result = Db::name('cart') -> where(['buyer_id'=>session(config('USER_ID')), 
                'goods_id'=>$id, 'spec'=>$sid]) -> setInc('num', $num);
            //增加修改价格（待修改）

            $data = Db::name('cart') -> where(['buyer_id'=>session(config('USER_ID')), 
                'goods_id'=>$id, 'spec'=>$sid]) -> find();
        }

        $config = mallConfig();
        $this->assign('config', ['page_title'=>$config['web_name']['value'], 'template'=>$config['mall_template']['value']
            ]);
        // return dump($goods);
        if($result){
            $this->assign('result', ['status'=>true, 'goods'=>$goods, 'num'=>$data['num']]);
        }else{
            $this->assign('result', ['status'=>false, 'goods'=>$goods]);
        }
        return $this->fetch('add');
        
    }


    #删除购物车商品
    public function del(){
        $id = input('id', 0, 'intval');
        return $id;
    }

    #清除购物车
    public function delate(){
        return '清除购物车';
        $result = Db::name('cart')->where(['buyer_id'=>session(config('USER_ID'))]) -> delete();
        if($result){
            return $this->success('购物车清除成功', 'Cart/index');
        }else{
            return $this->error('购物车清除失败');
        }
    }

}
