<?php

namespace app\modules\reports\models;

use app\modules\mails\models\AliexpressInbox;
use Yii;
use app\modules\mails\models\MailsModel;
use app\modules\mails\models\EbayInquiry;
use app\modules\mails\models\EbayReturnsRequests;
use app\modules\mails\models\EbayCancellations;
use app\modules\mails\models\AliexpressDisputeList;

/**
 * This is the model class for table "{{%dispute_statistics}}".
 *
 * @property integer $id
 * @property string $platform_code
 * @property integer $account_id
 * @property string $create_time
 * @property string $creation_date
 * @property string $return_creation_date
 * @property string $type
 * @property integer $status
 * @property string $dispute_id
 * @property string $reply
 * @property integer $requestor_type
 * @property string $cancel_request_date
 */
class DisputeStatistics extends MailsModel
{
    const TASK_TYPE_RETURN = 'return'; //return纠纷
    const TASK_TYPE_RETURN_UPDATE = 'return_update'; //return纠纷update
    const TASK_TYPE_INQUIRY = 'inquiry';//inquiry纠纷
    const TASK_TYPE_INQUIRY_UPDATE = 'inquiry_update';//inquiry纠纷
    const TASK_TYPE_CANCELLATION = 'cancellation'; //Cancellation纠纷
    const TASK_TYPE_CANCELLATION_UPDATE = 'cancellation_update'; //Cancellation纠纷update
    const TASK_TYPE_FEEDBACK = 'feedback';


    //物流纠纷
    public static $logisticsDispute = [
        '货物仍然在运输途中',
        '运单号无法查询到物流信息',
        '包裹丢失',
        '物流退回了包裹',
        '物流方式不一致',
        '发错地址',
        '海关扣关',
    ];
    //卖家原因纠纷
    public  static  $buyerReasonDispute = ['下错单', '我不需要', '我找到更便宜的'];

