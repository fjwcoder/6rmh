<?php
namespace app\index\controller;
use app\admin\controller\Wechat as Wechat;
use think\Controller;
use think\Session;
use think\Cookie;
use think\Config;
use think\Request;
use think\Db;
use think\Cache;

class Login extends controller
{

    public function index(){

        if(Session::get(Config::get('USER_ID'))){
            #验证成功后，跳转
            return $this->redirect('/index/index/index');
        }

        $this->assign('header', ['title'=>'用户登录', 'loginbg'=>'']);
        return $this->fetch();
    }

    public function login(){
        
        $wxcode = input('get.code', '', 'htmlspecialchars,trim');
        if(!empty($wxcode)){ 
            // return dump(request()->get());
            $redirect = input('get.redirect', '', 'htmlspecialchars,trim');

            // return $redirect;
            #微信登录
            $wechat = new Wechat();
            $wxconf = getWxConf();
            #==========静默授权方式=============
            $web_url = $wxconf['WEB_ACCESS_TOKEN']['value'].$wxconf['APPID']['value'].'&secret='.$wxconf['APPSECRET']['value'].'&code='.$wxcode.'&grant_type=authorization_code';;
            $open_res = httpsGet($web_url); //则本步骤中获取到网页授权access_token的同时，也获取到了openid，snsapi_base式的网页授权流程即到此为止。
            $open_arr = json_decode($open_res, true);
            if(!empty($open_arr['openid'])){
                $check = $this->checkOpenid($open_arr['openid']);

                if(!$check['status']){
                    return $this->error($check['content']); exit;
                }else{
                    $user = $check['user'];
                    Session::set(Config::get('USER_ID'), $user['id']);

                    #验证成功后，跳转
                    encodeCookie($user, 'user'); //设置加密cookie
                    return $this->redirect("/index/$redirect/index");
                }
            }else{
                return $this->redirect('/index/login/index');
            }
            #==========用户授权方式=============
            // return $web_url;


        }else{


            #账号登录
            $login['name'] = input('post.login.name', '', 'htmlspecialchars,trim'); //input = I;
            $login['password'] = input('post.login.password', '', 'htmlspecialchars,trim'); //input = I;
            if(empty($login['name'])){
                return $this->error('账号不可为空'); exit;
            }
            if(empty($login['password'])){
                return $this->error('密码不可为空'); exit;
            }


            $check = $this->checkUser($login);

            if(!$check['status']){
                return $this->error($check['content']); exit;
            }else{
                $user = $check['user'];
                Session::set(Config::get('USER_ID'), $user['id']);

                #验证成功后，跳转
                encodeCookie($user, 'user'); //设置加密cookie
                return $this->redirect('/index/index/index');
            }
        }

        


    }
    # +-------------------------------------------------------------
    # | CREATE by FJW IN 2017-5-18.
    # | 微信登录验证用户信息:
    # | 首先通过openid查找记录
    # | 匹配，则登录成功
    # +-------------------------------------------------------------
    private function checkOpenid($openid){
        $user = db('users', [], false) -> where(['openid'=>$openid]) -> find();
        if($user){
            if($user['status'] != 1){
                return ['status'=>false, 'content'=>'用户已锁定']; exit;
            }


            unset($user['password'], $user['encrypt'], $user['money'], $user['points']);
            return ['status'=>true, 'content'=>'正确', 'user'=>$user]; exit;
        }else{
            return ['status'=>false, 'content'=>'用户不存在']; exit;
        }
    }
    # +-------------------------------------------------------------
    # | CREATE by FJW IN 2017-5-18.
    # | 验证用户信息:
    # | 首先通过用户名查找记录，如果存在，就用encrypt对密码进行解密
    # | 密码匹配，则登录成功
    # +-------------------------------------------------------------
    private function checkUser($login){
        $user = db('users', [], false) -> where(array('name'=>$login['name'])) -> find();
        if($user){
            if($user['status'] != 1){
                return ['status'=>false, 'content'=>'用户已锁定']; exit;
            }

            $password = cryptCode($login['password'], 'ENCODE', substr(md5($login['password']), 0, 4));
            if($password === $user['password']){
                unset($user['password'], $user['encrypt'], $user['money'], $user['points']);
                return ['status'=>true, 'content'=>'正确', 'user'=>$user]; exit;
            }else{
                return ['status'=>false, 'content'=>'密码错误']; exit;
            }
        }else{
            return ['status'=>false, 'content'=>'用户不存在']; exit;
        }
    }

    #登出
    public function loginout(){
        session(null);
        return $this->redirect('/index/login/index');
    }
}
