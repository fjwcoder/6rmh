<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 商城商品
# |
# | email: fjwcoder@gmail.com
# +-------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Manage;
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Session;
use think\Cookie;
use think\Config;
use think\Request;
use think\Db;
use think\Cache;
// use think\Paginator;

#+-----------------------------------
#| navid 当前页面id
#|
#+-----------------------------------
class Goods extends Manage
{
    // dump(request()->module());//模块
    // dump(request()->controller()); //控制器
    // dump(request()->action()); //方法
    // private $module = '';
    // private $controller = '';

    // public function _initialize(){
    //     $this->module = request()->module();
    //     $this->controller = request()->controller();
    // }
    #用户列表
    public function index()
    {   
        $navid = input('navid', 30, 'intval');
        $nav = adminNav();
        $keyword = input('post.keyword', '', 'htmlspecialchars,trim');
        
        // if(!empty($keyword)){
        //     $where = ['title', 'like', "%$keyword%"];
        // } 
        // $list = db('admin_member', [], false) ->where($where) -> field('password, encrypt', true) -> paginate(15);
        // $user = getUserInfo('admin_member', Session::get(Config::get('USER_KEY')));
        // $where = "a.level>=$user[level]";
        // if($user['branch']>0){
        //     $where .= " and a.branch=$user[branch] ";
        // }
        $list = Db::name('goods') ->alias('a')
        //  -> join('keep_admin_branch b', 'a.branch=b.id', 'LEFT')
        //  -> join('keep_admin_level c', 'a.level=c.id', 'LEFT')
        //  -> where($where)
        //  -> field(array('a.id', 'a.name', 'a.title', 'a.email', 'a.authority', 'a.status', 'a.headimg', 'b.title as branch', 'c.title as level'))
         -> field(['id', 'name', 'price', 'amount', 'status']) 
         -> paginate();

        $this->assign('list', $list); 
        $header =  ['title'=>'扩展管理->后台用户->'.$nav[$navid]['title'], 'icon'=>$nav[$navid]['icon'], 
            'form'=>'list', 'navid'=>$navid ]; 
        $this->assign('header', $header);
        $this->assign('keyword', $keyword?$keyword:'');
        return $this->fetch();
    }
    
    public function add(){
        if(request()->post()){
            return $this->dataPost('add');
        }
        $mallObj = new Mall();
        $navid = input('navid', 30, 'intval');
        $nav = adminNav();

        $this->assign('category', $mallObj->getCatetory('status'));
        $this->assign('promotion', $mallObj->getPromotion());
        $this->assign('service', $mallObj->getService()); //服务可以有多个
        $this->assign('brand', $mallObj->getBrand());//品牌可以多个

        $this->assign('sercheck', []);
        $this->assign('picture', []);
        $this->assign('header', ['title'=>'添加商品', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('goods');

    }

    public function edit(){

        if(request()->post()){
            return $this->dataPost('edit');
        }
        $mallObj = new Mall();
        $navid = input('navid', 0, 'intval');
        $nav = adminNav();
        $id = input('id', 0, 'intval');

        $this->assign('category', $mallObj->getCatetory('status'));
        $this->assign('promotion', $mallObj->getPromotion());
        $this->assign('service', $mallObj->getService()); //服务可以有多个
        $this->assign('brand', $mallObj->getBrand());//品牌可以多个

        $goods = Db::name('goods') -> alias('a') 
            -> join('goods_detail b', 'a.id=b.gid', 'LEFT')
            -> where(['a.id'=>$id, 'a.userid'=>'b.uid']) -> find();

        $this->assign('sercheck', explode(',', $goods['service']));

        $this->assign('picture', $mallObj->getGoodsImg($id));
        

        $goods['detail'] = htmlspecialchars_decode(html_entity_decode($goods['detail']));
        $this->assign('result', $goods);
        $this->assign('header', ['title'=>'编辑商品:  【'.$goods['name'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);

        return $this->fetch('goods');
    }

    public function dataPost($type=''){
        $post = request()->post();

        if(!empty($_FILES)){
            $upload = uploadImg('goods'.DS.'image');
            if($upload['status'] == false){
                return $this->error('图片上传失败！'); exit;
            }
        }

        $data['catid_list'] = $post['category'];
        $id_list = explode(',', $data['catid_list'] );
        unset($post['navid'], $post['category']);
        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        $data['catid'] = $id_list[count($id_list)-1];
        $data['point'] = empty($data['point'])?$data['price']:$data['point'];
        if(!empty($data['detail'])){
            $detail = htmlspecialchars(stripslashes(trim($data['detail'])));
            unset($data['detail']);
        }   

        #处理促销活动和关联服务
        if(!empty($data['services'])){
            $data['service'] = '0';
            foreach($data['services'] as $k=>$v){
                $data['service'] .= ','.$v;
            }
            unset($data['services']);
        }
        
        if($type=='add'){
            
            if(Session::get(Config::get('ADMIN_AUTH_NAME'))){
                $data['adduser'] = Session::get(Config::get('ADMIN_AUTH_NAME'));
                $data['userid'] = 0;
            }
            $data['addtime'] = time();
            
            $result = Db::name('goods') -> insert($data);
            $id = Db::name('goods') ->getLastInsID();
            if($id>0){
                if(isset($detail)){
                    $result = Db::name('goods_detail') -> insert(['uid'=>$data['userid'], 'gid'=>$id, 'detail'=>$detail]);
                }
            }
        }else{
            $id = $data['id'];
            unset($data['id']);
            if(isset($detail)){
                $updetail = Db::name('goods_detail', [], false) -> where(array('gid'=>$id)) -> update(['detail'=>$detail]);
            }
            $result = Db::name('goods', [], false) -> where(array('id'=>$id)) ->update($data);    
        }

        if(isset($upload['path'])){
            foreach($upload['path'] as $k=>$v){
                $img[$k] = ['gid'=>$id, 'pic'=>$v];
            }
            if(isset($img)){
                $insertImg = Db::name('goods_picture') -> insertAll($img);
            }else{
                $insertImg = true;
            }
            
        }

        if($type=='add'){
            if($result){
                return $this->success('添加成功', request()->controller().'/index');
            }else{
                return $this->error('添加失败');
            }
        }else{
            if($result>0 || $updetail>0 || $insertImg){
                return $this->success('修改成功', request()->controller().'/index');
            }else{
                return $this->error('修改失败或没有修改项');
            }
        }
        
        
    }


    public function delImg(){
        $id = input('id', 0, 'intval');



    }


}
