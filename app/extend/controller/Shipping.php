<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-9-18.
# | 快递鸟
# |
# |
# +-------------------------------------------------------------

// 应用公共文件
namespace app\common\controller;
use think\Controller;
use think\Config;
use think\Session;
use think\Cache;
use think\Request;
use think\Db;

#快递鸟API信息
defined('EBusinessID') or define('EBusinessID', '1304772');// add by fjw in 17.9.21
defined('AppKey') or define('AppKey', 'eebdc96c-5019-404c-8df2-863d94d28428'); // add by fjw in 17.9.21
//请求url
defined('RequestURL') or define('RequestURL', 'http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx');
class Shipping extends controller
{
    
	// $logistic: 快递单号； $order: 订单编号
	public function index($logistic, $order=''){
        $orderLogistic = json_decode($this->getOrderLogisticByJson($logistic), true);
		$traceLogistic = json_decode($this->getOrderTracesByJson($order, $orderLogistic['Shippers'][0]['ShipperCode'], $logistic), true);
        return $traceLogistic;
    }
	

	/**
    * Json方式 单号识别
    */
	public function getOrderLogisticByJson($logistic = ''){
		$requestData= "{'LogisticCode':'".$logistic."'}";
		$datas = array(
			'EBusinessID' => EBusinessID,
			'RequestType' => '2002',
			'RequestData' => urlencode($requestData) ,
			'DataType' => '2',
		);
		$datas['DataSign'] = $this->encrypt($requestData, AppKey);
		$result=$this->sendPost(ReqURL, $datas);	
		
		//根据公司业务处理返回的信息......
		
		return $result;
	}

	/**
    * Json方式 物流跟踪
    */
    public function getOrderTracesByJson($order = '', $shipper = '', $logistic = ''){
        $requestData= "{'OrderCode': '".$order."', 'ShipperCode': '".$shipper."','LogisticCode': '".$logistic."'}";
        $datas = array(
            'EBusinessID' => EBusinessID,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, AppKey);
        $result=$this->sendPost(ReqURL, $datas);	
        
        //根据公司业务处理返回的信息......
        
        return $result;
    }

	/**
    *  post提交数据 
    * @param  string $url 请求Url
    * @param  array $datas 提交的数据 
    * @return url响应返回的html
    */
    public function sendPost($url, $datas) {
        $temps = array();	
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);		
        }	
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;	
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);  
        
        return $gets;
    }

    /**
    * 电商Sign签名生成
    * @param data 内容   
    * @param appkey Appkey
    * @return DataSign签名
    */
    public function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }



}