<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | Mobile控制器公共函数库
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

#高德地图API信息
defined('API_NAME') or define('API_NAME', 'sixer_service');// add by fjw in 17.9.21
defined('API_KEY') or define('API_KEY', '69fbfd1764813d235b1eec67dad3caea'); // add by fjw in 17.9.21

define('IP_LOCATION', 'http://restapi.amap.com/v3/ip?ip=');
define('CURRENT_IP', '122.4.213.67');
class Gaode extends controller
{
    
	public function IPLocation(){
		$ip = clientIP();
		if($ip == '127.0.0.1'){
			$ip = CURRENT_IP;
		}
		$url = IP_LOCATION.$ip.'&output=JSON&key='.API_KEY;
		$output = httpsGet($url);
		$output = json_decode($output, true);
		if($output['status'] === '1' && $output['info'] === 'OK'){ //数据返回成功
			$rectangle = explode(';', $output['rectangle']);
			$location = [
				'PROVINCE' => $output['province'],
				'CITY'	=> $output['city'],
				'X'	=> $rectangle[0],
				'Y'	=> $rectangle[1]
			];

			session('LOCATION', $location);
		}else{
			session('LOCATION', config('LOCATION'));
		}
	}
	

	// public function getAPI(){



	// }

}