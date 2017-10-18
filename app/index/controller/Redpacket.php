<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\index\controller\Reward as Reward;
use think\Controller;
use think\Config;
use think\Session;
use think\Request;
use think\Db;

class Redpacket extends Common
{
    public function createRedpacket($total=1000, $num=100, $min=1, $max=200){

        #总共要发的红包金额，留出一个最大值;
        $total = $total - $max;

        $reward = new Reward();
        $result_merge = $reward->splitReward($total, $num, $max - 1, $min);

        sort($result_merge);
        $result_merge[1] = $result_merge[1] + $result_merge[0];
        $result_merge[0] = $max * 100;
        foreach ($result_merge as &$v) {
            $v = floor($v) / 100;
        }
        echo array_sum($result_merge);
        return dump($result_merge);

    }

}
