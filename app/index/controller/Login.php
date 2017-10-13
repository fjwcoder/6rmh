<?php
namespace app\index\controller;
use app\admin\controller\Wechat as Wechat;
use think\Controller;
use think\Config;
use think\Session;
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
        #微信登录
        $wxcode = input('get.code', '', 'htmlspecialchars,trim');
        if(!empty($wxcode)){ 
            $wechat = new Wechat();
            #==========静默授权=============
            // $web_url = $wechat->getDef(WEB_AUTH).$wechat->getDef(APPID).'&secret='.$wechat->getDef(APPSECRET).'&code='.$wxcode.'&grant_type=authorization_code';;
            // $open_res = httpsGet($web_url);
            // $open_arr = json_decode($open_res, true);
            // $openid = $open_arr['openid'];

            #==========用户授权=============
            return $web_url;
        }

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
        // return dump($check);
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

    # +-------------------------------------------------------------
    # | CREATE by FJW IN 2017-5-18.
    # | 验证用户信息:
    # | 首先通过用户名查找记录，如果存在，就用encrypt对密码进行解密
    # | 密码匹配，则登录成功
    # +-------------------------------------------------------------
    private function checkUser($login){
        $user = db('users', [], false) -> where(array('name'=>$login['name'])) 
            // ->field(array('id', 'pid', 'name', 'realname', 'sex', 'mobile', 'qq', 'email', 'id_list', 'status'))
             -> find();
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
