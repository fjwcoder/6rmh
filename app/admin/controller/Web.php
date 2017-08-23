<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-19.
# | 后台网站管理控制器
# |
# | email: fjwcoder@gmail.com
# +-------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Manage;
use app\common\controller\Common;
use think\Session;
use think\Config;

class Web extends Manage
{
    public function index(){
        // $title = Manage::loadConfig('TRACE_ADMIN_TITLE');
        // $this->assign('admin', ['title'=>$title['tvalue']]);
        // return $this->fetch();
        return 'web控制器';
    }

    public function add(){

    }

    public function addPost(){

    }

    public function edit(){

    }

    public function editPost(){

    }

    public function del(){

    }

    public function delPost(){

    }

}