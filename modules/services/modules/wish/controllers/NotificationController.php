<?php
namespace wish\controllers;
use yii\web\Controller;
use wish\components\WishApi;
use wish\models\WishNotifaction;
use app\modules\mails\models\AccountTaskQueue;
use app\common\VHelper;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;

class NotificationController extends Controller{
	public function actionFetchall(){
		$url = \Yii::$app->request->get('url');
		$account = \Yii::$app->request->get('account');
		if(!empty($account)){
			$accountInfo = Account::findById($account);
			if (empty($accountInfo)) return false;
			$accountName = $accountInfo->account_short_name;
			$erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_WISH, $accountName);
			if (empty($erpAccount))
			{
				exit('获取账号信息失败');
			}
			$token = $erpAccount->access_token;
			$api = new WishApi($token);
			if(empty($url)){
				$list = $api->getNotifiaction();
			}else{
				$list = $api->getUrlResult($url);
			}
			if(!empty($list->data)){
				$idArray = array();
				foreach ($list->data as $val){
					array_push($idArray, $val->GetNotiResponse->id);
				}
				$model = new WishNotifaction();
				$existNoti = WishNotifaction::find()->andFilterWhere(['noti_id'=>$idArray])->asArray()->indexBy('noti_id')->all();
				$tran = $model->getDb()->beginTransaction();
				try {
					$keys = array_keys($existNoti);
					foreach ($list->data as $val){
						if(!in_array($val->GetNotiResponse->id, $keys)){
							if(!$model->getDb()->createCommand()->insert($model->tableName(), [
									'account_id'=>$account,
									'noti_id'=>$val->GetNotiResponse->id,
									'title'=>$val->GetNotiResponse->title,
									'message'=>$val->GetNotiResponse->message,
									'perma_link'=>$val->GetNotiResponse->perma_link,
									'add_time'=>date('Y-m-d H:i:s'),
							])->execute()){
								throw new \Exception("添加tran失败");
							}
						}
					}
					$tran->commit();
				} catch (Exception $e) {
					$tran->rollBack();
				}
				if(isset($list->paging)){
					sleep(1);
					VHelper::throwTheader('/services/wish/notification/fetchall', ['account'=> $account,'url'=>$list->paging->next]);
				}
			}
		}else{
			//去账号任务队列里面去查询还有没有完成的账号如果有，取若干账号去拉取数据
			//$list = AccountTaskQueue::findByPlatform(Platform::PLATFORM_CODE_WISH, AccountTaskQueue::TASK_TYPE_MESSAGE);
			//if (empty($list))
			//{
				//去账号表获取所有账号插入到账号队列
				$accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_WISH, Account::STATUS_VALID);
				if (!empty($accountList))
				{
					$list = [];
					foreach ($accountList as $account)
					{
						$accountTaskQenue = new AccountTaskQueue();
						$accountTaskQenue->account_id = $account->id;
						$accountTaskQenue->type = AccountTaskQueue::TASK_TYPE_MESSAGE;
						$accountTaskQenue->platform_code = $account->platform_code;
						$accountTaskQenue->create_time = time();
						$flag = $accountTaskQenue->save(false);
						if ($flag)
							$list[] = $accountTaskQenue;
					}
				}
			//}
			$taskList = AccountTaskQueue::getTaskList(['type' => AccountTaskQueue::TASK_TYPE_MESSAGE,
                'platform_code' => Platform::PLATFORM_CODE_WISH]);
			if (!empty($taskList))
			{
				foreach ($taskList as $accountId)
				{
					VHelper::throwTheader('/services/wish/notification/fetchall', ['account'=> $accountId]);
					sleep(2);
				}
			} else {
				die('there are no any account!');
			}
			exit('DONE');
		}
	}
}