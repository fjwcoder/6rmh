<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Goods extends controller
{

    public function index(){
        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
        }
        $mallObj = new Mall();
        $gid = input('gid', 0, 'intval');
        $in = '(1,2)';
        $goods = $mallObj->getGoodsInfo($in);
        if($goods['status']){
            echo $goods['content'].'<br>';
            return dump($goods['data']);
        }else{
            return $goods['content'];
        }


        // $config = mallConfig();
        // $this->assign('config', ['template'=>$config['mall_template']['value']
        //     ]);
        return $this->fetch();
    }


















    #======================================================angularjs的$http========================================================================
    public function topInfo(){
        $config = mallConfig();

        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
            $data = [
                'left'=> [
                    ['title'=>empty($user['realname'])?$user['name']:$user['realname'], 'url'=>'/index/user/index', 'iconfont'=>''],
                    ['title'=>'定位', 'url'=>'javascript: void(0);', 'iconfont'=>'fa-li fa fa-map-marker'],
                    // ['title'=>'注销', 'url'=>'javascript: void(0);', 'iconfont'=>'']
                ]
            ];
        }else{
            $data = [
                'left' => [
                    ['title'=>'欢迎来到'.$config['web_name']['value'], 'url'=>'/index/index/index', 'iconfont'=>''], 
                    ['title'=>'欢迎登录', 'url'=>'/index/login/index', 'iconfont'=>''],
                    ['title'=>'免费注册', 'url'=>'/index/register/index', 'iconfont'=>''],
                    ['title'=>'定位', 'url'=>'javascript: void(0);', 'iconfont'=>'fa-li fa fa-map-marker']
                ]
            ];
        }
        $data['right'] = [
            'mobile' => '/index/mobile/index', 
            'order'=> '/index/order/index', 
            'collection'=> '/index/collection/index', 
            'user'=> '/index/user/index'
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
            // cache('FOOTER_INFO', $data); //缓存 注释
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

}
