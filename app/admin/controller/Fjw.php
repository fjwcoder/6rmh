<?php

namespace app\admin\controller;
use app\common\controller\Common;
use think\Controller;
use think\Db;
use think\Config;
// use think\Model;
// use think\Request;
class Fjw extends Controller
{   
    public function index(){
        Config::get('config.json');
        return dump(config());
    }
    //解密
    public function decode(){
        $user = db('admin_member') -> where(array('id'=>1)) -> find();
        // $pass = 'admin';
        // $crypt = substr(md5($pass), 0, 4);
        // $passcode = cryptCode($pass, 'ENCODE', $crypt);
        // echo $passcode.'<br>';
        // echo $crypt.'<br>';
        // die;
        $passcode = $user['password'];
        $crypt = $user['encrypt'];

        $password = cryptCode($passcode, 'DECODE', $crypt);
        echo $password.'<br>';
        die;

        // return $passcode?$passcode:'空';
        
    }

    // 递归
    public function cate(){
        $result = db('categories', [], false)-> order('parentid_list') -> select();  //最主要的排序是order by ！！！！！！！！！
        global $nav, $html;
        $nav = $result;
        $html = '';
        $html .= '<div class="panel-group" id="accordion">';
        $this->digui(0);
        $html .= '</div>';
        return $html;
    }

    public function digui($fid=0){
        global $nav, $html;
        for($i=0; $i<count($nav); $i++){
            if($nav[$i]['parentid'] == $fid){
                $html .= '<div>';
                if($nav[$i]['isnode'] == 1){
                    $html .= '<a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" >';
                    $html .= '<div class="panel-heading">'.$nav[$i]['name'].'【'.$nav[$i]['parentid_list'].'】'.'</div>';
                    $html .= '</a>';
                    $html .= '<div id="collapseOne" class="panel-collapse collapse">';
                }else{
                    

                }
                $this -> digui($nav[$i]['id']);
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
            
        }
        
    }



    public function index99(){
        $def = time();
        echo $def.'<br>';
        echo date('Y-m-d H:i:s', $def).'<br>';
        $add = $def+7000;
        echo $add.'<br>';
        return date('Y-m-d H:i:s', $add).'<br>';
    }

    public function ManagerPwd(){
       //设置管理员密码
       $pwd = 'admin';
       $crypwd = md5($pwd);
       $string = $crypwd.$pwd.$crypwd;
       $crystr = md5($string);
       return $crystr;

    }
    public function Answer(){
        // $res = action('question/getLetter', array(1));
        
        // $id = 1;
        // $qid = 1;
        // $data = array();
        // for($i=0; $i<5; $i++){
        //     $data[$i] = ['id'=>$id, 'qid'=>$qid, 'aid'=>$i,
        //         'letter'=>action('question/getLetter', array($i)),
        //         'title'=>$i, 'point'=>$i*2, 'status'=>1, 'remark'=>''];
        // }
        $data['aid'] = 3; //答案id
        $data['title'] = '棒极了'; //答案title
        $data['point'] = 10; //答案分值
        $data['id'] = 10; //问题id
        $data['qid'] = 1; //问卷id
        $data['letter'] = action('question/getLetter', array($data['aid'])); //答案字母
        $data['status'] = 1;
        $data['remark'] = '';
        // return dump($data);
        $res = db('question_answer', [], false) -> insert($data);
        return var_export($res);
    }

    public function question(){
        
        $data = [
            'id'=>10,
            'qid' => 1,
            'question' => '您对自己的法务情况是否满意？',
        ];
        $res = db('question_question', [], false) -> insert($data);
        return var_export($res);
    }


    public function naire(){
        
        $data = [
            'title' => '安进法务测试系统',
            'sec_title' => '',
            'topic' => '良好法务体系建设是公司发展的基石。安进法务作为您事业的护航者与助推者，在COSO内控模型的基础上结合多年法律服务经验，
                独家开发出本测试系统，为您简要判断公司的法务能力。本测试将花费您大约2分钟的时间，测试结果仅供参考。',
            'direction' => '',
            'addtime' => time(),
            'start_time' => time(),
            'end_time' => '',
            'status' => 1,
            'remark' => ''
        ];
        $res = db('question_naire', [], false) -> insert($data);
        return print_r($res);
    }

}