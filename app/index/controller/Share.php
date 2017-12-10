<?php
namespace app\index\controller;
vendor('wxpay.WxPay#JsApiPay');
use app\admin\controller\Wechat as Wechat; 
// use app\extend\controller\Gaode as Gaode;
// use app\extend\controller\Mall as Mall;

use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Share extends controller
{
	
	
    public function index(){

    }
        //获取配置文件信息
    public function shareConfig($url=''){
        $wechatObj = new Wechat();
        $jsapiTicket = $wechatObj->jsapi_ticket();
        
       

        $timestamp = time();
        $nonceStr = \WxPayApi::getNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $wxconf = getWxConf();
        $signPackage = array(
            "appId"     => $wxconf['APPID']['value'],
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;

    }

    public function get_gameinfo(){
        $info = array(
            "title" => "飞赚钓鱼",
            "desc" => "《飞赚钓鱼》震撼来袭！休闲益智、快乐游戏，关注微信，坐拥五洋四海，做自己的波塞冬！游戏不仅可以体会到钓鱼寻宝的乐趣，还可以通过游戏交易来赚钱，游戏操作简洁，只需一指一键，即可畅玩《飞赚钓鱼》。",
            "imgUrl" => "http://www.5fz2.com/Public/Mobile/default/images/game_name.png",
            "link" => "http://www.5fz2.com",
        );
        return $info;
    }
    

}
