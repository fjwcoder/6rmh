<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\admin\controller\Wechat as Wechat;
use think\Controller;
use think\Config;
use think\Session;
use think\Paginator;
use think\Db;

class Qrcode extends Common
{
	public function index(){
        $id = session(config('USER_ID'));
        $qrcode = db('users', [], false) ->field('openid, qr_code') -> where(array('id'=>$id)) ->find();

        $config = mallConfig();
        $this->assign('config', ['page_title'=>'二维码', 'template'=>$config['mall_template']['value'] 
            ]);
        $this->assign('qrcode', $qrcode);
        return $this->fetch();
        
    }

    #更新二维码
    public function editqrcode(){
        $id = session(config('USER_ID'));
        $user = db('users', [], false) -> where(array('id'=>$id)) ->find();
        $wechat = new Wechat();
        $user = $wechat -> sceneQRCode($user['id'], $user, true);// 获取检测带场景值二维码后的用户信息
        unset($user['password'], $user['encrypt'], $user['pay_code'], $user['paycrype']);
        encodecookie($user, 'user'); //绑定后，更新cookie
        return $this->redirect('index/qrcode/index');

    }

}
