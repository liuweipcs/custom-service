<?php

namespace app\commands;

use yii\console\Controller;
use app\modules\systems\models\Updatestatustask;
use app\modules\orders\models\Order;
/**
* @desc 处理ebay取消交易时erp订单状态更改失败记录
*/
class UpdateorderstatusController extends Controller
{
	
	public function actionCheckall()
	{
		set_time_limit(150);
		//查询一条记录
		$data = Updatestatustask::checkAll();
		if(empty($data)) 
			exit("NO DATA");
		foreach ($data as $key => $value) {
			//获取erp接口数据
			$orderinfo=Order::systemCancelOrder($value->platform_code, null, $value->platform_order_id,$value->cancel_reason);
			if(!is_array($orderinfo) && $orderinfo == true){
				$value->task_status = Updatestatustask::EXEC_SUCCESS;
				$value->update_time = date('Y-m-d H:i:s');
				$value->complete_time = date('Y-m-d H:i:s');
				$value->failed_times = -1;	//成功
              
			}else{
				$value->task_status = Updatestatustask::EXEC_FAILED;
				$value->update_time = date('Y-m-d H:i:s');
				$value->failed_times = ($value->failed_times)+1;	
				$value->failed_reason = $orderinfo[1];
			}
			$flag = $value->save();
			if(!$flag)
				echo '订单号：'.$value->platform_order_id.'进程失败'. "\n";continue;
		}
		exit('DONE');
	}
}