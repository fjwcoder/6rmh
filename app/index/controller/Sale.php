<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Sale extends Common
{

    #立即够买
    public function buy(){
        $action = input('action', '', 'htmlspecialchars,trim');

        
        return $id.'=>'.$spec.'=>'.$num;
        $mallObj = new Mall();
        

    }

    #加入购物车
    public function cart(){
        $id = input('id', 0, 'intval');
        $sid = input('spec', 0, 'intval'); //spec  id
        $num = input('num', 0, 'intval');

        #检查商品状态
        $goods = db('goods', [], false) -> where(['id'=>$id]) -> find();
        if($goods['status'] != 1){
            return $this->error('商品已下架'); exit;
        }
        $spec = db('goods_spec', [], false) -> where(['id'=>$sid]) -> find();
        if($spec['num'] < $num){
            return $this->error('商品数量不足'); exit;
        }
  
        $data = ['buyer_id'=>Session::get(Config::get('USER_ID')), 
            'seller_id'=>$goods['userid'], 'goods_id'=>$id, 
            'price'=>$spec['price']?$spec['price']:$goods['price'],
            'num'=>$num, 'addtime'=>time(), 'spec'=>$sid
            ];
        
        $add = Db::name('cart') -> insert($data);
        if($add){
            return '添加购物车成功';
        }else{
            return '添加购物车失败';
        }
        

    }


    public function collect(){
        $id = input('id', 0, 'intval');
        $spec = input('spec', 0, 'intval');
        $num = input('num', 0, 'intval');

        
        
        return $id.'=>'.$spec.'=>'.$num;
        $mallObj = new Mall();
    }


    public function users($f){
        
        $sum = 0;
        for($i=1; $i<=$f; $i++){
            $sum += pow(2, $i-1);
        }
        
        return $sum;
    }

    public function five($f=5){
        $f -= 2; //开始计算的层数
        $sum = 0;
        $quan = 0;
        for($i=$f; $i>0; $i--){
            $quan += 1;
            //先算几个人
            $user = pow(2, $i-1);
            //算每人多少个
            $num = pow(2, $quan)-1;

            $sum += $user*$num;
        }


        return $sum;
    }


    public function fjw(){
        for($i=4; $i<100; $i++){
            $this->clocks($i);
        }
    }

    public function clocks($f=5){
        $NUM = 6000;
        

        echo '第'.$f.'层, 共'.number_format($this->users($f), 0, '', '').'人：<br>';
        echo "总共收入:".number_format(($NUM*$this->users($f)), 0, '', '').'<br>';
        $zhitui  = ($NUM*0.1)*($this->users($f)-7) + ($NUM*0.08*4) + ($NUM*0.05*2); 
        $forty = ($NUM*0.4)*($this->users($f)-pow(2, $f-1))+($NUM*0.2)*($this->users($f)-pow(2, $f-1)-pow(2, $f-2))+($NUM*0.05)*$this->five($f);
        $count = $zhitui+$forty;
        echo "总支出：".number_format($count, 0, '', '').'<br>';
        echo '盈利：'.number_format(($NUM*$this->users($f)-$count), 0, '', '').'<br>';
        echo '支出百分比：'.($count/($NUM*$this->users($f))*100).'%<br>';

        echo '直推提成总支出：'.number_format($zhitui, 0, '', '').'<br>';
        echo '碰对奖总支出：'.number_format($forty, 0, '', '').'<br>';
        
        echo '<br><br>';


    }



}
