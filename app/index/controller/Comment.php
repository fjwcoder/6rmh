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
        $orderinfo = db('order', [], false) -> where(array('order_id'=>$order_id)) ->find();
        $comment = db('order_detail', [], false) -> where(array('order_id'=>$order_id)) ->select();
        // return dump($comment);
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
        $order_id = input('order_id', '', 'htmlspecialchars,trim');
        $id = session(config('USER_ID'));
        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
        }
        $post = request()->post();
        foreach($post['comment'] as $k=>$v){
            $data[$k] = $v;
            $data[$k]['uid'] = $user['id'];
            $data[$k]['nickname'] = $user['nickname'];
            $data[$k]['headimgurl'] = $user['headimgurl'];
            $data[$k]['addtime'] = time();
        }
        foreach($data as $k=>$v){
            $star[$k] =$v["comment_star"];
            if($star[$k] == 0){
                return $this->error('请完整的填写评价');exit;
            }
        }
        $res = Db::name('goods_comment') ->insertAll($data);
        if($res){
            Db::name('order') -> where(['order_id'=>$order_id, 'status'=>4, 'pay_status'=>1]) -> update(['status'=>5]);
            return $this->redirect('Order/index');
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
    }

}
