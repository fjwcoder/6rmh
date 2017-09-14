<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Address extends Common
{
    public function index(){
        $userid = input('userid', 1, 'intval');
        $config = mallConfig();
        $this->assign('config', ['page_title'=>$config['web_name']['value'], 'template'=>$config['mall_template']['value']
            ]);

        $address = Db::name('user_address') -> field(['*', 'CONCAT(province,city,area) as addr']) 
        -> where(['userid' => $userid]) ->select();
        $count = count($address);
        $this->assign('count', $count);
        if($count < 10){
            #查出省份，assign到前台
            $province = Db::name('region') ->where(['type'=>1]) ->order('id') ->select();
            $this->assign('province', $province);
        }
        $this->assign('address', $address);
        return $this->fetch();
    }

    public function city(){
        // $province = input('province', '', 'htmlspecialchars,trim');
        $pid = input('id', 0, 'intval');
        $city = Db::name('region') -> where(['pid'=>$pid, 'type'=>2]) -> select();
        echo json_encode($city, JSON_UNESCAPED_UNICODE);
    }

    public function area(){
        // $province = input('province', '', 'htmlspecialchars,trim');
        $pid = input('id', 0, 'intval');
        $area = Db::name('region') -> where(['pid'=>$pid, 'type'=>3]) -> select();
        echo json_encode($area, JSON_UNESCAPED_UNICODE);
    }

    public function defAddr($id=0){
        if($id===0){
            $id = input('id', 0, 'intval');
        }
        if($id === 0){
            return $this->error('参数错误'); exit;
        }
        $update = Db::name('user_address') ->where(['userid'=>session(config('USER_ID'))]) -> update(['type'=>0]); //先全都变成0
        $setInc = Db::name('user_address') ->where(['userid'=>session(config('USER_ID')), 'id'=>$id]) -> setInc('type', 1);
        if($setInc){
            return true;
        }else{
            return false;
        }
    }

    public function delAddr($id=0){
        if($id===0){
            $id = input('id', 0, 'intval');
        }
        if($id === 0){
            return $this->error('参数错误'); exit;
        }

        $del = Db::name('user_address') -> where(['id'=>$id]) -> delete();
        
        if($del){
            $min = Db::name('user_address') ->where(['id'=>$id]) ->min('id');
            if($min>0){
                $this->defAddr($min);
            }
            return true;
        }else{
            return false;
        }

    }

}
