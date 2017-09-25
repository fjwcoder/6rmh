/**************用于表单验证*****************/

//2016-9-7 by ztf

//手机号码验证
function checkPhone(phone){ 
    if(!(/^1[34578]\d{9}$/.test(phone))){ 
        alert('请输入正确的手机号码');
        return false; 
    }else{
        return true;
    }
}

//判断手机是否注册过
function checkReg(phone){
    //检查电话是否注册过
    var result;
    $.ajax({
        url: '/index/sendsms/checkphone?phone='+phone,
        async: false,
        success: function(data){
            if (data) {
                result = true;
            }else{
                alert('号码已经被注册！');
                result = false;
            }
        }        
    });
    return result;
}
//验证电话
$(".phone").change(function() {
    var phoneNum = $(this).val();
    //判断手机是否为手机号
    if (checkPhone(phoneNum)&&checkReg(phoneNum)){
        $('.wrong_phone').css('display', 'none');
        $('.right_phone').css('display','block');
        $(this).attr('check', 'right');
    }else{
        $('.wrong_phone').css('display', 'block');
        $('.right_phone').css('display','none');
        $(this).attr('check', 'wrong');
    }
});
//验证输入密码是否相同
$('.password-repeat').change(function(){
    var input1=$(this).val();
    var input2=$('.password').val();
    if (input1==input2) {
        $('.wrong_password').css('display', 'none');
        $('.right_password').css('display','block');
        $(this).attr('check', 'right');
    }else{
        $('.wrong_password').css('display', 'block');
        $('.right_password').css('display','none');
        $(this).attr('check', 'wrong');
        alert("请输入相同的密码！");
    }
});

//检查表单数据是否全部输入正确
function checkForm(){
    var phone=$('.phone').attr('check');
    var password=$('.password-repeat').attr('check');
    var verify=$('.verify').attr('check');
    if (phone=='right'&&password=="right"&&verify=='right') {
        return true;
    }else{
        return false;
    }
}

//发送短信
function sendM(){
    //防止重复发送验证码
    var phone=$('.phone').val();
    $.ajax({
        url: '/index/sendsms/verify?phone='+phone,
        dataType: "xml",
        success:function(data){
            var code = $(data).find('code').html();
            /*var smsid = $(data).find('smsid').html();*/
            if(code == '2'){
                alert('发送成功!');
                /*$('.verify').append('<input stype="hidden" name="smdid" value='+smsid+'>');*/
            }
            var s = 60;
            var interval = setInterval(function(){
                if(s<10){
                  $('.time_info').html('0'+s);
                }else{
                  $('.time_info').html(''+s);
                }
                s--;
                if (s<0) {
                    clearInterval(interval);
                    $('.showtime').css('display', 'none');
                    $('.send_sms').css('display', 'table-cell');
                }
              },1000);
            $('.send_sms').css('display', 'none');
            $('.showtime').css('display', 'block');
        }
    });
}