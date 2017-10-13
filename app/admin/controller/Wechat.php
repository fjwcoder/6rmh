<?php
namespace app\admin\controller;
// defined('APPID') or define('APPID', 'wxa61ba5429b802e8f');
// defined('APPSECRET') or define('APPSECRET', '7e7e4f652449441dec476d2b99fa63ba');
// define('ACCESS_TOKEN', 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential');//公众号获取access_token
// define('USER_BASEINFO', 'https://api.weixin.qq.com/cgi-bin/user/info');//公众号获取用户详细信息
// define('MENU_URL', 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=');//自定义菜单
#define('REDIRECT_URL', 'http://www.ajconsulting.top');//定义网站地址
// define('OAUTHOR2_URL', 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=');//微信网页授权
// define('WEB_AUTH', 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=');
// define('WEB_AUTH_USERINFO', 'https://api.weixin.qq.com/sns/userinfo?access_token=');
// define('JSAPI_URL', 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=');//js_sdk url
// define('SUCAI_COUNT', 'https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=');//素材数量
// define('FOREVER_SUCAI', 'https://api.weixin.qq.com/cgi-bin/material/get_material?access_token='); //获取永久素材
// define('SUCAI_LIST', 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=');//素材列表
use app\common\controller\Common;
use think\Controller;
use think\Config;
use think\Session;

class Wechat extends Controller
{   
    
