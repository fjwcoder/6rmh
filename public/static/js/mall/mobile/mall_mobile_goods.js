//设置展示图片的高宽相同
        var scr_height=window.screen.width;
        $('.swiper-container img').css("height",scr_height);

        var mySwiper = new Swiper ('.swiper-container', {
            autoplay : 3000,
            loop: true,
            pagination: '.swiper-pagination',
        })
        //外部切换
        var Swiper1 = new Swiper('.swiper-container-contant',{
            loop:true,
            autoHeight: true,
            iOSEdgeSwipeDetection : true,
            onSlideChangeStart:function(swiper){
                $("html,body").animate({scrollTop:0}, 1);
                $('.current').removeClass('current');
                $('.tab').children().eq(swiper.realIndex).addClass('current');
            }
        });
        //标签页点击切换
        $('.tab li').click(function(){
            $('.tab li').removeClass('current');
            $(this).addClass('current');
            var index = $(this).index()+1;
            Swiper1.slideTo(index, 500, false);
            $("html,body").animate({scrollTop:0}, 1);
        });

        //判断滑动方向    
  /*      function scroll( fn ) {
            var beforeScrollTop = document.body.scrollTop,
                fn = fn || function() {};
            window.addEventListener("scroll", function() {
                var afterScrollTop = document.body.scrollTop,
                    delta = afterScrollTop - beforeScrollTop;
                if( delta === 0 ) return false;
                fn( delta > 0 ? "down" : "up" );
                beforeScrollTop = afterScrollTop;
            }, false);
        }
        scroll(function(direction) {
            if(direction=="up"){
                $(".tab").slideUp(100);
            }else if(direction=="down"){
                $(".tab").slideDown(100);
            }
         });*/
        //按钮点击切换显示
        $(".choice_size").click(function(){
            $(".choice_size").css("display","none");
            $(".size").slideToggle(100);
            $('.shop_car').css('display','none');
        });
        $('.close').click(function(){
            $(".size").slideToggle(100);
            $('.shop_car').css('display', 'block');
            $(".choice_size").css("display","block");
        });
        $('.add_car').click(function(){
            $(".size").slideToggle(100);
            $(".choice_size").css("display","block");
            $('.shop_car').css('display','block');
        });