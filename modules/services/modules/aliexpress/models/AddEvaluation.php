<?php
namespace app\modules\services\modules\aliexpress\models;
use app\common\VHelper;
use app\modules\mails\models\AliexpressTask;
use app\modules\mails\models\AliexpressEvaluate;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\services\modules\aliexpress\components\AliexpressApi;
use app\modules\services\modules\aliexpress\models\WhereAddEvaluation;
use app\modules\accounts\models\Account;
use app\modules\systems\models\ErpAccountApi;
use app\modules\accounts\models\Platform;
use app\modules\users\models\User;
use app\modules\systems\models\AliexpressLog;
/**
 * 站内信/订单留言更新处理状态
 */
class AddEvaluation
{
    protected $access;
    protected $app_key;
    protected $secret_key;
    protected $_taskId = 0;
    protected $_account_id = '';
    protected $_shortName = '';
    protected $_totalNumber = 0;
    protected $parent_id = 0;
    protected $errorMsg = '';


    /**
     * 获取店铺接口信息
     * @param $data 账号ID
     * @return  bool
     */
    public function getToStoreInformation($data)
    {

        $aliexpressTaskModel = new AliexpressTask();
        if ($aliexpressTaskModel->checkIsRunning($data['account_id'], 'AliexpressEvaluate'))
            exit('Task Running');
        $taskId = $aliexpressTaskModel->getAdd($data['account_id'],'AliexpressEvaluate');
        $this->_taskId = $taskId;
        $accountM = Account::findById($data['account_id']);
        if (empty($accountM)) return false;
        $accountName = $accountM->account_name;
        $params = ['platformCode' => Platform::PLATFORM_CODE_ALI, 'accountName' => $accountName];
        $ErpAccountApi = new ErpAccountApi();
        $ErpAccountApi->setApiMethod('getAccount')->sendRequest($params, 'get');
        if (!$ErpAccountApi->isSuccess())
        {
            $TaskModel = AliexpressTask::find()->where(['id'=>$this->_taskId])->one();
            $TaskModel->status = -1;
            $TaskModel->save();
        }
        $response = $ErpAccountApi->getResponse();
        $accountM = $response->account;
        $this->_account_id = $accountM->id;
        $this->_shortName = $accountM->short_name;
        $this->access =  $accountM->access_token;
        $this->app_key = $accountM->app_key;
        $this->secret_key = $accountM->secret_key;
        return $this->sendEvaluation($data);
    }

   public function sendEvaluation($data)
    {
        //实例化这个类
        $orderObj = new WhereAddEvaluation();
        //构造参数
        $response_s = $orderObj->setAccessToken($this->access);
        $response_s = $response_s->putOtherTextParam('app_key', $this->app_key);
        $response_s = $response_s->putOtherTextParam('secret_key', $this->secret_key);
        $response_s = $response_s->putOtherTextParam('orderId', $data['order_id']);//平台订单ID
        $response_s = $response_s->putOtherTextParam('score', $data['score']);//打分分数
        $response_s = $response_s->putOtherTextParam('feedbackContent', $data['feedback_content']);//评价内容
        //引入发送类
        $client = new AliexpressApi();
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        $retuelt = false;
        try{
            $User = User::findIdentity(\Yii::$app->user->id);
            /*如果接口报错*/
            if(!empty($response) && !empty($response->error_code)){
                $aliexpressLogModel = new AliexpressLog();
                $arr['create_user_name'] = $User->user_name;
                $arr['channel_id'] = $data['order_id'];
                $arr['account_id'] = $this->_account_id;
                $arr['update_content'] = '评价失败，错误代码为：'.$response->error_message;
                $arr['create_time'] = date('Y-m-d H:i:s');
                $aliexpressLogModel->getAdd($arr);
                $retuelt = false;

            }else{
                /*状态更新成功*/
                if ($response->result->isSuccess) {
                    $aliexpressLogModel = new AliexpressLog();
                    $Log['create_user_name'] = $User->user_name;
                    $Log['channel_id'] = $data['order_id'];
                    $Log['account_id'] = $this->_account_id;
                    $Log['update_content'] = '评价成功！';
                    $Log['create_time'] = date('Y-m-d H:i:s');
                    $aliexpressLogModel->getAdd($Log);
                    /*修改评价状态*/
                    $aliexpressEvaluateModel = new AliexpressEvaluate();
                    $aliexpressEvaluateModel->is_evaluate = 1;
                    $aliexpressEvaluateModel->save();
                    $retuelt = true;
                } else {
                    /*状态更新失败*/
                    $aliexpressLogModel = new AliexpressLog();
                    $daraArr['create_user_name'] = $User->user_name;
                    $daraArr['channel_id'] = $data['order_id'];
                    $daraArr['account_id'] = $this->_account_id;
                    $daraArr['update_content'] = $response->errorMessage;
                    $daraArr['create_time'] = date('Y-m-d H:i:s');
                    $aliexpressLogModel->getAdd($daraArr);
                    $retuelt = false;
                }
            }
            return $retuelt;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }
    
    /**
     * @desc 获取错误信息
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

}