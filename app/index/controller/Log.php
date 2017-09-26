<?php
namespace app\index\controller;
use app\common\controller\Common; 
// use app\common\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Paginator;
use think\Db;

class Log extends Common
{
	public function index(){
        
        $id = session(config('USER_ID'));
        $type = input('type', 'point', 'htmlspecialchars,trim');
        if(!empty($_POST)){
            $begintime = $_POST['begintime'];
            $endtime = $_POST['endtime'];
        }
        
        $this->assign('time', ['status'=>false]);
        if(isset($begintime) && isset($endtime)){ //设置了时间段
            if(date('Y-m-d H:i:s',strtotime($begintime))==$begintime && date('Y-m-d H:i:s',strtotime($endtime))==$endtime){
                $list = db($type.'_log', [], false)->where("addtime BETWEEN '{$begintime}' and '{$endtime}'") ->paginate();  
                $this->assign('time', ['status'=>true, 'begintime'=>$begintime, 'endtime'=>$endtime]);
            }else{
                return $this->error('日期格式不正确'); exit;
            }
        }else{
            $list = db($type.'_log', [], false) -> where(array('userid'=>$id)) ->order('addtime', 'DESC') ->paginate();
        }
        $this->assign('list', $list); 
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'明细中心', 'template'=>$config['mall_template']['value'] ]);


        return $this->fetch($type);
        
    }
    
    // public function search(){
    //     $id = session(config('USER_ID'));
    //     $begintime = $_POST['begintime'];
    //     $endtime = $_POST['endtime'];
    //     $config = mallConfig();
    //     $type = input('type', '', 'htmlspecialchars,trim');

    //     $this->assign('config', ['page_title'=>'明细中心', 'template'=>$config['mall_template']['value'] 
    //     ]);
        
        

    //     return $this->fetch($type);
    // }

}
