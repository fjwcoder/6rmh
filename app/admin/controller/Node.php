<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-19.
# | 后台节点设置控制器
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

class Node extends Manage
{
    // dump(request()->module());//模块
    // dump(request()->controller()); //控制器
    // dump(request()->action()); //方法
    // private $module = '';
    // private $controller = '';

    // public function _initialize(){
    //     request()->module() = request()->module();
    //     request()->controller() = request()->controller();
    // }

    #根据navid 判断 对什么进行节点设置
    # 8：用户
    # 12：部门
    # 13：级别
    public function index(){
        global $node, $html;
        $navid = input('navid', 8, 'intval');
        $id = input('id', 2, 'intval');
        if(($navid == 8) && ($id == Session::get(Config::get('USER_KEY')))){
            return $this->error('不能配置自己的权限');
        }
        switch($navid){
            case 8:
                $result = getAdminNode(Session::get(Config::get('USER_KEY'))); //参数为操作者的ID   查出该操作者可以授权的节点
                $this->assign('user', getUserInfo('admin_member', $id));
            break;
            case 12:

            break;
            case 13:

            break;
            default:

            break;
        }
        $node = $result;
        $html = '';
        $this->recursion(0);
        $this->assign('header', ['icon'=>'glyphicon-filter','title'=>'扩展管理->节点配置', 
            'form'=>"/request()->module()/request()->controller()/edit", 'navid'=>$navid, 'id'=>$id]);
        $this->assign('node', $html);
        //查出被配置用户的节点项
        $this->assign('checked', getAdminNode($id));
        return $this->fetch();
    }



    public function edit(){
        if(request()->post()){
            return $this->editPost();
        }
    }



    public function editPost(){
        $post = request()->post();
        if(($post['navid'] == 8) && ($post['id'] == Session::get(Config::get('USER_KEY')))){
            return $this->error('不能配置自己的权限');
        }
        if(empty($post['node'])){ //为空
            switch($post['navid']){
                case 8:
                    $delete = db('admin_node', [], false) -> where(array('user_id'=>$post['id'])) -> delete();
                    $update = db('admin_member', [], false) ->where(array('id'=>$post['id'])) -> update(['authority'=>0]);
                break;
                case 12:
                    $delete = db('admin_node', [], false) -> where(array('branch_id'=>$post['id'])) -> delete();
                break;
                case 13:
                    $delete = db('admin_node', [], false) -> where(array('level_id'=>$post['id'])) -> delete();
                break;
                default:

                break;
            }

            //无论如何，直接成功
            return $this->success('配置成功', "/request()->module()/request()->controller()/index/navid/$post[navid]/id/$post[id]");
        }else{
            $id_list = '';
            foreach($post['node'] as $k=>$v){ //把id_list 接成字符串
                $id_list .= $v.',';
            }
            $id_list = array_filter(array_unique(explode(',', $id_list))); //字符串分解成数组，然后去重，然后去空
            switch($post['navid']){
                case 8:
                    $delete = db('admin_node', [], false) -> where(array('user_id'=>$post['id'])) -> delete();
                    $update = db('admin_member', [], false) -> where(array('id'=>$post['id'])) -> update(['authority'=>1]);
                    foreach($id_list as $k=>$v){
                        $data[$k] = ['menu_id'=>$v, 'user_id'=>$post['id']];
                    }
                break;

                default:

                break;
            }
            $result = db('admin_node') -> insertAll($data);

        }
        if($result){
            return $this->success('配置成功', "/request()->module()/request()->controller()/index/navid/$post[navid]/id/$post[id]");
        }else{
            return $this->error('配置失败');
        }
    }


    //递归方法
    private function recursion($fid=0){
        global $node, $html;
        for($i=0; $i<count($node); $i++){
            if($node[$i]['pid'] == $fid){
                
                if($node[$i]['deep'] == 1){
                    $html .= '<div class="panel-heading">';
                    $html .= '<h3 class="panel-title">'.$node[$i]['title'].'</h3>';
                    $html .= '</div>';
                }else{
                    if($node[$i]['isnode'] == 1){
                        $html .= '<div class=" panel-body node-panel-body">';
                    }else{
                        $html .= '<label class="checkbox-inline">';
                        $html .= '<input id="menu-'.$node[$i]['id'].'" type="checkbox" name="node[]" value="'.$node[$i]['id_list'].'"> '.$node[$i]['title'];
                        $html .= '</label>';
                    }

                }

                $this->recursion($node[$i]['id']);
                if( ($node[$i]['deep'] != 1) && ($node[$i]['isnode'] == 1)){
                    $html .= '</div>';
                }
            }
        }

    }

}