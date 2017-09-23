
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
    $("[data-toggle='popover']").popover();
    $("[data-toggle='popover']").mousemove(function(){
        $(this).popover('show');
    });
    $("[data-toggle='popover']").mouseout(function(){
        $(this).popover('hide');
    });
    // $("[data-toggle='popover']").mouseover(function(){
    //     $(this).popover('toggle');
    // });




});