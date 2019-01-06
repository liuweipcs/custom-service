<?php
namespace app\modules\services\modules\aliexpress\models;
use app\common\VHelper;
use app\modules\mails\models\AliexpressTask;
use app\modules\mails\models\AliexpressReply;
use app\modules\mails\models\AliexpressInbox;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\mails\models\AliexpressSummary;
use app\modules\services\modules\aliexpress\components\AliexpressApi;
use app\modules\services\modules\aliexpress\models\WhereAddMsg;
use app\modules\systems\models\AliexpressLog;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
/**
 * 站内信/订单留言更新处理状态
 */
class DisputeLeaveOrderMsg
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
    public function getDisputeLeaveOrderMsg($data)
    {

        $this->_account_id = $data['account_id'];
        //$shortName = AliexpressAccount::findOne(['id'=>$data['account_id']]);
        $account = Account::findById($this->_account_id);
        if (empty($account)) return false;
        $accountName = $account->account_name;
        $erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_ALI, $accountName);
        if (empty($erpAccount))
            return false;
        $this->_shortName = $erpAccount->short_name;
        $this->access =  $erpAccount->access_token;
        $this->app_key = $erpAccount->app_key;
        $this->secret_key = $erpAccount->secret_key;
        return $this->getProductResponseByShortName($data);
    }

   public function getProductResponseByShortName($data)
    {
        //实例化这个类
        $orderObj = new WhereDisputeLeaveOrderMsg();
        //构造参数
        $response_s = $orderObj->setAccessToken($this->access);
        $response_s = $response_s->putOtherTextParam('app_key', $this->app_key);
        $response_s = $response_s->putOtherTextParam('secret_key', $this->secret_key);
        $response_s = $response_s->putOtherTextParam('content', $data['content']);//内容
        $response_s = $response_s->putOtherTextParam('orderId', $data['orderId']);//买家账号
        //引入发送类
        $client = new AliexpressApi();
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        
        /**
         * 纠纷订单下面的订单留言回复接口，如果订单留言回复成功，则将订单表中的订单留言标记为成功回复。如果订单留言回复失败，则不标记成功
         * 对于回复不成功的纠纷订单留言回复，暂时用定时任务进行补充回复。以后需要传调用接口，调用表和调用的功能给总表来调用（张凡规划中）
         * 纠纷订单下面的订单留言回复接口需要拿到生产环境数据以后才能进行测试
         * 
         */
        
        
        
        
        $retuelt = false;
        /*如果接口报错*/
        if(!empty($response) && !empty($response->error_code)){
            //如果接口提示未授权，清空账号缓存
            if ($response->error_code == '401')
            {
                if (isset(\Yii::$app->memcache))
                    \Yii::$app->memcache->flush('erp_account');
            }
                
            $aliexpressLogModel = new AliexpressLog();
            $arr['create_user_name'] = 'system';
            $arr['channel_id'] = $data['channel_id'];
            $arr['account_id'] = $this->_account_id;
            $arr['update_content'] = '回复失败，错误代码为：'.$response->error_message;
            $arr['create_time'] = date('Y-m-d H:i:s');
            $aliexpressLogModel->getAdd($arr);
            $this->errorMsg = $response->error_message;
            return false;
        }else{
            /*状态更新成功*/
            if ($response->result->isSuccess) {
                $aliexpressLogModel = new AliexpressLog();
                $Log['create_user_name'] = 'system';
                $Log['channel_id'] = $data['channel_id'];
                $Log['account_id'] = $this->_account_id;
                $Log['update_content'] = '回复成功！';
                $Log['create_time'] = date('Y-m-d H:i:s');
                $aliexpressLogModel->getAdd($Log);

                //更新成功则更新这条关系的信息列表
                //$detail = new Detail();
                //$AliexpressInbox = AliexpressInbox::findOne(['channel_id'=>$data['channel_id']]);
                //$retuelt = $detail->getProductResponseByShortName($AliexpressInbox->channel_id,$AliexpressInbox->msg_sources,1,$AliexpressInbox->id,true);
                return true;
            } else {
                /*状态更新失败*/
                $aliexpressLogModel = new AliexpressLog();
                $daraArr['create_user_name'] = 'system';
                $daraArr['channel_id'] = $data['channel_id'];
                $daraArr['account_id'] = $this->_account_id;
                $daraArr['update_content'] = $response->result->errorMsg;
                $daraArr['create_time'] = date('Y-m-d H:i:s');
                $aliexpressLogModel->getAdd($daraArr);
                $this->errorMsg = $response->result->errorMsg;
                return false;
            }
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