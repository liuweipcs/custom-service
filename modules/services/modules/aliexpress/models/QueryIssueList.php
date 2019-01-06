<?php
namespace app\modules\services\modules\aliexpress\models;
use app\common\VHelper;
use app\modules\mails\models\AliexpressTask;
use app\modules\mails\models\AliexpressInbox;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\services\modules\aliexpress\models\MsgDetailList;
use app\modules\services\modules\aliexpress\components\AliexpressApi;
use app\modules\mails\models\AliexpressDisputeList;
use app\modules\mails\models\AliexpressDisputeDetail;
use app\modules\mails\models\MailTemplate;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\AliexpressLog;
use app\modules\users\models\User;
/**
 * 速卖通订单纠纷列表
 */
class QueryIssueList
{
    protected $access;
    protected $app_key;
    protected $secret_key;
    protected $_taskId = 0;
    protected $_account_id = '';
    protected $_shortName = '';
    protected $_totalNumber = 0;
    protected $state = 0;
    protected $totalPage;
    protected $_errorMessage = '';


    /**
     * 通过帐号获取订单纠纷列表
     * @param $account
     */
    public function getIssueList($account)
    {

        //实例化这个模型
        $aliexpressTaskModel = new AliexpressTask();
        $taskId = $aliexpressTaskModel->getAdd($account,'AliexpressIssue');
        $this->_account_id = $account;
        $accountM = Account::findById($account);
        if (empty($accountM)) return false;
        $accountName = $accountM->account_name;
        $erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_ALI, $accountName);
        if (empty($erpAccount))
            return false;
        $this->_taskId = $taskId;
        $this->_shortName = $erpAccount->short_name;
        $this->access =  $erpAccount->access_token;
        $this->app_key = $erpAccount->app_key;
        $this->secret_key = $erpAccount->secret_key;
        $this->getTotalPage();
    }

    public function getTotalPage($pagenum = 1){
        $orderObj = new WhereIssueList();
        //构造参数
        $response_s = $orderObj->setPage($pagenum)
                    ->setAccessToken($this->access)
                    ->putOtherTextParam('app_key', $this->app_key)
                    ->putOtherTextParam('secret_key', $this->secret_key);
        //引入发送类
        $client = new AliexpressApi();
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        try {
            $User = User::findIdentity(\Yii::$app->user->id);
            if (!empty($response) && !empty($response->error_code)) {
                $aliexpressLogModel = new AliexpressLog();
                $data['create_user_name'] = $User->login_name;
                $data['channel_id'] = '';
                $data['account_id'] = $this->_account_id;
                $data['update_content'] = !empty($response->error_code)?$response->error_code:'';
                $data['create_time'] = date('Y-m-d H:i:s');
                $aliexpressLogModel->getAdd($data);
                $this->_errorMessage = !empty($response->error_code)?$response->error_code:'';
            }else{
                if(!empty($response->dataList)){
                    foreach ($response->dataList as $value){
                        $disputeListModule = new AliexpressDisputeList();
                        $disputeOne = $disputeListModule->find()->where(['platform_dispute_id'=>$value->id])->one();
                        /*修改*/
                        if(!empty($disputeOne)){
                            $disputeOne->issue_status = $value->issueStatus;
                            $disputeOne->save();
                            if($disputeOne->id){
                                $this->getSolution($disputeOne->id,$value->id);
                            }
                        }else{
                            /*新添加*/
                            $disputeListModule = new AliexpressDisputeList();
                            $dispute_id = $disputeListModule->newlyAdded($this->_account_id,$value);
                            if($dispute_id){
                                $this->getSolution($dispute_id,$value->id);
                            }
                        }
                    }
                    $pagenum++;
                    $this->getTotalPage($pagenum);
                }
                exit('执行完成！！');
            }
        } catch (\Exception $e) {
            $this->_errorMessage = $e->getMessage();
            echo $this->_errorMessage;exit;
            return false;
        }
    }
    /*
     * 根据纠纷ID，获取协商数据(新版纠纷)
     */
    public function getSolution($dispute_id,$issueId){
        $orderObj = new WhereSolution();
        //构造参数
        $response_s = $orderObj
            ->setAccessToken($this->access)
            ->putOtherTextParam('app_key', $this->app_key)
            ->putOtherTextParam('secret_key', $this->secret_key)
            ->putOtherTextParam('issueId', $issueId);
        //引入发送类
        $client = new AliexpressApi();

        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        try{
            if(!empty($response->resultObject)){
                $disputeDetailModule = new AliexpressDisputeDetail();
                $disputeDetailModule->newlyAdded($this->_account_id,$dispute_id,$response->resultObject);
            }else{
                return false;
            }
        }catch (\Exception $e){
            $this->_errorMessage = $e->getMessage();
            return false;
        }
    }

}