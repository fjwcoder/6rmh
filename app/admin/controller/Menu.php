<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-19.
# | 后台菜单管理控制器
# |
# | email: fjwcoder@gmail.com
# +-------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Manage;
use app\common\controller\Common;
use think\Session;
use think\Config;

class Menu extends Manage
{
    public function index(){





        $this->assign('content', ['title'=>'菜单列表', 'add'=>'menu/add', 'del'=>'menu/del', 'index'=>'menu']);
        return $this->fetch();
    }

    public function menuList(){
        $array = db('admin_menu', [], false) -> order('sort') -> select();
        $deep = db('admin_menu', [], false) -> distinct(true) -> field('deep') -> order('deep') -> select();

        $common = new Common();
        $common->arrayGroup($array, $deep);



    }

    public function add(){

        $this->assign('content', ['title'=>'添加菜单']);
        return $this->fetch();
    }

    public function addPost(){

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