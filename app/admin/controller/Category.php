<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 商品分类
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
use app\admin\model\GoodsCategory;
// use think\Paginator;

#+-----------------------------------
#| navid 当前页面id
#|
#+-----------------------------------
class Category extends Manage
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
        global $html, $cat_list;
        $navid = input('navid', 32, 'intval');
        $nav = adminNav();
        $keyword = input('post.keyword', '', 'htmlspecialchars,trim');
        // if(!empty($keyword)){
        //     $where = ['title', 'like', "%$keyword%"];
        // } 

        // $list = db('goods_category', [], false)  -> paginate(Config::get('TABLE_LIST_NUM'));
        // $this->assign('list', $list); 
        $cat_list = db('goods_category', [], false) -> select();
        if(empty($cat_list)){
            $this->assign('list_tree', '<h3>尚无分类信息</h3>');
        }else{
            $html = '';
            $this->treeBuilder(0);
            $html .= '';
            $this->assign('list_tree', $html);
        }
        
        $header =  ['title'=>'商品管理->商品配置->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
            'form'=>'list', 'navid'=>$navid ]; 
        $this->assign('header', $header);
        $this->assign('keyword', $keyword?$keyword:'');
        return $this->fetch();
    }
    
    public function treeBuilder($fid=0){
        global $html, $cat_list;
        for($i=0; $i<count($cat_list); $i++){
            if($cat_list[$i]['pid'] == $fid){
                $html .= '<dt>';
                $html .= '<a class="operate-a" data-toggle="collapse" data-parent="#accordion"  href="#collapse-'.$cat_list[$i]['id'].'"><i class="glyphicon-plus"></i></a>';
                $html .= $cat_list[$i]['title'];
                $html .= '<a class="operate-a" href="/admin/category/add/navid/32" title="添加分类"><i class="glyphicon glyphicon-plus-sign"></i></a>';
                $html .= '<a class="operate-a" href="javascript: void(0);"  title="编辑"><i class="	glyphicon glyphicon-edit"></i></a>';
                $html .= '<a class="operate-a" href="javascript: void(0);"  title="锁定"><i class="	glyphicon glyphicon-eye-open"></i></a>';
                $html .= '</dt>';
                if($cat_list[$i]['isnode'] == 1){
                    $html .= '<dd id="collapse-'.$cat_list[$i]['id'].'" class="panel-collapse collapse in">';
                    $html .= '<dl class="panel-body">';
                }
                $this->treeBuilder($cat_list[$i]['id']);

                if($cat_list[$i]['isnode'] == 1){
                    $html .= '</dl>';
                    $html .= '</dd>';
                }
            }
        }
            
        
    }

    public function add(){
        if(request()->post()){
            return $this->dataPost('add');
        }
        $navid = input('navid', 32, 'intval');
        $nav = adminNav();
        $list = db('goods_category', [], false) -> where(array('status'=>1)) ->order('id_list, sort') -> select();
        $this->assign('list', $list);
        $this->assign('header', ['title'=>'添加分类', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('category');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $id = input('id', 0, 'intval');
        $category = db('goods_category', [], false) -> where(array('id'=>$id)) -> find();
        $navid = input('navid', 32, 'intval');
        $nav = adminNav();
        $list = db('goods_category', [], false) -> where(array('status'=>1)) ->order('sort') -> select();
        $this->assign('list', $list);
        $this->assign('result', $category);
        $this->assign('header', ['title'=>'编辑分类:  【'.$category['title'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('category');
    }

    public function dataPost($type=''){
        $post = request()->post();
        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        unset($data['navid']);
        if($type=='add'){
            if($data['id_list'] == 0){ //顶级类别
                $data['pid'] = 0;
                $data['level'] = 1;
                $data['id_list'] = '';
                $max = db('goods_category', [], false) ->where(array('pid'=>0, 'level'=>1)) -> max('sort');
            }else{ 
                $id_list = explode(',', $data['id_list']);
                $data['pid'] = $id_list[count($id_list)-1];
                $data['level'] = count($id_list)+1;
                $max = db('goods_category', [], false) -> where(array('pid'=>$data['pid'])) ->max('sort');
            }
            $data['sort'] = intval($max)+1;
            $data['addtime'] = intval(time());
            $data['adduser'] = Session::get(Config::get('ADMIN_AUTH_NAME'));
            //获取到自增ID
            $insert = Db::name('goods_category') -> insert($data); //有bug
            $id = Db::name('goods_category') ->getLastInsID();
            $result = db('goods_category', [], false) -> where(array('id'=>$id)) 
                -> update(['id_list'=>empty($data['id_list'])?strval($id):$data['id_list'].",$id"]);

        }else{
            $id = $data['id'];
            unset($data['id']);
            if($data['id_list'] == 0){ //顶级类别
                $data['pid'] = 0;
                $data['level'] = 1;
                $data['id_list'] = '';
                $max = db('goods_category', [], false) ->where(array('pid'=>0, 'level'=>1)) -> max('sort');
            }else{
                $id_list = explode(',', $data['id_list']);
                $data['pid'] = $id_list[count($id_list)-1];
                $data['level'] = count($id_list)+1;
                $max = db('goods_category', [], false) -> where(array('pid'=>$data['pid'])) ->max('sort');
            }
            $data['sort'] = intval($max)+1;

            $result = db('goods_category', [], false) -> where(array('id'=>$id)) 
                -> update(['id_list'=>empty($data['id_list'])?strval($id):$data['id_list'].",$id"]);

            
            // #类别图片上传
            // if(!empty($_FILES)){
            //     $upload = uploadImg('images'.DS.'headimg');
            //     // return dump($upload);
            //     if($upload['status']){
            //         $data['headimg'] = $upload['path'];
            //     }else{
            //         return $this->error('头像上传失败');
            //     }
            // }
        }

        
        if($result){
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

        $navid = input('navid', 32, 'intval');
        $nav = adminNav();
        $user = getUserInfo('goods_category', Session::get(Config::get('USER_KEY')));
        $this->assign('user', $user);
        $this->assign('header', ['title'=>'扩展管理->后台用户->'.$nav[$navid]['title'].' 【'.$user['title'].'】', 'form'=>'passCode', 'icon'=>$nav[$navid]['icon'], 'navid'=>$navid]);
        return $this->fetch('passcode');
    }



    #修改密码
    public function passCode(){
        if(request()->post()){
            return $this->passPost();
        }
        $navid = input('navid', 32, 'intval');
        $nav = adminNav();
        $user = getUserInfo('goods_category', Session::get(Config::get('USER_KEY')));
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
        
        $user = getUserInfo('goods_category', $id);
        // $old_pwd = cryptCode($user['password'], 'DECODE', $user['encrypt']);
        $old_pwd = cryptCode($post['old-password'], 'ENCODE', substr(md5($post['old-password']), 0, 4));
        // if($post['old-password'] !== $old_pwd){
        if($old_pwd !== $user['password']){
            return $this->error('旧密码错误');
        }

        $data['encrypt'] = substr(md5($post['password0']), 0, 4);
        $data['password'] = cryptCode($post['password0'], 'ENCODE', $data['encrypt']);
        $result = db('goods_category', [], false) -> where(array('id'=>$id)) ->update($data);
        if($result){
            session(null);
            return msg('admin/login/index', '修改成功，请重新登录', 'iframe');
        }else{
            return $this->error('修改失败');
        }
    }
}
