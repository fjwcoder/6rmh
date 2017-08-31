<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-19.
# | 后台图标管理控制器
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

class Brand extends Manage
{
    public function index(){

        $navid = input('navid', 34, 'intval');
        $nav = adminNav();
        $keyword = input('keyword', '', 'htmlspecialchars,trim');
        if(!empty($keyword)){
            $where = array('name', 'like', "%$keyword%");
        }else{
            $where = '1=1';
        }

        
        $list = Db::name('mall_brand') ->alias('a') 
             -> join('mall_category b', 'a.catid=b.id', 'LEFT') 
             -> field(array('a.id', 'a.title', 'a.logo', 'b.title as category', 'a.status')) 
             -> where($where) -> order('a.id desc') 
             -> paginate();
        $this->assign('list', $list);
        $header =  ['title'=>'商品管理->商品配置->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
            'form'=>'list', 'navid'=>$navid ]; 
        $this->assign('header', $header);
        $this->assign('keyword', $keyword?$keyword:'');
        return $this->fetch();
    }


    public function add(){

        if(request()->isPost()){
            return $this->dataPost('add');
        }

        $catid = input('catid', 0, 'intval');//分类ID
        $navid = input('navid', 34, 'intval');
        $nav = adminNav();
        $list = db('mall_category', [], false) -> where(array('status'=>1)) ->order('id_list, sort') -> select();
        $this->assign('catlist', $list);

        $this->assign('header', ['title'=>'添加品牌', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('brand');
    }

    public function edit(){
        if(request()->isPost()){
            return $this->dataPost('edit');
        }
        $id = input('id', 0, 'intval');
        $navid = input('navid', 34, 'intval');
        $nav = adminNav();

        $list = db('mall_category', [], false) -> where(array('status'=>1)) ->order('id_list, sort') -> select();
        $this->assign('catlist', $list);

        $result = db('mall_brand', [], false) -> where(array('id'=>$id)) ->find();
        $this->assign('result', $result);
        $this->assign('header', ['title'=>'编辑品牌', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('brand');
    }
    public function dataPost($type){
        $post = request()->post();
        unset($post['navid']);
        foreach($post as $k=>$v){
            $data[$k] = $v;
        }

        if($type == 'add'){
            $check = db('mall_brand') -> where(array('title'=>$data['title'])) -> find(); 
            if($check){
                return $this->error('品牌已存在');
            }
            $result = db('mall_brand', [], false) -> insert($data);
        }else{
            $id = $data['id'];
            unset($data['id']);
            $check = db('mall_brand') -> where(array('title'=>$data['title'])) -> find(); 
            if($check){
                return $this->error('品牌已存在');
            }
            $result = db('mall_brand', [], false) -> where(array('id'=>$id)) ->update($data);
        }

        if($result){
            return $this->success('成功', request()->controller().'/index');
        }else{
            return $this->error('失败');
        }
    }

    


    public function del(){
        return 'del方法';
    }

    public function delPost(){

    }

}