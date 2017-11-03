<?php
namespace app\index\controller;
use app\common\controller\Common; 
use app\extend\controller\Mall as Mall;
use think\Controller;
use think\Config;
use think\Session;
use think\Db;
// use think\Image;

class Goods extends controller
{

    public function detail(){

        if(Session::get(Config::get('USER_ID'))){
            $user = decodeCookie('user');
        }
        $id = input('id', 0, 'intval');
        
        $config = mallConfig();
        $this->assign('config', ['page_title'=>'商品详情', 'template'=>$config['mall_template']['value'] ]);

        $mallObj = new Mall();
        $goods = $mallObj->getGoodsDetail($id); //获取商品详情

        if($goods['status']){
            // return dump($goods);
            $this->assign('goods', $goods['data']);
            return $this->fetch('detail');
        }else{

        }
    
    
        
    }


#没啥用
    function CreateSmallImage( $OldImagePath, $NewImagePath, $NewWidth=1, $NewHeight=134)
    {
        // 取出原图，获得图形信息getimagesize参数说明：0(宽),1(高),2(1gif/2jpg/3png),3(width="638" height="340")
        $OldImageInfo = getimagesize($OldImagePath);
        if ( $OldImageInfo[2] == 1 ) $OldImg = @imagecreatefromgif($OldImagePath);
        elseif ( $OldImageInfo[2] == 2 ) $OldImg = @imagecreatefromjpeg($OldImagePath);
        else $OldImg = @imagecreatefrompng($OldImagePath);
        // 创建图形,imagecreate参数说明：宽,高
        $NewImg = imagecreatetruecolor( $NewWidth, $NewHeight );
        //创建色彩,参数：图形,red(0-255),green(0-255),blue(0-255)
        $black = ImageColorAllocate( $NewImg, 0, 0, 0 ); //黑色
        $white = ImageColorAllocate( $NewImg, 255, 255, 255 ); //白色
        $red  = ImageColorAllocate( $NewImg, 255, 0, 0 ); //红色
        $blue = ImageColorAllocate( $NewImg, 0, 0, 255 ); //蓝色
        $other = ImageColorAllocate( $NewImg, 0, 255, 0 );
        //新图形高宽处理
        $WriteNewWidth = $NewHeight*($OldImageInfo[0] / $OldImageInfo[1]); //要写入的高度
        $WriteNewHeight = $NewWidth*($OldImageInfo[1] / $OldImageInfo[0]); //要写入的宽度
        //这样处理图片比例会失调，但可以填满背景
        if ($OldImageInfo[0] / $NewWidth > $org_info[1] / $NewHeight) {
        $WriteNewWidth = $NewWidth;
        $WriteNewHeight = $NewWidth / ($OldImageInfo[0] / $OldImageInfo[1]);
        } else {
        $WriteNewWidth = $NewHeight * ($OldImageInfo[0] / $OldImageInfo[1]);
        $WriteNewHeight = $NewHeight;
        }
        //以$NewHeight为基础,如果新宽小于或等于$NewWidth,则成立
        if ( $WriteNewWidth <= $NewWidth ) {
        $WriteNewWidth = $WriteNewWidth; //用判断后的大小
        $WriteNewHeight = $NewHeight; //用规定的大小
        $WriteX = floor( ($NewWidth-$WriteNewWidth) / 2 ); //在新图片上写入的X位置计算
        $WriteY = 0;
        } else {
        $WriteNewWidth = $NewWidth; // 用规定的大小
        $WriteNewHeight = $WriteNewHeight; //用判断后的大小
        $WriteX = 0;
        $WriteY = floor( ($NewHeight-$WriteNewHeight) / 2 ); //在新图片上写入的X位置计算
        }
        //旧图形缩小后,写入到新图形上(复制),imagecopyresized参数说明：新旧, 新xy旧xy, 新宽高旧宽高
        @imageCopyreSampled( $NewImg, $OldImg, $WriteX, $WriteY, 0, 0, $WriteNewWidth, $WriteNewHeight, $OldImageInfo[0], $OldImageInfo[1] );
        //保存文件
        //  @imagegif( $NewImg, $NewImagePath );
        @imagejpeg($NewImg, $NewImagePath, 100);
        //结束图形
        @imagedestroy($NewImg);
        }


}