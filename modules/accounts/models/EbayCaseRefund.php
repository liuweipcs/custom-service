<?php

namespace app\modules\accounts\models;

use app\modules\accounts\models\Account;
use Yii;
use app\modules\systems\models\AccountRefundaccountRelation;
/**
 * This is the model class for table "{{%refund_account}}".
 *
 * @property integer $id
 * @property string $email
 * @property string $api_username
 * @property string $api_password
 * @property string $api_signature
 * @property integer $status
 */
class EbayCaseRefund extends AccountsModel
{   
    const STATUS_REFUND_NO = 0; //升级不自动退款
    const STATUS_REFUND_YES = 1; //升级自动退款
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ebay_case_refund_set}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id'], 'required'],
            [['is_refund'], 'integer'],
            [['claim_amount'], 'double'],
            [['create_time', 'modify_time', 'currency'], 'safe'],
            [['create_by', 'modify_by'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'is_refund' => '是否自动退款',
            'currency' => '币种',
            'claim_amount' => '退款最大金额',
            'create_time' => '创建时间',
            'modify_time' => '修改时间',
            'create_by' => '创建人',
            'modify_by' => '修改人',
            'account_name' => '帐号名称',
            'account_short_name' => '帐号简称',
        ];
    }
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = ['account_name','account_short_name'];
        return array_merge($attributes, $extraAttributes);
    }
    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public  function searchList($params = [])
    {   
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_DESC
        );

        $query = self::find()->from(self::tableName().' as t');
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        foreach ($models as  $key => $model) {
            $models[$key]->setAttribute('is_refund', self::getStatusList($model->is_refund));
        }
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function dynamicChangeFilter(&$filterOptions,&$query,&$params)
    {
        $query->select('t.*,t1.account_name,t1.account_short_name')
            ->innerJoin('{{%account}} as t1','t1.id = t.account_id');
//            ->andWhere(['t1.status'=>Account::STATUS_VALID]);
        if(isset($params['account_name']) and !empty($params['account_name']))
        {
            $query->andWhere(['t1.account_name'=>$params['account_name']]);
            unset($params['account_name']);
        }
        if(isset($params['account_short_name']) and !empty($params['account_short_name']))
        {
            $query->andWhere(['t1.account_short_name'=>$params['account_short_name']]);
            unset($params['account_short_name']);
        }
    }

    /**获取账户的状态信息 **/
    public static function getStatusList($key = null)
    {   
        $list = [
            self::STATUS_REFUND_NO     => '否',
            self::STATUS_REFUND_YES     => '是',
        ];
        if (!is_null($key))
        {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }

    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'account_name',
                'type' => 'text',
                'alias' => 't1',
                'search' => '=',
            ],
            [
                'name' => 'account_short_name',
                'type' => 'text',
                'alias' => 't1',
                'search' => '=',
            ],
            [
                'name' => 'is_refund',
                'type' => 'dropDownList',
                'data' => self::getStatusList(),
                'search' => '=',
            ],
        ];
    }
    
    /** 获取所有的升级自动退款账号数据 **/
    public static function getList()
    {
        $list = self::find()
              ->select('id,account_id')
              ->where(['status' => self::STATUS_REFUND_YES])
              ->asArray()
              ->all(); 
        return $list; 
    }
}
