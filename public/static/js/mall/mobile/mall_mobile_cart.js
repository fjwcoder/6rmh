var app = angular.module('myApp', []);
        app.controller('cartCtrl', function($scope, $http) {
            

            $scope.checkCart = function($event, $id){
                var checkbox = $event.target; //获取checkbox元素
                var pos = check_list.indexOf($id);
                if(checkbox.checked){ //选中
                    if(pos < 0){ //不存在则添加
                        check_list.push($id); //添加元素
                    }
                }else{ //取消选中
                    if(pos >= 0){//存在则清除
                        check_list.splice(pos, 1); //清除
                    }
                }
                $scope.cart_list = check_list.join();
            }

            
            
            $scope.addnum = function($id){
                var curr_num = angular.element('#cart-goods-num-'+$id).val();
                $http.post("/index/cart/setInc/id/"+$id).success( function(data) {
                    angular.element('#cart-goods-num-'+$id).val(parseInt(curr_num)+parseInt(data.num));
                    angular.element('#goods-num-'+$id).html(parseInt(curr_num)+parseInt(data.num));
                });
            }

            $scope.subnum = function ($id, $num){
                var curr_num = angular.element('#cart-goods-num-'+$id).val();
                if(curr_num >=2){
                    $http.post("/index/cart/setDec/id/"+$id).success( function(data) {
                        angular.element('#cart-goods-num-'+$id).val(parseInt(curr_num)-parseInt(data.num));
                        angular.element('#goods-num-'+$id).html(parseInt(curr_num)-parseInt(data.num));
                    });
                }
                
            }
            
            $scope.slideCover = function($id){
                $('#goods-option-'+$id).fadeOut(100);
                $('#cart-goods-record-'+$id).animate({
                    left:'0em'
                },100);
            }

        });

        // 显示
        $('.edit').click(function(event){
            $(this).parent().parent().animate({
                left: '-10em'
            },100);
            $(this).parent().nextAll('.good-option').fadeIn(100);
            event.stopPropagation();
        });