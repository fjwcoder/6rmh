<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 后台Index控制器
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
use think\Cache;
use think\Db;

class Index extends Manage
{
    public function index()
    {   
        $config = webConfig();
        $this->assign('admin', ['title'=>$config['admin_title']['value']]);
        $this->assign('navbar', $this->loadNavbar());
        $this->assign('user', getUserInfo('admin_member', Session::get(Config::get('USER_KEY'))));
        return $this->fetch();
    }
    
    //清理缓存
    public function clearCache(){
        $param = request()->param(); //清理缓存
        if(isset($param['action']) && $param['action'] == 'clear'){ //清理缓存
            Cache::clear();
            return $this->redirect('admin/index/index');
        }
    }

    public function loadNavbar(){
        global $navbar, $html;
        if(session('ADMIN_NAVBAR')){
            $result =  session('ADMIN_NAVBAR'); //修改这里记得修改system里的ADMIN_NAVBAR
        }else{
            #查询用户权限
            $result = getAdminNode(Session::get(Config::get('USER_KEY')));
            
            // session('ADMIN_NAVBAR', $result);
        }
        if(empty($result)){
            session(null);
            return msg('/admin/login/index', '未配置节点', 'iframe');
            exit;
        }
        $navbar = $result;
        $html = ' ';
        $this->recursion(0);
        return $html;
    }

    public function recursion($fid=0){
        global $navbar, $html;
        for($i=0; $i<count($navbar); $i++){
            if($navbar[$i]['pid'] == $fid){
                if($navbar[$i]['deep'] == 1){
                    $html .= '<li class="dropdown">';
                    $html .= '<a href="javascript: void(0);" class="dropdown-toggle" data-toggle="dropdown" >';
                    $html .= '<span class="	glyphicon '.$navbar[$i]['icon'].'"></span>   ';
                    $html .= $navbar[$i]['title'].'</b>';
                    $html .= '</a>';
                    $html .= '<ul class="dropdown-menu">';
                }
                if($navbar[$i]['deep']!=1){
                    if($navbar[$i]['isnode']==1){ //节点
                        if($navbar[$i]['sort'] == 1){
                            $html .= '<li class="nav-node nav-node-bottom">';
                        }else{
                            $html .= '<li class="nav-node nav-node-all">';
                        }
                        
                        $html .= '<a href="javascript: void(0);" data-url="'.$navbar[$i]['url'].'">';
                        $html .= $navbar[$i]['title'].'</a></li>';
                    }else{ //不是节点
                        $html .= '<li><a class="navbar-li" href="javascript: void(0);" data-url="'.$navbar[$i]['url'].'">'.$navbar[$i]['title'].'</a></li>';
                    }
                    
                }

                $this->recursion($navbar[$i]['id']);

                if($navbar[$i]['deep'] == 1){
                    $html .= '';
                    $html .= '</ul>';
                    $html .= '</li>';

                }
            }
        }
        
    }
    //递归
    // public function recursion0($fid=0, $parent='accordion'){
    //     global $nav, $html;
    //     for($i=0; $i<count($nav); $i++){

    //         if($nav[$i]['pid'] == $fid){
    //             $module = explode(',', $nav[$i]['id_list']);
    //             $space = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', (count($module)-2)>0?(count($module)-2):0 );
    //             $html .= '<div id="collapse-nav-'.$module[0].'" class="collapse-nav d_n" >';
    //             $html .= '<a id="a-collapse'.$nav[$i]['id'].'" data-url="'.$nav[$i]['url'].'" data-toggle="collapse" data-parent="#collapse'.$parent.'" href="#collapse'.$nav[$i]['id'].'" ';
    //             if($nav[$i]['deep'] == 1)
    //                 $html .= 'style="display: none;"';
    //             $html .= '>';
    //             $html .= '<div class="a-nav" style="padding: 5px 15px 0 15px;">'.$space.$nav[$i]['title'];
    //             if($nav[$i]['isnode'] == 1){
    //                 $html .= '<span class="span-collapse'.$nav[$i]['id'].' f_r glyphicon glyphicon-chevron-down"></span>';
    //             }
    //             $html .= '</div>';
    //             $html .= '</a>';
                
    //             if($nav[$i]['isnode'] == 1){
    //                 $html .= '<div id="collapse'.$nav[$i]['id'].'" class="panel-collapse collapse">';
    //                 $html .= '<div>';
    //             }

    //             $this -> recursion($nav[$i]['id'], $nav[$i]['id']); //他妈的神圣的递归啊

    //             if($nav[$i]['isnode'] == 1){
    //                 $html .= '</div>';
    //                 $html .= '</div>';
    //             }
    //             $html .= '</div>';
    //         }
    //     }
    // }
    //加载导航数据项
    // public function loadModule(){
        
    //     if(cache('ADMIN_MODULE')){
    //         return cache('ADMIN_MODULE');
    //     }else{
    //         //查出模块
    //         $result = db('admin_menu', [], false) -> field(array('id', 'name', 'title', 'deep', 'sort', 'icon')) 
    //             -> where(array('deep'=>1, 'status'=>1)) -> order('sort')-> select();
    //         cache('ADMIN_MODULE', $result);
    //         return $result;
    //     }
        
    // }

    

    //加载单元和控制
    // public function loadMenu(){
    //     global $nav, $html;
    //     if(cache('ADMIN_NAV')){
    //         $result = cache('ADMIN_NAV');
    //     }else{
    //         $result = db('admin_menu', [], false) -> where('status=1') ->order('id_list, sort') -> select();
    //         cache('ADMIN_NAV', $result);
    //     }
    //     $nav = $result;
    //     $html = ' ';
    //     $html .= '<div class="panel-group" id="accordion">';
    //     $this->recursion(0, 'accordion');
    //     $html .= '</div>';
    //     return $html;
    // }   

    






// ===========================================无限分类的例子:挺重要的，别删============================================
// 层级关系
    public function cate_default(){
        $result = db('categories', [], false)-> order('parentid_list') -> select();  //最主要的排序是order by ！！！！！！！！！
        $option = '';
        foreach($result as $key=>$v){
            $space = str_repeat( '        ', count ( explode ( ',', $v['parentid_list'] ) ) - 1 );
            $option .= '<option value="' . $v['id'] . '">' . $space . $v['name'] .' ['.$v['parentid_list'].']' . '</option>';
        }
        return $option;
      
    }

// 递归
    public function cate(){
        $result = db('categories', [], false)-> order('parentid_list') -> select();  //最主要的排序是order by ！！！！！！！！！
        global $nav, $html;
        $nav = $result;
        $html = '';
        $html .= '<div class="panel-group" id="accordion">';
        $this->digui(0);
        $html .= '</div>';
        return $html;
    }

    public function digui($fid=0){
        global $nav, $html;
        for($i=0; $i<count($nav); $i++){
            if($nav[$i]['parentid'] == $fid){
                $html .= '<div>';
                if($nav[$i]['isnode'] == 1){
                    $html .= '<a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" ';
                    $html .= '<div class="panel-heading">'.$nav[$i]['name'].'【'.$nav[$i]['parentid_list'].'】'.'</div>';
                    $html .= '</a>';
                    $html .= '<div id="collapseOne" class="panel-collapse collapse">';
                }else{
                    $html .= '<div class="panel-body">'.$nav[$i]['name'].'【'.$nav[$i]['parentid_list'].'】';

                }
                $this -> digui($nav[$i]['id']);
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
            
        }
        
    }


}
