<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/17
 * Time: 20:54
 */

namespace app\modules\accounts\models;

use Yii;
use app\components\Model;
use yii\data\ActiveDataProvider;
class Aliexpressaccountdisputeproductlist extends Model
{
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['score', 'product_name'], 'trim'],
            [['id', 'account_id'], 'integer'],
            [['score', 'product_name'], 'safe'],
        ];
    }
    public static function tableName() {
        return '{{%aliexpress_account_disputeproductlist}}';
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


        $accounts = self::getAccountList(1);
        $accountlist = array();
        foreach( $accounts as $key=>$account ){
            $accountlist[] = $key;
        }

        if(!empty($params['Aliexpressaccountdisputeproductlist']['account_id']) && in_array($params['Aliexpressaccountdisputeproductlist']['account_id'],$accountlist)){
            $query->andFilterWhere([
                'account_id' => $params['Aliexpressaccountdisputeproductlist']['account_id'],
            ]);
        }else {
            $query->andWhere(['in', 'account_id', $accountlist]);
        }
        // grid filtering conditions

        $query->andFilterWhere([
            'product_name' => $this->product_name,
        ]);

        if(!empty($params['Aliexpressaccountdisputeproductlist']['score'])) {
            $query->andFilterWhere(['>', 'score', $params['Aliexpressaccountdisputeproductlist']['score']]);

        }
        if(!empty($params['Aliexpressaccountdisputeproductlist']['scoremax'])) {
            $query->andFilterWhere(['<', 'score', $params['Aliexpressaccountdisputeproductlist']['scoremax']]);
        }


        return $dataProvider;
    }
    /**
     * 拉取api数据存入数据库
     */
    public function accountdisputeproductlist($servicescoreinfo,$AliAccount){
        if(is_array($servicescoreinfo) && !empty($servicescoreinfo)) {
            foreach ($servicescoreinfo as $list) {
                $article = Aliexpressaccountdisputeproductlist::findOne(['account_id' => $AliAccount->id, 'product_id' => $list['product_id']]);
                if ($article === null) {
                    $article = new Aliexpressaccountdisputeproductlist;
                    $article->account_id = $AliAccount->id;
                    $article->product_id = $list['product_id'];
                }

                $article->is_offline = $list['is_offline'];
                $article->product_name = $list['product_name'];
                $article->score = $list['score'];
                $article->pulltime = date('Y-m-d');

                $article->save();
            }
        }
    }
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