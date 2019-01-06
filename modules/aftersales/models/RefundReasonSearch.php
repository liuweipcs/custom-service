<?php

namespace app\modules\aftersales\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\aftersales\models\RefundReason;
use app\modules\systems\models\BasicConfig;

/**
 * RefundReasonSearch represents the model behind the search form about `app\modules\aftersales\models\RefundReason`.
 */
class RefundReasonSearch extends RefundReason {

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'department_id', 'reason_type_id', 'formula_id', 'create_by_id', 'update_by_id'], 'integer'],
            [['remark', 'create_by', 'create_time', 'update_by', 'update_time','refund_cost_id','resend_cost_id'], 'safe'],
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
        $query = RefundReason::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
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
            'department_id' => $this->department_id,
            'reason_type_id' => $this->reason_type_id,
            'formula_id' => $this->formula_id,
            'refund_cost_id' => $this->refund_cost_id,
            'resend_cost_id' => $this->resend_cost_id,
            'create_by_id' => $this->create_by_id,
            'create_time' => $this->create_time,
            'update_by_id' => $this->update_by_id,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'remark', $this->remark])
                ->andFilterWhere(['like', 'create_by', $this->create_by])
                ->andFilterWhere(['like', 'update_by', $this->update_by]);

        return $dataProvider;
    }

    public function getReasonType() {
        if ($this->department_id) {
            return BasicConfig::getParentList($this->department_id);
        } else {
            return [];
        }
    }

}
