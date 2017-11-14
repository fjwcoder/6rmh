<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | 支付成功，逻辑问题
# |
# |
# +-------------------------------------------------------------
namespace app\index\controller;
use app\common\controller\Common; 
use app\admin\controller\Wechat as Wechat;
use app\extend\controller\Mall as Mall;
use app\index\controller\Wxpay as Wxpay;
use app\index\controller\Payresult as Payresult;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Paysuccess extends Common
{
    public function test(){
        $bait_log[0] = ['userid'=>1, 'value'=>100, 'type'=>1, 'remark'=>'购物获得【100】鱼饵，订单号：100000' ];
        $log_bait = Db::name('bait_log') -> insertAll($bait_log);
        echo $log_bait;
    }

    #购物支付成功
    public function order($resArr, $order=[]){
        if(!empty($order)){
            // return dump($order);
            # 查询上级用户
            $user = decodeCookie('user');
            if(!isset($user)){
                $user = Db::name('users') -> where(['id'=>$order['userid'], 'status'=>1]) -> find();
            }
            
            $id_list = explode(',', $user['id_list']);
            $id_list = array_reverse($id_list);
            # 更改用户资产
            $config = mallConfig();

            $bait1 = intval($order['baits']*floatval($config['FIRST_BAIT']['value']/100)); //第一层
            $bait2 = intval($order['baits']*floatval($config['SECOND_BAIT']['value']/100)); //第二层
            $bait3 = $order['baits'] - ($bait1+$bait2); //第三层

            $point1 = intval($order['points']*floatval($config['FIRST_POINT']['value']/100));
            $point2 = intval($order['points']*floatval($config['SECOND_POINT']['value']/100));
            $point3 = $order['points'] - ($point1+$point2);
            
            # 三级用户
            $user3 = Db::name('users') -> where(['id'=>$order['userid'], 'status'=>1]) -> setInc('bait', $bait3);
            $bait_log[0] = ['userid'=>$order['userid'], 'value'=>$bait3, 'type'=>1, 'remark'=>'购物获得【'.$bait3.'】鱼饵，订单号：'.$order['order_id'] ];

            $user3 = Db::name('users') -> where(['id'=>$order['userid'], 'status'=>1]) -> setInc('point', $point3);
            $point_log[0] = ['userid'=>$order['userid'], 'value'=>$point3, 'type'=>1, 'remark'=>'购物获得【'.$point3.'】积分，订单号：'.$order['order_id'] ];


            # 二级用户
            if(isset($id_list[1])){
                $user2 = Db::name('users') -> where(['id'=>$id_list[1], 'status'=>1]) -> setInc('bait', $bait2);
                $bait_log[1] = ['userid'=>$id_list[1], 'value'=>$bait2, 'type'=>1, 'remark'=>$user['nickname'].'购物，奖励获得【'.$bait2.'】鱼饵'];

                $user2 = Db::name('users') -> where(['id'=>$id_list[1], 'status'=>1]) -> setInc('point', $point2);
                $point_log[1] = ['userid'=>$id_list[1], 'value'=>$point2, 'type'=>1, 'remark'=>$user['nickname'].'购物，奖励获得【'.$point2.'】鱼饵'];
            }
            # 一级用户
            if(isset($id_list[2])){
                $user1 = Db::name('users') -> where(['id'=>$id_list[2], 'status'=>1]) -> setInc('bait', $bait1);
                $bait_log[2] = ['userid'=>$id_list[2], 'value'=>$bait1, 'type'=>1, 'remark'=>$user['nickname'].'购物，奖励获得【'.$bait1.'】鱼饵'];

                $user1 = Db::name('users') -> where(['id'=>$id_list[2], 'status'=>1]) -> setInc('point', $point1);
                $point_log[2] = ['userid'=>$id_list[2], 'value'=>$point1, 'type'=>1, 'remark'=>$user['nickname'].'购物，奖励获得【'.$point1.'】鱼饵'];
            }


            $data = ['status'=>2, 'pay_status'=>1, 'pay_result'=>'支付成功', 'pay_time'=>time()];
            $update = Db::name('order') -> where(['order_id'=>$order['order_id']]) -> update($data);
            
            if($update){
                $log_bait = Db::name('bait_log') -> insertAll($bait_log);

                $log_point = Db::name('point_log') -> insertAll($point_log);

                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    # 交易支付成功
    public function trade($resArr, $order=[]){
        if(!empty($order)){

        }else{
            return false;
        }
    }


    #充值支付成功
    public function charge($resArr, $order=[]){
        if(!empty($order)){
            # 用户余额增加
            $money = Db::name('users') -> where(['id'=>$order['userid']]) -> setInc('money', $order['value']);
            if($money){
                # 修改订单状态
                $update = Db::name('charge') -> where(['order_id'=>$order['order_id'], 'status'=>1]) -> update(['status'=>2, 'paytime'=>time() ]);
                if($update){
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    // public function addLog($table){
    //     $data = [];
    // }



}
