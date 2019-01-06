<?php

namespace app\modules\systems\models;

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
class RefundAccount extends SystemsModel
{   
    const STATUS_FORBIDDEN = 2; //禁用
    const STATUS_START = 1; //启用
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%refund_account}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email', 'api_username', 'api_password', 'api_signature','status'], 'required'],
            [['status'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [['email', 'api_username', 'api_password','create_by', 'modify_by'], 'string', 'max' => 50],
            [['api_signature'], 'string', 'max' => 100],
            [['client_id', 'secret'],'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => '邮箱',
            'api_username' => 'Api用户名',
            'api_password' => 'Api密码',
            'api_signature' => 'Api签名',
            'client_id' => 'client id',
            'secret' => 'secret',
            'status' => '状态',
            'create_time' => '创建时间',
            'modify_time' => '修改时间',
            'create_by' => '创建人',
            'modify_by' => '修改人',
            'status_text' => '状态',
        ];
    }
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = ['status_text'];             
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

        $query = self::find();
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        foreach ($models as  $key => $model) {
            $models[$key]->setAttribute('status_text', self::getStatusList($model->status));
        }
        $dataProvider->setModels($models);
        return $dataProvider;
    }
    /**获取退款账户的状态信息 **/
    public static function getStatusList($key = null)
    {   
        $list = [
            self::STATUS_FORBIDDEN     => '禁用',
            self::STATUS_START     => '启用', 
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
    /** 判断指定退票账户是否允许删除 **/
    public static function isAlowDeleteById($id)
    {
        $count = AccountRefundaccountRelation::getCountByRefundaccountId($id);

        //该退票账号已经被绑定账户不允许删除
        if ($count) {
            return false;
        }

        //该允许删除
        return true;
    } 
    /** 在批量删除的时候获取允许删除的账号id **/
    public static function getAllowDeleteId($ids)
    {
        $result = [];
        foreach ($ids as $key => $value) {
            if (static::isAlowDeleteById($value)) {
                $result[] = $value;
            }
        }
        return $result;
    }
    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'email',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'api_username',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'status',
                'type' => 'dropDownList',
                'data' => self::getStatusList(),
                'search' => '=',
            ],
        ];
    }
    
    /** 获取所有有效的退票账号数据 **/
    public static function getList()
    {
        $list = self::find()
              ->select('id,email,api_username,api_password,api_signature')
              ->where(['status' => self::STATUS_START])
              ->orderBy('email')
              ->asArray()
              ->all();
        return $list; 
    }

    public static function getOne($id)
    {
        $list = self::find()
        ->select('id,email')
        ->where(['status' => self::STATUS_START])
            ->andWhere(['id'=>$id])
        ->orderBy('email')
        ->asArray()
        ->one();
        return $list;
    }

    //获取client_id及secret
    public static function getAccountOne($email)
    {
        $list = self::find()
        ->select('id,email,client_id,secret')
        ->where(['status' => self::STATUS_START])
            ->andWhere(['email'=>$email])
        ->orderBy('email')
        ->asArray()
        ->one();
        return $list;
    }
}
