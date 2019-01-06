<?php

namespace app\modules\mails\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\mails\models\AmazonFeedBack;
use app\modules\accounts\models\UserAccount;
use app\modules\accounts\models\Platform;
use app\modules\orders\models\AmazonOrderList;
use app\modules\orders\models\OrderAmazonSearch;

/**
 * AmazonFeedBackSearch represents the model behind the search form about `app\modules\mails\models\AmazonFeedBack`.
 */
class AmazonFeedBackSearch extends AmazonFeedBack {

    const PLATFORM_CODE = Platform::PLATFORM_CODE_AMAZON;

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'comments', 'your_response', 'order_id', 'rater_email', 'rater_role', 'review_status', 'follow_status'], 'trim'],
            [['account_id', 'is_station', 'is_review'], 'integer'],
            [['account_id', 'rating', 'comments', 'your_response', 'arrived_on_time', 'item_as_described', 'customer_service', 'order_id', 'rater_email', 'rater_role',  'follow_status', 'review_status', 'modified_id', 'modified_name', 'modified_time', 'date'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
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
    public function search($params) {
        $query = AmazonFeedBack::find();
        $query->select('f.*,a.site_code')
              ->from('{{%amazon_feedback}} f')
              ->join('LEFT JOIN', '{{%account}} a', 'a.id = f.account_id');

        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
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
            //'f.account_id' => $this->account_id,
            //'f.order_id'   => $this->order_id,
            'f.follow_status' => $this->follow_status,
            'f.review_status' => $this->review_status,
            'f.modified_id' => $this->modified_id,
        ]);

        //客服只能查看自已绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        $query->andWhere(['in', 'f.account_id', $accountIds]);

        if ($this->account_id) {
            $query->andWhere(['=','a.old_account_id',$this->account_id]);
        }

        switch ($this->rating) {
            case 1:
                $query->andFilterWhere(['in', 'f.rating', [1, 2, 3]]);
                break;
            case 2:
                $query->andFilterWhere(['in', 'f.rating', [4, 5]]);
                break;
            default:
                break;
        }

        if (!empty($this->date)) {
            $query->andFilterWhere(['between', 'f.date', explode('/', $this->date)[0], explode('/', $this->date)[1]]);
        }

/*        if ($this->order_id == 'FBM') {
            $tableName = AmazonOrderList::dbTableName();

            $query->andWhere("EXISTS (SELECT * FROM {$tableName} WHERE amazon_fulfill_channel='MFN' AND amazon_order_id=f.order_id)");
        } else if ($this->order_id == 'FBA') {
            $tableName = AmazonOrderList::dbTableName();
            $query->andWhere("EXISTS (SELECT * FROM {$tableName} WHERE amazon_fulfill_channel='AFN' AND amazon_order_id=f.order_id)");
        } else {
            $query->andFilterWhere(['=','f.order_id',$this->order_id]);
        }*/
        if ($this->account_id) {

        }

        if ($this->is_review == 1) {
            $tableName = AmazonReviewMessageData::tableName();
            $query->andWhere("EXISTS (SELECT * FROM {$tableName} WHERE orderId=CONVERT(f.order_id USING utf8) COLLATE utf8_unicode_ci)");
        } else if ($this->is_review == 2) {
            $tableName = AmazonReviewMessageData::tableName();
            $query->andWhere("NOT EXISTS (SELECT * FROM {$tableName} WHERE orderId=CONVERT(f.order_id USING utf8) COLLATE utf8_unicode_ci)");
        }        

        if ($this->is_station == 1) {
            $tableName = AmazonInboxSubject::tableName();
            $query->andWhere("NOT EXISTS (SELECT * FROM {$tableName} WHERE order_id=f.order_id AND account_id=f.account_id)");
        } else if ($this->is_station == 2) {
            $tableName = AmazonInboxSubject::tableName();
            $query->andWhere("EXISTS (SELECT * FROM {$tableName} WHERE order_id=f.order_id AND account_id=f.account_id)");
        }

        $query->andFilterWhere(['like','f.comments',$this->comments])
              ->andFilterWhere(['=','f.order_id',$this->order_id])
                ->andFilterWhere(['like','f.your_response',$this->your_response]);

        //echo $query->createCommand()->getRawSql();exit;

         if(isset($params['type'])){
            $data = [];
            $res = $query->groupBy('follow_status')
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
        }else{
            $models = $dataProvider->getModels();
            $this->addition($models);
            $dataProvider->setModels($models);
            return $dataProvider;
        }
    }

    public function addition(&$models)
    {
        foreach ($models as $model) {
            $ship_phone = OrderAmazonSearch::find()->select('ship_phone')->where(['platform_order_id'=>$model->order_id])->scalar();
            $model->setAttribute('ship_phone', $ship_phone);
        }
    }
    
    /**
     * 根据跟进状态分组统计数量
     * @return type
     * @author allen <2018-06-05>
     */
    public function getstatistics() {
        $data = [];
        $query = AmazonFeedBack::find();
        $res = $query->select('follow_status,count(1) as total')
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
