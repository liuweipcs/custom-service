<?php
namespace app\modules\services\modules\aliexpress\models;
use app\common\VHelper;
use app\modules\mails\models\AliexpressTask;
use app\modules\mails\models\AliexpressInbox;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\orders\models\Order;
use app\modules\services\modules\aliexpress\models\MsgDetailList;
use app\modules\services\modules\aliexpress\components\AliexpressApi;
use app\modules\accounts\models\Account;
use app\modules\mails\models\AliexpressEvaluate;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\ErpAccountApi;
use app\modules\users\models\User;
/**
 * 站内信
 */
class Evaluation
{
    protected $access;
    protected $app_key;
    protected $secret_key;
    protected $_taskId = 0;
    protected $_account_id = '';
    protected $_shortName = '';
    protected $_totalNumber = 0;
    protected $state = 0;


    /**
     * 获取账号信息
     * @param $account
     */
    public function getAccountInformation($account)
    {
        $aliexpressTaskModel = new AliexpressTask();
        if ($aliexpressTaskModel->checkIsRunning($account, 'AliexpressEvaluate'))
            exit('Task Running');
        $taskId = $aliexpressTaskModel->getAdd($account,'AliexpressEvaluate');
        $this->_taskId = $taskId;
        $accountM = Account::findById($account);
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
        $this->_account_id = $account;
        $this->_shortName = $accountM->short_name;
        $this->access =  $accountM->access_token;
        $this->app_key = $accountM->app_key;
        $this->secret_key = $accountM->secret_key;
        // start to catch order
        $this->getFormattedSendMessage();

    }

    public function getFormattedSendMessage($pagenum = 1)
    {
        //实例化这个类
        $orderObj = new WhereEvaluation();
        //构造参数
        $response_s = $orderObj
            ->setPage($pagenum)
            ->setNum($orderObj->getNum())
            ->setAccessToken($this->access)
            ->putOtherTextParam('app_key', $this->app_key)
            ->putOtherTextParam('secret_key', $this->secret_key);
           // ->putOtherTextParam('orderIds', $orderIds);
        //引入发送类
        $client = new AliexpressApi();
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        $return = false;
        try{
            if (!empty($response->listResult)) {
                $User = User::findIdentity(\Yii::$app->user->id);
                foreach ($response->listResult as $value){
                    $findOne = AliexpressEvaluate::getFindOne($value->orderId);
                    if(empty($findOne)){
                        /*获取订单信息*/
                        $orderinfo = $this->sendOrderDetail($value->orderId);
                        $evaluateModels = new AliexpressEvaluate();
                        $evaluateModels->platform_order_id = $value->orderId;/*平台订单号(速卖通平台拉下来的订单号)*/
                        $evaluateModels->total_price = $orderinfo->orderAmount->amount.'.'.$orderinfo->orderAmount->cent;/*订单金额*/
                        $evaluateModels->buyer_id = $orderinfo->buyerloginid;/*买家ID*/
                        $evaluateModels->buyer_name = $orderinfo->buyerSignerFullname;/*买家全名*/
                        $evaluateModels->currency = $orderinfo->orderAmount->currencyCode;/*币种USD/RUB*/
                        $evaluateModels->create_by = $User->user_name;
                        $evaluateModels->account_id = $this->_account_id;
                        $evaluateModels->issue_status = $orderinfo->issueStatus;
                        $evaluateModels->create_time = date('Y-m-d H:i:s');
                        $return = $evaluateModels->save();
                    }
                }
                $pagenum++;
                $this->getFormattedSendMessage($pagenum);
            }else{
                /*如果报错*/
                if(!empty($response) && !empty($response->error_code)){
                    $TaskModel = AliexpressTask::find()->where(['id'=>$this->_taskId])->one();
                    $TaskModel->status = -1;
                    $TaskModel->errors = $response->error_message;
                    $TaskModel->save();
                }else{
                    $TaskModel = AliexpressTask::find()->where(['id'=>$this->_taskId])->one();
                    $TaskModel->status = 2;
                    $TaskModel->save();
                }
            }
        } catch (\Exception $e) {
            $this->_errorMessage = $e->getMessage();
            return false;
        }
        return $return;
    }
    /*获取订单信息*/
    public function sendOrderDetail($order_id){
        //实例化这个类
        $orderObj = new WhereOrderDetail();
        //构造参数
        $response_s = $orderObj
            ->setAccessToken($this->access)
            ->putOtherTextParam('app_key', $this->app_key)
            ->putOtherTextParam('secret_key', $this->secret_key)
            ->putOtherTextParam('orderId', $order_id);
        //引入发送类
        $client = new AliexpressApi();
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        return $client->exec();
    }
    /*获取订单信息*/
    public function sendRequest($order_id){

        return json_decode(json_encode(Order::getOrderStack('ALI',$order_id)),true);

//        $string = 'order_id='.$order_id.'&token=5E17C4488C2AC591';
//        return VHelper::getSendreQuest($string,true,'ALI');
    }
    /*计算产品数量*/
    public function calculatedQuantity($data){
        $quantity = 0;
        if(!empty($data)){
            foreach ($data as $value){
                $quantity = $quantity+$value['quantity'];
            }
        }
        return $quantity;
    }


}