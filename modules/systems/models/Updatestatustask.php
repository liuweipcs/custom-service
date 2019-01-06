<?php

namespace app\modules\systems\models;

use Yii;
use app\modules\systems\models\ErpOrderApi;
use app\modules\accounts\models\Platform;
/**
* 
*/
class Updatestatustask extends SystemsModel
{
	
	const INIT_STATUS_FAILED = 1;		//初始状态

	const EXEC_SUCCESS = 2;				//计划执行成功

	const EXEC_FAILED = 3;				//计划执行失败

	public static function tableName()
	{
		return '{{%update_task}}';
	}

	/*
	 * @desc 每失败一次，添加一条的数据
	 **/
	public static function insertOne($platform_code,$platform_id)
	{
		$model = new self;
		$model->platform_code = $platform_code;
		$model->platform_order_id = $platform_id;
		$model->create_time = date('Y-m-d H:i:s');
		$model->task_status = self::INIT_STATUS_FAILED;
		$model->failed_times = 0;
		return $model->save();
	}

	/*
	 * @desc 查询ini_status_failed所有数据
	 **/

	public static function checkAll()
	{

		return self::find()->where('task_status = :status',[':status'=>1])->all();
	}
}