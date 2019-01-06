<?php

namespace app\modules\mails\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\mails\models\EbCaseResponse;

/**
 * EbCaseResponseSearch represents the model behind the search form about `app\modules\mails\models\EbCaseResponse`.
 */
class EbCaseResponseSearch extends EbCaseResponse {

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'type', 'status', 'lock_status', 'account_id'], 'integer'],
            [['case_id', 'content', 'refund_source', 'refund_status', 'error', 'lock_time', 'create_by', 'create_time', 'modify_by', 'modify_time'], 'safe'],
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
        $query = EbCaseResponse::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'create_time' => SORT_DESC,
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
            'type' => $this->type,
            'status' => $this->status,
            'lock_status' => $this->lock_status,
            'lock_time' => $this->lock_time,
            'account_id' => $this->account_id,
            'create_time' => $this->create_time,
            'modify_time' => $this->modify_time,
        ]);

        $query->andFilterWhere(['like', 'case_id', $this->case_id])
                ->andFilterWhere(['like', 'content', $this->content])
                ->andFilterWhere(['like', 'refund_source', $this->refund_source])
                ->andFilterWhere(['like', 'refund_status', $this->refund_status])
                ->andFilterWhere(['like', 'error', $this->error])
                ->andFilterWhere(['like', 'create_by', $this->create_by])
                ->andFilterWhere(['like', 'modify_by', $this->modify_by]);

        return $dataProvider;
    }

}
