$('.myInfo').click(function(event) {
    $('.mask').css('display','block');
    $('.setting-content').animate({
        width:"80%",
        left:"10%",
        top:'5em',
        height:'30em',
        opacity:'1',
        padding: '2em 0 0 0',
        z_index: '99',
    },500);
    $('.mask').click(function(event) {
        $('.setting-content').animate({
            width:"0",
            height:'0',
            opacity:'0',
            top
        }, 500);
        $('.setting-content').siblings().unbind('click');
        $(this).css('display', 'none');
    });
    // $('.setting-content').siblings().click(function(event) {
    // 	$('.setting-content').animate({
    // 		width:"0",
    // 		height:'0',
    // 		opacity:'0',
    // 		top
    // 	}, 500);
    // 	$('.setting-content').siblings().unbind('click');
    // });
    event.stopPropagation();
});

$('.submit-button').click(function(event) {
    $('form').submit();
});
$(function(){

    var PutDown = function(el,tag){
        var _this_ = this
        this.el = el || {};
        this.tag = tag || false;
        var link = this.el.find('.link');
        link.on('click', {el: this.el, tag: this.tag}, this.dropDown)

    }
    PutDown.prototype = {
        dropDown:function(e){

            var $el = e.data.el,
                $tag = e.data.tag,
                $this = $(this),
                $next = $this.next();
                // console.log($next)

            $next.slideToggle();
            $this.parent().toggleClass('open');

            if(!$tag){
                $el.find('.desc').not($next).slideUp().parent().removeClass('open');
            }
        }
    }
    var putdown = new PutDown($('.body'),false);
})
$(function(){
  $(".ripple-wrapper").css({
          "position": "relative",
          "top": " -3em",
          "left": " 0",
          "z-index": " 1",
          "width": " 100%",
          "height": "3em",
          "mask": " hidden",
          "border-radius": " inherit",
          "pointer-events": " none"
  });
  $(".ripple-wrapper").parent().click(function(e){
    var ripple_obj=$(this).find(".ripple-wrapper");
    if(ripple_obj.find("div").length){ripple_obj.find("div").remove();}
    ripple_obj.prepend("<div></div>");
    var ripple_div=ripple_obj.find("div");
    ripple_div.css({
          "display": " block",
          "background": " rgba(204, 204, 204, 0.7)",
          "border-radius": " 50%",
          "position": " absolute",
          "-webkit-transform": " scale(0)",
          "transform": " scale(0)",
          "opacity": " 1",
        "transition": " all 0.7s",
        "-webkit-transition": " all 0.7s",
        "-moz-transition": " all 0.7s",
        "-o-transition": " all 0.7s",
        "z-index": " 1",
        "mask": " hidden",
        "pointer-events": " none"
    });
     var R= parseInt(ripple_obj.outerWidth());
     if(parseInt(ripple_obj.outerWidth())<parseInt(ripple_obj.outerHeight())){
     R= parseInt(ripple_obj.outerHeight());
     }
      ripple_div.css({"width":(R*2)+"px","height":(R*2)+"px","top": (e.pageY -ripple_obj.offset().top - R)+'px', "left": ( e.pageX -ripple_obj.offset().left -R)+'px',"transform":"scale(1)", "-webkit-transform":"scale(1)","opacity":"0"});;
    });
});