<?php
namespace app\index\controller;
use app\admin\controller\Wechat as Wechat;
use app\index\controller\Register as Register;
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
                $check = $this->checkUser($open_arr['openid']);

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


            $check = $this->checkUser('', $login);

            if(!$check['status']){
                return $this->error($check['content']); exit;
            }else{
                $user = $check['user'];
                
                Session::set(Config::get('USER_ID'), $user['id']);
                unset($user['password'], $user['encrypt'], $user['money'], $user['bait'], 
                    $user['point'], $user['pay_code'], $user['paycrypt']);
                encodeCookie($user, 'user'); //设置加密cookie
                #验证成功后，跳转
                return $this->redirect('/index/index/index');
            }
        }

        


    }

    # +-------------------------------------------------------------
    # | CREATE by FJW IN 2017-5-18.
    # | 验证用户信息:
    # | 首先通过用户名查找记录，如果存在，就用encrypt对密码进行解密
    # | 密码匹配，则登录成功
    # +-------------------------------------------------------------
    private function checkUser($openid='',  $login=[] ){
        if(!empty($login)){
            $user = db('users', [], false) -> where(array('name'=>$login['name'])) -> find();
            $type = 'login';
        }else{
            $user = db('users', [], false) -> where(['openid'=>$openid]) -> find();
            $type = 'wechat';
        }
        
        if($user){
            if($user['status'] != 1){
                return ['status'=>false, 'content'=>'用户已锁定, 不可登录']; exit;
            }
            $wechat = new Wechat();
            if($type==='login'){
                $password = cryptCode($login['password'], 'ENCODE', substr(md5($login['password']), 0, 4));
                if($password === $user['password']){
                    #验证用户的时效性信息(用户带场景值二维码)
                    $user = $wechat -> sceneQRCode($user['id'], $user);// 获取检测带场景值二维码后的用户信息

                    return ['status'=>true, 'content'=>'正确', 'user'=>$user]; exit;
                }else{
                    return ['status'=>false, 'content'=>'密码错误']; exit;
                }
            }elseif($type==='wechat'){
                #验证用户的时效性信息(用户带场景值二维码)
                $user = $wechat -> sceneQRCode($user['id'], $user);// 获取检测带场景值二维码后的用户信息

                return ['status'=>true, 'content'=>'正确', 'user'=>$user]; exit;
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

    public function test(){
        $user = '{"subscribe":1,"openid":"o0CVFxBGajko1pXxjpdByf0V1HUM","nickname":"9\uff1a30","sex":1,"language":"zh_CN","city":"\u9752\u5c9b","province":"\u5c71\u4e1c","country":"\u4e2d\u56fd","headimgurl":"http:\/\/wx.qlogo.cn\/mmopen\/Q3auHgzwzM4Ziaqw4Uibblzz9FiaogAGs0kTPEw3IsYGrwibfcrXe6TAH2UJut54PAlbJWJsicl17ylCC4ZQDkC3wibA\/0","subscribe_time":1508143259,"remark":"","groupid":0,"tagid_list":[]}';
        $user = json_decode($user, true);
        $param = '{"uid":"1","subscribe":"1","pid":"0"}';
        $param = json_decode($param, true);
        // dump($user);
        // return dump($param);
        $register = new Register();
        $user = $register -> scanQRCode($user, $param);
        return dump($user);

        // return dump(json_decode($json, true));
    }
}
