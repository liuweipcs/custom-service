<?php

namespace app\modules\reports\models;

use Yii;
use app\modules\mails\models\MailsModel;
use app\modules\mails\models\EbayFeedback;
use app\modules\mails\models\AliexpressEvaluateList;

/**
 * This is the model class for table "{{%feedback_statistics}}".
 *
 * @property integer $id
 * @property string $platform_code
 * @property integer $account_id
 * @property string $create_time
 * @property string $comment_time
 * @property integer $comment_type
 * @property integer $status
 * @property string $feedback_id
 */
class FeedbackStatistics extends MailsModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%feedback_statistics}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform_code', 'account_id'], 'required'],
            [['account_id', 'status', 'comment_type'], 'integer'],
            ['feedback_id', 'unique'],
            [['create_time','comment_time'], 'safe'],
            [['platform_code', 'feedback_id'], 'string', 'max' => 50],
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
            'comment_type' => 'Type',
            'feedback_id' => 'Feedback Id',
            'comment_time' => 'Comment Time',
        ];
    }

    /**
     * 当月所有评价
     * 好评/中评
     */
    public static function feedbackList($platform_code, $accountId, $range = [])
    {
        //中评or差评
        if($platform_code == 'EB'){
            $comment_type = [2, 3];
            foreach ($range as $key => $item) {
                $query = self::find()
                    ->andWhere(['in', 'comment_type', $comment_type])
                    ->andWhere(['platform_code' => $platform_code])
                    ->andWhere(['between', 'comment_time', $item['start_time'], $item['end_time']]);

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }else{
            $buyer_evaluation = [1, 2, 3];
            foreach ($range as $key => $item){
                $query = AliexpressEvaluateList::find()
                    ->andWhere(['in', 'buyer_evaluation', $buyer_evaluation])
                    ->andWhere(['between', 'buyer_fb_date', $item['start_time'], $item['end_time']]);
                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }
                $data[$key] = $query->count();
            }
        }

        return $data;

    }

    /**
     * 当月所有评价
     * 好评/中评
     */
    public static function feedbackDay($platform_code, $accountId, $range = [])
    {
        //中评or差评
        if($platform_code == 'EB'){
            $comment_type = [2, 3];
            $query = self::find()
                ->andWhere(['in', 'comment_type', $comment_type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $buyer_evaluation = [1, 2, 3];
            $query = AliexpressEvaluateList::find()
                ->andWhere(['in', 'buyer_evaluation', $buyer_evaluation])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

       //echo $query->createCommand()->getRawSql();die;
        $data = $query->count();
        return $data;

    }

    /**
     * 当月所有评价
     * 好评/中评
     * 未处理
     */
    public static function feedbackNotList($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            $comment_type = [2, 3];
            foreach ($range as $key => $item) {
                $query = self::find()
                    ->where(['status' => 0])
                    ->andWhere(['in', 'comment_type', $comment_type])
                    ->andWhere(['platform_code' => $platform_code])
                    ->andWhere(['between', 'comment_time', $item['start_time'], $item['end_time']]);

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }else{
            $buyer_evaluation = [1, 2, 3];
            foreach ($range as $key => $item){
                $query = AliexpressEvaluateList::find()
                    ->where(['reply_status' => 0])
                    ->andWhere(['in', 'buyer_evaluation', $buyer_evaluation])
                    ->andWhere(['between', 'buyer_fb_date', $item['start_time'], $item['end_time']]);
                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }
                $data[$key] = $query->count();
            }
        }

        return $data;

    }

    /**
     * 当月所有评价
     * 好评/中评
     * 未处理
     */
    public static function feedbackNotDay($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            $comment_type = [2, 3];
            $query = self::find()
                ->where(['status' => 0])
                ->andWhere(['in', 'comment_type', $comment_type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }

        }else{
            $buyer_evaluation = [1, 2, 3];
            $query = AliexpressEvaluateList::find()
                ->where(['reply_status' => 0])
                ->andWhere(['in', 'buyer_evaluation', $buyer_evaluation])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();

        return $data;

    }

    /**
     * 当月所有取消交易纠纷
     * 好评/中评
     * 已处理
     */
    public static function feedbackEndList($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            $comment_type = [2, 3];
            foreach ($range as $key => $item) {
                $query = self::find()
                    ->where(['status' => 1])
                    ->andWhere(['in', 'comment_type', $comment_type])
                    ->andWhere(['platform_code' => $platform_code])
                    ->andWhere(['between', 'comment_time', $item['start_time'], $item['end_time']]);

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }else{
            $buyer_evaluation = [1, 2, 3];
            foreach ($range as $key => $item){
                $query = AliexpressEvaluateList::find()
                    ->where(['<>', 'reply_status', 0])
                    ->andWhere(['in', 'buyer_evaluation', $buyer_evaluation])
                    ->andWhere(['between', 'buyer_fb_date', $item['start_time'], $item['end_time']]);
                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }
                $data[$key] = $query->count();
            }
        }

        return $data;

    }

    /**
     * 当月所有取消交易纠纷
     * 好评/中评
     * 已处理
     */
    public static function feedbackEndDay($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            $comment_type = [2, 3];
            $query = self::find()
                ->where(['status' => 1])
                ->andWhere(['in', 'comment_type', $comment_type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $buyer_evaluation = [1, 2, 3];
            $query = AliexpressEvaluateList::find()
                ->where(['<>', 'reply_status', 0])
                ->andWhere(['in', 'buyer_evaluation', $buyer_evaluation])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();

        return $data;

    }

    /**
     * 当月所有取消交易纠纷
     * 好评/中评
     * 待处理
     */
    public static function feedbackWaitList($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            $comment_type = [2, 3];
            $query = EbayFeedback::find()
                ->where(['status' => 0])
                ->andWhere(['in', 'comment_type', $comment_type])
                ->andWhere(['role' => 1])
                ->andWhere(['between', 'comment_time', $range['start_time'], $range['end_time']]);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $buyer_evaluation = [1, 2, 3];
            $query = AliexpressEvaluateList::find()
                ->where(['reply_status' => 0])
                ->andWhere(['in', 'buyer_evaluation', $buyer_evaluation])
                ->andWhere(['between', 'buyer_fb_date', $range['start_time'], $range['end_time']]);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();
        return $data;

    }

    /**
     * 当天所有取消交易纠纷
     * 好评/中评
     * 待处理
     */
    public static function feedbackWaitDay($platform_code, $accountId, $range = [])
    {
        $comment_type = [2, 3];
        $query = self::find()
            ->where(['status' => 0])
            ->andWhere(['in', 'comment_type', $comment_type])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere(['between', 'comment_time', $range['start_time'], $range['end_time']]);
        if ($accountId) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
      //  echo $query->createCommand()->getRawSql();die;
        $data = $query->count();

        return $data;

    }

    /**
     * 当前所有评价
     *好评/中评
     *
     */
    public static function feedbackAll($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            $comment_type = [2, 3];
            $query = self::find()
                ->andWhere(['in', 'comment_type', $comment_type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $buyer_evaluation = [1, 2, 3];
            $query = AliexpressEvaluateList::find()
                ->andWhere(['in', 'buyer_evaluation', $buyer_evaluation])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();
        return $data;

    }

    /**
     * 当前所有评价
     *好评/中评
     *已处理
     */
    public static function feedbackEndAll($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            $comment_type = [2, 3];
            $query = self::find()
                ->where(['status' => 1])
                ->andWhere(['in', 'comment_type', $comment_type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $buyer_evaluation = [1, 2, 3];
            $query = AliexpressEvaluateList::find()
                ->where(['<>', 'reply_status', 0])
                ->andWhere(['in', 'buyer_evaluation', $buyer_evaluation])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();

        return $data;

    }

    /**
     * 当前所有评价
     *好评/中评
     *未处理
     */
    public static function feedbackNotAll($platform_code, $accountId, $range = [])
    {
        if($platform_code == 'EB'){
            $comment_type = [2, 3];
            $query = self::find()
                ->where(['status' => 0])
                ->andWhere(['in', 'comment_type', $comment_type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $buyer_evaluation = [1, 2, 3];
            $query = AliexpressEvaluateList::find()
                ->where(['reply_status' => 0])
                ->andWhere(['in', 'buyer_evaluation', $buyer_evaluation])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();


        return $data;

    }

    /**
     * 当前所有评价
     *好评/中评
     *待处理
     */
    public static function feedbackWaitAll($platform_code, $accountId, $range = [])
    {

        $comment_type = [2, 3];
        $query = self::find()
            ->where(['status' => 0])
            ->andWhere(['in', 'comment_type', $comment_type])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere(['between', 'comment_time', $range['start_time'], $range['end_time']]);
        if (!empty($accountId)) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();

        return $data;

    }
}
