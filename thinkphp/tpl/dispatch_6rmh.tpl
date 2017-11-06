
{__NOLAYOUT__}
<!--+----------------------------------------------------
    | Modify by FJW IN 2017-5-20.
    |
    |
    +----------------------------------------------------
-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>六耳猕猴提示标题</title>
    <link rel="stylesheet" href="http://cdn.static.runoob.com/libs/bootstrap/3.3.7/css/bootstrap.min.css">
    <style type="text/css">
        .system-message{position: absolute; width: 99%; top: 0; bottom: 0; padding: 0; margin: 0;}
        .info-panel{width: 1200px;margin: 0 auto;}
        .info-title{height: 80px;position: absolute;top:30px;left:18%;padding: 30 15%;line-height: 60px; font-size: 36px;font-weight: 600; color: #7b7b7b;}
        .info-content{text-align: center; height: 360px; line-height: 330px;}
        .info-content h1{font-size: 70px; color: #7b7b7b;}
        .info-content p{font-size: 40px; color: #7b7b7b;}
        h1, p{display: inline-block;}
        .glyphicon-ok{color: #00ec00;}
        .glyphicon-remove{color: #ff2d2d;}
        .info-jump{height: 50px;position: absolute;left:20%; line-height: 50px;font-size: 20px;}

    </style>
</head>
<body>
    <div class="system-message">
        <div class="info-panel">
            <div class="info-title">
                <a href="javascript: void(0);">
                    <img class="login-logo pull-left" src="__STATIC__/images/company/logo.jpg"/>
                </a>
                六耳猕猴提示标题
            </div>
            <div class="info-content">
                <?php switch ($code) {?>
                    <?php case 1:?>
                    <h1>
                        <span class="glyphicon glyphicon-ok"></span>
                    </h1>
                    <p class="success"><?php echo(strip_tags($msg));?></p>
                    <?php break;?>
                    <?php case 0:?>
                    <h1>
                        <span class="glyphicon glyphicon-remove"></span>
                    </h1>
                    <p class="error"><?php echo(strip_tags($msg));?></p>
                    <?php break;?>
                <?php } ?>
            </div>
            <div class="info-jump">
                <p class="jump">
                    页面自动 <a id="href" href="<?php echo($url);?>">跳转</a> 等待时间： <b id="wait"><?php echo($wait);?></b>
                </p>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        (function(){
            var wait = document.getElementById('wait'),
                href = document.getElementById('href').href;
            var interval = setInterval(function(){
                var time = --wait.innerHTML;
                if(time <= 0) {
                    location.href = href;
                    clearInterval(interval);
                };
            }, 1000);
        })();
    </script>
</body>
</html>
