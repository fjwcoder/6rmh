<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | 后台登录控制器
# |
# | email: fjwcoder@gmail.com
# +-------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Authority as Auth;
use think\Controller;
use think\Config;
use think\Session;
use think\Request;
class Login extends Controller
{
    public function index(){

        if(Session::get(Config::get('USER_KEY'))){
            return $this->redirect('/admin/index/index');
        }
        //每次到登录页面，都加载一次网站配置
        $config = webConfig();
        $this->assign('admin', ['title'=>$config['admin_title']['value']]);
        return $this->fetch();
    }

    //登录
    public function login(){

        $login['name'] = input('post.login.name', '', 'htmlspecialchars,trim'); //input = I;
        $login['password'] = input('post.login.password', '', 'htmlspecialchars,trim'); //input = I;
        $login['verify'] = input('post.login.verify', '', 'htmlspecialchars,trim'); //input = I;
        if(empty($login['name'])){
            return $this->error('账号不可为空'); exit;
        }
        if(empty($login['password'])){
            return $this->error('密码不可为空'); exit;
        }
        if(!captcha_check($login['verify'])){
            return $this->error('验证码错误！'); exit;
        }

        $check = $this->checkUser($login);
        if(!$check['status']){
            return $this->error($check['content']); exit;
        }else{
            $user = $check['user'];
            Session::set(Config::get('USER_KEY'), $user['id']);
            $auth = new Auth();
            $auth->setAdminInfo(true); //true 设置cookie

            #验证成功后，跳转
            return $this->redirect('/admin/index/index');
        }
    }

    # +-------------------------------------------------------------
    # | CREATE by FJW IN 2017-5-18.
    # | 验证用户信息:
    # | 首先通过用户名查找记录，如果存在，就用encrypt对密码进行解密
    # | 密码匹配，则登录成功
    # +-------------------------------------------------------------
    private function checkUser($login){
        $user = db('admin_member', [], false) -> where(array('name'=>$login['name'])) -> find();
        if($user){
            if($user['status'] != 1){
                return ['status'=>false, 'content'=>'用户已锁定']; exit;
            }

            $password = cryptCode($login['password'], 'ENCODE', substr(md5($login['password']), 0, 4));
            // $decryptPwd = cryptCode($user['password'], 'DECODE', $user['encrypt']);
            // if($decryptPwd === $login['password']){
            if($password === $user['password']){
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
        return $this->redirect('/admin/login/index');
    }



}