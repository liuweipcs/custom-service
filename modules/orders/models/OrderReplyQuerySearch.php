<?php

namespace app\modules\orders\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\orders\models\OrderReplyQuery;

/**
 * OrderReplyQuerySearch represents the model behind the search form about `app\modules\orders\models\OrderReplyQuery`.
 */
class OrderReplyQuerySearch extends OrderReplyQuery
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'is_send', 'template_id', 'rule_id', 'fail_count', 'execute_id', 'reply_id'], 'integer'],
            [['platform_code', 'order_id', 'order_create_time', 'order_pay_time', 'order_ship_time', 'reply_date', 'error_info'], 'safe'],
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
    public function searchs($params)
    {
        $query = OrderReplyQuery::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'reply_date' => SORT_DESC,            
                ]
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
            'id' => $this->id,
            'is_send' => $this->is_send,
            'template_id' => $this->template_id,
            'rule_id' => $this->rule_id,
            'order_create_time' => $this->order_create_time,
            'order_pay_time' => $this->order_pay_time,
            'order_ship_time' => $this->order_ship_time,
            'reply_date' => $this->reply_date,
            'fail_count' => $this->fail_count,
            'execute_id' => $this->execute_id,
            'reply_id' => $this->reply_id,
        ]);

        $query->andFilterWhere(['like', 'platform_code', $this->platform_code])
            ->andFilterWhere(['like', 'order_id', $this->order_id])
            ->andFilterWhere(['like', 'error_info', $this->error_info]);

        return $dataProvider;
    }
}
