<?php  
namespace app\index\controller;
use think\Controller;
use think\Config;
use think\Db;
use think\Session;
/**
*2017-9-7 by ztf 
*/
class Sendsms extends controller
{
    public function verify(){
        $phone = $_GET['phone'];
        $host="http://106.ihuyi.com/webservice/sms.php?method=GetNum";
        $username=iconv('UTF-8','UTF-8','cf_57407966');
        $password=iconv('UTF-8', 'UTF-8','e4bfc480419da57b836ec07e2abdfe89');
        $code = rand(100000,999999);
        session('verify_code', $code);
        session('phone', $phone);
        $data='您的验证码是：【'.$code.'】。请不要把验证码泄露给其他人。如非本人操作，可不用理会！';//要发送的短信内容
        $content=mb_convert_encoding("$data",'UTF-8', 'UTF-8');

        $url='http://106.ihuyi.com/webservice/sms.php?method=Submit&account='.$username.'&password='.$password.'&mobile='.$phone.'&content='.$content;

        $ch = curl_init();
        $timeout = 5;
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $result = curl_exec($ch);
        curl_close($ch);

        echo $result;
    }

    //检查电话是否注册过
    public function checkPhone(){
        $phone = input('get.phone');
        $result = Db::table('keep_users')->where('mobile',$phone)->find();
        if ($result==null) {
            echo true;
        }else{
            echo false;
        }
    }
}
?>