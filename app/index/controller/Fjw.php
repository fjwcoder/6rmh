<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Fjw extends Common
{


    public function users($f){
        
        $sum = 0;
        for($i=1; $i<=$f; $i++){
            $sum += pow(2, $i-1);
        }
        
        return $sum;
    }

    public function fjw(){

        for($i=4; $i<60; $i++){
            $this->clocks($i);
            
        }
    }
    public function clocks($f=4){


        $NUM = 6000;
        echo '第'.$f.'层, 共'.number_format(pow(2, $f)-1, 0, '', '').'人：<br>';
        echo "总共收入:".number_format(($NUM*(pow(2, $f)-1)), 0, '', '').'<br>';
        $zhitui  = ($NUM*0.1)*((pow(2, $f)-1)-7) + ($NUM*0.08*4) + ($NUM*0.05*2); 
        $forty = ($NUM*0.4)*((pow(2, $f)-1)-pow(2, $f-1));
        $two = ($NUM*0.2)*($this->getTwo($f));
        $five = ($NUM*0.05)*($this->getFive($f)) ;
        $level = $forty+$two+$five;
        $count = $zhitui+$level;
        echo "总支出：".number_format($count, 0, '', '').'<br>';
        echo '盈利：'.number_format(($NUM*(pow(2, $f)-1)-$count), 0, '', '').'<br>';
        echo '支出百分比：'.($count/($NUM*(pow(2, $f)-1))*100).'%<br>';

        echo '直推提成总支出：'.number_format($zhitui, 0, '', '').'<br>';
        echo '碰对奖总支出：'.number_format($level, 0, '', '').'<br>';
        
        echo '<br><br>';


    }

    public function getTwo($deep=7){
        $sum = 0;
        $while = 0;
        for($i=1; $i<=$deep-2; $i++){
            $a = $i+1;
            $while += pow(2, $a-2)*($deep-$a);
            
        }
        $sum += $while;
        return $sum;
    }

    public function getFive($deep=6){
        $sum = 0;
        $a = 2;
        $pow = 0;
        for($i=1; $i<=$deep-2; $i++){  //代
            $while = 0; //这一代的总数
            $a = $i+1;
            while($deep-$a>=1){ //每代的每个数
                $while += (pow(2, $deep-$a)-1);
                $a += 1;
            }
            $sum += pow(2, $pow)*$while;
            $pow += 1;
        }

        return $sum;
    }

}
