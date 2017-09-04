<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 商品分类
# |
# | email: fjwcoder@gmail.com
# +-------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Manage;
use app\extend\controller\Mall;
use think\Controller;
use think\Session;
use think\Cookie;
use think\Config;
use think\Request;
use think\Db;
use think\Cache;

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
        if(!empty($keyword)){
            //关键字的这个还没写好！！！！！！！
            $id_list = Db::name('mall_category') -> where('title', 'like', $keyword.'%') -> find();
            $cat_list = Db::name('mall_category') ->where('id_list', 'like', $id_list['id_list'].'%') -> select();
        }else{
            $cat_list = Db::name('mall_category') ->order('id_list', 'sort') -> select();
        }
        
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
                $html .= '<input class="cat-tree-input text-center" name="category['.$cat_list[$i]['id'].']" value="'.$cat_list[$i]['title'].'"/>';
                
                $html .= '<a class="operate-a " href="/admin/category/add/navid/32/id/'.$cat_list[$i]['id'].'" title="添加字分类"><i class="glyphicon glyphicon-plus-sign"></i></a>';
                $html .= '<a class="operate-a pull-right" href="/admin/category/edit/id/'.$cat_list[$i]['id'].'"  title="编辑"><i class="	glyphicon glyphicon-edit"></i></a>';
                $html .= '<a class="operate-a pull-right" href="/admin/category/status/id/'.$cat_list[$i]['id'].'"  ';
                if($cat_list[$i]['status'] == 1){
                    $html .= 'title="点击锁定" ><i class="	glyphicon glyphicon-eye-open"></i></a>';
                }else{
                    $html .= 'title="点击启用" ><i class="	glyphicon glyphicon-eye-close"></i></a>';
                }
                $html .= '</dt>';
                if($cat_list[$i]['isnode'] > 0){
                    $html .= '<dd id="collapse-'.$cat_list[$i]['id'].'" class="panel-collapse collapse in">';
                    $html .= '<dl class="panel-body">';
                }
                $this->treeBuilder($cat_list[$i]['id']);

                if($cat_list[$i]['isnode'] > 0){
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
        $pid = input('id', 0, 'intval'); //父类别的id
        $navid = input('navid', 32, 'intval');
        $nav = adminNav();

        $category = Db::name('mall_category') -> where(array('id'=>$pid)) -> find();
        if($category){
            $pid_list = $category['id_list'];
        }else{
            $pid_list = 0;
        }
        $this->assign('pid_list', $pid_list);

        $mallObj = new Mall();
        $this->assign('brand', $mallObj->getBrand());//品牌可以多个
        $this->assign('service', $mallObj->getService()); //服务可以有多个

        // $promotion = $mallObj->getPromotion(); //促销同一时间只能有一个    待开发
        $this->assign('sercheck', []);
        $this->assign('bracheck', []);

        $list = $mallObj->getCatetory();
        foreach($list as $k=>$v){
            $list[$k]['prex'] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $v['deep']);
        }
        $this->assign('list', $list);
        $this->assign('header', ['title'=>'添加分类', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('category');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $id = input('id', 0, 'intval');
        $navid = input('navid', 32, 'intval');
        $nav = adminNav();

        $category = Db::name('mall_category') -> where(array('id'=>$id)) -> find();

        if($category && $category['pid'] != 0){
            $pid_list = Db::name('mall_category') -> where(array('id'=>$category['pid'])) -> value('id_list');
        }else{
            $pid_list = 0;
        }
        $this->assign('pid_list', $pid_list);
        $this->assign('result', $category);

        $mallObj = new Mall();
        $this->assign('brand', $mallObj->getBrand());//品牌可以多个
        $this->assign('service', $mallObj->getService()); //服务可以有多个

        $this->assign('sercheck', explode(';', $category['service']));
        $this->assign('bracheck', explode(';', $category['brand']));

        $list = $mallObj->getCatetory();
        foreach($list as $k=>$v){
            $list[$k]['prex'] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $v['deep']);
        }
        $this->assign('list', $list);
        
        $this->assign('header', ['title'=>'编辑分类:  【'.$category['title'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('category');
    }

    public function dataPost($type=''){
        $post = request()->post();

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

        foreach($post as $k=>$v){
            $data[$k] = $v;
        }

        #处理服务和品牌
        if(!empty($data['services'])){
            $data['service'] = '';
            foreach($data['services'] as $k=>$v){
                $data['service'] .= $v.';';
            }
        }

        if(!empty($data['brands'] )){
            $data['brand'] = '';
            foreach($data['brands'] as $k=>$v){
                $data['brand'] .= $v.';';
            }
        }
        unset($data['services'], $data['brands'], $data['navid']);
        if($type=='add'){
            if($data['id_list'] == 0){ //顶级类别
                $data['pid'] = 0;
                $data['deep'] = 1;
                $data['id_list'] = '';
                $max = db('mall_category', [], false) ->where(array('pid'=>0, 'deep'=>1)) -> max('sort');
            }else{ 
                $id_list = explode(',', $data['id_list']);
                $data['pid'] = $id_list[count($id_list)-1];
                $data['deep'] = count($id_list)+1;
                $max = db('mall_category', [], false) -> where(array('pid'=>$data['pid'])) ->max('sort');
            }
            
            $data['sort'] = intval($max)+1;
            $data['addtime'] = intval(time());
            $data['adduser'] = Session::get(Config::get('ADMIN_AUTH_NAME'));

            //获取到自增ID
            $insert = Db::name('mall_category') -> insert($data); //有bug
            $id = Db::name('mall_category') ->getLastInsID();
            $result = db('mall_category', [], false) -> where(array('id'=>$id)) 
                -> update(['id_list'=>empty($data['id_list'])?strval($id):$data['id_list'].",$id"]);
            if($result && $data['pid']>0){
                $result = db('mall_category', [], false) -> where(array('id'=>$data['pid'])) -> setInc('isnode', 1);
            }
        }else{
            $id = $data['id'];
            unset($data['id']);

            //先把原来的父类别isnode-1
            $curr_cat = db('mall_category') -> where(['id'=>$id]) -> find();
            $sub_node = db('mall_category') -> where(['id'=>$curr_cat['pid']]) -> setDec('isnode', 1);

            if($data['id_list'] == 0){ //顶级类别
                $data['pid'] = 0;
                $data['deep'] = 1;
                $data['id_list'] = '';
                $max = db('mall_category', [], false) ->where(array('pid'=>0, 'deep'=>1)) -> max('sort');
            }else{
                $id_list = explode(',', $data['id_list']);
                $data['pid'] = $id_list[count($id_list)-1];
                $data['deep'] = count($id_list)+1;
                $max = db('mall_category', [], false) -> where(array('pid'=>$data['pid'])) ->max('sort');
            }
            $data['sort'] = intval($max)+1;
            $data['id_list'] = empty($data['id_list'])?strval($id):$data['id_list'].",$id";

            //这地方最好是写成事务
            $result = db('mall_category', [], false) -> where(array('id'=>$id)) -> update($data);
            if($result){
                $result = db('mall_category', [], false) -> where(array('id'=>$data['pid'])) -> setInc('isnode', 1);
                
                $sql = " update keep_mall_category set id_list = replace(id_list, '".$curr_cat['id_list']."', '".$data['id_list']."')";
                $sql .= " where id_list like '".$curr_cat['id_list']."%' ";
                Db::query($sql);
            }

        }

        if($result){
            return $this->success('成功', request()->controller().'/index');
        }else{
            return $this->error('失败');
        }
    }

    //修改状态
    public function status(){
        $id = input('id', 0, 'intval');
        
        $result = $this->changeStatus($id);
        // echo $result['content'];

        if($result['status']){

            return $this->success($result['content'], request()->controller().'/index');
        }else{
            return $this->error($result['content']);
        }
    }

    public function changeStatus($id=0){
        $cat = db('mall_category', [], false) -> where(array('id'=>$id)) -> find();

        if($cat['status'] == 1){

            $cat_tree = Db::name('mall_category') 
                -> where(" id_list='".$cat['id_list']."' or id_list like '".$cat['id_list'].",%'  ") 
                -> update(['status'=>2]);

            // $sql = "update ";

        }else{
            //由 锁定->启用 需要判断父级状态
            if($cat['pid'] != 0){
                $temp = explode(',', $cat['id_list']);
                unset($temp[count($temp)-1]);
                $in = '0';
                foreach($temp as $k=>$v){
                    $in .= ','.$v;
                }
                $status = Db::name('mall_category') -> where("id in ($in) and status=2") -> select();
                
                if($status){
                    return ['status'=>false, 'content'=>'父级锁定，不可启用']; exit;
                }
            }

            $cat_tree = Db::name('mall_category') 
                -> where(" id_list='".$cat['id_list']."' or id_list like '".$cat['id_list'].",%'  ") 
                -> update(['status'=>1]);
        }

        if($cat_tree){
            return ['status'=>true, 'content'=>'修改成功'];
        }else{
            return ['status'=>false, 'content'=>'修改失败'];
        }
    }


}
