<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 菜单管理
# |
# | email: fjwcoder@gmail.com
# +-------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Manage;
use app\common\controller\Common;
use app\admin\controller\Wechat as Wechat;
use think\Controller;
use think\Session;
use think\Cookie;
use think\Config;
use think\Request;
use think\Db;
use think\Cache;
use app\admin\model\Goodsmenu;
// use think\Paginator;

#+-----------------------------------
#| navid 当前页面id
#|
#+-----------------------------------
class Wxmenu extends Manage
{
    #微信自定义菜单
    public function index()
    {   
        die('自定义菜单');
        $navid = input('navid', 21, 'intval');
        $nav = adminNav();
        $keyword = input('post.keyword', '', 'htmlspecialchars,trim');
        
        
        $header =  ['title'=>'扩展管理->微信设置->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
            'form'=>'list', 'navid'=>$navid ]; 
        $this->assign('header', $header);
        $this->assign('keyword', $keyword?$keyword:'');
        return $this->fetch();
    }


    #查询自定义菜单
    public function getMenu(){
        $menu_get_url = getWxConf('MENU_GET_URL');
        $wechat = new Wechat();
        $menu_url = $menu_get_url['value'].$wechat->access_token();
        $menu_res = httpsGet($menu_url);
        return $menu_res;


    }

    #创建自定义菜单
    public function createMenu(){
        $menu_url = getWxConf('MENU_CREATE_URL');
        $wechat = new Wechat();
		$menu_url = $menu_url['value'].$wechat->access_token();
        $menu_data = $this->getMenuData();
        // return $menu_data;
		$menu_res = httpsPost($menu_url, $menu_data);
        $response = json_decode($menu_res, true);
        return dump($response);
        if($response['errcode'] == 0 && $response['errmsg'] == 'ok'){
            return $this->success('自定义菜单设置成功', 'Wxmenu/index');  
        }else{
            return $this->error('自定义菜单设置失败');
        }
        
    }


    #生成json格式的菜单数据
    public function getMenuData(){
        global $json, $menu;
        $menu = Db::name('wechat_menu') -> where(['status'=>1]) ->select();
        $json = '{';
        $json .= '"button":[';
        $this->MenuBuild(0);
        $json .= ']';
        $json .= '}';
        return $json;
    }

    public function MenuBuild($fid=0){
        global $json, $menu;
        for($i=0; $i<count($menu); $i++){
            if($menu[$i]['pid'] == $fid){
                if($menu[$i]['deep'] == 1){ //一级菜单
                    if($menu[$i]['isnode'] == 0){
                        $json .= '{ ';
                        $json .= ' "type":"'.$menu[$i]['type'].'", ';
                        $json .= ' "name":"'.$menu[$i]['title'].'", ';
                        $json .= ' "url":"'.$this->createView($menu[$i]['url']).'" ';
                        $json .= '},';
                    }else{
                        $json .= '{';
                        $json .= ' "name":"'.$menu[$i]['title'].'", ';
                        $json .= ' "sub_button": [ ';
                        #递归调用
                        $this->MenuBuild($menu[$i]['id']);
                        $json .= ']';
                        $json .= '},';
                    }
                        
                }else{
                    $json .= '{';
                    $json .= ' "type":"'.$menu[$i]['type'].'", ';
                    $json .= ' "name":"'.$menu[$i]['title'].'", ';
                    $json .= ' "url":"'.$this->createView($menu[$i]['url']).'", ';
                    $json .= '},';
                }
            }
        }

    }

    #静默授权
    public function createView($url = 'http://www.yiqianyun.com'){
        $wxconf = getWxConf();
        $url = urlencode($url);
        $view_url = $wxconf['MENU_VIEW_URL']['value'].$wxconf['APPID']['value'].'&redirect_uri='.$url."&response_type=code&scope=snsapi_base&state=1#wechat_redirect";
        return $view_url;
    }





