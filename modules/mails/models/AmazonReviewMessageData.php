<?php

namespace app\modules\mails\models;

use Yii;

/**
 * This is the model class for table "{{%amazon_review_message_data}}".
 *
 * @property string $id
 * @property integer $messageId
 * @property string $asin
 * @property string $sellerAcct
 * @property string $orderId
 * @property string $custId
 * @property string $custName
 * @property string $custEmail
 * @property string $insertDate
 * @property string $addTime
 * @property string $site
 */
class AmazonReviewMessageData extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_review_message_data}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['messageId'], 'integer'],
            [['addTime'], 'safe'],
            [['asin'], 'string', 'max' => 25],
            [['sellerAcct', 'custEmail'], 'string', 'max' => 120],
            [['orderId', 'custId', 'custName'], 'string', 'max' => 50],
            [['insertDate'], 'string', 'max' => 20],
            [['site'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'messageId'  => 'Message ID',
            'asin'       => 'Asin',
            'sellerAcct' => 'Seller Acct',
            'orderId'    => 'Order ID',
            'custId'     => 'Cust ID',
            'custName'   => 'Cust Name',
            'custEmail'  => 'Cust Email',
            'insertDate' => 'Insert Date',
            'addTime'    => 'Add Time',
            'site'       => 'Site',
        ];
    }

    /**
     * 保存数据
     * @param type $val
     * @return boolean
     * @author allen <2018-03-24>
     */
    public function saveData($val)
    {
        $bool                                     = FALSE;
        $messageDataModel                         = new AmazonReviewMessageData();
        $messageDataModel->id                     = trim($val->id);
        $messageDataModel->messageId              = trim($val->messageId);
        $messageDataModel->asin                   = trim($val->asin);
        $messageDataModel->sellerAcct             = trim($val->sellerAcct);
        $messageDataModel->orderId                = trim($val->orderId);
        $messageDataModel->custId                 = trim($val->custId);
        $messageDataModel->custName               = trim($val->custName);
        $messageDataModel->custEmail              = trim($val->custEmail);
        $messageDataModel->insertDate             = trim($val->insertDate);
        $messageDataModel->addTime                = trim($val->addTime);
        $messageDataModel->site                   = trim($val->site);
        $messageDataModel->amazon_fulfill_channel = trim($val->amazon_fulfill_channel);
        if (!$messageDataModel->save()) {
            $bool = TRUE;
        }
        return $bool;
    }

    /**
     * 平台订单号获取amazon的review
     * @param $order_id
     * @return array|null|string|\yii\db\ActiveRecord
     */
    public static function getReviewByOrderId($order_id)
    {
        $res = self::find()
            ->from('{{%amazon_review_message_data}} d')
            ->select('m.asin,m.title,m.star,m.accountId,m.reviewId')
            ->join('left join', '{{%amazon_review_data}} m', 'd.custId=m.customerId')
            ->where(['CONVERT(d.orderId USING utf8) COLLATE utf8_unicode_ci' => $order_id])
            ->asArray()
            ->one();
        if (!empty($res)) {
            return $res;
        }
        return '';

    }


}
