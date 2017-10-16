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
        // 商品具体信息、规格、图片、促销活动、买家信息
        $field = ['a.id as cart_id', 'a.goods_id', 'a.buyer_id', 'a.num', 'a.spec as spec_id', 'a.price', 'b.promotion as promotion_id', 
            'c.spec', 'b.name', 'b.sub_name', 'b.description', 'b.key_words', 'b.brand', 'b.bait', 'b.point','d.pic'];
        $cart = Db::name('cart') ->alias('a') 
             -> join('goods b', 'a.goods_id=b.id', 'LEFT') 
             -> join('goods_spec c', 'a.spec=c.id', 'LEFT')  
             -> join('goods_picture d', 'a.goods_id=d.gid', 'LEFT') 
             -> field($field)    
             -> group('b.id, a.spec') 
             -> order('a.addtime desc') 
             -> where(['a.buyer_id'=>session(config('USER_ID')), 'a.status'=>1, 'b.status'=>1]) -> select(); 
        if(!empty($cart)){
            # 查询促销
            $promotion = Db::name('mall_promotion') 
                -> where('status=1 and begin_time<='.time().' and end_time>='.time()) -> select();
            $promotion = getField($promotion, 'id');

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

        }else{
            return '没有商品'; die;
        }
        
        $this->assign('carts', $cart);
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'购物车', 'template'=>$config['mall_template']['value'] ]);



        return $this->fetch();
    }

    #数量增加
    public function setInc(){
        $id = input('id', 0, 'intval');
        $num = input('num', 0, 'intval');
        $inc = Db::name('cart') -> where(['id'=>$id]) -> setInc('num', 1);
        if($inc){
            return $this->redirect('index'); exit;
        }else{
            return $this->error('修改失败');
        }



    }

    #数量减少
    public function setDec(){
        $id = input('id', 0, 'intval');
        $num = input('num', 0, 'intval');
        $inc = Db::name('cart') -> where(['id'=>$id]) -> setDec('num', 1);
        if($inc){
            return $this->redirect('index'); exit;
        }else{
            return $this->error('修改失败');
        }


    }

    #手动修改数量
    public function changeNum(){

    }

    #获取当前商品信息
    public function getCartGoods($id, $sid=0){

        $goods = Db::name('goods') ->alias('a') 
            -> join('goods_picture b', 'a.id=b.gid', 'LEFT') 
            -> join('goods_spec c', 'a.id=c.gid', 'LEFT') 
            -> field(['a.id as id', 'a.name', 'a.description', 'a.weight', 'a.price as gprice', 'a.userid', 'a.status',
                'b.pic', 'c.spec', 'c.num', 'c.price as sprice'])  
            -> where(['a.id'=>$id, 'c.id'=>$sid]) 
            -> group('b.gid')
            -> find();
        return $goods;
    }

    #加入购物车
    public function add(){
        $user = decodeCookie('user');
        $id = input('id', 0, 'intval'); //商品id
        $sid = input('spec', 0, 'intval'); //规格  id
        $num = input('num', 0, 'intval'); //数量

        $goods = $this->getCartGoods($id, $sid);
        
        if($goods['status'] != 1 || empty($goods)){
            return $this->error('商品已下架'); exit;
        }

        #检查商品数量
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
                'num'=>$num, 'addtime'=>time(), 'spec'=>$sid , 'parent_id'=>$user['pid']
                ];
            
            $result = Db::name('cart') -> insert($data);

        }else{
            $result = Db::name('cart') -> where(['buyer_id'=>session(config('USER_ID')), 
                'goods_id'=>$id, 'spec'=>$sid]) -> setInc('num', $num);
            //增加修改价格（待修改）

            $data = Db::name('cart') -> where(['buyer_id'=>session(config('USER_ID')), 
                'goods_id'=>$id, 'spec'=>$sid]) -> find();
        }

        if($result){

            $res = ['status'=>true, 'id'=>$id, 'sid'=>$sid, 'num'=>$num];

        }else{
            $res = ['status'=>false, 'id'=>$id, 'sid'=>$sid];
            
        }
        return $this->redirect('status', $res);
        
    }

    public function status($status, $id, $sid, $num=0){
        
        $goods = $this->getCartGoods($id, $sid);

        $this->assign('result', ['status'=>$status, 'goods'=>$goods, 'num'=>$num]);

        $config = mallConfig();
        $this->assign('config', ['page_title'=>$config['web_name']['value'], 'template'=>$config['mall_template']['value']
            ]);

        return $this->fetch('status');
    }

    

    #删除购物车商品
    public function del(){
        $id = input('id', 0, 'intval');
        return $id;
    }


    #清除购物车
    public function delete($id_list, $action=''){
        if(empty($id_list)){
            if($action === 'order'){ //订单
                return false; exit;
            }else{
                return $this->error('所选商品为空'); exit;
            }
            
        }else{
            $result = Db::name('cart')->where('buyer_id='.session(config('USER_ID')).' and id in ('.$id_list.')') -> delete();
            if($result){
                if($action === 'order'){ //订单
                    return true; exit;
                }else{
                    return $this->success('购物车清理成功', 'Cart/index');
                }
                
            }else{
                if($action === 'order'){ //订单
                    return false; exit;
                }else{
                    return $this->error('购物车清理失败'); exit;
                }
            }
        }
        
        
    }

}
