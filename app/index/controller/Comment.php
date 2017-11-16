<?php
namespace app\index\controller;
use app\common\controller\Common; 
// use app\common\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Paginator;
use think\Db;

class Comment extends Common
{
	public function index(){
        $order_id = input('id', '', 'htmlspecialchars,trim');
        $id = session(config('USER_ID'));
        $list = db('bait_log', [], false) -> where(array('userid'=>$id)) ->order('addtime', 'DESC') ->paginate();
        // 商品信息
        $orderinfo = db('order_detail', [], false) -> where(array('order_id'=>$order_id)) ->find();
        $comment = db('order_detail', [], false) -> where(array('order_id'=>$order_id)) ->select();
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'商品评价', 'template'=>$config['mall_template']['value'] 
            ]);
        $this->assign('list', $list);
        $this->assign('orderinfo', $orderinfo);
        $this->assign('comment', $comment);
        return $this->fetch();
        
    }

    #商品评论
    public function comment(){
        $order_id = input('id', '', 'htmlspecialchars,trim');
        $textarea = input('textarea', '', 'htmlspecialchars,trim');
        $satisfied = input('satisfied', 0, 'intval');
        // $post = request()->post();

        $id = session(config('USER_ID'));

        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
        }
        
        if($textarea == ''){
            return $this->error('请填写商品评价'); exit;
        }
        $comment = db('order_detail', [], false) -> where(array('order_id'=>$order_id)) ->find();
        
        $data['uid'] = $user['id'];
        $data['nickname'] = $user['nickname'];
        $data['headimgurl'] = $user['headimgurl'];
        $data['gid'] = $comment['gid'];
        $data['gname'] = $comment['name'];
        $data['spec'] = $comment['spec'];
        $data['comment_star'] = $satisfied;
        $data['comment'] = $textarea;
        $data['addtime'] = time();

        $res = Db::name('goods_comment') ->insert($data);
        if($res){
            return $this->success('评论成功', 'Order/index');
        }else{
            return $this->error('评论失败');
        }

        #头像上传
        // if(!empty($_FILES['headimg']['name'])){
        //     $upload = uploadHeadImg('images'.DS.'headimage');
        //     if($upload['status']){
        //         $data['headimgurl'] = $upload['path'][0];
        //     }else{
        //         return $this->error('头像上传失败');
        //     }
        // }
        // if('1'=='1'){
        //     return $this->success('修改成功', 'Comment/index');
        // }else{
        //     return $this->error('修改失败');
        // }
    }

    #商品满意度
    public function satisfied(){
        $satisfied = input('satisfied', 0, 'intval');
        return dump($satisfied);
    }

    // public function zan(){
    //     $id = input('id', 0, 'intval');return dump($id);
    //     $list = db('goods_comment', [], false) -> where(array('id'=>$id)) ->setInc('agree');
    //     if($list){
    //         $data['info'] = "ok";
    //         $data['status'] = 1;
    //         $this->ajaxReturn($data);
    //         exit();
    //     }else{
    //         $data['info'] = "fail";
    //         $data['status'] = 0;
    //         $this->ajaxReturn($data);
    //         exit();
    //     }
    // }
    

}
