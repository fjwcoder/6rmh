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
use app\index\controller\Share as Share;
use think\Controller;
use think\Config;
use think\Session;
use think\Request;
use think\Db;

class Fishing extends controller
{
    public function index(){

        $uid = session(config('USER_ID'));

        // 注意 URL 一定要动态获取，不能 handcode.!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $shareObj = new Share();
        $signPackage = $shareObj->shareConfig($url);
        $this->assign('shareconfig', $signPackage);

        $shareInfo = $shareObj->shareInfo($url, '红包来袭');
        $this->assign('shareinfo', $shareInfo);
        $wxconf = getWxConf();
        $this->assign('wxconf', ['jsjdk'=>$wxconf['JSJDK_URL']['value']]);

        if(isset($uid)){
            $user = Db::name('users') -> where(['id'=>$uid, 'status'=>1]) -> find();
            $this->assign('login', true);
        }else{
            $user = ['headimgurl'=>'__STATIC__/images/mall/default_headimg.png', 
                'bait'=>999, 'point'=>999];
            $this->assign('login', false);
        }
        $this->assign('user', $user);

        return $this->fetch();

    }

    public function fishing(){
        header('Content-type: application/json, charset=utf-8');
        $uid = session(config('USER_ID'));

        if(empty($uid)){
            echo json_encode(array('status'=>false, 'content'=>'请先登录<br><small><a class="redirect-a" href="/index/login/index">点击登录</a></small>'), JSON_UNESCAPED_UNICODE); exit;
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
