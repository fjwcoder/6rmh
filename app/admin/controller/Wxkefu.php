<?php
namespace app\admin\controller;
use app\common\controller\Common; 
use app\admin\controller\Wechat as Wechat;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Wxkefu extends Common
{

    # 获取在线客服信息
    public function getOnlineKF(){
        $wechat = new Wechat();
        $wxconf = getWxconf();
        $url = $wxconf['GET_ONLINE_KEFU']['value'].$wechat->access_token();
        $response = httpsGet($url);
        return var_dump($response);
    }




}
