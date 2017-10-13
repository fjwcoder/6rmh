<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 微信配置控制器
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


class Wxconfig extends Manage
{
    public function index()
    {   
        

        $navid = input('navid', 23, 'intval');
        $config = mallConfig();
        $keyword = input('post.keyword', '', 'htmlspecialchars,trim');

        $list = getWxConf();
        // return dump($list);
        $this->assign('list', $list);
        $this->assign('config', $config);
        $this->assign('header', ['icon'=>'glyphicon-cog','title'=>'扩展管理->微信设置->配置信息', 
        'form'=>'list', 'navid'=>$navid]);

        $this->assign('keyword', $keyword?$keyword:'');
        return $this->fetch('index');
    }
    



    public function add(){

        if(request()->post()){
            return $this->dataPost('add');
        }
        $navid = input('navid', 23, 'intval');
        $this->assign('header', ['icon'=>'glyphicon-cog','title'=>'扩展管理->微信设置->添加配置', 
        'form'=>'add', 'navid'=>$navid
        ]);
        return $this->fetch('add');
    }

    public function edit(){
        $status = false;
        $post = request() -> post();
        foreach($post as $k=>$v){

            $update = Db::name('wechat_config') -> where(['name'=>strtoupper($k)]) -> update(['value'=>$v]);
            if($update){
                $status = true;
            }
        }
 
        if($status){
            return $this->success('修改成功', request()->controller().'/index');
        }else{
            return $this->error('无修改项或有失败项');
        }
    }
    public function dataPost($type){
        $post = request() -> post();
        // unset($post['navid']);
        foreach($post['config'] as $k=>$v){
            $data[$k] = $v;
        }
        
        // return dump($data);
        if($type == 'add'){
            foreach($data as $k=>$v){
                $data[$k]['adduser'] = session(config('ADMIN_AUTH_NAME'));
                $data[$k]['addtime'] = time();
            }
            // return dump($data);
            $result = Db::name('wechat_config') -> insertAll($data);
            if($result){
                return $this->success('添加成功', request()->controller().'/index');
            }
        }
        // else{
        //     $id = $data['id'];
        //     unset($data['title'], $data['id']);
        //     if(empty($pic)){
        //         unset($data['pic']);
        //     }
        //     $update = Db::name('term') -> where(['id'=>$id]) -> update($data);
        //     return $this->success('添加成功', request()->controller().'/index');
        // }
        

    }


    public function verify(){
        header('Content-type: application/json, charset=utf-8');
        $post = request()->post();
        switch($post['type']){
            case 'name':
                $where['name'] = strtoupper($post['value']);
            break;
            case 'title':
                $where['title'] = $post['value'];
            break;
            default:
                $where['1'] = '1'; //保持正确查询
            break;
        }

        $result = db('wechat_config')->where($where) -> find();
        if($result){
            echo json_encode(array('status'=>false, 'content'=>'*已存在'), JSON_UNESCAPED_UNICODE);
        }else{
            echo json_encode(array('status'=>true, 'content'=>'*可提交'), JSON_UNESCAPED_UNICODE);
        }
    }

}
