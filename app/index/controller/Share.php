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

    public function shareInfo($url="http://www.6rmh.com", $desc="欢迎来到六耳猕猴商城",$imgUrl="http://www.6rmh.com/static/images/mall/share_img.png"){
        
        $info = array(
            "title" => "六耳猕猴商城",
            "desc" => $desc,
            "imgUrl" => $imgUrl,
            "link" => $url,
        );
        return $info;
    }
    

}
