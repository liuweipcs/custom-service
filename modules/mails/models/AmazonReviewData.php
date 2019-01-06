<?php

namespace app\modules\mails\models;

use app\modules\mails\models\AmazonInboxSubject;

use Yii;

/**
 * This is the model class for table "{{%amazon_review_data}}".
 *
 * @property string $id
 * @property string $reviewId
 * @property string $asin
 * @property string $sellerSku
 * @property string $customerName
 * @property string $title
 * @property string $customerId
 * @property integer $star
 * @property string $sellerAcct
 * @property integer $vp
 * @property string $insertDate
 * @property string $imgUrl
 * @property string $marketplaceId
 * @property string $merchantId
 * @property integer $accountId
 * @property string $content
 * @property string $reviewDate
 * @property string $remark
 * @property integer $status
 * @property string $addTime
 * @property integer $review_status
 * @property integer $follow_status
 * @property integer $is_reply
 * @property integer $is_station
 */
class AmazonReviewData extends \yii\db\ActiveRecord
{

    public $contact_buyer;
    public $orderId;
    public $site;
    public $custEmail;
    public $amazon_fulfill_channel;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_review_data}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['star', 'vp', 'accountId', 'status', 'review_status', 'follow_status', 'is_reply', 'is_station'], 'integer'],
            [['content'], 'string'],
            [['reviewDate', 'addTime', 'modified_id', 'modified_name', 'modified_time'], 'safe'],
            [['reviewId'], 'string', 'max' => 20],
            [['asin'], 'string', 'max' => 40],
            [['sellerSku', 'customerName', 'customerId'], 'string', 'max' => 50],
            [['title', 'remark'], 'string', 'max' => 255],
            [['sellerAcct'], 'string', 'max' => 100],
            [['insertDate', 'marketplaceId', 'merchantId'], 'string', 'max' => 25],
            [['imgUrl'], 'string', 'max' => 150],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'reviewId'      => 'Review ID',
            'asin'          => 'Asin',
            'sellerSku'     => 'Seller Sku',
            'customerName'  => '评论客户',
            'title'         => 'Title',
            'customerId'    => '用户ID',
            'star'          => '星级',
            'sellerAcct'    => 'Seller Acct',
            'vp'            => 'Vp',
            'insertDate'    => 'Insert Date',
            'imgUrl'        => '产品图片',
            'marketplaceId' => '市场ID',
            'merchantId'    => 'Merchant ID',
            'accountId'     => '账号',
            'content'       => '内容',
            'reviewDate'    => 'Review时间',
            'remark'        => '备注',
            'status'        => '状态',
            'addTime'       => '导入系统时间',
            'review_status' => '差评原因',
            'follow_status' => '跟进状态',
            'is_reply'      => '是否回复',
            'is_station'    => '是否有站内消息',
            'modified_time' => '最后修改时间',
            'modified_id'   => '最后修改人ID',
            'modified_name' => '最后修改人名'
        ];
    }

    /**
     * b保持数据
     * @param type $value
     * @return boolean
     * @author allen <2018-03-24>
     */
    public function saveData($model, $value)
    {
        $bool = FALSE;
        $model->id            = $value->id;
        $model->reviewId      = $value->reviewId;
        $model->asin          = $value->asin;
        $model->sellerSku     = $value->sellerSku;
        $model->customerName  = $value->customerName;
        $model->title         = $value->title;
        $model->customerId    = $value->customerId;
        $model->star          = $value->star;
        $model->sellerAcct    = $value->sellerAcct;
        $model->vp            = $value->vp;
        $model->insertDate    = $value->insertDate;
        $model->imgUrl        = $value->imgUrl;
        $model->marketplaceId = $value->marketplaceId;
        $model->merchantId    = $value->merchantId;
        $model->accountId     = $value->accountId;
        $model->content       = $value->content;
        $model->reviewDate    = $value->reviewDate;
        $model->remark        = $value->remark;
        $model->status        = $value->status;
        $model->addTime       = $value->addTime;
        if (!$model->save()) {
            $bool = TRUE;
        }
        return $bool;
    }

    /**
     * 星级列表
     * @return type
     * @author allen <2018-03-26>
     */
    public static function startList()
    {
        return [
            1 => '一颗星',
            2 => '二颗星',
            3 => '三颗星',
            4 => '四颗星',
            5 => '五颗星'
        ];
    }

    /**
     * 获取站内信信息
     * @param type $id
     * @author allen <2018-03-27>
     */
    public static function getStationLetter($id)
    {
        $html  = '';
        $query = self::find();
        $res   = $query->select('m.custName,m.custEmail,a.old_account_id')
            ->from('{{%amazon_review_data}} t')
            ->join('inner join', '{{%amazon_review_message_data}} m', 't.customerId = m.custId')
            ->join('left join', '{{%account}} a', 't.accountId = a.id')
            ->where(['t.id' => $id])
            ->asArray()
            ->all();

        if (!empty($res)) {
            foreach ($res as $value) {
                if ($value['old_account_id'] && $value['custName'] && $value['custEmail']) {
                    $email = AmazonInboxSubject::find()->where(['account_id' => $value['old_account_id'], 'buyer_id' => $value['custName'], 'sender_email' => $value['custEmail']])->one();
                    if ($email) {
                        $html .= '<a style="color:' . $email->is_replied ? 'green' : 'red' . '" href="/mails/amazoninboxsubject/view?id=' . $email->id . '" target="_blank">' . $email->first_subject . '</a><br/>';
                    }
                }
            }
        }
        return $html;
    }

}
