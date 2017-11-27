<?php
namespace app\index\controller;
use app\common\controller\Common; 
// use app\index\controller\Wxpay as Wxpay;
use app\index\controller\Paysuccess as Paysuccess;
use think\Controller;
use think\Config;
use think\Session;
use think\Paginator;
use think\Db;

class Inner extends Common
{
    public function index(){
        $id = session(config('USER_ID'));
        //个人资产
        $list = db('inner_goods', [], false) ->paginate();
        //查询用户信息
        $userinfo = db('users', [], false) ->where(array('id'=>$id)) ->find();  
        //查询购买记录表
        $order = db('inner_order', [], false) ->alias('a')
            ->join('inner_goods b', 'a.type=b.id', 'LEFT')
            ->where(['userid'=>$id])
            ->order('addtime DESC') ->paginate();
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'个人资产', 'template'=>$config['mall_template']['value'] 
            ]);
        $this->assign('list', $list);
        $this->assign('userinfo', $userinfo);
        $this->assign('log', $order);
        return $this->fetch();
    }
    //出售
    public function sellgoods(){
        
        $id = session(config('USER_ID'));
        $userinfo = db('users', [], false) ->where(array('id'=>$id)) ->find();
        
        $sell['value'] = input('value', 0, 'intval');
        $sell['money'] = input('money', 0, 'intval');
        $sell['selltime'] = input('selltime', 0, 'intval');
        $sell['order_id'] = 'T'.time();
        $sell['userid'] = $id;
        $sell['type'] = input('type', 0, 'intval');  //1积分  2 鱼饵
        $sell['username'] = $userinfo['nickname']; //昵称
        $sell['addtime'] = date('Y-m-d H:i:s',time());

        $goods = Db::name('inner_goods') ->where(['id'=>$sell['type']]) -> find();
        // dump($goods["name"]);
        if($sell['type'] == $goods['id'] && $sell['value'] > $userinfo[$goods['name']]){
            return $this->error('数量不足');die;
        }
        
        //总数-出售数量
        if($sell['type'] == $goods['id']){
            // $userinfo[$goods['name']] = $userinfo[$goods["name"]] - $sell['value'];
            // $result = Db::name('users') ->where(array('id'=>$id)) ->update($userinfo);

            //交易完成后更新数据 # tp setDec()
            $result = Db::name('users') ->where(array('id'=>$id)) ->setDec($goods["name"], $sell['value']);
        }
        
        $res = Db::name('inner_shop') ->insert($sell);
        
        if($res){  
            return $this->success('出售成功', 'Inner/index');  
        }else{
            return $this->error('出售失败');
        } 
        
        return $this->fetch();
    }


    
    //出售列表
    public function purchase(){
        $id = session(config('USER_ID'));
        $title = input('title', '', 'htmlspecialchars,trim');
        $myid = input('myid', 0, 'intval');
        if(!empty($_POST)){
            $title = input('title', '', 'htmlspecialchars,trim');
            $begintime = input('begintime', '', 'htmlspecialchars,trim');
            $endtime = input('endtime', '', 'htmlspecialchars,trim');
            $maxprice = input('maxprice', 0,'intval');
            $minprice = input('minprice', 0,'intval');
            $value = input('value', 0,'intval');  
        }
        //按条件搜索
        $where = '(1=1) and a.status=1'; 
        //按名称搜索
        $this->assign('timer', ['status'=>false]);
        if(!empty($title)){
            $where .= " and title LIKE '%$title%' ";
            $this->assign('timer', ['status'=>true, 'title'=>$title]);
        }
        //按时间段搜索
        $this->assign('time', ['status'=>false]);
        if(!empty($begintime) && !empty($endtime)){ //设置了时间段
            if(date('Y-m-d H:i:s',strtotime($begintime))==$begintime && date('Y-m-d H:i:s',strtotime($endtime))==$endtime){
                $where .= " and addtime BETWEEN '{$begintime}' and '{$endtime}'";
                $this->assign('time', ['status'=>true, 'begintime'=>$begintime, 'endtime'=>$endtime]);
            } 
        }
        //按价格区间搜索
        $this->assign('times', ['status'=>false]);
        if(!empty($minprice) && !empty($maxprice)){
            $where .= " and money BETWEEN '{$minprice}' and '{$maxprice}'";
            $this->assign('times', ['status'=>true, 'minprice'=>$minprice, 'maxprice'=>$maxprice]);
        }
        //按出售数量搜索
        $this->assign('sum', ['status'=>false]);
        if(!empty($value)){
            $where .= " and value >= $value ";
            $this->assign('sum', ['status'=>true, 'value'=>$value]);
        }
        # 过滤时间
        $where .= "  ";
        
    // echo $where; die;
        if($myid == 0){
            $list = Db::name('inner_shop') ->alias('a')
            ->join('inner_goods b', 'a.type=b.id', 'LEFT')
            ->field('a.*, b.pic, b.title')
            ->where($where)
            ->order('addtime DESC')
            ->paginate();
        }else{
            $where .= " and userid = '{$myid}'";
            $list = Db::name('inner_shop') ->alias('a')
            ->join('inner_goods b', 'a.type=b.id', 'LEFT')
            ->field('a.*, b.pic, b.title')
            ->where($where)
            ->order('addtime DESC')
            ->paginate();
        }
        $title = Db::name('inner_goods') -> field('title') ->select();
        $this->assign('title', $title);
        $this->assign('id', $id);
        $this->assign('list', $list);
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'交易平台', 'template'=>$config['mall_template']['value'] ]);  
        return $this->fetch();
    }

    //支付
    public function pay(){
        
        $id = session(config('USER_ID'));
        $user = decodeCookie('user');
        $pass = input('pass', '','htmlspecialchars,trim');

        $order_id = input('order_id', 0, 'intval');

        $order = Db::name('inner_shop') ->alias('a')
            ->join('inner_goods b', 'a.type=b.id', 'LEFT')
            -> where(['order_id'=>$order_id]) -> find();
        if($order['status'] == 1){
            
            $upd = Db::name('inner_shop') ->where(array('order_id'=>$order_id)) ->update(['status'=>2, 'uptime'=>time()]);
            $userinfo = db('users', [], false)  ->where(array('id'=>$id)) ->find(); 
            $log = ['order_id'=>$order['order_id'], 'userid'=>$id, 'username'=>$userinfo['nickname'], 'title'=>$order['title'],
            'sellerid'=>$order['userid'], 'sellername'=>$order['username'], 'type'=>$order['type'], 'value'=>$order['value'],
            'name'=>$order['name'], 'price'=>$order['money'] ,'account'=>$order['value']*$order['money']  ];
            
            
            if($pass !== ''){ //输入密码， 使用余额支付
                $old_pwd = cryptCode($pass, 'ENCODE', substr(md5($pass), 0, 4));
                if($old_pwd == $userinfo['pay_code']){ //验证支付密码
                    if($log['account'] > $userinfo['money']){
                        $log['money'] =  $log['account'] - $userinfo['money'];// 还需支付
                        $log['balance'] = $userinfo['money'];
                    }else{
                        $log['balance'] = $log['account'];
                        $log['money'] = 0;
                    }
                }else{
                    return $this->error('支付密码错误');
                }
            }else{ // 直接支付
                $log['balance'] = 0;
                $log['money'] = $log['account'];
            }
            $log['addtime'] = time();
            $log['remark'] = '生成订单，等待支付';
            $insert = Db::name('inner_order') -> insert($log);
            if($insert){
                if($log['money'] != 0){ //微信支付
                    return $this->redirect('Index/Wxpay/index',  ['id'=>$log['order_id'], 
                        'type'=>'trade']);
                }else{ // 支付完成
                    $success = new Paysuccess();
                    $result = $success->trade([], $log);
                    if($result){
                        return $this->success('购买成功', 'Inner/purchase');
                    }
                }
            }

        }


    }



}
