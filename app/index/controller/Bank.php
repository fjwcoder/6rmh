<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Request;
use think\Db;

class Bank extends Common
{
    public function index(){
        $userid = session(config('USER_ID'));
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'银行信息', 'template'=>$config['mall_template']['value']
            ]);

        $bank = Db::name('user_bank') ->where(['userid'=>$userid, 'status'=>1]) -> 
            order('type desc') -> select();
        
        $count = count($bank);
        $this->assign('count', $count);
        if($count < 10){
            #查出银行类型，assign到前台
            $banktype = Db::name('bank') ->order('id') ->select();
            
            $this->assign('banktype', $banktype);
        }
        $this->assign('bank', $bank);
        return $this->fetch();
    }

    public function add(){
        $data['bankid'] = input('banktype','','htmlspecialchars,trim');
        $data['accountbank'] = input('accountbank','','htmlspecialchars,trim');
        $data['accountholder'] = input('accountholder','','htmlspecialchars,trim');
        $data['cartnumber'] = input('cartnumber','','htmlspecialchars,trim');
        $data['mobile'] = input('mobile','','htmlspecialchars,trim');
        $data['userid'] = session(config('USER_ID'));
        
        if($data['mobile'] == ''){
            $user = decodeCookie('user');
            $data['mobile'] = $user['mobile'];
        }
        $bankinfo = Db::name('bank') ->where(['bankid'=>$data['bankid']]) ->find();
        $data['banktype'] = $bankinfo['bankname'];

        $res = Db::name('user_bank') ->insert($data);

        if($res){  
            return $this->redirect('Bank/index');
            // return $this->success('添加成功', 'Bank/index');  
        }else{
            return $this->error('添加失败');
        }
        
        
    }

    public function edit(){
        $id = input('id',0,'intval');
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'编辑银行信息', 'template'=>$config['mall_template']['value']
            ]);
        $bank = Db::name('user_bank') ->where(['id'=>$id]) ->find();
        #查出银行类型，assign到前台
        $banktype = Db::name('bank') ->order('id') ->select();
        $this->assign('banktype', $banktype);
        $this->assign('bank',$bank);
        
        return $this->fetch();
    }

    public function editor(){
        $id = input('id',0,'intval');
        $data['bankid'] = input('banktype','','htmlspecialchars,trim');
        $data['accountbank'] = input('accountbank','','htmlspecialchars,trim');
        $data['accountholder'] = input('accountholder','','htmlspecialchars,trim');
        $data['cartnumber'] = input('cartnumber','','htmlspecialchars,trim');
        $data['mobile'] = input('mobile','','htmlspecialchars,trim');

        $bankinfo = Db::name('bank') ->where(['bankid'=>$data['bankid']]) ->find();
        $data['banktype'] = $bankinfo['bankname'];        
        
        $res = Db::name('user_bank') ->where(['id'=>$id]) ->update($data);
        if($res){
            return $this->redirect('Bank/index');
        }else{
            return $this->error('修改失败');
        }
    }

    public function del(){
        $id = Request::instance()->param('id');
        $res = db('user_bank')->where(array('id'=>$id))->delete();
        if ($res) {
            $this->redirect("Bank/index");
        } else {
            $this->error('删除失败');
        }
    }

    public function defBank($id, $order=false){
        $uid = session(config('USER_ID'));

        # 全都修改为0
        $set0 = Db::name('user_bank') -> where(['userid'=>$uid]) -> update(['type'=>0]);

        $set1 = Db::name('user_bank') -> where(['id'=>$id, 'userid'=>$uid]) -> update(['type'=>1]);
        if($set1){
            if($order){
                return true;
            }else{
                return $this->redirect("Bank/index");
                // return $this->success('设置成功', "Address/index");
            }
            
        }

    }


}
