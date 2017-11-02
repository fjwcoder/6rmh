<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-18.
# | 网站关停页面
# |
# |
# +-------------------------------------------------------------


namespace app\index\controller;
use think\Controller;
use think\Config;
use think\Session;
use think\Cache;
use think\Db;



class Close extends controller
{

    public function index(){
       return $this->fetch();
    }
    

    public function test(){

        $id = 7;
        $user = Db::table('ecs_users') -> where(['user_id'=>$id]) -> field([
            'user_id', 'parent_id', 'id_list', 'parent_side', 'user_name'
        ]) -> find();
        dump($user);
        $id_list = explode(',', $user['id_list']);
        dump($id_list);

        $data = Db::table('ecs_users') -> where("id_list like '".$id_list[0]."%' ") -> field([
            'user_id', 'parent_id', 'id_list', 'parent_side', 'user_name', 'deep'
        ]) -> select();
        return dump($data);
    }



}