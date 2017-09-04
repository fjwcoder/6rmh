var aParameter = {
    curr_mod: '', //当前选择的module对象
    nav_display: true,
    curr_nav: 0, //当前点击的导航按钮
    curr_ctrl: '', //当前选择的ctrl对象
    more_search: false, //展开更多搜索
};

//初始化fileinput
function initFileInput(ctrlName, uploadUrl) { 
    var control = $('#' + ctrlName); 
    control.fileinput({ 
        resizeImage : true, 
        maxImageWidth : 200, 
        maxImageHeight : 200, 
        resizePreference : 'width', 
        language : 'zh', //设置语言 
        uploadUrl : uploadUrl, 
        uploadAsync : false, 
        allowedFileExtensions : [ 'jpg', 'png', 'gif' ],//接收的文件后缀 
        showUpload : false, //是否显示上传按钮 
        showCaption : false,//是否显示输入框
        browseClass : "btn btn-default", //按钮样式 
        previewFileIcon : "<i class='glyphicon glyphicon-king'></i>", 
        enctype: 'multipart/form-data',
        validateInitialCount:true,

        maxFileCount : 10, 
        msgFilesTooMany : "选择图片超过了最大数量", 
        maxFileSize : 2000, 
    }); 
}; 

$(document).ready(function(){

    $('nav.navbar .navbar-collapse li.dropdown').mouseover(function(){
        $(this).addClass('open');
    });
    $('nav.navbar .navbar-collapse li.dropdown').mouseout(function(){
        $(this).removeClass('open');
    });


    //修改iframe的src
    $('a.navbar-li').click(function(){
        if($(this).attr('data-url') != '' && $(this).attr('data-url') != null && $(this).attr('data-url') != 'undefind'){
            $('#iframe').attr('src', $(this).attr('data-url')); 
        }
    });

    // $('.fileinput').fileinput(function(){
    //     initFileInput($(this).attr('id'), $(this).attr('upurl'));
    // });



});