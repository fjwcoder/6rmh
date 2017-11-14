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

        $user = decodeCookie('user');
        if(empty($user)){
            $user = ['headimgurl'=>'http://wx.qlogo.cn/mmopen/Q3auHgzwzM4Ziaqw4Uibblzz9FiaogAGs0kTPEw3IsYGrwibfcrXe6TAH2UJut54PAlbJWJsicl17ylCC4ZQDkC3wibA/0'];
        }
        $this->assign('user', $user);

        return $this->fetch();

    }

    public function fishing(){
        header('Content-type: application/json, charset=utf-8');
        $uid = session(config('USER_ID'));

        // sleep(3); //睡眠一段时间

        if(empty($uid)){
            echo json_encode(array('status'=>false, 'content'=>'没有登录'), JSON_UNESCAPED_UNICODE);
            die;
        }else{
            $mallConf = mallConfig();
            #获取用户的鱼饵和积分
            $user = Db::name('users') -> where(['id'=>$uid, 'status'=>1]) -> field(['bait', 'point']) -> find();
            if(empty($user)){
                echo json_encode(array('status'=>false, 'content'=>'用户数据错误'), JSON_UNESCAPED_UNICODE);
            }else{
                if( ($user['bait']<1) || ($user['point']<$mallConf['FISH_USE_POINT']['value'] ) ){
                    echo json_encode(array('status'=>false, 'content'=>'资产不足'), JSON_UNESCAPED_UNICODE);
                }else{
                    
                    # 计算钓上来的物品
                    $rand = rand(1,100);
                    if($rand<=$mallConf['FISH_POINT_PERCENT']['value']){//积分
                        // return '获得积分';
                        $data = [''];
                        echo json_encode(array('status'=>true, 'content'=>'获得积分'), JSON_UNESCAPED_UNICODE);

                    }else{ //红包+特殊物品
                        $rand = rand(1, 1000);
                        if($rand<=$mallConf['FISH_MOON_PERCENT']['value']){ //特殊物品 => 月亮
                            // return '获得月亮';
                            echo json_encode(array('status'=>true, 'content'=>'获得月亮'), JSON_UNESCAPED_UNICODE);


                        }else{
                            # 发放红包
                            echo json_encode(array('status'=>true, 'content'=>'获得红包'), JSON_UNESCAPED_UNICODE);
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

        
    }



}
