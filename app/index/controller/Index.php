<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Gaode as Gaode;
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Index extends controller
{

    public function index(){
        
        // $user = decodeCookie('user');
        // $id_list = explode(',', $user['id_list']);
        // return dump(array_reverse($id_list));

        if(empty(session('LOCATION'))){
            $gaode = new Gaode();
            $gaode->IPLocation();
        }

        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
        }
        $term = getTerm();

        $goods = Db::name('term_goods') -> alias('a') 
            -> join('goods b', 'a.gid=b.id', 'LEFT') 
            -> field(['b.id, b.name', 'b.price', 'b.description', 'b.bait', 'b.point', 'img'])
            -> where(['a.term'=>$term['id']]) -> select();
        if($goods){
            $this->assign('goods', $goods);
        }else{
            return '商品报错';
        }
        

        $config = mallConfig();
        $this->assign('config', ['page_title'=>$config['web_name']['value'], 'template'=>$config['mall_template']['value']
            ]);
        return $this->fetch($config['mall_template']['value']);
    }


    
    #======================================================angularjs的$http========================================================================
    public function topInfo(){
        $config = mallConfig();

        if(Session::get(Config::get('USER_ID'))){ //如果没有登录
            $user = decodeCookie('user');
            $data = [
                'left'=> [
                    ['title'=>empty($user['realname'])?$user['name']:$user['realname'], 'url'=>'/index/order/index', 'iconfont'=>''],
                    ['title'=>session('LOCATION.CITY'), 'url'=>'javascript: void(0);', 'iconfont'=>'fa-li fa fa-map-marker'],
                    // ['title'=>'注销', 'url'=>'javascript: void(0);', 'iconfont'=>'']
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
            'logout' => '/index/login/logout', // 注销
            'mobile' => '/index/login/mobilemall',  // 手机商城
            'order'=> '/index/order/index',  // 我的订单
            'cart'=> '/index/cart/index', // 购物车
            'user'=> '/index/order/index', // 
            'index' => '/index/index/index'
        ];

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

}
