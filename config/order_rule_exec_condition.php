<?php

return [
    '1'           => ['name'=>'买家下单之后未付款','field'=>'info.payment_status','value'=>'0','is_checked'=>1,'time_type'=>'order_create_time','status'=>1],
    '2'           => ['name'=>'买家下单之后已付款(资金未到帐)','field'=>'','value'=>'','is_checked'=>0,'time_type'=>'','status'=>0],
    '3'           => ['name'=>'订单收到买家付款','field'=>'info.refund_status','value'=>'0','is_checked'=>1,'time_type'=>'order_pay_time','status'=>1],
    '4'           => ['name'=>'订单审核成功(匹配产品、仓库、物流方式)','field'=>'','value'=>'','is_checked'=>0,'time_type'=>'','status'=>0],
    '5'           => ['name'=>'订单分配库存(成功)','field'=>'','value'=>'','is_checked'=>0,'time_type'=>'','status'=>0],
    '6'           => ['name'=>'订单分配库存(失败)','field'=>'','value'=>'','is_checked'=>1,'time_type'=>'','status'=>0],
    '7'           => ['name'=>'订单标记打印','field'=>'','value'=>'','is_checked'=>0,'time_type'=>'','status'=>0],
    '8'           => ['name'=>'订单执行发货','field'=>'','value'=>'','is_checked'=>0,'time_type'=>'order_ship_time','status'=>1],
    '9'           => ['name'=>'订单同步发货状态成功','field'=>'','value'=>'','is_checked'=>0,'time_type'=>'order_ship_time','status'=>0],
];



?>