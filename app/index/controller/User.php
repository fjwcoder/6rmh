<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\index\controller\Order as Order;
use app\index\controller\Announce as Announce;
use app\index\controller\Index as Index;
// use app\index\controller\Announce as Announce;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class User extends Common
{
    protected function _initialize(){
        if(empty(session('LOCATION'))){
            $gaode = new Gaode();
            $gaode->IPLocation();
        }

        #是否登录
        if( Session::get(Config::get('USER_ID')) ){
            //登陆后，每次跳转，都设置一下session，保持登录状态
            Session::set(Config::get('USER_ID'), Session::get(Config::get('USER_ID')));

            $id = session(config('USER_ID'));
            $user = Db::name('users') ->where(['id' =>$id]) ->find();
            
            unset($user['password'], $user['encrypt'], $user['pay_code'], $user['paycrypt']);

            encodeCookie($user, 'user'); //设置加密cookie
            $this->assign('cookie', decodecookie('user'));
        }else{
            session(null);
            return $this->redirect('/index/login/index');
            exit;
        }

    }

    public function index(){
        
        # 获取订单
        $orderObj = new Order();
        $where['a.userid'] = session(config('USER_ID'));
        $this->assign('order', $orderObj->getOrder($where, 0, 4));

        # 获取公告
        $announce = new Announce();
        $this->assign('announce', $announce->defaultAnnounce());
        // return dump($announce->defaultAnnounce());

        $index = new Index();
        $goods = $index->termGoods();
        $this->assign('goods', $goods);

        # 获取我的出售
        $mysell =  Db::name('inner_shop') ->alias('a')
            ->join('inner_goods b', 'a.type=b.id', 'LEFT')
            ->field('a.*, b.pic, b.title')
            ->where($where)
            ->order('addtime DESC')
            ->limit(3)
            ->select();

        if(!empty($mysell)){
            $this->assign('mysell', ['status'=>true, 'mysell'=>$mysell]);
        }else{
            $this->assign('mysell', ['status'=>false, 'mysell'=>'空空如也']);
        }


        $config = mallConfig();
        $this->assign('config', ['page_title'=>'用户中心', 'template'=>$config['mall_template']['value'] 
            ]);
        return $this->fetch();
    }

    public function userinfo(){
        $id = session(config('USER_ID'));
        
        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
        }
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'用户信息', 'template'=>$config['mall_template']['value'] 
            ]);

        $users = Db::name('users') ->where(['id' =>$id]) ->find();
        
        $this->assign('header', [ 'form'=>'user', 'id'=>$id ]);
        
        $this->assign('users', $users);
        return $this->fetch();
    }


    # |------------------------------------
    # | 重新加载用户信息，进入用户中心
    # | 
    # |
    # |------------------------------------
    public function headimgurl(){
        $id = session(config('USER_ID'));

        $user = Db::name('users') -> where(['id'=>$id, 'status'=>1]) -> find();
        unset($user['password'], $user['encrypt'], $user['money'], $user['bait'], 
                    $user['point'], $user['pay_code'], $user['paycrypt']);
        encodeCookie($user, 'user'); //设置加密cookie

        return $this->redirect('/index/user/index');
    }
    # |------------------------------------
    # | 修改用户信息模块
    # |
    # |
    # |------------------------------------

    public function editor(){
        // return dump(request()->post());
        $id = input('id',0,'intval');
        $data['nickname'] = input('nickname','','htmlspecialchars,trim');
        $data['realname'] = input('realname','','htmlspecialchars,trim');
        $data['sex'] = input('sex',0,'intval');
        $data['mobile'] = input('mobile','','htmlspecialchars,trim');
        $data['qq'] = input('qq',0,'intval');
        $data['email'] = input('email','','htmlspecialchars,trim');
        $data['id_num'] = input('id_num','','htmlspecialchars,trim');
        $data['birthday'] = input('birthday','','htmlspecialchars,trim');
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
            return $this->redirect('user/userinfo');
            // return $this->success('修改成功', 'User/index');    
        }else{
            return $this->error('修改失败');
        }
    }


    # |------------------------------------
    # | 修改密码模块
    # |
    # |
    # |------------------------------------

    public function passcode(){
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'用户中心', 'template'=>$config['mall_template']['value'] 
            ]);

        return $this->fetch('passcode');
    }

    # 修改密码
    public function password(){
        $id = session(config('USER_ID'));
        if(empty($_POST['oldpassword'])){
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

        $old_pwd = cryptCode($_POST['oldpassword'], 'ENCODE', substr(md5($_POST['oldpassword']), 0, 4));
        
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

    # 修改支付密码
    public function payword(){
        $id = session(config('USER_ID'));
        if(empty($_POST['oldpassword'])){
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

        $old_pwd = cryptCode($_POST['oldpassword'], 'ENCODE', substr(md5($_POST['oldpassword']), 0, 4));
        
        if($old_pwd !== $user['pay_code']){
            return $this->error('旧密码错误');
        }

        $data['paycrypt'] = substr(md5($_POST['pass']), 0, 4);
        $data['pay_code'] = cryptCode($_POST['pass'], 'ENCODE', $data['paycrypt']);
        $result = db('users', [], false) -> where(array('id'=>$id)) ->update($data);
        if($result){
            return $this->success('修改成功');
            // return $this->redirect('user/index');
        }else{
            return $this->error('修改失败');
        }
    }

    // public function refreshQRCode(){
    //     $get = request() ->get();
    //     return dump($get);
    // }

    // public function refreshWechat(){
    //     $get = request() ->get();
    //     return dump($get);
    // }

}
