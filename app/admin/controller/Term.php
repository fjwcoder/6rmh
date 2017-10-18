<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 前台商城配置控制器
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


class Term extends Manage
{
    public function index()
    {   

        $navid = input('navid', 44, 'intval');
        $config = mallConfig();
        $keyword = input('post.keyword', '', 'htmlspecialchars,trim');

        $term = Db::name('term') -> order('addtime desc') -> paginate();
        $this->assign('list', $term);
        $this->assign('config', $config);
        $this->assign('header', ['icon'=>'glyphicon-cog','title'=>'系统配置->系统配置->分期配置', 
        'form'=>'list', 'navid'=>$navid]);

        $this->assign('keyword', $keyword?$keyword:'');
        return $this->fetch();
    }
    



    public function add(){

        if(request()->post()){
            return $this->dataPost('add');
        }
        $navid = input('navid', 44, 'intval');
        $this->assign('header', ['icon'=>'glyphicon-cog','title'=>'系统配置->系统配置->添加分期', 
        'form'=>'add', 'navid'=>$navid
        ]);
        #查出上期结束时间
        $last = Db::name('term') -> order('addtime desc') -> limit(1) -> find();
        $this->assign('last', $last);
        return $this->fetch('term');
    }


    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }

        $id = input('id', 0, 'intval'); //期数
        $term = Db::name('term') -> where(['id'=>$id]) -> find();
        // $term['begintime'] = date('Y-m-d', $term['begintime']);
        // $term['endtime'] = date('Y-m-d', $term['endtime']);
        $term['termtime'] = date('Y-m-d', $term['begintime']).' - '.date('Y-m-d', $term['endtime']);
        $goods = Db::name('term_goods') -> alias('a') 
            -> join('goods b', 'a.gid=b.id', 'LEFT') 
            -> field(['b.id', 'b.userid', 'b.name', 'b.sub_name', 'b.brand', 'b.promotion', 'b.price', 'b.img']) 
            -> where(['a.term'=>$id]) 
            -> select();
        // return dump($term);
        $this->assign('result', $term);
        $this->assign('goods', $goods);
        
        $navid = input('navid', 44, 'intval');
        $this ->assign('id', $id);
        $this->assign('header', ['icon'=>'glyphicon-cog','title'=>'系统配置->系统配置->编辑分期', 
        'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('term');
    }

    public function dataPost($type){
        $post = request() -> post();
        $date = explode(' - ', $post['termtime']);
        // return dump($date);
        if(empty($date[0])){
            return $this->error('起始时间不可为空');
        }
        if(empty($date[1])){
            return $this->error('结束时间不可为空');
        }
        unset($post['navid'], $post['termtime']);
        foreach($post as $k=>$v){
            $data[$k] = $v;
        }

        // return dump($data);
        #处理该期的时间
        $data['begintime'] = strtotime($date[0]);
        $data['endtime'] = intval(strtotime($date[1])+3600*24-1); //取到结束时间当天的最后一秒
        
        if($data['begintime'] <= time()){
            return $this->error('起始时间错误');
        }
        if($data['endtime'] <= $data['begintime']){
            return $this->error('结束时间错误');
        }
        #查出上一期的结束时间
        $last = Db::name('term') -> order('addtime desc') ->limit(1) -> find();
        if($last['endtime'] >= $data['begintime']){
            return $this->error('起始时间错误');
        }
        
        if(!empty($_FILES['pic']['name'])){
            $upload = uploadHeadImg('mall'.DS.'term');
            if($upload['status'] == false){
                return $this->error('图片上传失败！'); exit;
            }
        }

        $data['pic'] = isset($upload['path'])?$upload['path'][0]:'';
        
        // return dump($data);
        if($type == 'add'){
            $data['addname'] = session(config('ADMIN_AUTH_NAME'));
            $result = Db::name('term') -> insert($data);
            if($result){
                return $this->success('添加成功', request()->controller().'/index');
            }
        }else{
            $id = $data['id'];
            unset($data['title'], $data['id']);
            if(empty($pic)){
                unset($data['pic']);
            }
            $update = Db::name('term') -> where(['id'=>$id]) -> update($data);
            return $this->success('添加成功', request()->controller().'/index');
        }
        

    }


    //增加本期商品
    public function addGoods(){
        $id = input('id', 0, 'intval');
        $navid = input('navid', 44, 'intval');
        $keyword = input('post.keyword', '', 'htmlspecialchars,trim');
        // dump($id);die;
        $goods = db('goods', [], false) ->field('name,id') ->select();
        $this->assign('goods', $goods);
        $this->assign('term', $id);
        $this->assign('keyword', $keyword?$keyword:'');
        $this->assign('header', ['icon'=>'glyphicon-cog','title'=>'系统配置->系统配置->添加商品', 
        'form'=>'list', 'navid'=>$navid]);
        return $this->fetch('add');
    }

    public function addgoodsname(){
        $post = request()->post();
        unset($post['navid']);
        $id_list = Db::name('term_goods') -> where(['term'=>$post['term']]) -> field('gid') -> select();   
        
        foreach($post['config'] as $array){
            foreach($id_list as $secondarray){
                if($array==$secondarray){
                    return $this->error('商品已存在'); 
                }
                
            }
        }
        $count = Db::name('term_goods') ->where(['term'=>$post['term']]) ->count();   
        foreach($post['config'] as $k=>$v){
            $data[$k] = $v;
            $data[$k]['term'] = $post['term'];
        }
        // dump($data);die;
        
        if($count <6 && count($data) + $count < 7){
            $result = Db::name('term_goods') -> insertAll($data);
            if($result){
                return $this->success('添加成功', request()->controller().'/index');
            }else{
                return $this->error('添加失败');
            }

        }else{
            return $this->error('最多添加6个商品');
        }
    
    }

    // public function test(){
        
    //     $id_list = Db::name('term_goods') -> where(['term'=>2]) -> field('gid') -> select();
    //     // dump($id_list);
    //     foreach($id_list as $k => $v){
    //         $gid[] = $v["gid"];
    //     }
    //     $res = implode(",",$gid);
    //     dump($res);
    //     $sql = "select * from keep_goods where id not in($res) and status=1";
    //     $goods = Db::query($sql);
    //     return dump($goods);
    // }

}
