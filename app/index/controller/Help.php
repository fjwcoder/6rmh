<?php
namespace app\index\controller;
use app\common\controller\Common; 
// use app\common\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Paginator;
use think\Db;

class Help extends controller
{

    public function index(){
        global $html, $show_list;
        $id = session(config('USER_ID'));
        
        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
        }
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'帮助中心', 'template'=>$config['mall_template']['value'] 
            ]);

        $users = Db::name('users') ->where(['id' =>$id]) ->find();
        $show_list = db('help_center', [], false) ->order('id_list', 'sort') ->select();
        
        if(empty($show_list)){
            $this->assign('list_tree', '<h3>尚无列表信息</h3>');
        }else{
            $html = '';
            $this->listBuilder(0);
            $html .= '';
            $this->assign('list_tree', $html);
        }
        $this->assign('header', [ 'form'=>'user', 'id'=>$id ]);
        
        $this->assign('users', $users);
        return $this->fetch();
    }

    public function listBuilder($fid=0){
        global $html, $show_list;
        for($i=0; $i<count($show_list); $i++){
            if($show_list[$i]['pid'] == $fid){
                if($show_list[$i]['deep'] === 1){
                    $html .= '<li>';
                    $html .= '<div class="header none">';
                    $html .= '<span class="label">'.$show_list[$i]['title'].'</span>';
                    $html .= '<span class="arrow down"></span>';
                    $html .= '</div>';
                    $html .= '<ul class="menu">';         
                    
                }else{                    
                    $html .= '<li onClick="licolor(this)" class="licolor" id="'.$show_list[$i]['id'].'">';
                    $html .= '<a href="'.$show_list[$i]['url'].'"><span>'.$show_list[$i]['title'].'</span></a>';    
                    $html .= '</li>';  
                }
                $this->listBuilder($show_list[$i]['id']);
                if($show_list[$i]['deep'] === 1){
                    $html .= '</ul>';
                    $html .= '</li>';
                }
            }
        }
    }

    public function detail(){

        $id = input('id', 0, 'intval');

        $detail = Db::name('help_center')->field('title,detail') ->where(['id' => $id]) ->select();

        echo json_encode($detail, JSON_UNESCAPED_UNICODE);

    }

}
