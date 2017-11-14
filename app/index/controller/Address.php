<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Request;
use think\Db;

class Address extends Common
{
    public function index(){
        // $userid = input('userid', 0, 'intval');
        $userid = session(config('USER_ID'));
        $config = mallConfig();
        $this->assign('config', ['page_title'=>$config['web_name']['value'], 'template'=>$config['mall_template']['value']
            ]);

        $address = Db::name('user_address') -> alias('a') 
            ->join('region b', 'a.province=b.id', 'LEFT')
            ->join('region c', 'a.city=c.id', 'LEFT')
            ->join('region d', 'a.area=d.id', 'LEFT')
            ->field(['a.id', 'b.name as province', 'c.name as city', 'd.name as area', 'a.address', 'a.zipcode', 'a.name', 'a.mobile', 'a.type']) 
            ->where(['a.userid'=>$userid]) -> select();
        
        foreach($address as $k=>$v){
            $address[$k]['addr'] = $v['province'].$v['city'].$v['area'];
        }

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

    public function add(){
        
        $data['name'] = input('name','','htmlspecialchars,trim');
        $data["province"] = input('province',0,'intval');
        $data["city"] = input('city',0,'intval');
        $data["area"] = input('area',0,'intval');
        $data['address'] = input('address','','htmlspecialchars,trim');
        $data['mobile'] = input('mobile','','htmlspecialchars,trim');
        $data['zipcode'] = input('zipcode','','htmlspecialchars,trim');
        $data['userid'] = session(config('USER_ID'));

        // if(!checkPost($data)){
        //     return '参数错误'; exit;
        // }


        $a = Db::name('user_address') ->insert($data);

        if($a){  
            return $this->redirect('index');  
        }else{
            return $this->error('添加失败');
        }
        
        
    }

    public function edit(){
        $id = input('id',0,'intval');
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>$config['web_name']['value'], 'template'=>$config['mall_template']['value']
            ]);
        $address = Db::name('user_address') ->where(['id'=>$id]) ->find();
        
        #查出省份，assign到前台
        $province = Db::name('region') ->where(['type'=>1]) ->order('id') ->select();
        #查出市，assign到前台
        $pro = Db::name('region') ->where(['type'=>2,'pid'=>$address['province']]) ->order('id') ->select();
        #查出区，assign到前台
        $prov = Db::name('region') ->where(['type'=>3,'pid'=>$address['city']]) ->order('id') ->select();
        
        
        $this->assign('province', $province);
        $this->assign('pro', $pro);
        $this->assign('prov', $prov);
        $this->assign('address',$address);
        
        return $this->fetch();
    }

    public function editor(){
        $id = input('id',0,'intval');
        $data['name'] = input('name','','htmlspecialchars,trim');
        $data["province"] = input('province','','intval');
        $data["city"] = input('city','','intval');
        $data["area"] = input('area','','intval');
        $data['address'] = input('address','','htmlspecialchars,trim');
        $data['mobile'] = input('mobile','','htmlspecialchars,trim');
        $data['zipcode'] = input('zipcode','','htmlspecialchars,trim');
        $address = Db::name('region') ->field('name') ->where(array('id'=>$id)) ->select();        
        
        $a = Db::name('user_address') ->where(['id'=>$id]) ->update($data);
        if($a){
            return $this->success('修改成功', 'Address/index');
        }else{
            return $this->error('修改失败');
        }
    }

    public function del(){
        $id = Request::instance()->param('id');
        $rs = db('user_address')->where(array('id'=>$id))->delete();
        if ($rs) {
            $this->success('删除成功', "Address/index");
        } else {
            $this->error('删除失败');
        }
    }

    public function city(){
        
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

    #$order ：true 订单预览页修改，false: 地址信息页修改
    public function defAddr($id, $order=false){

        $uid = session(config('USER_ID'));

        # 全都修改为0
        $set0 = Db::name('user_address') -> where(['userid'=>$uid]) -> update(['type'=>0]);

        $set1 = Db::name('user_address') -> where(['id'=>$id, 'userid'=>$uid]) -> update(['type'=>1]);

        if($set1){
            if($order){
                return true;
            }else{
                return $this->success('设置成功', "Address/index");
            }
            
        }
        
        
    }

}
