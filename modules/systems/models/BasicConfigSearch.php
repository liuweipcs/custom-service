<?php

namespace app\modules\systems\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\systems\models\BasicConfig;

/**
 * BasicConfigSearch represents the model behind the search form about `app\modules\systems\models\BasicConfig`.
 */
class BasicConfigSearch extends BasicConfig
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'parent_id','status','level','create_id'], 'integer'],
            [['name', 'text', 'create_time', 'create_name'], 'safe'],
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
        $query = BasicConfig::find();

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
            'parent_id' => $this->parent_id,
            'level' => $this->level,
            'status' => $this->status,
            'create_time' => $this->create_time,
            'create_id' => $this->create_id,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'text', $this->text])
            ->andFilterWhere(['like', 'create_name', $this->create_name]);

        return $dataProvider;
    }
}
