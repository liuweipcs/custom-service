<?php

namespace app\modules\mails\models;

use Yii;
use app\modules\accounts\models\Account;
use app\modules\orders\models\AmazonOrderList;
use app\modules\mails\models\AmazonInboxSubject;

/**
 * This is the model class for table "{{%amazon_feedback}}".
 *
 * @property string $id
 * @property integer $account_id
 * @property string $date
 * @property integer $rating
 * @property string $comments
 * @property string $your_response
 * @property string $arrived_on_time
 * @property string $item_as_described
 * @property string $customer_service
 * @property string $order_id
 * @property string $rater_email
 * @property string $rater_role
 * @property string $update_date
 */
class AmazonFeedBack extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_feedback}}';
    }

    public function attributes()
    {
        // 添加关联字段到可搜索属性集合
        $attributes    = parent::attributes();
        $extAttributes = [
            'order_type',
            'contact_buyer',
            'site_code',
            'ship_phone',
        ];
        return array_merge($attributes, $extAttributes);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'hash'], 'required'],
            [['account_id', 'rating', 'review_status', 'follow_status', 'is_reply', 'is_station', 'is_review', 'modified_id'], 'integer'],
            [['date', 'modified_time', 'comments', 'your_response'], 'safe'],
            [['arrived_on_time'], 'string', 'max' => 4],
            [['order_id', 'rater_role'], 'string', 'max' => 20],
            [['item_as_described', 'customer_service'], 'string', 'max' => 100],
            [['rater_email'], 'string', 'max' => 60],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'account_id'        => 'Account ID',
            'date'              => 'Date',
            'rating'            => 'Rating',
            'comments'          => 'Comments',
            'your_response'     => 'Your Response',
            'arrived_on_time'   => 'Arrived On Time',
            'item_as_described' => 'Item As Described',
            'customer_service'  => 'Customer Service',
            'order_id'          => 'Order ID',
            'rater_email'       => 'Rater Email',
            'rater_role'        => 'Rater Role',
            'review_status'     => '差评原因',
            'follow_status'     => '跟进状态',
            'is_reply'          => '是否有回复',
            'is_station'        => '是否有站内消息',
            'is_review'         => '是否有review',
            'modified_name'     => '更新人',
            'modified_time'     => '时间',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne(Account::className(), ['id' => 'account_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    /*    public function getOrderType()
        {
            return $this->hasOne(AmazonOrderList::className(), ['order_id' => 'amazon_order_id']);
        }*/

    /**
     * 星级列表
     * @return type
     * @author allen <2018-03-26>
     */
    public static function startList()
    {
        return [
            1 => '一星级',
            2 => '二星级',
            3 => '三星级',
            4 => '四星级',
            5 => '五星级'
        ];
    }

    /**
     * 根据平台订单id查询订单评价信息
     * @param $platform_order_id
     * @return AmazonFeedBack
     */
    public static function getFindOne($platform_order_id)
    {
        return self::findOne(['order_id' => $platform_order_id]);
    }

    /**
     * 根据平台订单id查询订单类型
     * @param $order_id
     * @return amazon_fulfill_channel
     */
    public static function getOrderType($order_id)
    {
        $orderType = AmazonOrderList::find()
            ->select(['amazon_fulfill_channel'])
            ->where(['amazon_order_id' => $order_id])
            ->asArray()
            ->one();
        if ($orderType) {
            $type = $orderType['amazon_fulfill_channel'];
            switch ($type) {
                case 'MFN':
                    $order_type = 'FBM';
                    break;
                case 'AFN':
                    $order_type = 'FBA';
                    break;
                default:
                    $order_type = '';
                    break;
            }
        }
        return $order_type;
    }


    /**
     * 获取amazon review
     * @param $id
     * @return string
     */
    public static function getAmazonReview($id)
    {
        $html  = '';
        $query = self::find();
        $res   = $query->select('d.custId,d.sellerAcct,d.orderId')
            ->from('{{%amazon_feedback}} t')
            ->join('left join', '{{%amazon_review_message_data}} d', 'CONVERT(t.order_id USING utf8) COLLATE utf8_unicode_ci = d.orderId')
            ->where(['t.id' => $id])
            ->asArray()
            ->all();
        if (!empty($res)) {
            foreach ($res as $value) {
                if ($value['custId'] && $value['sellerAcct'] && $value['orderId']) {
                    $review = AmazonReviewData::find()->where(['customerId' => $value['custId'], 'sellerAcct' => $value['sellerAcct']])->one();
                    if ($review) {
                        $html .= '<a style="color:green" href="/mails/amazonreviewdata/index?&AmazonReviewDataSearch[asin]=' . $value['orderId'] . '" target="_blank">' . $review->title . '</a><br/>';
                    }
                }
            }
        }
        return $html;
    }


    /**
     * @author allen <2018-03-27>
     * 获取站内信信息
     * @param $id
     * @return string
     */
    public static function getStationLetter($id)
    {
        $html  = '';
        $query = self::find();
        $res   = $query->select('t.rater_email,a.old_account_id,t.order_id')
            ->from('{{%amazon_feedback}} t')
            ->join('left join', '{{%account}} a', 't.account_id = a.id')
            ->where(['t.id' => $id])
            ->asArray()
            ->all();
        if (!empty($res)) {
            foreach ($res as $value) {
                if ($value['order_id'] && $value['old_account_id'] && $value['rater_email']) {
                    $email = AmazonInboxSubject::find()->where(['order_id' => $value['order_id'], 'account_id' => $value['old_account_id'], 'sender_email' => $value['rater_email']])->one();
                    if ($email) {
                        $html .= '<a  style="color:green;" href="/mails/amazoninboxsubject/view?id=' . $email->id . '" target="_blank">' . $email->first_subject . '</a><br/>';
                    }
                }
            }
        }
        return $html;
    }
}
