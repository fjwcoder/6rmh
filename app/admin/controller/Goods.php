<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 商城商品
# |
# | email: fjwcoder@gmail.com
# +-------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Manage;
use app\common\controller\Thumb as Thumb;
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

    #商品列表
    public function index()
    {   
        // return 'here';
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

        $term = Db::name('term') -> where('begintime >= '.time()) ->order('id desc') -> select();
        $this->assign('term', $term);

        $this->assign('category', $mallObj->getCatetory('status'));
        $this->assign('promotion', $mallObj->getPromotion());
        $this->assign('service', $mallObj->getService()); //服务可以有多个
        $this->assign('brand', $mallObj->getBrand());//品牌可以多个

        #设置规格
        $specs = $mallObj->getSpec();
        foreach($specs as $k=>$v){
            $value = explode(';', $v['value']);
            foreach($value as $key=>$val){
                $value[$key] = explode('|', $val); 
            }
            $specs[$k]['value'] = $value;
        }
        // return dump($specs);
        $this->assign('specs', $specs);

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
        $navid = input('navid', 30, 'intval');
        $nav = adminNav();
        $id = input('id', 0, 'intval');

        $term = Db::name('term') -> where('begintime >= '.time()) ->order('id desc') -> select();
        $this->assign('term', $term);

        $this->assign('category', $mallObj->getCatetory('status'));
        $this->assign('promotion', $mallObj->getPromotion());
        $this->assign('service', $mallObj->getService()); //服务可以有多个
        $this->assign('brand', $mallObj->getBrand());//品牌可以多个

        #全部规格
        $specs = $mallObj->getSpec();
        foreach($specs as $k=>$v){
            $value = explode(';', $v['value']);
            foreach($value as $key=>$val){
                $value[$key] = explode('|', $val); 
            }
            $specs[$k]['value'] = $value;
        }
        $this->assign('specs', $specs); 

        


        $goods = Db::name('goods') -> alias('a') 
            -> join('goods_detail b', 'a.id=b.gid', 'LEFT')
            -> where(['a.id'=>$id, 'a.userid'=>'b.uid']) -> find();
        
        $speccheck = $mallObj->getGoodsSpec($id);
        $this->assign('speccheck', empty($speccheck)?[]:$speccheck);

        $this->assign('sercheck', explode(',', $goods['service'])); //服务

        $this->assign('picture', $mallObj->getGoodsImg($id));
        

        $goods['detail'] = htmlspecialchars_decode(html_entity_decode($goods['detail']));
        $this->assign('result', $goods);
        $this->assign('header', ['title'=>'编辑商品:  【'.$goods['name'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);

        return $this->fetch('goods');
    }

    public function dataPost($type=''){
        $advimg = false;
        $images = false;
        $post = request()->post();

        // return dump($post);
        // return dump($_FILES);
        
        if(!empty($_FILES['advimg']['name'][0] )){
            $advimg = true;
        }
        if(!empty($_FILES['images']['name'][0])){
            $images = true;
        }
        #商品详情的图片
        if( $advimg || $images){
            $upload = uploadImg('goods'.DS.'image');
            if($upload['status'] == false){
                return $this->error('图片上传失败！'); exit;
            }

        }else{
            $insertImg = true;
        }

        $data['catid_list'] = $post['category'];
        $id_list = explode(',', $data['catid_list'] );
        #处理规格
        $specs = empty($post['specs'])?[]:$post['specs'];
        
        if(!empty($specs)){
            $data['amount'] = 0;
            foreach($specs as $v){
                $data['amount'] += $v['num'];
            }
            unset($post['amount']);
        }
        

        unset($post['navid'], $post['category'], $post['specs']);

        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        $data['catid'] = $id_list[count($id_list)-1];
        $data['point'] = empty($data['point'])?intval($data['price']):$data['point'];
        if(!empty($data['detail'])){
            $detail = htmlspecialchars(stripslashes(trim($data['detail'])));
            unset($data['detail']);
        }   
        #处理促销活动
        if(!isset($data['promotion'])){
            $data['promotion'] = 0;
        }
        #处理和关联服务
        if(isset($data['services'])){
            $data['service'] = implode(',', $data['services']);
            unset($data['services']);
        }else{
            $data['service'] = '';
        }
        if(empty($data['bait'])){
            $data['bait'] = intval($data['price']/$data['baitprice']); //处理鱼饵数量
        }
        unset($data['baitprice']);

        if($advimg || $type=='add'){ //设置了展示图片
            $data['img'] = $upload['path'][0];
        }

        if($type=='add'){
            
            if(Session::get(Config::get('ADMIN_AUTH_NAME'))){
                $data['adduser'] = Session::get(Config::get('ADMIN_AUTH_NAME'));
                $data['userid'] = 0;
            }
            $data['addtime'] = time();
            
            $result = Db::name('goods') -> insert($data);
            $id = Db::name('goods') ->getLastInsID();
            if($id>0){ //商品插入成功
                if(!empty($specs)){ //商品规格
                    foreach($specs as $k=>$v){
                        $specs[$k]['gid'] = $id;
                    }
                    $result = Db::name('goods_spec') ->insertAll($specs);
                }
                if(isset($detail)){ //商品详情
                    $result = Db::name('goods_detail') -> insert(['uid'=>$data['userid'], 'gid'=>$id, 'detail'=>$detail]);
                }
            }
        }else{
            $id = $data['id']; //商品ID
            unset($data['id']);
            if(!empty($specs)){
                $editspec = [];
                foreach($specs as $k=>$v){
                    $specid = $v['id'];
                    unset($v['id']);
                    if($specid != 0){ //规格ID
                        $upspec = Db::name('goods_spec') -> where(['id'=>$specid]) -> update($v);
                    }else{
                        $v['gid'] = $id;
                        $editspec[] = $v;
                    }
                }

                if(!empty($editspec)){
                    $result = Db::name('goods_spec') ->insertAll($editspec);
                }
            }
            if(isset($detail)){
                $updetail = Db::name('goods_detail', [], false) -> where(array('gid'=>$id)) -> update(['detail'=>$detail]);
            }
            $result = Db::name('goods', [], false) -> where(array('id'=>$id)) ->update($data);    
        }

        
        
        #处理详情图片
        if( isset($upload) && isset($upload['path']) ){
            if($advimg){
                unset($upload['path'][0]);
            }
            foreach($upload['path'] as $k=>$v){
                $img[$k] = ['gid'=>$id, 'pic'=>$v];
            }

            if(isset($img)){
                $delete = Db::name('goods_picture') -> where(['gid'=>$id]) -> delete();
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

    #删除图片
    public function delImg(){
        $id = input('id', 0, 'intval');



    }

 



}
