<?php

namespace app\modules\mails\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * AmazonReviewDataSearch represents the model behind the search form about `app\modules\mails\models\AmazonReviewData`.
 */
class AmazonReviewDataSearch extends AmazonReviewData
{

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['asin', 'customerName', 'title'], 'trim'],
            [['id', 'star', 'vp', 'accountId', 'status', 'review_status', 'follow_status', 'is_reply', 'is_station'], 'integer'],
            [['reviewId', 'asin', 'sellerSku', 'customerName', 'title', 'customerId', 'sellerAcct', 'insertDate', 'imgUrl', 'marketplaceId', 'merchantId', 'content', 'reviewDate', 'remark', 'addTime', 'modified_id', 'modified_name', 'modified_time', 'amazon_fulfill_channel'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = AmazonReviewData::find();
        if (isset($params['type'])) {
            $query->select('follow_status,count(1) as total')
                ->from('{{%amazon_review_data}} t')
                ->join('LEFT JOIN', '{{%amazon_review_message_data}} m', 't.customerId = m.custId and t.sellerAcct = m.sellerAcct');
        } else {
            $query->select('t.*,m.orderId,m.site,m.custEmail,m.amazon_fulfill_channel')
                ->from('{{%amazon_review_data}} t')
                ->join('LEFT JOIN', '{{%amazon_review_message_data}} m', 't.customerId = m.custId and t.sellerAcct = m.sellerAcct');
        }

        // add conditions that should always apply here


        $query->orderBy('m.orderId DESC');
        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);


        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id'              => $this->id,
            //'star' => $this->star,
            't.vp'            => $this->vp,
            't.accountId'     => $this->accountId,
//            't.reviewDate' => $this->reviewDate,
            't.status'        => $this->status,
            't.addTime'       => $this->addTime,
            't.review_status' => $this->review_status,
            't.follow_status' => $this->follow_status,
            't.is_reply'      => $this->is_reply,
            't.is_station'    => $this->is_station,
            't.modified_name' => $this->modified_name,
        ]);

        switch ($this->star) {
            case 1:
                $query->andFilterWhere(['in', 't.star', [1, 2, 3]]);
                break;
            case 2:
                $query->andFilterWhere(['in', 't.star', [4, 5]]);
                break;
            default:
                break;
        }

        if (!empty($this->reviewDate)) {
            $query->andFilterWhere(['between', 't.reviewDate', explode('/', $this->reviewDate)[0], explode('/', $this->reviewDate)[1]]);
        }

        $query->andFilterWhere(['like', 't.reviewId', $this->reviewId])
            ->andFilterWhere(['or', ['like', 't.asin', $this->asin], ['like', 'm.orderid', $this->asin], ['like', 'm.custEmail', $this->asin], ['like', 'm.amazon_fulfill_channel', $this->asin]])
            ->andFilterWhere(['like', 't.sellerSku', $this->sellerSku])
            ->andFilterWhere(['like', 't.customerName', $this->customerName])
            ->andFilterWhere(['like', 't.title', $this->title])
            ->andFilterWhere(['like', 't.customerId', $this->customerId])
            ->andFilterWhere(['like', 't.sellerAcct', $this->sellerAcct])
            ->andFilterWhere(['like', 't.insertDate', $this->insertDate])
            ->andFilterWhere(['like', 't.imgUrl', $this->imgUrl])
            ->andFilterWhere(['like', 't.marketplaceId', $this->marketplaceId])
            ->andFilterWhere(['like', 't.merchantId', $this->merchantId])
            ->andFilterWhere(['like', 't.content', $this->content])
            ->andFilterWhere(['like', 't.remark', $this->remark])
            ->andFilterWhere(['in', 't.modified_id', $this->modified_id]);
        if (isset($params['type'])) {
            $data = [];
            $res  = $query->groupBy('follow_status')
                ->asArray()
                ->all();
            if (!empty($res)) {
                foreach ($res as $value) {
                    if (empty($value['follow_status'])) {
                        $data['noset'] = $value['total'];
                    } else {
                        $data[$value['follow_status']] = $value['total'];
                    }
                }
            }
            return $data;
        } else {
            return $dataProvider;
        }
    }

    /**
     * 根据跟进状态分组统计数量
     * @return type
     * @author allen <2018-06-05>
     */
    public function getstatistics()
    {
        $data  = [];
        $query = AmazonReviewData::find();
        $res   = $query->select('follow_status,count(1) as total')
            ->from('{{%amazon_review_data}} t')
            ->join('LEFT JOIN', '{{%amazon_review_message_data}} m', 't.customerId = m.custId')
            ->groupBy('follow_status')
            ->asArray()
            ->all();
        if (!empty($res)) {
            foreach ($res as $value) {
                if (empty($value['follow_status'])) {
                    $data['noset'] = $value['total'];
                } else {
                    $data[$value['follow_status']] = $value['total'];
                }
            }
        }
        return $data;
    }

}
