<?php

namespace app\modules\blacklist\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\blacklist\models\BlackList;

/**
 * BlackListSearch represents the model behind the search form about `app\modules\blacklist\models\BlackList`.
 */
class BlackListSearch extends BlackList
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'platfrom_id'], 'integer'],
            [['platfrom_code', 'username','myself_username', 'create_time', 'modify_time'], 'safe'],
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
        $query = BlackList::find();

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
            'platfrom_id' => $this->platfrom_id,
            'create_time' => $this->create_time,
            'modify_time' => $this->modify_time,
        ]);

        $query->andFilterWhere(['like', 'platfrom_code', $this->platfrom_code])
            ->andFilterWhere(['like','myself_username',$this->myself_username])
            ->andFilterWhere(['like', 'username', $this->username]);

        return $dataProvider;
    }
}
