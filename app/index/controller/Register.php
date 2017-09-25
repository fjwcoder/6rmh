<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Config;
use think\Session;
use think\Cache;

class Register extends controller
{

    public function index(){


        $this->assign('header', ['title'=>'用户注册', 'loginbg'=>'']);
        return $this->fetch();
    }
    #####处理注册提交##############
    ######2017-9-10 by ztf##############
    public function Register(){
        $phone = input('post.phone');
        $password = input('post.password');
        $verify_code = input('post.verify'); //验证码
        $v_phone = session('phone');
        $code = session('verify_code');

        if ($verify_code==$code && $phone==$v_phone) {
            #返回注册成功
            $password=cryptCode($password,'ENCODE',substr(md5($password), 0, 4));
            $data = ['name' => $phone,'mobile' => $phone, 'password' => $password];
            Db::table('keep_users')->insert($data);
            $this->success('注册成功！', 'User/index');
        }else{
            //返回注册失败
            $this->error('注册失败！', 'Register/index');
        }
    }
}