    //质量纠纷
    public  static  $qualityDispute = ['物流纠纷', '买家原因纠纷'];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%dispute_statistics}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform_code', 'account_id'], 'required'],
            [['account_id', 'status', 'requestor_type'], 'integer'],
            ['dispute_id', 'unique'],
            [['create_time','cancel_request_date','creation_date'], 'safe'],
            [['platform_code', 'dispute_id'], 'string', 'max' => 50],
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
            'type' => 'Type',
            'requestor_type' => 'Requestor Type',
            'dispute_id' => 'Dispute Id',
            'cancel_request_date' => 'Cancel Request Date',
            'creation_date' => 'Creation Date',
            'return_creation_date' => 'Return Creation Date',
            'reply' => 'Reply'
        ];
    }

    /**
     * 当月所有未收到纠纷/退款退货
     */
    public static function disputeList($platform_code, $accountId, $range = [], $type)
    {
        if($platform_code == 'EB'){
            foreach ($range as $key => $item) {
                $query = self::find()
                    ->andWhere(['type' => $type])
                    ->andWhere(['platform_code' => $platform_code]);
                if($type == self::TASK_TYPE_INQUIRY){
                    $query->andWhere(['between', 'creation_date', $item['start_time'], $item['end_time']]);
                }
                if($type == self::TASK_TYPE_RETURN){
                    $query->andWhere(['between', 'return_creation_date', $item['start_time'], $item['end_time']]);
                }

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }else{
            foreach ($range as $key => $item) {
                $query = AliexpressDisputeList::find()
                      ->andWhere(['between', 'gmt_create', $item['start_time'], $item['end_time']]);
                if($type == 'logistics'){
                    $query->andWhere(['in', 'reason_chinese', self::$logisticsDispute]);
                }else if($type == 'buyer'){
                    $query->andWhere(['in', 'reason_chinese', self::$buyerReasonDispute]);
                }else{
                    $query->andWhere(['not in', 'reason_chinese', self::$qualityDispute]);
                }

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }

        return $data;

    }

    /**
     * 当天所有未收到纠纷/退款退货
     */
    public static function disputeDay($platform_code, $accountId, $range = [], $type = '')
    {

        if($platform_code == 'EB'){
            $query = self::find()
                ->andWhere(['type' => $type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressDisputeList::find()
                ->andWhere($range);
            if($type == 'logistics') {
                $query->andWhere(['in', 'reason_chinese', self::$logisticsDispute]);
            }else if($type == 'buyer'){
                $query->andWhere(['in', 'reason_chinese', self::$buyerReasonDispute]);
            }else{
                $query->andWhere(['not in', 'reason_chinese', self::$qualityDispute]);
            }
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }

        }


        // echo $query->createCommand()->getRawSql();die;
        $data = $query->count();

        return $data;

    }

    /**
     * 当月所有未收到纠纷/退款退货
     * 未处理
     */
    public static function disputeNotList($platform_code, $accountId, $range = [], $type)
    {
        if($platform_code == 'EB'){
            foreach ($range as $key => $item) {
                $query = self::find()
                    ->where(['status' => 0])
                    ->andWhere(['type' => $type])
                    ->andWhere(['platform_code' => $platform_code]);
                if($type == self::TASK_TYPE_INQUIRY){
                    $query->andWhere(['between', 'creation_date', $item['start_time'], $item['end_time']]);
                }
                if($type == self::TASK_TYPE_RETURN){
                    $query->andWhere(['between', 'return_creation_date', $item['start_time'], $item['end_time']]);
                }

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }else{
            foreach ($range as $key => $item) {
                $query = AliexpressDisputeList::find()
                    ->where(['is_handle' => 0])
                    ->andWhere(['between', 'gmt_create', $item['start_time'], $item['end_time']]);
                if($type == 'logistics'){
                    $query->andWhere(['in', 'reason_chinese', self::$logisticsDispute]);
                }else if($type == 'buyer'){
                    $query->andWhere(['in', 'reason_chinese', self::$buyerReasonDispute]);
                }else{
                    $query->andWhere(['not in', 'reason_chinese', self::$qualityDispute]);
                }

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }


        return $data;

    }

    /**
     * 当天所有未收到纠纷/退款退货
     * 未处理
     */
    public static function disputeNotDay($platform_code, $accountId, $range = [], $type)
    {

        if($platform_code == 'EB'){
            $query = self::find()
                ->where(['status' => 0])
                ->andWhere(['type' => $type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);

            if ($accountId) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressDisputeList::find()
                ->where(['is_handle' => 0])
                ->andWhere($range);
            if($type == 'logistics') {
                $query->andWhere(['in', 'reason_chinese', self::$logisticsDispute]);
            }else if($type == 'buyer'){
                $query->andWhere(['in', 'reason_chinese', self::$buyerReasonDispute]);
            }else{
                $query->andWhere(['not in', 'reason_chinese', self::$qualityDispute]);
            }
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }

        }

        $data = $query->count();

        return $data;

    }

    /**
     * 当月所有未收到纠纷/退款退货
     * 已处理
     */
    public static function disputeEndList($platform_code, $accountId, $range = [], $type)
    {
        if($platform_code == 'EB'){
            foreach ($range as $key => $item) {
                $query = self::find()
                    ->where(['status' => 1])
                    ->andWhere(['type' => $type])
                    ->andWhere(['platform_code' => $platform_code]);
                if($type == self::TASK_TYPE_INQUIRY){
                    $query->andWhere(['between', 'creation_date', $item['start_time'], $item['end_time']]);
                }
                if($type == self::TASK_TYPE_RETURN){
                    $query->andWhere(['between', 'return_creation_date', $item['start_time'], $item['end_time']]);
                }

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }else{
            foreach ($range as $key => $item) {
                $query = AliexpressDisputeList::find()
                    ->where(['<>', 'is_handle', 0])
                    ->andWhere(['between', 'gmt_create', $item['start_time'], $item['end_time']]);
                if($type == 'logistics'){
                    $query->andWhere(['in', 'reason_chinese', self::$logisticsDispute]);
                }else if($type == 'buyer'){
                    $query->andWhere(['in', 'reason_chinese', self::$buyerReasonDispute]);
                }else{
                    $query->andWhere(['not in', 'reason_chinese', self::$qualityDispute]);
                }

                if (!empty($accountId)) {
                    $query->andWhere(['in', 'account_id', $accountId]);
                }

                $data[$key] = $query->count();
            }
        }

        return $data;

    }

    /**
     * 当天所有未收到纠纷/退款退货
     * 已处理
     */
    public static function disputeEndDay($platform_code, $accountId, $range = [], $type)
    {

        if($platform_code == 'EB'){
            $query = self::find()
                ->where(['status' => 1])
                ->andWhere(['type' => $type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);

            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{

            $query = AliexpressDisputeList::find()
                ->where(['<>','is_handle',0])
                ->andWhere($range);
            if($type == 'logistics') {
                $query->andWhere(['in', 'reason_chinese', self::$logisticsDispute]);
            }else if($type == 'buyer'){
                $query->andWhere(['in', 'reason_chinese', self::$buyerReasonDispute]);
            }else{
                $query->andWhere(['not in', 'reason_chinese', self::$qualityDispute]);
            }
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();

        return $data;

    }

    /**
     * 当月所有未收到纠纷/退款退货
     * 待处理
     */
    public static function disputeWaitList($platform_code, $accountId, $range = [], $type)
    {

        if($platform_code == 'EB'){
            $query = EbayInquiry::find()
                ->where('status in ("OPEN","PENDING","WAITING_SELLER_RESPONSE")')
                ->andWhere(['between', 'creation_date', $range['start_time'], $range['end_time']])
                ->andWhere(['is_deal'=>1]);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }

        }else{
            $query = AliexpressDisputeList::find()
                ->where(['is_handle' => 0])
                ->andWhere(['between', 'gmt_create', $range['start_time'], $range['end_time']]);
            if($type == 'logistics') {
                $query->andWhere(['in', 'reason_chinese', self::$logisticsDispute]);
            }else if($type == 'buyer'){
                $query->andWhere(['in', 'reason_chinese', self::$buyerReasonDispute]);
            }else{
                $query->andWhere(['not in', 'reason_chinese', self::$qualityDispute]);
            }

            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();
        return $data;

    }

    /**
     * 当天所有未收到纠纷/退款退货
     * 待处理
     */
    public static function disputeWaitDay($platform_code, $accountId, $range = [], $type)
    {

        $query = EbayReturnsRequests::find()
            ->where('status in ("PARTIAL_REFUND_REQUESTED","REPLACEMENT_LABEL_REQUESTED","REPLACEMENT_REQUESTED","REPLACEMENT_WAITING_FOR_RMA","RETURN_LABEL_REQUESTED","RETURN_REQUESTED","RETURN_REQUESTED_TIMEOUT","WAITING_FOR_RETURN_LABEL","WAITING_FOR_RMA")')
            ->andWhere(['between', 'return_creation_date', $range['start_time'], $range['end_time']])
            ->andWhere(['is_deal' => 0])
            ->andWhere(['is_transition' => 1]);
        if ($accountId) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();
        return $data;

    }

    /**
     * 当月所有取消交易纠纷
     */
    public static function cancellationList($platform_code, $accountId, $range = [], $type)
    {
        foreach ($range as $key => $item) {
            $query  = self::find()
                ->andWhere(['requestor_type' => 1])
                ->andWhere(['type' => $type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere(['between', 'cancel_request_date', $item['start_time'], $item['end_time']]);

            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }

            $data[$key] = $query->count();
        }
        return $data;

    }

    /**
     * 当天所有取消交易纠纷
     */
    public static function cancellationDay($platform_code, $accountId, $range = [], $type)
    {
        $query = self::find()
            ->andWhere(['requestor_type' => 1])
            ->andWhere(['type' => $type])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere($range);
        if ($accountId) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();

        return $data;

    }

    /**
     * 当月所有取消交易纠纷
     * 未处理
     */
    public static function cancellationNotList($platform_code, $accountId, $range = [], $type)
    {
        foreach ($range as $key => $item) {
            $query  = self::find()
                ->where(['status' => 0])
                ->andWhere(['requestor_type' => 1])
                ->andWhere(['type' => $type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere(['between', 'cancel_request_date', $item['start_time'], $item['end_time']]);

            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }

            $data[$key] = $query->count();
        }
        return $data;

    }

    /**
     * 当天所有取消交易纠纷
     * 未处理
     */
    public static function cancellationNotDay($platform_code, $accountId, $range = [], $type)
    {

        $query = self::find()
            ->where(['status' => 0])
            ->andWhere(['requestor_type' => 1])
            ->andWhere(['type' => $type])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere($range);
        if ($accountId) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();

        return $data;

    }

    /**
     * 当月所有取消交易纠纷
     * 已处理
     */
    public static function cancellationEndList($platform_code, $accountId, $range = [], $type)
    {
        foreach ($range as $key => $item) {
            $query = self::find()
                ->where(['status' => 1])
                ->andWhere(['requestor_type' => 1])
                ->andWhere(['type' => $type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere(['between', 'cancel_request_date', $item['start_time'], $item['end_time']]);

            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }

            $data[$key] = $query->count();
        }
        return $data;

    }


    /**
     * 当天所有取消交易纠纷
     * 已处理
     */
    public static function cancellationEndDay($platform_code, $accountId, $range = [], $type)
    {

        $query = self::find()
            ->where(['status' => 1])
            ->andWhere(['requestor_type' => 1])
            ->andWhere(['type' => $type])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere($range);
        if ($accountId) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();

        return $data;

    }

    /**
     * 当月所有取消交易纠纷
     * 已处理
     */
    public static function cancellationEndAll($platform_code, $accountId, $range = [], $type)
    {

        $query = self::find()
            ->where(['status' => 1])
            ->andWhere(['requestor_type' => 1])
            ->andWhere(['type' => $type])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere($range);
        if (!empty($accountId)) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();
        return $data;

    }

    /**
     * 当月所有取消交易纠纷
     *
     */
    public static function cancellationAll($platform_code, $accountId, $range = [], $type)
    {

        $query = self::find()
            ->andWhere(['requestor_type' => 1])
            ->andWhere(['type' => $type])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere($range);
        if (!empty($accountId)) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();

        return $data;

    }

    /**
     * 当月所有取消交易纠纷
     * 未处理
     */
    public static function cancellationNotAll($platform_code, $accountId, $range = [], $type)
    {
        $query = self::find()
            ->where(['status' => 0])
            ->andWhere(['requestor_type' => 1])
            ->andWhere(['type' => $type])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere($range);
        if (!empty($accountId)) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();

        return $data;

    }

    /**
     * 当月所有取消交易纠纷
     * 待处理
     */
    public static function cancellationWaitList($platform_code, $accountId, $range = [], $type)
    {

        $query = EbayCancellations::find()
            ->where(['<>','cancel_state',2])
            ->andWhere(['requestor_type' => 1]);
        if (!empty($accountId)) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();
        return $data;

    }

    /**
     * 当月所有取消交易纠纷
     * 待处理
     */
    public static function cancellationWaitDay($platform_code, $accountId, $range = [], $type)
    {

        $query = self::find()
            ->where(['status' => 0])
            ->andWhere(['requestor_type' => 1])
            ->andWhere(['type' => $type])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere(['between', 'cancel_request_date', $range['start_time'], $range['end_time']]);
        if ($accountId) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();

        return $data;

    }

    /**
     * 当前所有取消交易纠纷
     * 待处理
     */
    public static function cancellationWaitAll($platform_code, $accountId, $range = [], $type)
    {
        $query = self::find()
            ->where(['status' => 0])
            ->andWhere(['requestor_type' => 1])
            ->andWhere(['type' => $type])
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere(['between', 'cancel_request_date', $range['start_time'], $range['end_time']]);
        if (!empty($accountId)) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();

        return $data;

    }

    /**
     * 当前所有未收到纠纷/退款退货
     * 速卖通物流纠纷
     */
    public static function disputeAll($platform_code, $accountId, $range = [], $type)
    {
        if($platform_code == 'EB'){
            $query = self::find()
                ->andWhere(['type' => $type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressDisputeList::find()
                ->andWhere($range);
            if($type == 'logistics') {
                $query->andWhere(['in', 'reason_chinese', self::$logisticsDispute]);
            }else if($type == 'buyer'){
                $query->andWhere(['in', 'reason_chinese', self::$buyerReasonDispute]);
            }else{
                $query->andWhere(['not in', 'reason_chinese', self::$qualityDispute]);
            }

            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();

        return $data;

    }

    /**
     * 当前所有未收到纠纷/退款退货
     * 已处理
     */
    public static function disputeEndAll($platform_code, $accountId, $range = [], $type)
    {
        if($platform_code == 'EB'){
            $query = self::find()
                ->where(['status' => 1])
                ->andWhere(['type' => $type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressDisputeList::find()
                ->where(['<>','is_handle', 0])
                ->andWhere($range);
            if($type == 'logistics') {
                $query->andWhere(['in', 'reason_chinese', self::$logisticsDispute]);
            }else if($type == 'buyer'){
                $query->andWhere(['in', 'reason_chinese', self::$buyerReasonDispute]);
            }else{
                $query->andWhere(['not in', 'reason_chinese', self::$qualityDispute]);
            }

            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();

        return $data;

    }

    /**
     * 当前所有未收到纠纷/退款退货
     * 未处理
     */
    public static function disputeNotAll($platform_code, $accountId, $range = [], $type)
    {
        if($platform_code == 'EB'){
            $query = self::find()
                ->where(['status' => 0])
                ->andWhere(['type' => $type])
                ->andWhere(['platform_code' => $platform_code])
                ->andWhere($range);
            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }else{
            $query = AliexpressDisputeList::find()
                ->where(['is_handle' => 0])
                ->andWhere($range);
            if($type == 'logistics') {
                $query->andWhere(['in', 'reason_chinese', self::$logisticsDispute]);
            }else if($type == 'buyer'){
                $query->andWhere(['in', 'reason_chinese', self::$buyerReasonDispute]);
            }else{
                $query->andWhere(['not in', 'reason_chinese', self::$qualityDispute]);
            }

            if (!empty($accountId)) {
                $query->andWhere(['in', 'account_id', $accountId]);
            }
        }

        $data = $query->count();
        return $data;

    }

    /**
     * 当前所有未收到纠纷/退款退货
     * 待处理
     */
    public static function disputeWaitAll($platform_code, $accountId, $range = [], $type)
    {

        $query = self::find()
            ->where(['status' => 0])
            ->andWhere(['type' => $type])
            ->andWhere(['platform_code' => $platform_code]);
        if($type == self::TASK_TYPE_INQUIRY){
            $query->andWhere(['between', 'creation_date', $range['start_time'], $range['end_time']]);
        }
        if($type == self::TASK_TYPE_RETURN){
            $query->andWhere(['between', 'return_creation_date', $range['start_time'], $range['end_time']]);
        }
        if (!empty($accountId)) {
            $query->andWhere(['in', 'account_id', $accountId]);
        }
        $data = $query->count();

        return $data;

    }

}
