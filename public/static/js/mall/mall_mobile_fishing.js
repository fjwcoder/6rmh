var qiandao = {
    isfishing: false, //是否正在钓鱼
    river_width: 0,
    river_height: 0,
    canvas: '',  // 画布
    context: '', //二维化画布
    float_height: 60, //鱼漂的长度
    setShake: '', //
    fishing_time: 5000, //钓鱼时间
    wait_time: 6, //提示收杆等待时间 三秒(每0.5秒浮动一次)
    float_count: 1, //浮动次数
    bar_top: 0, //鱼竿顶部
    bar_left: 0, //鱼竿左边
    bait: 0, //当前鱼饵
    point: 0, //当前积分
};

//获取点击坐标, 并开始钓鱼动画
function getClickPosition(event){
    console.log('2.获取点击坐标点，出现鱼漂');
    if(event.originalEvent.targetTouches.length == 1){
        event.preventDefault();// 阻止浏览器默认事件 important
        var touch = event.originalEvent.targetTouches[0];
        var mapx = parseInt(touch.pageX); //页面的X点
        var mapy = parseInt(touch.pageY); //页面的Y点
        var pointx = mapx; // 点击坐标的x点
        var pointy = mapy-qiandao.canvas.top; // 点击坐标的y点


        // 画鱼线===============================================
        qiandao.context.beginPath();
        qiandao.context.lineWidth=1;
        with(qiandao.context){
            moveTo(qiandao.bar_left, qiandao.bar_top);
            if(pointx < qiandao.bar_left-40){
                quadraticCurveTo(qiandao.bar_left, pointy, pointx+20, pointy-qiandao.bar_top);
            }else if(pointx > qiandao.bar_left+30){
                quadraticCurveTo(qiandao.bar_left, pointy, pointx-20, pointy-qiandao.bar_top);
            }else{
                quadraticCurveTo(qiandao.bar_left, pointy, pointx, pointy-qiandao.bar_top);
            }
        }
        qiandao.context.strokeStyle = "rgba(255,255,255, 0.4)";
        qiandao.context.stroke();
        // =====================================================

        $("#fish-float").css({"position":"absolute" ,"display":"block", "top":mapy-qiandao.float_height, "left": pointx});
        setTimeout(function(){
            $("#fish-float").animate({
                top: '+='+parseInt(qiandao.float_height/3)+'px',
                height: parseInt(qiandao.float_height/3)*2+'px',
                backgroundSize: 'auto 60%',
            }, 2000);
            /* ************************获取数据************************ */
            setTimeout(function() {
                qiandao.setShake = setInterval(function(){

                    if(qiandao.float_count>6){
                        loseFish(); //脱钩执行的方法
                    }else{
                        if(qiandao.float_count%2 != 0){ //奇数
                            $("#fish-float").animate({top: '+=20px', height: '20px',}, 500);//往下沉
                        }else{
                            $("#fish-float").animate({top: '-=20px', height: '40px',}, 500);//往上浮
                        }
                    }
                    qiandao.float_count++;
                }, 500);

                $("#fish-finish").fadeIn();
            }, qiandao.fishing_time);
        }, 500);

    }
}

// 钓鱼执行的方法
function getFishingResult(){
    console.log('4.执行收杆方法');
    clearInterval(qiandao.setShake);
    qiandao.float_count = 0;
    $.ajax({
        type: 'POST',
        url: '/Index/Fishing/fishing',
        success: function(response){
            cleanCanvas();
            console.log(response);
            if(response['status']){
                $('#fishing-panel').css({'background':'url(../../static/images/mall/'+response.type+'.png) no-repeat',
                     'display':'block'});
                
                $('#fishing-panel-content').html(response.content);
                
                qiandao.bait -= response.bait;
                qiandao.point -= response.point;
                if(response.type == 'point'){
                    qiandao.point += response.value;
                }

                refreshProperty();


            }else{
                showInfo(response['status'], '温馨提示', response['content']);
            }

            qiandao.isfishing = false;
        },
        error: function(e){
            cleanCanvas();
            qiandao.isfishing = false;
            showInfo(false, '温馨提示', '数据错误');
        }
    });
    
}

