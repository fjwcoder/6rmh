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

        return $this->fetch('term');
    }


    public function edit(){

        if(request()->post()){
            return $this->dataPost('edit');
        }

        $id = input('id', 0, 'intval'); //期数
        $term = Db::name('term') -> where(['id'=>$id]) -> find();
        $term['begintime'] = date('Y-m-d H:i:s', $term['begintime']);
        $goods = Db::name('term_goods') -> alias('a') 
            -> join('goods b', 'a.gid=b.id', 'LEFT') 
            -> field(['b.id', 'b.userid', 'b.name', 'b.sub_name', 'b.brand', 'b.promotion', 'b.price', 'b.img']) 
            -> where(['a.term'=>$id]) 
            -> select();
        // return dump($goods);
        $this->assign('result', $term);
        $this->assign('goods', $goods);
        
        $navid = input('navid', 44, 'intval');
        $this->assign('header', ['icon'=>'glyphicon-cog','title'=>'系统配置->系统配置->编辑分期', 
        'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('term');
    }

    public function dataPost($type){
        $post = request() -> post();
        if(empty($post['begintime'])){
            return $this->error('起始时间不可为空');
        }
        if(empty($post['endtime'])){
            return $this->error('结束时间不可为空');
        }
        unset($post['navid']);
        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        #处理该期的时间
        $data['begintime'] = strtotime($data['begintime']);
        $data['endtime'] = strtotime($data['endtime']);
        if($data['begintime'] <= time()){
            return $this->error('起始时间错误');
        }
        if($data['endtime'] <= $data['begintime']){
            return $this->error('结束时间错误');
        }
        #查出上一期的结束时间

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

        }
        
        return dump($data);

    }


    //增加本期商品
    public function addGoods(){


    }

}
