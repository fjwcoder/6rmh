<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | 钓鱼游戏控制器
# | 一、每10元1个鱼饵+10积分
# | 二、一个鱼饵+20积分 钓一次鱼
# | 三、 两种模式：
# |     1.每次钓鱼获得积分的时候 20积分
# |     2.每次钓鱼获得积分的时候 40积分
# | 
# | 四、以第一种模式开发
# | 
# |
# | 
# |
# +-------------------------------------------------------------
namespace app\index\controller;
use app\common\controller\Common; 
use app\index\controller\Redpacket as Redpacket; 
use think\Controller;
use think\Config;
use think\Session;
use think\Request;
use think\Db;

class Fishing extends controller
{
    public function index(){

        // $user = decodeCookie('user');
        // if(empty($user)){
        //     $user = ['headimgurl'=>'http://wx.qlogo.cn/mmopen/Q3auHgzwzM4Ziaqw4Uibblzz9FiaogAGs0kTPEw3IsYGrwibfcrXe6TAH2UJut54PAlbJWJsicl17ylCC4ZQDkC3wibA/0'];
        // }
        $uid = session(config('USER_ID'));
        if(isset($uid)){
            $user = Db::name('users') -> where(['id'=>$uid, 'status'=>1]) -> find();
        }else{
            $user = ['headimgurl'=>'http://wx.qlogo.cn/mmopen/Q3auHgzwzM4Ziaqw4Uibblzz9FiaogAGs0kTPEw3IsYGrwibfcrXe6TAH2UJut54PAlbJWJsicl17ylCC4ZQDkC3wibA/0', 
                'bait'=>999, 'point'=>999];
        }
        $this->assign('user', $user);

        return $this->fetch();

    }

    public function fishing(){
        header('Content-type: application/json, charset=utf-8');
        // $uid = session(config('USER_ID'));
        $uid = 1;

        // sleep(3); //睡眠一段时间

        if(empty($uid)){
            echo json_encode(array('status'=>false, 'content'=>'没有登录'), JSON_UNESCAPED_UNICODE); exit;
        }else{
            $mallConf = mallConfig();
            #获取用户的鱼饵和积分
            $user = Db::name('users') -> where(['id'=>$uid, 'status'=>1]) -> field(['bait', 'point', 'nickname']) -> find();
            if(empty($user)){
                echo json_encode(array('status'=>false, 'content'=>'用户数据错误'), JSON_UNESCAPED_UNICODE); exit;
            }else{
                if( ($user['bait']<1) || ($user['point']<$mallConf['FISH_USE_POINT']['value'] ) ){
                    echo json_encode(array('status'=>false, 'content'=>'资产不足'), JSON_UNESCAPED_UNICODE); exit;
                }else{
                    # 减去数据
                    Db::name('users') -> where(['id'=>$uid, 'status'=>1]) -> setDec('bait', 1);
                    Db::name('users') -> where(['id'=>$uid, 'status'=>1]) -> setDec('point', $mallConf['FISH_USE_POINT']['value']);

                    
                    # 计算钓上来的物品
                    $rand = rand(1,100);
                    if($rand<=$mallConf['FISH_POINT_PERCENT']['value']){//积分
                        $point = rand(5, intval($mallConf['FISH_GET_POINT']['value']));
                        $get_point = Db::name('users') -> where(['id'=>$uid, 'status'=>1]) -> setInc('point', $point);
                        if($get_point){
                            Db::name('point_log') -> insert(['userid'=>$uid, 'name'=>$user['nickname'], 
                            'value'=>$point, 'type'=>1, 'remark'=>'【'.$user['nickname'].'】钓鱼获得【'. $point.'】积分']);
                        }
                        echo json_encode(array('status'=>true, 'bait'=>1, 'point'=>$mallConf['FISH_USE_POINT']['value'], 'type'=>'point', 'value'=>$point, 'content'=>'获得 【'.$point.'】 积分'), JSON_UNESCAPED_UNICODE); exit;

                    }else{ //红包+特殊物品
                        $rand = rand(1, 1000);
                        if($rand<=$mallConf['FISH_MOON_PERCENT']['value']){ //特殊物品 => 月亮
                            
                            echo json_encode(array('status'=>true, 'type'=>'moon', 'value'=>1, 'content'=>'获得月亮'), JSON_UNESCAPED_UNICODE); exit;
 
                        }else{
                            # 发放红包
                            # 获得红包，放到余额里
                            $redpacket = new Redpacket();
                            $money = $redpacket -> getPacketMoney();
                            // echo json_encode(array('status'=>true, 'type'=>'redpacket', 'value'=>$money['money'],'content'=>'获得红包【'.$money['money'].'】元'), JSON_UNESCAPED_UNICODE); exit;

                            $add_money = Db::name('users') -> where(['id'=>$uid, 'status'=>1]) -> setInc('money', $money['money']); // 增加余额

                            $log = ['userid'=>$uid, 'name'=>$user['nickname'], 'value'=>$money['money'], 'type'=>2, 
                                'clear'=>$money['clear'], 'remark'=>'钓鱼获得红包【'.$money['money'].'】RMB'];
                            Db::name('redpacket_log') -> insert($log);

                            echo json_encode(array('status'=>true, 'bait'=>1, 'point'=>$mallConf['FISH_USE_POINT']['value'],'type'=>'redpacket', 'value'=>$money['money'],'content'=>'红包 【'.$money['money'].'】 元'), JSON_UNESCAPED_UNICODE); exit;
                            // return '获得红包';
                            // $packet = new Redpacket();
                            // $result = $packet->redpacket();

                        }
                    }


                    
                }
            }
        }
        

        
    }

    public function loseFish(){
        header('Content-type: application/json, charset=utf-8');
        $uid = session(config('USER_ID'));
        $mallConf = mallConfig();
        Db::name('users') -> where(['id'=>$uid, 'status'=>1]) -> setDec('bait', 1);
        Db::name('users') -> where(['id'=>$uid, 'status'=>1]) -> setDec('point', $mallConf['FISH_USE_POINT']['value']);
        
        echo json_encode(array('status'=>false, 'content'=>'狡猾的鱼儿脱钩了', 'bait'=>1, 'point'=>$mallConf['FISH_USE_POINT']['value']), JSON_UNESCAPED_UNICODE); exit;
                            
    }

    


}