function refreshProperty(){
    $('#bait').html(qiandao.bait);
    $('#point').html(qiandao.point);
}

// 脱钩后执行的方法方法
function loseFish(){
    $.ajax({
        type: 'POST',
        url: '/Index/Fishing/loseFish',
        success: function(response){
            qiandao.bait -= response.bait;
            qiandao.point -= response.point;
            refreshProperty();
        },
        error: function(e){
            console.log(e);
        }

    });
    clearInterval(qiandao.setShake); //结束晃动
    cleanCanvas(); //清空画布
    qiandao.isfishing = false;
    qiandao.float_count = 0;
    showInfo(false, '温馨提示', '狡猾的鱼儿脱钩了'); //提示信息
}

// 显示提示信息
function showInfo(status, title, content){
    if(status === false){
        $('.info-panel-title').html(title);
        $('.info-panel-content').html(content);
        $('#info-panel').fadeIn();
        // $('#fish-finish').fadeOut();
    }else{
        $('.info-panel-title').html(title);
        $('.info-panel-content').html(content);
        $('#info-panel').fadeIn();
        
    }
    $('#fish-finish').fadeOut();
}



//获取水面的坐标
$(document).ready(function(){
    qiandao.bait = parseInt($('#bait').html());
    qiandao.point = parseInt($('#point').html());

    qiandao.river_width = $('#river-panel').width();
    qiandao.river_height = $('#river-panel').height();

    qiandao.canvas = document.getElementById("river-canvas"); //获取画布
    qiandao.context = qiandao.canvas.getContext("2d"); 

    qiandao.canvas.width = qiandao.river_width; //水面的宽度
    qiandao.canvas.height = $('#river-canvas').height(); // 水面的高度
    qiandao.canvas.left = $('#river-canvas').offset().left; //画布的左顶点
    qiandao.canvas.top = $('#river-canvas').offset().top; //画布的上顶点


    qiandao.float_height = $('#fish-float').height(); //获取鱼漂的高度

    //获取鱼竿顶端的位置
    qiandao.bar_left = qiandao.canvas.width*0.625;
    qiandao.bar_top = 0; //qiandao.canvas.height*0.064;

    
    
    
    

    // $("#river-canvas").bind('touchstart', function(event){
    $("#river-panel").bind('touchstart', function(event){
        
        
            
        if(!qiandao.isfishing){
            
            if(qiandao.bait < 1){
                var str = '鱼饵不足'; //<br><a href="#">前往获取</a>';
                showInfo(false, '温馨提示', str);

            }else if(qiandao.point < 20){
                var str = '积分不足'; //<br><a href="#">前往获取</a>';
                showInfo(false, '温馨提示', str);

            }else{

                qiandao.isfishing = true;
                qiandao.context.clearRect(0, 0, qiandao.river_width, qiandao.river_height);//清空画布
                getClickPosition(event);
                $('#mask').fadeIn();
            }
  
        }
        
    });

    // 点击收杆
    $("#fish-finish").click(function(){
        if(qiandao.isfishing){ //正在钓鱼
            if(qiandao.float_count < 6){
                getFishingResult();
            }else{
                loseFish();
            }
            
        }
    });


    $("#mask, .top-panel").click(function(){
        if(qiandao.isfishing === false){
            $('#mask').fadeOut();
            $('.top-panel').fadeOut();

        }
    });



});

function cleanCanvas(){

    // 鱼漂动画
    $("#fish-float").animate({
		top: "+="+parseInt(qiandao.float_height/3)*2+"px",
		height: "0px",
	}, 300, function(){$("#fish-float").css({"display":"none", "height": qiandao.float_height+"px"}); });
    qiandao.context.clearRect(0, 0, qiandao.canvas.width, qiandao.canvas.height);
    $('#fish-finish').fadeOut();

}