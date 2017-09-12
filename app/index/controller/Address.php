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

}
