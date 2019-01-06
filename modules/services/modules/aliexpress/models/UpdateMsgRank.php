<?php
namespace app\modules\services\modules\aliexpress\models;
use app\common\VHelper;
use app\modules\mails\models\AliexpressTask;
use app\modules\mails\models\AliexpressInbox;
use app\modules\accounts\models\AliexpressAccount;
use app\modules\mails\models\AliexpressSummary;
use app\modules\services\modules\aliexpress\components\AliexpressApi;
use app\modules\systems\models\AliexpressLog;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
/**
 * 站内信/订单留言更新处理状态
 */
class UpdateMsgRank
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
     * @param $data
     * @return  bool
     */
    public function getRank($data = [])
    {
        $this->_account_id = $data['account_id'];
        $accountM = Account::findById($data['account_id']);
        if (empty($accountM)) return false;
        $accountName = $accountM->account_name;
        $erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_ALI, $accountName);
        if (empty($erpAccount))
            return false;
        $this->_account_id = $data['account_id'];
        $this->_shortName = $erpAccount->short_name;
        $this->access =  $erpAccount->access_token;
        $this->app_key = $erpAccount->app_key;
        $this->secret_key = $erpAccount->secret_key;
        return $this->getProductResponseByShortName($data['channel_id'],$data['rank']);
    }

   public function getProductResponseByShortName($channelId,$rank)
    {
        //实例化这个类
        $orderObj = new WhereMsgRank();
        //构造参数
        $response_s = $orderObj
            ->setAccessToken($this->access)
            ->putOtherTextParam('app_key', $this->app_key)
            ->putOtherTextParam('secret_key', $this->secret_key)
            ->putOtherTextParam('channelId', $channelId)
            ->putOtherTextParam('rank', $rank);
        //引入发送类
        $client = new AliexpressApi();
        //压入参数
        $client->setRequest($response_s);
        //发送请求
        $response = $client->exec();
        $retuelt = false;
        $Label = [
            'rank0'=>'白',
            'rank1'=>'红',
            'rank2'=>'橙',
            'rank3'=>'绿',
            'rank4'=>'蓝',
            'rank5'=>'紫'
        ];
        /*如果接口报错*/
        if(!empty($response) && !empty($response->error_code)){
            $aliexpressLogModel = new AliexpressLog();
            $data['create_user_name'] = \Yii::$app->user->id;
            $data['channel_id'] = $channelId;
            $data['account_id'] = $this->_account_id;
            $data['update_content'] = '给关系ID为'.$channelId.'站内信/订单留言 打标签时接口报错，错误信息为：'.$response->error_code;
            $data['create_time'] = date('Y-m-d H:i:s');
            $aliexpressLogModel->getAdd($data);
            $retuelt = false;
        }else{
            /*状态更新成功*/
            if ($response->result->isSuccess) {
                $aliexpressLogModel = new AliexpressLog();
                $data['create_user_name'] = \Yii::$app->user->id;
                $data['channel_id'] = $channelId;
                $data['account_id'] = $this->_account_id;
                $data['update_content'] = '打标签功能标签颜色为'.$Label[$rank];
                $data['create_time'] = date('Y-m-d H:i:s');
                $aliexpressLogModel->getAdd($data);
                $AliexpressInbox = AliexpressInbox::findOne(['channel_id'=>$channelId]);
                //更新处理状态
                $AliexpressInbox->rank = $this->getNumber($rank);
                $AliexpressInbox->save();
                echo '处理成功！';
                $retuelt = true;
            } else {
                /*状态更新失败*/
                $aliexpressLogModel = new AliexpressLog();
                $data['create_user_name'] = \Yii::$app->user->id;
                $data['channel_id'] = $channelId;
                $data['account_id'] = $this->_account_id;
                $data['update_content'] = '给关系ID为'.$channelId.'站内信/订单留言 打标签时接口报错，错误信息为：'.$response->result->errorMsg;
                $data['create_time'] = date('Y-m-d H:i:s');
                $aliexpressLogModel->getAdd($data);
                $retuelt  = false;
            }
        }

        return $retuelt;

    }
    public function getNumber($str)
    {
        return (int)preg_replace('/\D/s', '', $str);
    }

}