<?php
namespace app\index\controller;
use think\Controller;
use think\Config;
use think\Session;
use think\Cache;

class Register extends controller
{

    public function index(){


        $this->assign('header', ['title'=>'用户注册', 'loginbg'=>'']);
        return $this->fetch();
    }

    public function Register(){
        $code = 'fjw';
        $encode = authCode($code, 'ENCODE', $code);
        echo $encode.'<br>';
        $decode = authCode($encode, 'DECODE', $code);
        echo $decode.'<br>';
        // $data['password'] = cryptCode($code, 'ENCODE', $data['encrypt']);
        // db('users') -> where(['id'=>1]) -> update($data);
        // return dump($data);
    }
}
