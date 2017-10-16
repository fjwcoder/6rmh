<?php
namespace app\index\controller;
use app\admin\controller\Wechat as Wechat;
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
        $pid = 0;

        if ($verify_code==$code && $phone==$v_phone) {
            #返回注册成功
            $encrypt = substr(md5($password), 0, 4);
            $password=cryptCode($password,'ENCODE',  $encrypt);
            $data = ['pid'=>$pid, 'name' => $phone,'mobile' => $phone, 'password' => $password, 'encrypt'=>$encrypt, 'regtime'=>time(),
                'nickname'=>$phone, 'subscribe' =>2, 'qr_code'=>'', 'qr_seconds'=>0, 'qr_ticket'=>''

            ]; //修改 by fjw: 增加注册时间和个人二维码等字段
            $add = Db::name('users')->insert($data);
            $uid = Db::name('users') ->getLastInsID();
            if($uid>0){
                #生成自己的二维码
                $wechat = new Wechat();
                $ticket = $wechat -> sceneQRCode($uid, $data); //设置我的微信二维码
                $this->success('注册成功！', 'Login/index');
            }
        }else{
            //返回注册失败
            $this->error('注册失败！', 'Register/index');
        }
    }

    #扫描二维码(绑定微信/扫描推广码)
    public function scanQRCode($user, $param){
        
        $wechat = new Wechat();
        
        if($param['subscribe'] == 2){ //绑定微信  $param['id'] == $user['id']
            #1.检查该微信是否已经绑定
            $check = Db::name('users') -> where(['openid'=>$user['openid']]) -> find();
            if(!empty($check)){
                return ['status'=>false, 'content'=>"该微信已注册/绑定账号\n"]; exit;
            }

            $res = Db::name('users') ->where(['id'=>$param['uid']]) -> update($user);
            if($res){
                #修改我的二维码
                $user = $wechat->sceneQRCode($param['uid'], $user, true); //true强制更新
                encodecookie($user, 'user'); //绑定后，更新cookie
                $content = "尊敬的用户【".$user['nickname']."】: \n";
                $content .= "您已经成功绑定账号【".$user['mobile']."】\n";
                return ['status'=>true, 'content'=>$content];
            }else{
                return ['status'=>false, 'content'=>"微信绑定失败！请重新扫码\n"];
            }

        }else{ // 扫描二维码关注 $param['id'] == $user['pid']
            
            $res = $this->subscribe($user, $param['uid']);
            return $res;

        }
    }



    #微信关注(不带场景值) by fjw
    public function subscribe($user, $pid=0){
        
        if(!empty($user)){
            $openid = $user['openid'];
			
            $result = Db::name('users') -> where(['openid'=>$openid]) -> find();
            if(!empty($result)){ //已经关注过

                $res = Db::name('users') ->where(['openid'=>$openid]) -> update($user);
                
                $content = "欢迎回来，尊敬的【".$user['nickname']."】: \n";
                $content .= "您的用户信息已更新为最新的！\n";
                return ['status'=>true, 'content'=>$content];

            }else{ //第一次关注

                #生成账号
                $user['pid'] = $pid;
                if($pid != 0){ //更新用户链
                    $puser = Db::name('users') ->where(['id'=>$pid]) -> find();
                }
                $user['name'] = time();
                $user['mobile'] = $user['name'];
                $password = substr($user['name'], -6);
                $user['encrypt'] = substr(md5($password), 0, 4);
                $user['password'] = cryptCode($password, 'ENCODE',  $user['encrypt']);
                $user['regtime'] = intval($user['name']);
                
                $add = Db::name('users') -> insert($user);
                $uid = Db::name('users') ->getLastInsID();
                if($uid>0){
                    #更新用户链
                    $id_list = isset($puser)?$puser['id_list'].','.$uid:$uid;
                    $uplist = Db::name('users') -> where(['id'=>$uid]) -> update(['id_list'=>$id_list]);
                    #生成自己的二维码
                    $wechat = new Wechat();
                    $user['qr_code'] = '';
                    $user['qr_seconds'] = 0;
                    $user['qr_ticket'] = '';

                    $user = $wechat -> sceneQRCode($uid, $user); //获取我的二维码、返回用户信息

                    $content = "您已成功注册成为商城用户\n";
                    $content .= "您的初始登录账号为：".$user['name']."\n";
                    $content .= '您的初始登录密码为：'.$password."\n";

                    return ['status'=>true, 'content'=>$content];
                }else{
                    return ['status'=>false, 'content'=>"注册失败\n"];
                }
            }
        }else{
            return ['status'=>false, 'content'=>"参数为空\n"];
        }
    }

    #微信取消关注
    public function unSubscribe(){

    }
}
