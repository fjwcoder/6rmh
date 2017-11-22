<?php
# +-------------------------------------------------------------
# | CREATE by ZMX IN 2017-11-16.
# | 后台Comment控制器
# | 后台银行管理控制器
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



class Comment extends Manage
{
    public function index()
    {   
        $navid = input('navid', 59, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('goods_comment', [], false) ->order('id desc') -> paginate();
        
        $where = '(1=1) '; 
        $this->assign('key', ['status'=>false]);
        if(!empty($key)){
            $where .= " and gname LIKE '%$key%' ";
            $this->assign('key', ['status'=>true, 'key'=>$key]);
            $list = Db::name('goods_comment')->where($where)->order('addtime DESC')->paginate();
        }
        $this->assign('list', $list);  
        $header =  ['title'=>'商品管理->商品配置->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }
    
    #用户评论详情显示
    public function comdetail(){
        $navid = input('navid', 0, 'intval');
        $id = input('id', 0, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $comment = db('goods_comment', [], false) -> where(['id'=>$id]) -> find();
        $header =  ['title'=>'商品管理->商品配置->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'edit', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        $this->assign('comment', $comment);
        return $this->fetch();
    }

    // public function add(){
    //     if(request()->post()){
    //         return $this->dataPost('add');
    //     }
    //     $navid = input('navid', 59, 'intval');
    //     $nav = adminNav();
    //     $this->assign('header', ['title'=>'增加评论', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
    //     return $this->fetch('goods_comment');

    // }

    // public function edit(){
        
    //     if(request()->post()){
    //         return $this->dataPost('edit');
    //     }
    //     $navid = input('navid', 0, 'intval');
    //     $nav = adminNav();
    //     $id = input('id', 0, 'intval');
    //     $result = db('goods_comment', [], false) -> where(array('id'=>$id)) -> find();
    //     $this->assign('result', $result);
    //     $this->assign('header', ['title'=>'编辑评论:  【'.$result['bankname'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
    //     return $this->fetch('goods_comment');
    // }

    // public function del(){
    //     $id = Request::instance()->param('id');
    //     $rs = db('goods_comment')->where(array('id'=>$id))->delete();
    //     if ($rs) {
    //         $this->success('删除成功', "Comment/index");
    //     } else {
    //         $this->error('删除失败');
    //     }
    // }

    // public function dataPost($type=''){
    //     $post = request()->post();
    //     foreach($post as $k=>$v){
    //         $data[$k] = $v;
    //     }
        
    //     unset($data['navid']);
    //     if($type=='add'){

    //         $result = db('goods_comment', [], false) -> insert($data);
    //     }else{
    //         $id = $data['id'];
    //         unset($data['id']);
    //         $result = db('goods_comment', [], false) -> where(array('id'=>$id)) ->update($data);
    //     }

        
    //     if($result){
    //         // return $this->success('成功', "request()->controller()/index");
    //         return $this->success('成功', "Comment/index");
    //     }else{
    //         return $this->error('失败');
    //     }
    // }




}
