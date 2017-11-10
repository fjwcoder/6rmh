<?php
# +-------------------------------------------------------------
# | CREATE by ZMX IN 2017-11-09.
# | 后台Link控制器
# | 后台友情链接控制器
# | 
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



class Link extends Manage
{
    public function index()
    {   
        $navid = input('navid', 19, 'intval');
        $nav = adminNav();
        $key = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('link', [], false) -> paginate();
        $this->assign('list', $list);  
        $header =  ['title'=>'系统设置->系统设置->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
        'form'=>'list', 'navid'=>$navid];
        $this->assign('header', $header);
        $this->assign('keyword', $key?$key:'');
        return $this->fetch();
    }
	
	public function add(){
        if(request()->post()){
            return $this->dataPost('add');
        }
        $navid = input('navid', 19, 'intval');
        $nav = adminNav();
        $this->assign('header', ['title'=>'增加链接', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('link');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $navid = input('navid', 0, 'intval');
        $nav = adminNav();
        $id = input('id', 0, 'intval');
        $result = db('link', [], false) -> where(array('id'=>$id)) -> find();
        $this->assign('result', $result);
        $this->assign('header', ['title'=>'编辑链接:  【'.$result['title'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('link');
    }

    public function del(){
        $id = Request::instance()->param('id');

        $rs = db('link')->where(array('id'=>$id))->delete();
        if ($rs) {
            $this->success('删除成功', "Link/index");
        } else {
            $this->error('删除失败');
        }
    }

    public function dataPost($type=''){
        $post = request()->post();
        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        
        unset($data['navid']);
        if($type=='add'){

            $result = db('link', [], false) -> insert($data);
        }else{
            $id = $data['id'];

            #头像上传
            if(!empty($_FILES['logo']['name'])){
                $upload = uploadHeadImg('images'.DS.'logo');

                if($upload['status']){
                    $data['logo'] = $upload['path'][0];
                }else{
                    return $this->error('头像上传失败');
                }
            }

            unset($data['id']);
            $result = db('link', [], false) -> where(array('id'=>$id)) ->update($data);
        }

        
        if($result){
            return $this->success('成功', "Link/index");
        }else{
            return $this->error('失败');
        }
    }
    
}