    #获取素材
    // public function sucaiList(){
    //     $token = $this->access_token();
    //     $url = SUCAI_LIST.$token;
    //     $comm = new Common();
    //     $data = '{
    //         "type":"news",
    //         "offset": 4,
    //         "count":1,
    //     }';
    //     $post = $comm -> https_post($url, $data);
    //     return $post;
    // }
    private function getMenu(){
        $menu_json = '{
            "button":[
                {
                    "name":"了解安进",
                    "sub_button":[
                        {
                            "type":"media_id",
                            "name":"安进简介",
                            "media_id":"h_axBe_dStGjHPGeaFsmtYGVsZDVCfBSMx_XDyajFGY"
                        },
                        {
                            "type":"media_id",
                            "name":"选择安进",
                            "media_id":"h_axBe_dStGjHPGeaFsmtWfhagcQiWP3n-gBdGn0Al4"
                        },
                    ]
                },
                {
                    "name":"业务领域",
                    "sub_button":[
                        {
                            "type":"media_id",
                            "name":"业务领域",
                            "media_id":"h_axBe_dStGjHPGeaFsmtbqyFgLcE4aoLG5UrN3TwU0"
                        },
                        {
                            "type":"media_id",
                            "name":"安进客户",
                            "media_id":"h_axBe_dStGjHPGeaFsmtZctTWyd8qPcqlwdFmf523s"
                        },
                    ]
                },
                {
                    "type":"view",
                    "name":"法务测评",
                    "url": "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa61ba5429b802e8f&redirect_uri=http%3A%2F%2Fwww.ajconsulting.top&response_type=code&scope=snsapi_base&state=1#wechat_redirect"
                }
        ]}';
        return $menu_json;
    }
    

    public function createMenu(){
		$access_token = $this -> access_token();
		$menu_url = MENU_URL.$access_token;
		$menu_res = $this -> https_post($menu_url, $this->getMenu());
        return $menu_res;
	}

    public function index(){
        if(!isset($_GET['echostr'])){
			$this -> responseMsg();
		}else{
			$this -> valid();//验证key
		}
    }

    public function valid()
    {
       
        $echoStr = $_GET['echostr'];
        if($this->checkSignature()){//调用验证签名checkSignature函数
        	echo $echoStr;
        	exit;
        }
    }

    //验证签名		
	private function checkSignature()
	{
        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}

    public function responseMsg()
	{
		
		$postStr = file_get_contents('php://input');
		if (!empty($postStr))
		{
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$RX_TYPE = trim($postObj -> MsgType);
			switch($RX_TYPE)
			{
				case 'event':
					$resultStr = $this -> handleEvent($postObj);
				break;
				case 'text':
					$resultStr = $this -> handleText($postObj);
				break;
				default:
					$resultStr = 'Unknow msg type: '.$RX_TYPE;
				break;
			}
			echo $resultStr;
		}else{
			echo "no user's post data";
		}
	}
    
    //获取access_token
    public function access_token() {
        $access_token = db("wechat_config", [], false) -> where(array("name"=>'ACCESS_TOKEN')) -> find();
        $access_token_value = json_decode( $access_token['value'], true);
        if( $access_token_value['stop_time'] < time() ){
            $url = ACCESS_TOKEN."&appid=".APPID."&secret=".APPSECRET;
            $get = $this->https_get($url);
            $access_token = json_decode($get, true); 
            if( !empty($access_token['access_token']) ){
                $value = array(
                    'access_token' => $access_token['access_token'],
                    'stop_time' => intval(time()+7000) //7000秒，7200是两个小时
                );
                db('wechat_config') -> where(array('name'=>'ACCESS_TOKEN')) -> update(['value'=>json_encode($value)]);
            }
            return $access_token['access_token'];
        }else{
            return $access_token_value['access_token'];
        }
    }
    
    //接收事件消息
    public function handleEvent($object){
        $openid = strval($object->FromUserName);
        $content = "";
        switch ($object->Event){
            case "subscribe":
                $access_token = $this->access_token();
                $user_url = USER_BASEINFO.'?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
                $user_res = $this->https_get($user_url);
                $user_arr = json_decode($user_res, true);//获取到的用户信息

                $content .= "欢迎关注 山东安进企业管理咨询有限公司";
                $content .= "\n\n详情请点击公众号底部菜单";
                $content .= "\n\n".'<a href="https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa61ba5429b802e8f&redirect_uri=http%3A%2F%2Fwww.ajconsulting.top&response_type=code&scope=snsapi_base&state=1#wechat_redirect">法务测评</a>';
            break;
            case "CLICK":
                switch($object->EventKey){
                    
                }
            case "VIEW":
                $content = "跳转链接 ".$object->EventKey;
            break;
            case "SCAN":
                $content = "扫描场景 ".$object->EventKey;
            break;
            case "LOCATION":
                $content = "上传位置：纬度 ".$object->Latitude.";经度 ".$object->Longitude;
            break;
            case "scancode_waitmsg":
                if ($object->ScanCodeInfo->ScanType == "qrcode"){
                    $content = "扫码带提示：类型 二维码 结果：".$object->ScanCodeInfo->ScanResult;
                }else if ($object->ScanCodeInfo->ScanType == "barcode"){
                    $codeinfo = explode(",",strval($object->ScanCodeInfo->ScanResult));
                    $codeValue = $codeinfo[1];
                    $content = "扫码带提示：类型 条形码 结果：".$codeValue;
                }else{
                    $content = "扫码带提示：类型 ".$object->ScanCodeInfo->ScanType." 结果：".$object->ScanCodeInfo->ScanResult;
                }
            break;
            case "scancode_push":
                $content = "扫码推事件";
            break;
            case "pic_sysphoto":
                $content = "系统拍照";
            break;
            case "pic_weixin":
                $content = "相册发图：数量 ".$object->SendPicsInfo->Count;
            break;
            case "pic_photo_or_album":
                $content = "拍照或者相册：数量 ".$object->SendPicsInfo->Count;
            break;
            case "location_select":
                $content = "发送位置：标签 ".$object->SendLocationInfo->Label;
            break;
            default:
                $content = "receive a new event: ".$object->Event;
            break;
        }
        if(is_array($content)){
            $result = $this->transmitNews($object, $content);
        }else{
            $result = $this->transmitText($object, $content);
        }
        return $result;
    }


    // public function getMediaId($content){
	// 	$find = M('config') -> where(array('remark'=>"$content")) -> find();
	// 	if(empty($find)){
	// 		return "";
	// 	}else{
	// 		$media_id = $find['tvalue'];
	// 		if(empty($media_id)){
	// 			$count_json = $this->https_get(SUCAI_COUNT.$this->access_token());
	// 			$count_arr = json_decode($count_json, true);//素材总数
	// 			$img_count = $count_arr['image_count'];

	// 			$post_arr = array("type"=>"image", "offset"=>0, "count"=>$img_count);
	// 			$post_json = json_encode($post_arr);
	// 			$url = SUCAI_LIST.$this->access_token();
	// 			$list_json = $this->https_post($url, $post_json);
	// 			$list_arr = json_decode($list_json, true);
	// 			\Think\Log::write(var_export($list_arr), true);//写入日志
	// 			foreach($list_arr as $v){
	// 				if($v['name'] === 'share.jpg' ){
	// 					$save_media_id = M("config") -> where(array("name"=>"SHARE_ID")) -> setField("tvalue", $v['media_id']);
	// 					if($save_media_id){
	// 						return $v['media_id'];
	// 						break;
	// 					}
	// 				}
	// 			}
	// 		}else{
	// 			return $media_id;
	// 		}
	// 	}
	// }

	//回复图文消息
    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        $itemTpl = "        <item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[news]]></MsgType>
			<ArticleCount>%s</ArticleCount>
			<Articles>$item_str</Articles>
		</xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }	

	//文本消息处理函数
	private function handleText($object)
	{
		// $openid = strval($object->FromUserName);
		// $content = "";
		// if(($object->Content) == '分享'){
		// 	$content = array();
		// 	$result = $this->transmitImage($object, $object->Content);
		// }else{
		// 	$result = $this->transmitText($object, "系统收到信息，客服人员正在处理，请稍后……");
		// }
        $result = $this->transmitText($object, "系统收到信息，客服人员正在处理，请稍后……");
		return $result;
	}
	
    //回复图片消息
	private function transmitImage($object, $content=''){
		if(!isset($object)){
			return "";
		}
		if(empty($content)){
			return "";
		}
		
		$media_id = $this->getMediaId($content);
		$xmlTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[image]]></MsgType>
						<Image>
							<MediaId><![CDATA[%s]]></MediaId>
						</Image>
				</xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $media_id);
		return $result;
	}

    //回复文本消息
    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)){
            return "";
        }
        
        $xmlTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[text]]></MsgType>
						<Content><![CDATA[%s]]></Content>
			       </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);

        return $result;
    }

    public function getDef($def){
        return $def;
    }

    // +----------------------------------------------------
    // | https请求：POST
    // |应用实例：1.微信
    // |
    // +----------------------------------------------------
	public function https_post($url, $data = null){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if(!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}

    // +----------------------------------------------------
    // |https请求：GET
    // |应用实例：1.微信
    // |
    // +----------------------------------------------------
	public function https_get($url){
		$oCurl = curl_init();
		if(stripos($url, 'https://')!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1);
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus['http_code'])==200){
			return $sContent;
		}else{
			return false;
		}
	}

}
