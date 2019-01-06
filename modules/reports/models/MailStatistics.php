<?php

namespace app\modules\reports\models;

use Yii;
use app\modules\mails\models\MailsModel;
use app\modules\mails\models\EbayInboxSubject;
use app\modules\mails\models\MailOutbox;
use app\modules\mails\models\AliexpressInbox;

/**
 * This is the model class for table "{{%mail_statistics}}".
 *
 * @property integer $id
 * @property string $platform_code
 * @property integer $account_id
 * @property string $create_time
 * @property string receive_date
 * @property integer $status
 * @property integer $message_id
 * @property string $sender
 */
class MailStatistics extends MailsModel
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%mail_statistics}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform_code', 'account_id'], 'required'],
            [['account_id', 'status'], 'integer'],
            [['create_time','receive_date'], 'safe'],
            [['platform_code', 'message_id'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_code' => 'Platform Code',
            'account_id' => 'Account ID',
            'create_time' => 'Create Time',
            'status' => 'Status',
            'message_id' => 'Message Id',
            'sender' => 'Sender',
            'receive_date'=> 'Receive Date'
        ];
    }

    /**
     * 当月未回复邮件列表
     */
    public static function mailNotList($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            foreach ($range as $key => $item) {
                $query = MailStatistics::find()
                    ->where(['status' => 0])
                    ->andWhere(['<>', 'sender', 'ebay'])
                    ->andWhere(['platform_code' => $platform_code])
                    ->andWhere(['between', 'receive_date', $item['start_time'], $item['end_time']]);

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }else{
            foreach ($range as $key => $item) {
                $query = AliexpressInbox::find()
                    ->where(['deal_stat' => 0])
                    ->andWhere(['between', 'receive_date', $item['start_time'], $item['end_time']]);

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }

        return $data;

    }

    /**
     * 当天未回复邮件列表
     */
    public static function mailNotDay($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            $query = MailStatistics::find()
                ->where(['status' => 0])
                ->andWhere(['<>', 'sender', 'ebay'])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressInbox::find()
                ->where(['deal_stat' => 0])
                ->andWhere($range);
            if (!empty($accountId)){
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }
        $data = $query->count();
        return $data;

    }

    /**
     * 当月所有邮件列表
     */
    public static function mailList($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            foreach ($range as $key => $item) {
                $query = MailStatistics::find()
                    ->andWhere(['<>', 'sender', 'ebay'])
                    ->andWhere(['platform_code' => $platform_code])
                    ->andWhere(['between', 'receive_date', $item['start_time'], $item['end_time']]);

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }else{
            foreach ($range as $key => $item) {
                $query = AliexpressInbox::find()
                    ->andWhere(['between', 'receive_date', $item['start_time'], $item['end_time']]);

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }

        return $data;

    }

    /**
     * 当天所有邮件列表
     */
    public static function mailDay($platform_code, $accountId, $range = [])
    {

        if($platform_code == 'EB'){
            $query = MailStatistics::find()
                ->andWhere(['<>', 'sender', 'ebay'])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if ($accountId) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressInbox::find()
                ->where($range);
            if (!empty($accountId)){
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();
        return $data;
    }

    /**
     * 主动联系
     */
    public static function mailAction($platform_code, $accountId,$range = [])
    {
        $query = MailOutbox::find()
            ->andWhere(['<>', 'create_by', 'system'])
            ->andWhere(['send_status' => 2])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere($range);
        if($accountId){
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();
        return $data;
    }

    /**
     * 当月所有已回复邮件列表
     */
    public static function mailEndList($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            foreach ($range as $key => $item) {
                $query = MailStatistics::find()
                    ->where(['status' => 1])
                    ->andWhere(['<>', 'sender', 'ebay'])
                    ->andWhere(['platform_code' => $platform_code])
                    ->andWhere(['between', 'receive_date', $item['start_time'], $item['end_time']]);

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }else{
            foreach ($range as $key => $item) {
                $query = AliexpressInbox::find()
                    ->where(['deal_stat' => 1])
                    ->andWhere(['between', 'receive_date', $item['start_time'], $item['end_time']]);

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }

        return $data;

    }

    /**
     * 当月所有主动联系
     */
    public static function mailActionAll($platform_code, $accountId,$range = [])
    {
        foreach ($range as $key => $item) {
            $query = MailOutbox::find()
                ->andWhere(['<>', 'create_by', 'system'])
                ->andWhere(['send_status' => 2])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere(['between', 'send_time', $item['start_time'], $item['end_time']]);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
            $data[$key] = $query->count();
        }
        return $data;
    }

    /**
     * 当天所有已回复邮件列表
     */
    public static function mailEndDay($platform_code, $accountId, $range = [])
    {

        if($platform_code == 'EB'){
            $query = MailStatistics::find()
                ->where(['status' => 1])
                ->andWhere(['<>', 'sender', 'ebay'])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if ($accountId) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressInbox::find()
                ->where(['deal_stat' => 1])
                ->andWhere($range);
            if (!empty($accountId)){
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();
        return $data;
    }

    /**
     * 当月所有待回复邮件列表
     */
    public static function mailWaitList($platform_code, $accountId, $range=[])
    {

        if($platform_code == 'EB'){
            $query = EbayInboxSubject::find()
                ->where(['<>', 'buyer_id', 'ebay'])
                ->andWhere(['between', 'receive_date', $range['start_time'], $range['end_time']])
                ->andWhere(['is_replied' => 0]);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressInbox::find()
                ->andWhere(['between', 'receive_date', $range['start_time'], $range['end_time']])
                ->andWhere(['deal_stat' => 0]);
            if (!empty($accountId)){
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

       // echo $query->createCommand()->getRawSql();die;
        $data = $query->count();
        return $data;
    }

    /**
     * 当天所有待回复邮件列表
     */
    public static function mailWaitDay($platform_code, $accountId, $range)
    {
        $query = MailStatistics::find()
            ->where(['status' => 0])
            ->andWhere(['<>', 'sender', 'ebay'])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere(['between', 'receive_date', $range['start_time'], $range['end_time']]);
        if ($accountId) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();
        return $data;

    }

    /**
     * 当月未回复邮件所有
     */

    public static function mailnotAll($platform_code, $accountId, $range)
    {
        if($platform_code == 'EB'){
            $query = MailStatistics::find()
                ->where(['status' => 0])
                ->andWhere(['<>', 'sender', 'ebay'])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressInbox::find()
                ->andWhere($range)
                ->andWhere(['deal_stat' => 0]);
            if (!empty($accountId)){
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();

        return $data;

    }

    /**
     * 已回复
     */
    public static function mailEndAll($platform_code, $accountId, $range)
    {
        if($platform_code == 'EB'){
            $query = MailStatistics::find()
                ->where(['status' => 1])
                ->andWhere(['<>', 'sender', 'ebay'])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressInbox::find()
                ->where($range)
                ->andWhere(['deal_stat' => 1]);
            if (!empty($accountId)){
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }


        $data = $query->count();

        return $data;

    }

    /**
     * 所有邮件
     */
    public static function mailAll($platform_code, $accountId, $range)
    {
        if($platform_code == 'EB'){
            $query = MailStatistics::find()
                ->andWhere(['<>', 'sender', 'ebay'])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressInbox::find()
                ->where($range);
            if (!empty($accountId)){
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();

        return $data;

    }

    /**
     * 所有待回复邮件
     */
    public static function mailWaitAll($platform_code, $accountId, $range=[])
    {

        $query = MailStatistics::find()
            ->where(['status' => 0])
            ->andWhere(['<>', 'sender', 'ebay'])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere(['between', 'receive_date', $range['start_time'], $range['end_time']]);
        if (!empty($accountId)) {
            $query->andWhere(['in', 'account_id', $accountId]);

        }

        $data = $query->count();

        return $data;

    }
}
