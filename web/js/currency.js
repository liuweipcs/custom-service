function verified(id) {
    $.ajax({
        url: "/aftersales/domesticreturngoods/getreturngoods",
        type: "GET",
        data: {'order_id':id},
        dataType: "json",
        success: function (data) {
            if (data.ack) {
                layer.confirm('包裹已退件，请确认是否需要修改订单信息发出而不是建立重寄。', {
                    btn: ['是', '否'] //按钮
                }, function (index) {
                    $("#orderadd_"+id).click();
                    layer.close(index);
                })
            }else{
                $("#orderadd_"+id).click();
            }
        }
    });

}
