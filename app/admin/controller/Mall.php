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


class Mall extends Manage
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

    public function index()
    {   
        $navid = input('navid', 40, 'intval');
        $conf = db('mall_config', [], false) -> where(array('status'=>1)) -> select();
        
        $config = mallConfig();
        $this->assign('config', $config);
        $this->assign('conf', $conf);
        $this->assign('header', ['icon'=>'glyphicon-cog','title'=>'系统配置->系统配置->商城配置', 
        'form'=>'index', 'navid'=>$navid]);
        return $this->fetch();
    }
    

    //修改商城配置
    public function edit(){
        $post = request()->post();
        
        $flag = false; //是否有更新项，如果有则为true
        if(empty($post['web_url'])){
            $post['web_url'] = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'];
        }
        $post['doc_root'] = ROOT_PATH?ROOT_PATH:$_SERVER['DOCUMENT_ROOT'];
        $list = array();
        foreach($post as $k=>$v){
            $list[] = ['name'=>$k, 'value'=>$v];
        }
        
        foreach($list as $data){
            $update = Db::table('keep_mall_config') -> where(array('name'=>$data['name'])) -> update($data);
            if($update > 0){
                $flag = true;
            }
        }
        
        if($flag){
            Cache::rm('MALL_CONFIG'); // 删除mall_config 的缓存
            mallConfig(); // 重新设置mall_config 的缓存

            return $this->success('更新成功', request()->controller()."/index");
        }else{
            return $this->error('无更新项');
        }

    }

    public function add(){
        $navid = input('navid', 40, 'intval');
        $this->assign('header', ['icon'=>'glyphicon-cog','title'=>'系统配置->系统配置->添加配置', 
        'form'=>'add', 'navid'=>$navid]);
        return $this->fetch();
    }

    public function editor(){
        $name = input('name', '', 'htmlspecialchars,trim');
        $data['title'] = input('title', '', 'htmlspecialchars,trim');
        $data['value'] = input('value', '', 'htmlspecialchars,trim');
        $data['remark'] = input('remark', '', 'htmlspecialchars,trim');
        $data['name'] = strtoupper($name);
        $list = db('mall_config', [], false) ->field('name') -> select();
        foreach($list as $k => $v){
            $das[] = $v['name'];
        }
        if(in_array($data['name'],$das)){
            return $this->error('此标识已经存在');
        }else{
            
            $res = db('mall_config', [], false) -> insert($data);
            if($res){
                return $this->success('添加成功', "Mall/index");
            }else{
                return $this->error('添加失败');
            }
        }
    }

    public function edits(){
        $navid = input('navid', 0, 'intval');
        $id = input('id', 40, 'intval');
        $result = db('mall_config', [], false) -> where(array('id'=>$id)) -> find();
        
        $this->assign('result', $result);
        $nav = adminNav();
        $this->assign('header', ['title'=>'修改配置', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch();
    }

    public function editors(){
        $id = input('id', 0, 'intval');
        $data['name'] = input('name', '', 'htmlspecialchars,trim');
        $data['title'] = input('title', '', 'htmlspecialchars,trim');
        $data['value'] = input('value', '', 'htmlspecialchars,trim');
        $data['remark'] = input('remark', '', 'htmlspecialchars,trim');
        
        $result = db('mall_config', [], false) -> where(array('id'=>$id)) ->update($data);
        if($result){
            return $this->success('修改成功', "Mall/index");
        }else{
            return $this->error('修改失败');
        }
        
    }

}
