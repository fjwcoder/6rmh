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
use think\Session;
use think\Config;
use think\Paginator;
// use think\Request;
class Icon extends Manage
{
    public function index(){

        $keyword = input('keyword', '', 'htmlspecialchars,trim');
        if(!empty($keyword)){
            $where = array('name', 'like', "%$keyword%");
        }
        $where['status'] = 1;
        
        $list = db('web_icon', [], false) -> where($where) -> order('id desc') -> paginate();
        $this->assign('list', $list);
        // $this->assign('content', ['title'=>'图标列表', 'add'=>$add_url, 'del'=>$del_url, 'index'=>'icon']);
        $this->assign('content', ['title'=>'图标列表', 'index'=>'icon/index']);
        return $this->fetch();
    }


    public function add(){

        // return dump(request());

        if(request()->isPost()){
            return $this->addPost();
        }

        $array = db('web_config', [], false) -> where(array('status'=>1)) -> order('id') -> select();

        $this->assign('content', ['title'=>'添加图标']);
        return $this->fetch();
    }

    public function addPost(){
        $data['name'] = input('name', '', 'htmlspecialchars,trim');
        $data['value'] = input('value', '', 'htmlspecialchars,trim');
        $data['tvalue'] = 'glyphicon'.' '.$data['value'];
        $data['status'] = input('status', 0, 'intval');
        $data['remark'] = input('remark', '', 'htmlspecialchars,trim');

        if(db('web_icon', [], false) -> insert($data)){
            //return $this->success('添加成功');  //目前写成返回到当前页面
            // 如果重定位的是当前模块，则不需要写模块名（icon/index），参数为方法名
            return $this->success('添加成功', 'icon/index');
        }else{
            return $this->error('添加失败');
        }
        // return dump($data);
    }

    public function edit(){

    }

    public function editPost(){

    }

    public function del(){
        return 'del方法';
    }

    public function delPost(){

    }

}