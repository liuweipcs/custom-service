<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/17
 * Time: 20:55
 */

namespace app\modules\accounts\models;
use Yii;
use app\components\Model;
use yii\data\ActiveDataProvider;


class Aliexpressaccountservicescoreinfo extends Model
{

    public static function getDb()
    {
        return \Yii::$app->db;
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'account_id'], 'integer'],
        ];
    }

    public static function tableName() {
        return '{{%aliexpress_account_servicescoreinfo}}';
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
     * !CodeTemplates.overridecomment.nonjd!
     * @see \app\modules\mails\models\Inbox::addition()
     */
    public function addition(&$models)
    {
        foreach ($models as $key => $model) {
            $models[$key]->setAttribute('account_id', $model->account_id);
            $models[$key]->setAttribute('nr_disclaimer_issue_rate', $model->nr_disclaimer_issue_rate);


        }
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

        $query = self::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        $this->load($params);
        //查询出权限下的订单列表
        $accounts = self::getAccountList(1);
        $accountlist = array();
        foreach( $accounts as $key=>$account ){
            $accountlist[] = $key;
        }

        if(!empty($params['Aliexpressaccountservicescoreinfo']['account_id']) && in_array($params['Aliexpressaccountservicescoreinfo']['account_id'],$accountlist)){
            $query->andFilterWhere([
                'account_id' => $params['Aliexpressaccountservicescoreinfo']['account_id'],
            ]);
        }else {
            $query->andWhere(['in', 'account_id', $accountlist]);
        }
        // grid filtering conditions


        if (!empty($params['Aliexpressaccountservicescoreinfo']['total_score'])) {
            $query->andFilterWhere(['>=', 'total_score', $params['Aliexpressaccountservicescoreinfo']['total_score']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['total_scoremax'])) {
            $query->andFilterWhere(['<=', 'total_score', $params['Aliexpressaccountservicescoreinfo']['total_scoremax']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['buyn'])) {
            $query->andFilterWhere(['>=', 'buy_not_sel_rate', $params['Aliexpressaccountservicescoreinfo']['buyn']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['buynmax'])) {
            $query->andFilterWhere(['<=', 'buy_not_sel_rate', $params['Aliexpressaccountservicescoreinfo']['buynmax']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['nrd'])) {
            $query->andFilterWhere(['>=', 'nr_disclaimer_issue_rate', $params['Aliexpressaccountservicescoreinfo']['nrd']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['nrdmax'])) {
            $query->andFilterWhere(['<=', 'nr_disclaimer_issue_rate', $params['Aliexpressaccountservicescoreinfo']['nrdmax']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['snad_d'])) {
            $query->andFilterWhere(['>=', 'snad_disclaimer_issue_rate', $params['Aliexpressaccountservicescoreinfo']['snad_d']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['snad_dmax'])) {
            $query->andFilterWhere(['<=', 'snad_disclaimer_issue_rate', $params['Aliexpressaccountservicescoreinfo']['snad_dmax']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['dsr_p'])) {
            $query->andFilterWhere(['>=', 'dsr_prod_score', $params['Aliexpressaccountservicescoreinfo']['dsr_p']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['dsr_pmax'])) {
            $query->andFilterWhere(['<=', 'dsr_prod_score', $params['Aliexpressaccountservicescoreinfo']['dsr_pmax']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['dsr_c'])) {
            $query->andFilterWhere(['>=', 'dsr_communicate_score', $params['Aliexpressaccountservicescoreinfo']['dsr_c']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['dsr_cmax'])) {
            $query->andFilterWhere(['<=', 'dsr_communicate_score', $params['Aliexpressaccountservicescoreinfo']['dsr_cmax']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['dsr_l'])) {
            $query->andFilterWhere(['>=', 'dsr_logis_score', $params['Aliexpressaccountservicescoreinfo']['dsr_l']]);

        }
        if (!empty($params['Aliexpressaccountservicescoreinfo']['dsr_lmax'])) {
            $query->andFilterWhere(['<=', 'dsr_logis_score', $params['Aliexpressaccountservicescoreinfo']['dsr_lmax']]);

        }


        return $dataProvider;
    }

    /**
     * @desc 账号列表
     * @param string $status
     * @return unknown
     */
    public static function getAccountList($status = null){
        $accountList = Account::getCurrentUserPlatformAccountList('ALI', $status);
        $list = [];
        if(!empty($accountList)){
            foreach ($accountList as $value){
                $list[$value->attributes['old_account_id']] = $value->attributes['account_name'];
            }
        }
        return $list;
    }

}