<?php
namespace app\index\controller;
use app\common\controller\Common; 
// use app\common\controller\Mall as Mall;
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
        $log = db('inner_log', [], false) ->order('addtime DESC') ->paginate();
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'个人资产', 'template'=>$config['mall_template']['value'] 
            ]);
        $this->assign('list', $list);
        $this->assign('userinfo', $userinfo);
        $this->assign('log', $log);
        return $this->fetch();
    }
    //出售
    public function sellgoods(){
        
        $id = session(config('USER_ID'));
        $userinfo = db('users', [], false) ->where(array('id'=>$id)) ->find();
        
        $sell['value'] = input('value', 0, 'intval');
        $sell['money'] = input('money', 0, 'intval');
        $sell['selltime'] = input('selltime', 0, 'intval');
        $sell['order_id'] = time();
        $sell['userid'] = $id;
        $sell['type'] = input('type', 0, 'intval');  //1积分  2 鱼饵
        $sell['username'] = $userinfo['realname'];
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
        if(!empty($_POST)){
            $title = input('title', '', 'htmlspecialchars,trim');
            $begintime = input('begintime', '', 'htmlspecialchars,trim');
            $endtime = input('endtime', '', 'htmlspecialchars,trim');
            $maxprice = input('maxprice', 0,'intval');
            $minprice = input('minprice', 0,'intval');
            $value = input('value', 0,'intval');  
        }
        //按条件搜索
        $where = '(1=1) '; 
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

        $list = Db::name('inner_shop') ->alias('a')
        ->join('inner_goods b', 'a.type=b.id', 'LEFT')
        ->field('a.*, b.pic')
        ->where($where)
        ->order('addtime DESC')
        ->paginate();

        $title = Db::name('inner_goods') -> field('title') ->select();
        $this->assign('title', $title);
        $this->assign('list', $list);
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'交易平台', 'template'=>$config['mall_template']['value'] ]);  
        return $this->fetch();
    }

    //余额支付
    public function pay(){
        
        $id = session(config('USER_ID'));
        $user = decodeCookie('user');
        $pass = input('pass',0,'intval');
        $order_id = input('order_id', 0, 'intval');
        // if($order['status'] == 1){
        //     $upd = Db::name('inner_shop') ->where(array('order_id'=>$order_id)) ->update(['status'=>3]);
            //输入框被选中
            if(isset($_POST['checkbox'])){
                //判断密码是否为空
                if(empty($pass)){
                    return $this->error('支付密码不可为空');
                }
                $order = db('inner_shop', [], false) ->where(['order_id' => $order_id]) ->find(); //fjw修改
                //查询用户信息
                $userinfo = db('users', [], false)  ->where(array('id'=>$id)) ->find(); //$userinfo
                
                $pay['value'] = $order['value'];
                $pay['type'] = $order['type'];
                $pay['addtime'] = date('Y-m-d H:i:s',time());
                $pay['money'] = $order['money'];
                $pay['sellername'] = $order['username'];
                $pay['userid'] = $order_id;
                $pay['username'] = $userinfo['realname'];
                
                $old_pwd = cryptCode($pass, 'ENCODE', substr(md5($pass), 0, 4));
                
                if($old_pwd == $userinfo['pay_code']){
                    
                    if($pay['money'] > $userinfo['money']){
                        // 余额不足
                        $surplus = $pay['money'] -$userinfo['money'];
                        # 添加微信支付

                    }else{
                        // $ins['money'] = $userinfo['money'] - $pay['money'];
                        $money = Db::name('users') ->where(array('id'=>$id)) ->setDec('money', $pay['money']);
                    }
                    
                    // $money = Db::name('users') ->where(array('id'=>$id)) ->update($ins);
                    $goods = Db::name('inner_goods') ->where(['id'=>$order['type']]) -> find();

                    // $ins[$goods['name']] = $userinfo[$goods['name']] + $pay['value'];

                    //交易完成后更新数据 # tp setInc()
                    $result = Db::name('users') ->where(array('id'=>$id)) ->setInc($goods["name"], $pay['value']);
                    $res = Db::name('inner_log') ->insert($pay);
                    //交易成功后修改订单状态
                    if($order['status'] == 1){
                        $upd = Db::name('inner_shop') ->where(array('order_id'=>$order_id)) ->update(['status'=>2]);
                    }
    
                    if($res){  
                        return $this->success('购买成功', 'Inner/index');  
                    }else{
                        return $this->error('购买失败');
                    } 
                }else{
                    return $this->error('密码错误');
                }
            }else{
                echo '输入框未选中跳转微信支付页面';
            } 
            
        // }else{
        //         $order = Db::name('inner_shop') ->where(array('order_id'=>$order_id)) ->find();
        //         if($order['status'] == 3){
        //             $upd = Db::name('inner_shop') ->where(array('order_id'=>$order_id)) ->update(['status'=>1]);
        //         }
        //     }
               
    }

}
