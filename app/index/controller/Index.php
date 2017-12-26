<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Gaode as Gaode;
use app\extend\controller\Mall as Mall;
use app\index\controller\Share as Share;
use app\index\controller\Active as Active;
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

        if(isMobile()){ // 设置分享信息
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
        }
        $is_active = false;
        
        # 1. 查询活动
        $active = Db::name('active') -> where('status =1 and begin_time<'.time().' and end_time>'.time()) -> find();
        if(isset($active)){ // add by fjw in 17.12.24 增加活动商品
            $this->assign('active', $active);
            if(session(config('USER_ID'))){ // 登录了
                $user = Db::name('users') -> where(['id'=>session(config('USER_ID')), 'status'=>1]) -> find();
                if($user['isactive'] > 0){
                    $is_active = true;
                }
            }else{
                $is_active = true;
            }
        }

        if($is_active){ // 存在活动
            $term = getTerm();
            # 1.获取当前期的产品
            $goods = Db::name('term_goods') -> alias('a') 
                    -> join('goods b', 'a.gid=b.id', 'LEFT') 
                    -> join('active_detail c', 'a.gid=c.gid', 'LEFT')
                    -> field(['b.id, b.name', 'b.price','c.price as active_price', 'b.description', 'c.bait', 'c.point', 'b.img', 'c.gbegin_time', 'c.gend_time'])
                    -> where(['a.term'=>$term['id']]) -> select();

            if(isset($goods)){
                foreach($goods as $k=>$v){
                    if(empty($v['gbegin_time']) && empty($v['gend_time']) ){ //没设时间，默认活动中
                        $goods[$k]['price'] = $v['active_price'];
                    }else{
                        $time = time();
                        if( ($v['gbegin_time']<intval($time)) && ($v['gend_time']>intval($time)) ){

                            $goods[$k]['price'] = $v['active_price'];
                        }
                    }
                }

                $this->assign('goods', $goods);
            }
        }else{
            $goods = $this->termGoods();
            if($goods['status']){
                $this->assign('goods', $goods['goods']);
            }else{
                $this->assign('goods', []); 
            }
        }
        $this->assign('active', $is_active); //默认没有活动
        $config = mallConfig();
        $this->assign('config', ['page_title'=>$config['web_name']['value'], 'template'=>$config['mall_template']['value']
            ]);
        return $this->fetch($config['mall_template']['value']);
    }

    

    # 获取每期产品
    public function termGoods(){
        if(cache('TERM_GOODS')){
            $goods = cache('TERM_GOODS');
        }else{
            $term = getTerm();
            # 1.获取当前期的产品
            $goods = Db::name('term_goods') -> alias('a') 
                    -> join('goods b', 'a.gid=b.id', 'LEFT') 
                    -> field(['b.id, b.name', 'b.price', 'b.description', 'b.bait', 'b.point', 'img'])
                    -> where(['a.term'=>$term['id']]) -> select();
            
            if(isset($goods)){
                cache('TERM_GOODS', $goods);
            }
        }
        if(isset($goods)){
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
