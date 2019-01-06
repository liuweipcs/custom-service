<?php

namespace app\modules\mails\models;

use app\common\VHelper;
use app\modules\mails\models\WishInboxInfo;
use app\modules\mails\models\WishInbox;
use app\modules\users\models\User;
use app\modules\mails\models\Reply;
use wish\components\MerchantWishApi;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use Yii;
use wish\models\WishAccount;

/**
 * This is the model class for table "{{%wish_inbox}}".
 *
 * @property integer $id
 * @property integer $info_id
 * @property string $transaction_id
 * @property string $platform_id
 * @property integer $account_id
 * @property string $merchant_id
 * @property string $label
 * @property string $sublabel
 * @property string $open_date
 * @property string $state
 * @property string $subject
 * @property integer $photo_proof
 * @property string $user_locale
 * @property string $user_id
 * @property string $user_name
 * @property string $create_by
 * @property string $create_time
 * @property string $modify_by
 * @property string $modify_time
 */
class WishReply extends Reply
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wish_reply}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_id' => '收件ID',
            'message' => '回复内容',
            'message_translated' => '回复标题',
            'message_zh' => '留言语种',
            'message_time' => '消息的时间',
            'type' => '回复类型',
            'reply_by' => '回复人',
            'is_draft' => '是否为草稿',
            'is_delete' => '是否删除',
            'is_send' => '是否发送成功',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
        ];
    }

    public function rules()
    {
        return [
            [['platform_id', 'reply_content', 'type'], 'required'],
            [['image_urls', 'message_translated', 'message_zh'], 'string'],
            [['is_draft', 'is_delete', 'is_send'], 'integer'],
            [['reply_by', 'create_by', 'modify_by', 'modify_time', 'create_time', 'message_time'], 'safe']
        ];
    }

    /**
     * @desc 获取所属平台
     * @return string
     */
    public function getPlatformCode()
    {
        return \app\modules\accounts\models\Platform::PLATFORM_CODE_WISH;
    }

    /**
     * 判断是否跟订单有关联,有则返回订单号,无则返回null
     */
    public function getOrderId()
    {
        return WishInboxInfo::getOrderIdByInfoId($this->platform_id);
    }

    /*
     *获取回复信息
     */
    public function getReplyList($platform_id)
    {
        $list = self::find()->where(['platform_id' => $platform_id])
            ->orderBy([
                //'id' => SORT_DESC,
                'message_time' => SORT_DESC,
                //'create_time'=>SORT_DESC,
            ])->asArray()->all();
        return $list;
    }

    /*添加*/
    public function getAdd($data)
    {
        $dbTransaction = self::getDb()->beginTransaction();
        try {
            $Inbox = WishInbox::findOne(['platform_id' => $data['platform_id']]);
            /*标记已回复*/
            $Inbox->is_replied = 1;
            $Inbox->status = 'Awaiting buyer response';
            //$this->getAddOutbox($data,$User);
            $obj = new WishReply();
            $obj->platform_id = $data['platform_id'];
            if($data['content'] != $data['content_en']){
                $obj->reply_content = $data['content_en']."\n\n".$data['content'];
            }else{
                $obj->reply_content = $data['content_en'];
            }
            $obj->type = 'merchant';
            $obj->reply_by = Yii::$app->user->identity->login_name;
            $obj->image_urls = $data['image_url_merchant'];
            $obj->is_draft = 0;
            $obj->is_delete = 0;
            $obj->is_send = 1;
            $obj->create_by = Yii::$app->user->identity->login_name;
            $obj->create_time = date('Y-m-d H:i:s');
            $obj->modify_by = Yii::$app->user->identity->login_name;
            $obj->modify_time = date('Y-m-d H:i:s');
            $obj->message_time = date('Y-m-d H:i:s',time()-8*3600);//修改utc时间
            $flag = $obj->save();
            if (!$flag)
                throw new \Exception('Save Reply Failed');

            if(!$Inbox->save()){
                throw new \Exception('Save Outbox Info Failed');
            }
            $result = $obj->sendWish($Inbox,$data);

            if (!$result->data)
                throw new \Exception('操作失败');
            $dbTransaction->commit();
            /*保存已回复状态*/
            return $obj->id;
        } catch (\Exception $e) {
            $dbTransaction->rollBack();
            return false;
        }
    }

    /**
     * @param $Inbox
     * @param $data
     * @return bool|mixed
     */
    public function sendWish($Inbox,$data)
    {
        $accountInfo = Account::findById($Inbox->account_id);
        if (empty($accountInfo)) return false;
        $accountName = $accountInfo->account_name;
        $erpAccount = Account::getAccountFromErp(Platform::PLATFORM_CODE_WISH, $accountName);
        if (empty($erpAccount)){
            exit('获取账号信息失败');
        }
        $token = $erpAccount->access_token;
        $url = 'https://merchant.wish.com/api/v2/ticket/reply';
        $post_data['access_token'] = $token;
        $post_data['reply'] = $data['content_en'];
        $post_data['id'] = $Inbox->platform_id;
        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);
        $res = $this->sendWishInbox($url,$post_data);
        return $res;
    }

    /**
     * @param $account_id
     * @param $platform_id
     * @return bool|mixed
     */
    public function closeWish($account_id,$platform_id)
    {
        $accountInfo = Account::findOne($account_id);

        if (empty($accountInfo)){
            return false;
        }
        $erpAccount = WishAccount::findOne(['wish_id' =>$accountInfo->old_account_id]);

        if (empty($erpAccount)){
            exit('获取账号信息失败');
        }
        $token = $erpAccount->access_token;

        $url = 'https://merchant.wish.com/api/v2/ticket/close';
        $post_data['access_token'] = $token;
        $post_data['id'] = $platform_id;
        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);
        $res = $this->sendWishInbox($url,$post_data);
        return $res;
    }

    public function sendWishInbox($url,$post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        $data = curl_exec($ch);
        curl_close($ch);
        if(Yii::$app->user->identity->login_name == '吴峰'){
            var_dump($data);exit;
        }
        return $this->getResponseResult($data);
    }
    protected function getResponseResult($result)
    {
        $obj = json_decode($result, false, 512, JSON_BIGINT_AS_STRING);
        return (empty($obj) || $obj->code==0)?$obj:false;
    }

    /**
     * @desc 获取添加到发件箱参数
     * @param unknown $inbox
     * @return \yii\helpers\string
     */
    public function getSendParams($inbox)
    {
        $sendParams = '';
        try {
            $sendParams = [
                'account_id' => $inbox->account_id,
                'platform_id' => $inbox->platform_id,
            ];
            $sendParams = \yii\helpers\Json::encode($sendParams);
        } catch (\Exception $e) {
            //echo $e->getMessage();exit;
        }
        return $sendParams;
    }

    /**
     * @desc 获取回复标题
     */
    public function getSubject()
    {
        return $this->reply_content;
    }

    /**
     * @desc 获取回复内容
     */
    public function getReplyContent()
    {
        return $this->reply_content;
    }
}
