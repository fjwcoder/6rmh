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

    


    public function collect(){
        $id = input('id', 0, 'intval');
        $spec = input('spec', 0, 'intval');
        $num = input('num', 0, 'intval');

        
        
        return $id.'=>'.$spec.'=>'.$num;
        $mallObj = new Mall();
    }




}
