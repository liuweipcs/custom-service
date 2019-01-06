<?php
namespace app\modules\services\modules\aliexpress\models;
use app\common\VHelper;
use app\modules\mails\models\AliexpressTask;
use app\modules\mails\models\AliexpressInbox;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\mails\models\AliexpressSummary;
use app\modules\services\modules\aliexpress\components\AliexpressApi;
use app\modules\services\modules\aliexpress\models\WhereMsgRead;
use app\modules\systems\models\AliexpressLog;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\ErpAccountApi;
use Yii;
/**
 * 站内信/订单留言更新处理状态
 */
class UpdateMsgRead
{
    protected $access;
    protected $app_key;
    protected $secret_key;
    protected $_taskId = 0;
    protected $_account_id = '';
    protected $_shortName = '';
    protected $_totalNumber = 0;
    protected $parent_id = 0;


    /**
     * 获取店铺接口信息
     * @param $account 账号ID
     * @param $channelId 通道ID(即关系ID)
     * @param $msgSources 处理状态(0未处理,1已处理)
     * @return  bool
     */
    public function getRead($account,$channelId,$msgSources)
    {
        $this->_account_id = $account;
        $accountM = Account::findById($account);
        if (empty($accountM)) return false;
        $accountName = $accountM->account_name;
        $erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_ALI, $accountName);
        if (empty($erpAccount))
            return false;
        $this->_account_id = $account;
        $this->_shortName = $erpAccount->short_name;
        $this->access =  $erpAccount->access_token;
        $this->app_key = $erpAccount->app_key;
        $this->secret_key = $erpAccount->secret_key;
        return $this->getProductResponseByShortName($channelId,$msgSources);
    }

   public function getProductResponseByShortName($channelId,$msgSources)
    {
        //实例化这个类
        $orderObj = new WhereMsgRead();
        //构造参数
        $response_s = $orderObj
            ->setAccessToken($this->access)
            ->putOtherTextParam('app_key', $this->app_key)
            ->putOtherTextParam('secret_key', $this->secret_key)
            ->putOtherTextParam('msgSources', $msgSources)
            ->putOtherTextParam('channelId', $channelId);
        //引入发送类
        $client = new AliexpressApi();
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        
        $retuelt = false;
        /*如果接口报错*/
        if(!empty($response) && !empty($response->error_code)){
            $aliexpressLogModel = new AliexpressLog();
            $data['create_user_name'] = \Yii::$app->user->id;
            $data['channel_id'] = $channelId;
            $data['account_id'] = $this->_account_id;
            $data['update_content'] = '更新关系ID为'.$channelId.'站内信/订单留言更新处理状态时接口报错，错误信息为：'.$response->error_code;
            $data['create_time'] = date('Y-m-d H:i:s');
            $aliexpressLogModel->getAdd($data);
            $AliexpressInbox = AliexpressInbox::findOne(['channel_id'=>$channelId]);
            $AliexpressInbox->read_stat = 2;
            $AliexpressInbox->unread_count = 0;
            $AliexpressInbox->save();
            $retuelt = false;
        }else{
            /*状态更新成功*/
            if ($response->result->isSuccess) {
                $aliexpressLogModel = new AliexpressLog();
                $data['create_user_name'] = \Yii::$app->user->id;
                $data['channel_id'] = $channelId;
                $data['account_id'] = $this->_account_id;
                $data['update_content'] = '更新了关系ID为'.$channelId.'站内信/订单留言更新处理状态为已读';
                $data['create_time'] = date('Y-m-d H:i:s');
                $aliexpressLogModel->getAdd($data);

                //更新成功则更新这条关系
               // $detail = new Detail();
                $AliexpressInbox = AliexpressInbox::findOne(['channel_id'=>$channelId]);
                //$detail->getProductResponseByShortName($AliexpressInbox->channel_id,$AliexpressInbox->msg_sources,1,$AliexpressInbox->id);
                $AliexpressInbox->read_stat = 1;
                $AliexpressInbox->unread_count = 0;
                $AliexpressInbox->save();
                $retuelt = true;
            } else {
                /*状态更新失败*/
                $aliexpressLogModel = new AliexpressLog();
                $data['create_user_name'] = \Yii::$app->user->id;
                $data['channel_id'] = $channelId;
                $data['account_id'] = $this->_account_id;
                $data['update_content'] = '更新关系ID为'.$channelId.'站内信/订单留言更新处理状态时接口报错，错误信息为：'.$response->errorMsg;
                $data['create_time'] = date('Y-m-d H:i:s');
                $aliexpressLogModel->getAdd($data);
                $AliexpressInbox = AliexpressInbox::findOne(['channel_id'=>$channelId]);
                $AliexpressInbox->read_stat = 2;
                $AliexpressInbox->unread_count = 0;
                $AliexpressInbox->save();
                $retuelt  = false;
            }
        }

        return $retuelt;

    }

}