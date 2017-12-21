<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Gaode as Gaode;
use app\extend\controller\Mall as Mall;
use app\index\controller\Share as Share;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Index extends controller
{
	
	
    public function index(){

        if(empty(session('LOCATION'))){
            $gaode = new Gaode();
            $gaode->IPLocation();
        }

        $isactive = $this->isActive();
        $this->assign('isactive', $isactive['isactive']);

        
        // 注意 URL 一定要动态获取，不能 handcode.!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $shareObj = new Share();
        $signPackage = $shareObj->shareConfig($url);
        $this->assign('shareconfig', $signPackage);

        $shareInfo = $shareObj->shareInfo($url);
        $this->assign('shareinfo', $shareInfo);
        $wxconf = getWxConf();
        $this->assign('wxconf', ['jsjdk'=>$wxconf['JSJDK_URL']['value']]);

        $goods = $this->termGoods();

        if($goods['status']){
            $this->assign('goods', $goods['goods']);
        }else{
            $this->assign('goods', []); 
        }
        
        
        

        $config = mallConfig();
        $this->assign('config', ['page_title'=>$config['web_name']['value'], 'template'=>$config['mall_template']['value']
            ]);
        return $this->fetch($config['mall_template']['value']);
    }

    // 是否在活动中
    public function isActive(){
        $active = Db::name('active') -> where('status =1 and begin_time<'.time().' and end_time>'.time()) -> find();
        if(!empty($active)){
            if(Session::get(Config::get('USER_ID'))){
                $user = Db::name('users') -> where(['id'=>Session::get(Config::get('USER_ID')), 'status'=>1])-> find();
            }
            if(!empty($user)){
                if($user['isactive'] == 1){
                    return ['isactive'=>true, 'user'=>$user];
                }else{
                    return ['isactive'=>false, 'user'=>$user];
                }
            }else{
                return ['isactive'=>true];
            }

        }else{
            if(Session::get(Config::get('USER_ID'))){
                $user = decodeCookie('user');
            }
            return ['isactive'=>false, 'user'=>$user];
        }
    }

    # 获取每期产品
    public function termGoods(){
        $term = getTerm();

        $goods = Db::name('term_goods') -> alias('a') 
            -> join('goods b', 'a.gid=b.id', 'LEFT') 
            // -> join('goods_spec c', 'a.gid=c.gid')
            -> field(['b.id, b.name', 'b.active_price', 'b.price', 'b.description', 'b.bait', 'b.point', 'img'])
            -> where(['a.term'=>$term['id']]) -> select();
        if($goods){
            return ['status'=>true, 'goods'=>$goods];
        }else{
            return ['status'=>false, 'goods'=>'空空如也'];
        }
    }
    
    #======================================================angularjs的$http========================================================================
    public function topInfo(){
        $config = mallConfig();
        

        if(Session::get(Config::get('USER_ID'))){ //如果登录了
            $user = decodeCookie('user');
            
            $data = [
                'left'=> [
                    ['title'=>empty($user['nickname'])?$user['name']:$user['nickname'], 'url'=>'/index/user/index', 'iconfont'=>''],
                    ['title'=>session('LOCATION.CITY'), 'url'=>'javascript: void(0);', 'iconfont'=>'fa-li fa fa-map-marker'],
                ]
            ];
        }else{
            $data = [
                'left' => [
                    ['title'=>'欢迎来到'.$config['web_name']['value'], 'url'=>'/index/index/index', 'iconfont'=>''], 
                    ['title'=>'欢迎登录', 'url'=>'/index/login/index', 'iconfont'=>''],
                    ['title'=>'免费注册', 'url'=>'/index/register/index', 'iconfont'=>''],
                    ['title'=>session('LOCATION.CITY'), 'url'=>'javascript: void(0);', 'iconfont'=>'fa-li fa fa-map-marker']
                ]
            ];
        }

        $data['right'] = [
            'index'=>['name'=>'index', 'title'=>'进入商城','url' => '/index/index/index', 'iconfont'=>'fa-li fa fa-user',  'target'=>'_blank'],
            'user'=>['name'=>'user', 'title'=>'会员中心','url'=> '/index/user/index', 'iconfont'=>'fa-li fa fa-user',  'target'=>'_blank'], // 
            'cart'=>['name'=>'cart',  'title'=>'购物车','url'=> '/index/cart/index', 'iconfont'=>'fa-li fa fa-heart',  'target'=>'_blank'], // 购物车
            'order'=>[ 'name'=>'order', 'title'=>'我的订单','url'=> '/index/order/index', 'iconfont'=>'fa-li fa fa-user',  'target'=>'_blank'],  // 我的订单
            'mobile'=>['name'=>'mobile', 'title'=>'手机商城', 'url' => '/index/login/mobilemall', 'iconfont'=>'fa-li fa fa-qrcode',  'target'=>'_blank'],  // 手机商城
            'logout'=>['name'=>'logout', 'title'=>'注销', 'url' => '/index/login/logout', 'iconfont'=>'fa-li fa fa-user', 'target'=>'']
        ];

        if(Session::get(Config::get('USER_ID'))){ //登录
            $data['show'] = true;
        }else{
            $data['show'] = false;
        }
        $data['right'] = array_reverse($data['right']);
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function footerInfo(){
        if(cache('FOOTER_INFO')){
            $data = cache('FOOTER_INFO');
        }else{
            $footer = db('web_info', [], false) -> where(array('type'=>'footer', 'status'=>1)) -> select();
            $data['footer'] = getField($footer);
            $data['company'] = $data['footer']['company_info']['value'];
            unset($data['footer']['company_info']);
            cache('FOOTER_INFO', $data); //缓存 注释
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
	
	
	public function wktest(){
		$postStr = file_get_contents('php://input');
		
		return $postStr;

	}
}
