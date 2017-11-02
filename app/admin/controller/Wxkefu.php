<?php
namespace app\admin\controller;
use app\common\controller\Common; 
use app\admin\controller\Wechat as Wechat;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;

class Wxkefu extends Common
{
    public function index()
    {   
        $navid = input('navid', 22, 'intval');
        $nav = adminNav();
        $keyword = input('post.keyword', '', 'htmlspecialchars,trim');
        $list = db('wechat_custom', [], false) ->order('id desc') -> paginate();
        $header =  ['title'=>'扩展管理->微信设置->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
            'form'=>'list', 'navid'=>$navid ]; 
        $this->assign('list', $list);
        $this->assign('header', $header);
        $this->assign('keyword', $keyword?$keyword:'');
        return $this->fetch();
    }

    public function add(){
        if(request()->post()){
            return $this->dataPost('add');
        }
        $navid = input('navid', 22, 'intval');
        $nav = adminNav();
        $this->assign('header', ['title'=>'增加客服', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('wxkefu');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $navid = input('navid', 0, 'intval');
        $nav = adminNav();
        $id = input('id', 0, 'intval');
        $result = db('wechat_custom', [], false) -> where(array('id'=>$id)) -> find();
        $this->assign('result', $result);
        $this->assign('header', ['title'=>'修改客服:  【'.$result['account'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('wxkefu');
    }

    public function dataPost($type=''){
        $post = request()->post();
        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        
        unset($data['navid']);
        if($type=='add'){

            $result = db('wechat_custom', [], false) -> insert($data);
        }else{
            $id = $data['id'];
            #头像上传
            // return dump($_FILES);
            // return dump($_FILES['headimg']['name'][0]);
            if(!empty($_FILES['headimg']['name'])){
                $upload = uploadHeadImg('images'.DS.'headimg');

                if($upload['status']){
                    $data['headimg'] = $upload['path'][0];
                }else{
                    return $this->error('头像上传失败');
                }
            }
            unset($data['id']);
            $result = db('wechat_custom', [], false) -> where(array('id'=>$id)) ->update($data);
        }

        
        if($result){
            // return $this->success('成功', "request()->controller()/index");
            return $this->success('成功', "Wxkefu/index");
        }else{
            return $this->error('失败');
        }
    }





















    #========================================================================
    # 获取在线客服信息
    public function getOnlineKF(){
        $wechat = new Wechat();
        $wxconf = getWxconf();
        $url = $wxconf['GET_ONLINE_KEFU']['value'].$wechat->access_token();
        $response = httpsGet($url);
        return var_dump($response);
    }

    #获取聊天记录
    public function getChatRecord(){
        $wechat = new Wechat();
        $wxconf = getWxconf();
        $url = $wxconf['GET_CONVERSION_RECORD']['value'].$wechat->access_token();
        $data = '{
            "starttime" : 987654321,
            "endtime" : 987654321,
            "msgid" : 1,
            "number" : 10000
        }';
        $response = httpsPost($url, $data);
        return var_dump($response);
    }

    #创建客服会话
    public function createKFConversation(){
        $wechat = new Wechat();
        $wxconf = getWxconf();
        $url = $wxconf['OPEN_CONVERSION']['value'].$wechat->access_token();
        $response = httpsPost($url);
        return var_dump($response);
        $res = json_decode($response, true);
        if($res['errcode'] == 0 && $res['errmsg'] == 'ok'){
            return $this->success('创建客服会话成功', 'Wxkefu/index');  
        }else{
            return $this->error('创建客服会话失败');
        }
    }

    #关闭客服会话
    public function closeKFConversation(){
        $wechat = new Wechat();
        $wxconf = getWxconf();
        $url = $wxconf['CLOSE_CONVERSION']['value'].$wechat->access_token();
        $response = httpsPost($url);
        return var_dump($response);
        $res = json_decode($response, true);
        if($res['errcode'] == 0 && $res['errmsg'] == 'ok'){
            return $this->success('关闭客服会话成功', 'Wxkefu/index');  
        }else{
            return $this->error('关闭客服会话失败');
        }
    }

    #获取用户会话状态
    public function getConversationStatus(){
        $wechat = new Wechat();
        $wxconf = getWxconf();
        $url = $wxconf['GET_CONVERSION_STATUS']['value'].$wechat->access_token();
        $response = httpsGet($url);
        return var_dump($response);
    }

    #获取会话列表
    public function getConversationList(){
        $wechat = new Wechat();
        $wxconf = getWxconf();
        $url = $wxconf['GET_CONVERSION_LIST']['value'].$wechat->access_token();
        $response = httpsGet($url);
        return var_dump($response);
    }

}
