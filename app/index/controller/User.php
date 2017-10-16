<?php
namespace app\index\controller;
use app\common\controller\Common; 
// use app\common\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class User extends Common
{

    public function index(){
        $id = session(config('USER_ID'));
        
        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
        }
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'用户中心', 'template'=>$config['mall_template']['value'] 
            ]);

        $users = Db::name('users') ->where(['id' =>$id]) ->find();
        
        $this->assign('header', [ 'form'=>'user', 'id'=>$id ]);
        
        $this->assign('users', $users);
        return $this->fetch();
    }

    public function editor(){
        $id = input('id',0,'intval');
        $data['name'] = input('name','','htmlspecialchars,trim');
        $data['sex'] = input('sex',0,'intval');
        $data['mobile'] = input('mobile','','htmlspecialchars,trim');
        $data['qq'] = input('qq',0,'intval');
        $data['email'] = input('email','','htmlspecialchars,trim');
        #头像上传
        if(!empty($_FILES['headimg']['name'])){
            $upload = uploadHeadImg('images'.DS.'headimage');
            if($upload['status']){
                $data['headimgurl'] = $upload['path'][0];
            }else{
                return $this->error('头像上传失败');
            }
        }

        $res = Db::name('users') ->where(['id'=>$id]) ->update($data);
        if($res){
            return $this->success('修改成功', 'User/index');
        }else{
            return $this->error('修改失败');
        }
    }

    public function passcode(){
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'用户中心', 'template'=>$config['mall_template']['value'] 
            ]);

        return $this->fetch('passcode');
    }

    public function password(){
        $id = session(config('USER_ID'));
        if(empty($_POST['old-password'])){
            return $this->error('旧密码不可为空');
        }

        if(empty($_POST['pass'])){
            return $this->error('新密码不可为空');
        }

        if(empty($_POST['repass'])){
            return $this->error('重复密码不可为空');
        }

        if($_POST['pass'] !== $_POST['repass']){
            return $this->error('新密码输入不一致');
        }
        
        $user = getUserInfo('users', $id);

        $old_pwd = cryptCode($_POST['old-password'], 'ENCODE', substr(md5($_POST['old-password']), 0, 4));
        
        if($old_pwd !== $user['password']){
            return $this->error('旧密码错误');
        }

        $data['encrypt'] = substr(md5($_POST['pass']), 0, 4);
        $data['password'] = cryptCode($_POST['pass'], 'ENCODE', $data['encrypt']);
        $result = db('users', [], false) -> where(array('id'=>$id)) ->update($data);
        if($result){
            session(null);
            return msg('index/login/index', '修改成功，请重新登录');
        }else{
            return $this->error('修改失败');
        }
    }

    public function payword(){
        $id = session(config('USER_ID'));
        if(empty($_POST['old-password'])){
            return $this->error('旧密码不可为空');
        }

        if(empty($_POST['pass'])){
            return $this->error('新密码不可为空');
        }

        if(empty($_POST['repass'])){
            return $this->error('重复密码不可为空');
        }

        if($_POST['pass'] !== $_POST['repass']){
            return $this->error('新密码输入不一致');
        }
        
        $user = getUserInfo('users', $id);

        $old_pwd = cryptCode($_POST['old-password'], 'ENCODE', substr(md5($_POST['old-password']), 0, 4));
        
        if($old_pwd !== $user['pay_code']){
            return $this->error('旧密码错误');
        }

        $data['paycrypt'] = substr(md5($_POST['pass']), 0, 4);
        $data['pay_code'] = cryptCode($_POST['pass'], 'ENCODE', $data['paycrypt']);
        $result = db('users', [], false) -> where(array('id'=>$id)) ->update($data);
        if($result){
            return $this->success('修改成功');
        }else{
            return $this->error('修改失败');
        }
    }

    public function refreshQRCode(){
        $get = request() ->get();
        return dump($get);
    }

    public function refreshWechat(){
        $get = request() ->get();
        return dump($get);
    }

}
