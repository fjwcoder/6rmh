<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\index\controller\Active as Active;
// use app\index\controller\Order as Order;
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Cart extends Common
{
    public function index(){
        $flag = false;
        $user = decodeCookie('user');
        $mallObj = new Mall();
        // 查出购物车信息，包括
        // 商品具体信息、规格、图片、促销活动、买家信息
        $field = ['a.id as cart_id', 'a.goods_id', 'a.buyer_id', 'a.num', 'a.spec as spec_id', 'a.price', 'a.shipping_money',
            'a.active' ,'b.promotion as promotion_id', 
            'c.spec', 'b.name', 'b.sub_name', 'b.description', 'b.key_words', 'b.brand', 'b.bait', 'b.point','d.pic'];
        $cart = Db::name('cart') ->alias('a') 
             -> join('goods b', 'a.goods_id=b.id', 'LEFT') 
             -> join('goods_spec c', 'a.spec=c.id', 'LEFT')  
             -> join('goods_picture d', 'a.goods_id=d.gid', 'LEFT') 
             -> field($field)    
             -> group('b.id, a.spec') 
             -> order('a.addtime desc') 
             -> where(['a.buyer_id'=>session(config('USER_ID')), 'a.status'=>1, 'a.active'=>0,'b.status'=>1]) -> select(); // 购物车只查询非活动商品
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


            $all_cart = json_encode($cart_list);
            //$this->assign('all_cart', json_encode($cart_list)); //把全部购物车ID
            $flag = true;
        }else{
            $flag = false;
        }
        // dump($cart);
        $this->assign('carts', $cart);
        $this->assign('flag', $flag);
        $this->assign('all_cart', isset($all_cart)?$all_cart:'');
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'购物车', 'template'=>$config['mall_template']['value'] ]);



        return $this->fetch();
    }

    # 移动端数量增加 ajax
    public function setInc(){
        $id = input('id', 0, 'intval');
        $inc = Db::name('cart') -> where(['id'=>$id]) -> setInc('num', 1);
        if($inc){
            return json_encode(['num'=>1]);
        }else{
            return json_encode(['num'=>0]);
        }

    }

    # 移动端数量减少 ajax
    public function setDec(){
        $id = input('id', 0, 'intval');
        $inc = Db::name('cart') -> where(['id'=>$id]) -> setDec('num', 1);
        if($inc){
            return json_encode(['num'=>1]);
        }else{
            return json_encode(['num'=>0]);
        }
    }

    # 没别的想法，就是特么的为了省事儿，不想动PC端！！！
    # PC端数量增加 刷新页面
    public function setIncPC(){
        $id = input('id', 0, 'intval');
        
        $inc = Db::name('cart') -> where(['id'=>$id]) -> setInc('num', 1);
        if($inc){
            return $this->redirect('index'); exit;
        }else{
            return $this->error('修改失败');
        }

    }
    
    # 没别的想法，就是特么的为了省事儿，不想动PC端！！！
    # PC端数量减少 刷新页面
    public function setDecPC(){
        $id = input('id', 0, 'intval');
        // $num = input('num', 0, 'intval');
        $inc = Db::name('cart') -> where(['id'=>$id]) -> setDec('num', 1);
        if($inc){
            // return json_encode(['num'=>1]);
            return $this->redirect('index'); exit;
        }else{
            // return json_encode(['num'=>0]);
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
            -> field(['a.id as id', 'a.name', 'a.description', 'a.weight','a.price as gprice', 'a.userid', 'a.status',
                'b.pic', 'c.spec', 'c.num', 'c.price as sprice', 'a.shipping_money'])  
            -> where(['a.id'=>$id, 'c.id'=>$sid]) 
            -> group('b.gid')
            -> find();
        return $goods;
    }

    #加入购物车
    public function add(){
        
        
        $user = Db::name('users') -> where(['id'=>session(config('USER_ID')), 'status'=>1]) -> find();
        
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

        if($user['isactive'] >= 1){
            $activeObj = new Active();
            $active = $activeObj->isActive($id);
            if($active['status']){
                $add_time = time();
                $aleadyOrder = Db::name('order') -> where('userid='.session(config('USER_ID')).' and active=1 and pay_status=1 and '.$add_time.' between '.
                    $active['goods']['begin_time'].' and '.$active['goods']['end_time']
                    ) -> select();
                if(!empty($aleadyOrder)){
                    return msg('-1', '已存本次活动的完成订单'); exit;
                }else{
                    $data = ['buyer_id'=>Session::get(Config::get('USER_ID')), 
                    'seller_id'=>$goods['userid'], 'goods_id'=>$id, 'active'=>$active['goods']['id'],
                    'num'=>$num, 'addtime'=>time(), 'spec'=>$sid , 'price'=>$active['goods']['price'],
                    'parent_id'=>empty($user['pid'])?0:$user['pid'], 
                    'remark'=>$active['goods']['active_name']
                    ];
                    
                    $result = Db::name('cart') -> insert($data);
                    $cart_id = Db::name('cart') ->getLastInsID();
                    if($cart_id >0){
                        return $this->redirect('/index/order/preview?cart_list='.$cart_id);
                        exit;
                    }
                }
            }
        }

        #查询是否存在 同商品 同规格
        #有则数量相加、没有则新建一条
        $cart = Db::name('cart') -> where(['buyer_id'=>session(config('USER_ID')), 
            'goods_id'=>$id, 'spec'=>$sid]) -> find();

        if(($goods['sprice'] <= 0) || ($goods['sprice'] == '') ){
            $goods['sprice'] = $goods['gprice'];
        }
        if(empty($cart)){ //空的，新加
            $data = ['buyer_id'=>Session::get(Config::get('USER_ID')), 
                'seller_id'=>$goods['userid'], 'goods_id'=>$id, 'price'=>$goods['sprice'],
                'num'=>$num, 'addtime'=>time(), 'spec'=>$sid , 'shipping_money'=>$goods['shipping_money'],
                'parent_id'=>empty($user['pid'])?0:$user['pid']
                ];


            $result = Db::name('cart') -> insert($data);

        }else{


            $result = Db::name('cart') -> where(['buyer_id'=>session(config('USER_ID')), 
                'goods_id'=>$id, 'spec'=>$sid]) -> setInc('num', $num);
            //增加修改价格（待修改）
            // $data = Db::name('cart') -> where(['buyer_id'=>session(config('USER_ID')), 
            //     'goods_id'=>$id, 'spec'=>$sid]) -> find();
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
        if($id==0){
            return msg('-1', '商品信息错误'); exit;
        }else{
            $del = Db::name('cart') -> where(['buyer_id'=>session(config('USER_ID')), 'id'=>$id]) -> delete();
            if($del){
                return $this->redirect('Cart/index');
            }else{
                return msg('-1', '购物车商品删除失败');
            }
        }
    }


    #清除购物车
    public function delete($id_list='', $action=''){
        $id_list = empty($id_list)?input('id_list', '', 'htmlspecialchars,trim'):$id_list;
        if(empty($id_list)){
            if($action === 'order'){ //订单
                return false; exit;
            }else{
                return msg('-1', '所选商品为空'); exit;
            }
            
        }else{
            $result = Db::name('cart')->where('buyer_id='.session(config('USER_ID')).' and id in ('.$id_list.')') -> delete();
            if($result){
                if($action === 'order'){ //订单
                    return true; exit;
                }else{
                    return $this->redirect('Cart/index');
                    // return $this->success('购物车清理成功', 'Cart/index');
                }
                
            }else{
                if($action === 'order'){ //订单
                    return false; exit;
                }else{
                    return msg('-1', '购物车清理失败');
                    // return $this->error('购物车清理失败'); exit;
                }
            }
        }
        
        
    }

}
