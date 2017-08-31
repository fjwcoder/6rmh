<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\common\controller\Web;
use think\Controller;
use think\Config;
use think\Session;

class User extends Common
{

    public function index(){
        return 'User控制器';
        if(Session::get(Config::get('USER_KEY'))){
            $user = decodeCookie('user');
        }
        
        // return dump($user);
        $mall_config = mallConfig();
        $this->assign('config', ['template'=>$mall_config['index_template']['value']
            ]);
        // $this->assign('user', ['']);
        return $this->fetch($mall_config['index_template']['value']);
    }

    public function topInfo(){
        $login = '/index/login/index';
        if(Session::get(Config::get('USER_KEY'))){
            $user = decodeCookie('user');
            $data = [
                'left'=> [
                    ['title'=>empty($user['realname'])?$user['name']:$user['realname'], 'url'=>'/index/center/index', 'iconfont'=>''],
                    ['title'=>'定位', 'url'=>'javascript: void(0);', 'iconfont'=>'fa-li fa fa-map-marker'],
                    // ['title'=>'注销', 'url'=>'javascript: void(0);', 'iconfont'=>'']
                ]
                // 'right'=> [
                //     'mobile' => '/index/mobile/index', 
                //     'order'=> '/index/order/index', 
                //     'collection'=> '/index/collection/index', 
                //     'center'=> '/index/center/index'
                // ]
            ];
        }else{
            $data = [
                'left' => [
                    ['title'=>'欢迎来到六耳猕猴网', 'url'=>'/index/index/index', 'iconfont'=>''], 
                    ['title'=>'欢迎登录', 'url'=>$login, 'iconfont'=>''],
                    ['title'=>'免费注册', 'url'=>'/index/register/index', 'iconfont'=>''],
                    ['title'=>'定位', 'url'=>'javascript: void(0);', 'iconfont'=>'fa-li fa fa-map-marker']
                ]
                // 'right' => [
                //     'mobile' => $login, 
                //     'order'=> $login, 
                //     'collection'=> $login, 
                //     'center'=> $login
                // ]
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
