<?php
# +-------------------------------------------------------------
# | CREATE by FJW IN 2017-5-17.
# | 商城商品
# |
# | email: fjwcoder@gmail.com
# +-------------------------------------------------------------
namespace app\admin\controller;
use app\common\controller\Manage;
use app\common\controller\Common;
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
        $navid = input('navid', 30, 'intval');
        $nav = adminNav();
        $category = db('mall_category', [], false) -> where(['status'=>1]) -> order('id_list, sort') -> select();
        $this->assign('category', $category);
        $brand = db('mall_brand', [], false) -> where(['status'=>1]) -> order('id') -> select();
        $this->assign('brand', $brand);
        $this->assign('header', ['title'=>'添加商品', 'icon'=>$nav[$navid]['icon'], 'form'=>'add', 'navid'=>$navid]);
        return $this->fetch('goods');

    }

    public function edit(){
        
        if(request()->post()){
            return $this->dataPost('edit');
        }
        $navid = input('navid', 0, 'intval');
        $nav = adminNav();
        $id = input('id', 0, 'intval');

        $category = db('mall_category', [], false) -> where(['status'=>1]) -> order('id_list, sort') -> select();
        $this->assign('category', $category);
        $brand = db('mall_brand', [], false) -> where(['status'=>1]) -> order('id') -> select();
        $this->assign('brand', $brand);

        $goods = Db::name('goods') -> alias('a') 
            -> join('goods_detail b', 'a.id=b.gid', 'LEFT')
            -> where(['a.id'=>$id, 'a.userid'=>'b.uid']) -> find();

        $goods['detail'] = htmlspecialchars_decode(html_entity_decode($goods['detail']));
        $this->assign('result', $goods);
        $this->assign('header', ['title'=>'编辑商品:  【'.$goods['name'].'】', 'icon'=>$nav[$navid]['icon'], 'form'=>'edit', 'navid'=>$navid]);
        return $this->fetch('goods');
    }

    public function dataPost($type=''){
        $post = request()->post();
        $data['catid_list'] = $post['category'];
        $id_list = explode(',', $data['catid_list'] );
        unset($post['navid'], $post['category']);
        foreach($post as $k=>$v){
            $data[$k] = $v;
        }
        $data['catid'] = $id_list[count($id_list)-1];
        $data['point'] = empty($data['point'])?$data['price']:$data['point'];

        $detail = htmlspecialchars(stripslashes($data['editorValue']));
        unset($data['editorValue']);
        if($type=='add'){
            
            if(Session::get(Config::get('ADMIN_AUTH_NAME'))){
                $data['adduser'] = Session::get(Config::get('ADMIN_AUTH_NAME'));
                $data['userid'] = 0;
            }
            $data['addtime'] = time();
            
            $result = Db::name('goods') -> insert($data);
            $id = Db::name('goods') ->getLastInsID();
            if($id>0 && !empty($detail)){
                $result = Db::name('goods_detail') -> insert(['uid'=>$data['userid'], 'gid'=>$id, 'detail'=>$detail]);
            }
            
            
        }else{
            $id = $data['id'];
            

            // unset($data['id'], $data['password'] );
            // $result = db('admin_member', [], false) -> where(array('id'=>$id)) ->update($data);
        }

        
        if($result){
            return $this->success('成功', request()->controller().'/index');
        }else{
            return $this->error('失败');
        }
    }

    public function showDetail(){
        $id = 2;
        $detail = db('goods_detail', [], false) -> where(['gid'=>2]) -> find();

        echo htmlspecialchars_decode(html_entity_decode($detail['detail']));

    }

}
