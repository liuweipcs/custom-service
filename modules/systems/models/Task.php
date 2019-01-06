<?php
	namespace app\modules\systems\models;
	use yii\db\ActiveRecord;
	
	class Task extends ActiveRecord{
		
		const CREATE_STATUS = 1;   		//新建状态
		const IN_EXECUTION_TASK = 2;    //执行中
		const EXECUTE_SUCCESS = 3;		//执行成功
		const EXECUTE_FAILED = 4;		//执行失败
		
		public static function tableName() {
			
			return "{{%task}}";
		}
		
		public function rules(){
			
			return[
				[['create_time','account_id'],'safe'],
				[['start_date_time','end_date_time'],'required']

			];
		}
		/**
		 * @desc 每次执行拉取eb交易信息计划任务的时候新增一条计划任务
		 * @return unknow
		 */
        public static function insertTask($account_id = null,$time_offset=1200)
        {
            $task_data = self::find()->where(['status' => self::EXECUTE_SUCCESS])
						->andFilterWhere(['account_id' => $account_id])
						->orderBy('complete_time DESC')
                                                ->limit(1)
                       ->one();
			if(!$task_data)
			{
				$task_data = self::find()->where(['status' => self::EXECUTE_SUCCESS])
					->orderBy('complete_time DESC')
                                        ->limit(1)
					->one();
			}
            //以当前时间新建一条计划任务
			$insert_data['start_date_time'] = date('Y-m-d H:i:s', strtotime('-3 months'));
			$insert_data['end_date_time'] = date('Y-m-d H:i:s', strtotime($insert_data['start_date_time']) + $time_offset);

//            $insert_data['end_date_time'] = date('Y-m-d H:i:s', time() - 8*3600);

            //如果找到执行成功的计划任务则取执行成功的任务的结束时间为开始时间
            if (!empty($task_data)) {
                $insert_data['start_date_time'] = $task_data->end_date_time;
                $insert_data['end_date_time'] = date('Y-m-d H:i:s',strtotime($task_data->end_date_time) + $time_offset);
            }

			if(strtotime($insert_data['start_date_time']) > time())
				$insert_data['start_date_time'] = date('Y-m-d H:i:s',time());

			if(strtotime($insert_data['end_date_time']) > time())
				$insert_data['end_date_time'] = date('Y-m-d H:i:s',time());
			
			// 测试取到空数据
//			$insert_data['start_date_time'] = date('Y-m-d H:i:s', time());
//			$insert_data['end_date_time'] = date('Y-m-d H:i:s', strtotime($insert_data['start_date_time']) + 1800);

			// 拉取规定时间的paypal交易信息
//			$insert_data['start_date_time'] = date('Y-m-d H:i:s',strtotime('2017-09-24 17:21:48'));
//			$insert_data['end_date_time'] = date('Y-m-d H:i:s', strtotime($insert_data['start_date_time']) + 7200);

			$insert_data['create_time'] = date('Y-m-d H:i:s',time());
			$insert_data['account_id'] = $account_id;
//获取新增计划任务模型
            $model = new self();

            //新增计划任务成功
            if ($model->load($insert_data, '') && $model->save()) {
                return $model;
            }
         
            return false;
        }




	}