    public function add(){
        if(request()->post()){
            return $this->dataPost('add');
        }
        $pid = input('id', 0, 'intval'); //父类别的id
        $navid = input('navid', 24, 'intval');
        $nav = adminNav();

        $menu = Db::name('admin_menu') -> where(array('id'=>$pid)) -> find();
        if($menu){
            $pid_list = $menu['id_list'];
        }else{
            $pid_list = 0;
        }

        $this->assign('pid_list', $pid_list);

        $list = db('admin_menu', [], false) -> where(array('status'=>1)) ->order('id_list, sort') -> select();
        foreach($list as $k=>$v){
            $list[$k]['prex'] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $v['deep']);
        }
        $this->assign('list', $list);
        $this->assign('header', ['title'=>'添加菜单', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('menu');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $id = input('id', 0, 'intval');
        $navid = input('navid', 24, 'intval');
        $nav = adminNav();

        $menu = Db::name('admin_menu') -> where(array('id'=>$id)) -> find();
        if($menu && $menu['pid'] != 0){
            $pid_list = Db::name('admin_menu') -> where(array('id'=>$menu['pid'])) -> value('id_list');
        }else{
            $pid_list = 0;
        }
        $this->assign('pid_list', $pid_list);
        $this->assign('result', $menu);

        $list = db('admin_menu', [], false) -> where(array('status'=>1)) ->order('id_list, sort') -> select();
        foreach($list as $k=>$v){
            $list[$k]['prex'] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $v['deep']);
        }
        $this->assign('list', $list);
        
        $this->assign('header', ['title'=>'编辑菜单:  【'.$menu['title'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('menu');
    }

    public function dataPost($type=''){
        $post = request()->post();


        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        unset($data['navid']);
        if($type=='add'){
            if($data['id_list'] == 0){ //顶级类别
                $data['pid'] = 0;
                $data['deep'] = 1;
                $data['id_list'] = '';
                $max = db('admin_menu', [], false) ->where(array('pid'=>0, 'deep'=>1)) -> max('sort');
            }else{ 
                $id_list = explode(',', $data['id_list']);
                $data['pid'] = $id_list[count($id_list)-1];
                $data['deep'] = count($id_list)+1;
                $max = db('admin_menu', [], false) -> where(array('pid'=>$data['pid'])) ->max('sort');
            }

            $data['sort'] = intval($max)+1;
            $data['addtime'] = intval(time());
            $data['adduser'] = Session::get(Config::get('ADMIN_AUTH_NAME'));
            //获取到自增ID
            $insert = Db::name('admin_menu') -> insert($data); //有bug
            $id = Db::name('admin_menu') ->getLastInsID();
            $result = db('admin_menu', [], false) -> where(array('id'=>$id)) 
                -> update(['id_list'=>empty($data['id_list'])?strval($id):$data['id_list'].",$id"]);
            if($result && $data['pid']>0){
                $result = db('admin_menu', [], false) -> where(array('id'=>$data['pid'])) -> setInc('isnode', 1);
            }
        }else{
            $id = $data['id'];
            unset($data['id']);

            //先把原来的父类别isnode-1
            $curr_cat = db('admin_menu') -> where(['id'=>$id]) -> find();
            $sub_node = db('admin_menu') -> where(['id'=>$curr_cat['pid']]) -> setDec('isnode', 1);

            if($data['id_list'] == 0){ //顶级类别
                $data['pid'] = 0;
                $data['deep'] = 1;
                $data['id_list'] = '';
                $max = db('admin_menu', [], false) ->where(array('pid'=>0, 'deep'=>1)) -> max('sort');
            }else{
                $id_list = explode(',', $data['id_list']);
                $data['pid'] = $id_list[count($id_list)-1];
                $data['deep'] = count($id_list)+1;
                $max = db('admin_menu', [], false) -> where(array('pid'=>$data['pid'])) ->max('sort');
            }
            $data['sort'] = intval($max)+1;
            $data['id_list'] = empty($data['id_list'])?strval($id):$data['id_list'].",$id";

            //这地方最好是写成事务
            $result = db('admin_menu', [], false) -> where(array('id'=>$id)) -> update($data);
            if($result){
                $result = db('admin_menu', [], false) -> where(array('id'=>$data['pid'])) -> setInc('isnode', 1);
                
                $sql = " update keep_admin_menu set id_list = replace(id_list, '".$curr_cat['id_list']."', '".$data['id_list']."')";
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






}
