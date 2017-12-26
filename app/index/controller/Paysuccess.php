<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | 支付成功，逻辑问题
# |
# |
# +-------------------------------------------------------------
namespace app\index\controller;
// use app\admin\controller\Wechat as Wechat;
// use app\extend\controller\Mall as Mall;
// use app\index\controller\Wxpay as Wxpay;
use app\index\controller\Redpacket as Redpacket;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Paysuccess extends controller
{


    #购物支付成功
    public function order($resArr, $order=[]){
        if(!empty($order)){
            # 查询上级用户
            $user = decodeCookie('user');
            if(empty($user)){
                $user = Db::name('users') -> where(['id'=>$order['userid'], 'status'=>1]) -> find();
            }
            if($order['active'] > 0){
                $bait3 = $order['baits'];
                $point3 = $order['points'];
            }else{
                $id_list = explode(',', $user['id_list']);
                $id_list = array_reverse($id_list);
                # 更改用户资产
                $config = mallConfig();
                $bait1 = intval($order['baits']*floatval($config['FIRST_BAIT']['value']/100)); //第一层
                $bait2 = intval($order['baits']*floatval($config['SECOND_BAIT']['value']/100)); //第二层
                $bait3 = $order['baits'] - ($bait1+$bait2); //第三层

                # 推荐人积分
                $point1 = intval($order['points']*floatval($config['FIRST_POINT']['value']/100));
                $point2 = intval($order['points']*floatval($config['SECOND_POINT']['value']/100));
                $point3 = $order['points'] - ($point1+$point2);
            }
            
            
            
            # 三级用户
            $user3 = Db::name('users') -> where(['id'=>$order['userid'], 'status'=>1]) -> setInc('bait', $bait3);
            $bait_log[0] = ['userid'=>$order['userid'], 'name'=>$user['nickname'], 'value'=>$bait3, 'type'=>1, 'remark'=>'购物获得【'.$bait3.'】鱼饵，订单号：'.$order['order_id'] ];

            $user3 = Db::name('users') -> where(['id'=>$order['userid'], 'status'=>1]) -> setInc('point', $point3);
            $point_log[0] = ['userid'=>$order['userid'], 'name'=>$user['nickname'], 'value'=>$point3, 'type'=>1, 'remark'=>'购物获得【'.$point3.'】积分，订单号：'.$order['order_id'] ];

            if($order['active'] == 0){
                # 二级用户
                if(isset($id_list[1])){
                    $user2 = Db::name('users') -> where(['id'=>$id_list[1], 'status'=>1]) -> setInc('bait', $bait2);
                    $bait_log[1] = ['userid'=>$id_list[1], 'name'=>$user['nickname'], 'value'=>$bait2, 'type'=>1, 'remark'=>$user['nickname'].'购物，奖励获得【'.$bait2.'】鱼饵'];

                    $user2 = Db::name('users') -> where(['id'=>$id_list[1], 'status'=>1]) -> setInc('point', $point2);
                    $point_log[1] = ['userid'=>$id_list[1], 'name'=>$user['nickname'], 'value'=>$point2, 'type'=>1, 'remark'=>$user['nickname'].'购物，奖励获得【'.$point2.'】鱼饵'];
                }

                # 一级用户
                if(isset($id_list[2])){
                    $user1 = Db::name('users') -> where(['id'=>$id_list[2], 'status'=>1]) -> setInc('bait', $bait1);
                    $bait_log[2] = ['userid'=>$id_list[2], 'name'=>$user['nickname'], 'value'=>$bait1, 'type'=>1, 'remark'=>$user['nickname'].'购物，奖励获得【'.$bait1.'】鱼饵'];

                    $user1 = Db::name('users') -> where(['id'=>$id_list[2], 'status'=>1]) -> setInc('point', $point1);
                    $point_log[2] = ['userid'=>$id_list[2], 'name'=>$user['nickname'], 'value'=>$point1, 'type'=>1, 'remark'=>$user['nickname'].'购物，奖励获得【'.$point1.'】鱼饵'];
                }
            }
                


            $data = ['status'=>2, 'pay_status'=>1, 'pay_result'=>'支付成功', 'pay_time'=>time()];
            // if($order['active'] > 0){
            //     $data['active'] = 0;
            // }
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
    public function trade($resArr=[], $order=[]){
        if(!empty($order)){

            # 1. 买家增加商品 减少金额
            Db::name('users') -> where(['id'=>$order['userid'], 'status'=>1]) -> setInc($order['name'], $order['value']);
            Db::name('users') -> where(['id'=>$order['userid'], 'status'=>1]) -> setDec('money', $order['account']);

            $goods[0] = ['userid'=>$order['userid'], 'name'=>$order['username'], 'value'=>$order['value'], 
            'type'=>1, 'remark'=>'交易获得【'.$order['value'].'】'.$order['title'].'，订单号：'.$order['order_id']];
            $goods[1] = ['userid'=>$order['sellerid'], 'name'=>$order['sellername'], 'value'=>$order['value'], 
            'type'=>2, 'remark'=>'交易售出【'.$order['value'].'】'.$order['title'].'，订单号：'.$order['order_id']];
            # 2. 卖家增加余额
            Db::name('users') -> where(['id'=>$order['sellerid'], 'status'=>1]) -> setInc('money', $order['account']); 

            $money[0] = ['userid'=>$order['userid'], 'name'=>$order['username'], 'value'=>$order['value'], 
            'type'=>2, 'remark'=>'交易失去余额【'.$order['balance'].'】，订单号：'.$order['order_id']];
            $money[1] = ['userid'=>$order['sellerid'], 'name'=>$order['sellername'], 'value'=>$order['value'], 
            'type'=>1, 'remark'=>'交易获得余额【'.$order['balance'].'】，订单号：'.$order['order_id']];
            # 3. 删除inner_shop 信息
            Db::name('inner_shop') -> where(['order_id'=>$order['order_id']]) -> delete();
            # 4. 修改inner_order 状态
            Db::name('inner_order') -> where(['order_id'=>$order['order_id'], 'status'=>1, 'pay_status'=>0]) -> update([
                'status'=>2, 'pay_status'=>1, 'paytime'=>time(), 'remark'=>'订单完成']); 

            Db::name($order['name'].'_log') -> insertAll($goods); //商品日志
            Db::name('balance_log') -> insertAll($money); //余额日志
            return true;
        }else{
            return false;
        }
    }


    #充值支付成功
    public function charge($resArr, $order=[]){
        if(!empty($order)){
            
            # 用户余额增加
            $money = Db::name('users') -> where(['id'=>$order['userid']]) -> setInc('money', $order['money']);
            

            if($money){
                # 修改订单状态
                $update = Db::name('recharge') -> where(['order_id'=>$order['order_id'], 'status'=>1]) -> update(['status'=>2, 'pay_status'=>1, 
                    'optime'=>time()]);

                if($update){
                    Db::name('balance_log') -> insert(['userid'=>$order['userid'], 'name'=>$order['nickname'], 
                    'value'=>$order['money'], 'type'=>1, 'remark'=>$order['nickname'].'充值获得【'.$order['money'].'】余额']);
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

    # 企业付款到银行卡
    public function payToBank($resArr, $opname){
        
        $order = Db::name('withdraw') -> where(['order_id'=>$resArr['partner_trade_no'], 
            'status'=>1, 'pay_status'=>0]) -> find();
        if(!empty($order)){

            $update = ['status'=>2, 'pay_status'=>1, 'paytime'=>time(), 'optime'=>time(), 'opname'=>$opname, 
                'error_reason'=>$resArr['err_code_des']];
            $res = Db::name('withdraw') -> where(['order_id'=>$resArr['partner_trade_no'], 
            'status'=>1, 'pay_status'=>0]) -> update($update);
            if($res){
                return ['status'=>true, 'content'=>'提现订单完成'];
            }else{
                return ['status'=>false, 'content'=>'付款完成，订单状态修改失败', 'id'=>$resArr['partner_trade_no']];
            }
        }else{
            return ['status'=>false, 'content'=>'付款完成，回调订单未查询到', 'id'=>$resArr['partner_trade_no']];
        }
        
    }

    # 生成红包
    public function createRedpacket($money=0, $limit = 10000){
        $num_wan = 0; //一万的个数
        $more_wan = 0;
        $current = Db::name('redpacket') -> where(['name'=>'CURRENT_MONEY']) -> find();
        $sum = floatval($current['num'])+floatval($money);
        if($sum >= $limit){
            $redObj = new Redpacket();
            $num_wan = intval($sum/$limit); //生成了几个一万
            $more_wan = $sum%$limit;
            
            for($i=0; $i<$num_wan; $i++){
                $redObj -> createRedPacket($limit, 1000); //生成$limit的红包， 共1000个
            }
            
            Db::name('redpacket') -> where(['name'=>'CURRENT_MONEY']) -> update(['num'=>$more_wan]);
        }else{
            Db::name('redpacket') -> where(['name'=>'CURRENT_MONEY']) -> setInc('num', $money);
        }
    }


}
