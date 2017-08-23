<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 后台管理员
# |
# | email: fjwcoder@gmail.com
# +-------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Manage;
use app\common\controller\Common;
use think\Controller;
use think\Session;
use think\Cookie;
use think\Config;
use think\Request;
use think\Db;
use think\Cache;
// use think\Paginator;

#+-----------------------------------
#| navid 当前页面id
#|
#+-----------------------------------
class Goods extends Manage
{
    // dump(request()->module());//模块
    // dump(request()->controller()); //控制器
    // dump(request()->action()); //方法
    // private $module = '';
    // private $controller = '';

    // public function _initialize(){
    //     $this->module = request()->module();
    //     $this->controller = request()->controller();
    // }
    #用户列表
    public function index()
    {   
        $navid = input('navid', 8, 'intval');
        $nav = adminNav();
        $keyword = input('post.keyword', '', 'htmlspecialchars,trim');
        
        // if(!empty($keyword)){
        //     $where = ['title', 'like', "%$keyword%"];
        // } 
        // $list = db('admin_member', [], false) ->where($where) -> field('password, encrypt', true) -> paginate(15);
        $user = getUserInfo('admin_member', Session::get(Config::get('USER_KEY')));
        $where = "a.level>=$user[level]";
        if($user['branch']>0){
            $where .= " and a.branch=$user[branch] ";
        }
        $list = Db::table('keep_admin_member') ->alias('a')
         -> join('keep_admin_branch b', 'a.branch=b.id', 'LEFT')
         -> join('keep_admin_level c', 'a.level=c.id', 'LEFT')
         -> where($where)
         -> field(array('a.id', 'a.name', 'a.title', 'a.email', 'a.authority', 'a.status', 'a.headimg', 'b.title as branch', 'c.title as level')) -> paginate(15);

        $this->assign('list', $list); 
        $header =  ['title'=>'扩展管理->后台用户->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
            'form'=>'list', 'navid'=>$navid ]; 
        $this->assign('header', $header);
        $this->assign('keyword', $keyword?$keyword:'');
        return $this->fetch();
    }
    
    public function add(){
        if(request()->post()){
            return $this->dataPost('add');
        }
        $navid = input('navid', 8, 'intval');
        $nav = adminNav();
        $this->assign('branch', getAdminBranch());
        $this->assign('level', getAdminLevel());
        $this->assign('header', ['title'=>'增加用户', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('member');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $navid = input('navid', 0, 'intval');
        $nav = adminNav();
        if( ($navid>0) && ($navid==9) ){
            $userid = Session::get(Config::get('USER_KEY'));
        }else{
            $userid = input('id', 0, 'intval');
        }
        if($userid>0){
            $user = getUserInfo('admin_member', $userid);
            if($user){
                $this->assign('result', $user);
            }else{
                return $this->error('未找到该用户');
            }
            
        }

        $this->assign('branch', getAdminBranch());
        $this->assign('level', getAdminLevel());
        $this->assign('header', ['title'=>'编辑用户:  【'.$user['title'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('member');
    }

    public function dataPost($type=''){
        $post = request()->post();
        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        unset($data['navid']);
        
        if($type=='add'){

            $check = db('admin_member') -> where(array('name'=>$data['name'])) -> find();
            if($check){
                return $this->error('账号已存在');
            }
            $data['encrypt'] = substr(md5($data['password']), 0, 4);
            $data['password'] = cryptCode($data['password'], 'ENCODE', $data['encrypt']);
            $data['addtime'] = time();
            $data['adduser'] = Session::get(Config::get('ADMIN_AUTH_NAME'));

            $result = db('admin_member', [], false) -> insert($data);
        }else{
            $id = $data['id'];
            #头像上传
            if(!empty($_FILES)){
                $upload = uploadImg('images'.DS.'headimg');
                // return dump($upload);
                if($upload['status']){
                    $data['headimg'] = $upload['path'];
                }else{
                    return $this->error('头像上传失败');
                }
            }

            unset($data['id'], $data['password'] );
            $result = db('admin_member', [], false) -> where(array('id'=>$id)) ->update($data);
        }

        
        if($result){
            session('user', null);
            return $this->success('成功', request()->controller().'/index');
        }else{
            return $this->error('失败');
        }
    }

    #用户节点权限设置
    public function auth(){
        if(request()->post()){
            return $this->authPost();
        }

        $navid = input('navid', 0, 'intval');
        $nav = adminNav();
        $user = getUserInfo('admin_member', Session::get(Config::get('USER_KEY')));
        $this->assign('user', $user);
        $this->assign('header', ['title'=>'扩展管理->后台用户->'.$nav[$navid]['title'].' 【'.$user['title'].'】', 'form'=>'passCode', 'icon'=>$nav[$navid]['icon'], 'navid'=>$navid]);
        return $this->fetch('passcode');
    }



    #修改密码
    public function passCode(){
        if(request()->post()){
            return $this->passPost();
        }
        $navid = input('navid', 0, 'intval');
        $nav = adminNav();
        $user = getUserInfo('admin_member', Session::get(Config::get('USER_KEY')));
        $this->assign('user', $user);
        $this->assign('header', ['title'=>'扩展管理->后台用户->'.$nav[$navid]['title'].' 【'.$user['title'].'】', 'form'=>'passCode', 'icon'=>$nav[$navid]['icon'], 'navid'=>$navid]);
        return $this->fetch('passcode');
    }

    public function passPost(){
        $post = request() -> post();

        // return dump($post);

        $id = Session::get(Config::get('USER_KEY'));
        if(empty($post['old-password'])){
            return $this->error('旧密码不可为空');
        }

        if(empty($post['password0'])){
            return $this->error('新密码不可为空');
        }

        if(empty($post['password1'])){
            return $this->error('重复密码不可为空');
        }

        if($post['password0'] !== $post['password1']){
            return $this->error('新密码输入不一致');
        }
        
        $user = getUserInfo('admin_member', $id);
        // $old_pwd = cryptCode($user['password'], 'DECODE', $user['encrypt']);
        $old_pwd = cryptCode($post['old-password'], 'ENCODE', substr(md5($post['old-password']), 0, 4));
        // if($post['old-password'] !== $old_pwd){
        if($old_pwd !== $user['password']){
            return $this->error('旧密码错误');
        }

        $data['encrypt'] = substr(md5($post['password0']), 0, 4);
        $data['password'] = cryptCode($post['password0'], 'ENCODE', $data['encrypt']);
        $result = db('admin_member', [], false) -> where(array('id'=>$id)) ->update($data);
        if($result){
            session(null);
            return msg('admin/login/index', '修改成功，请重新登录', 'iframe');
        }else{
            return $this->error('修改失败');
        }
    }
}
