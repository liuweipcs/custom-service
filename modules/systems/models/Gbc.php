<?php

namespace app\modules\systems\models;

use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\Tag;
use app\modules\mails\models\MailTemplate;
use app\modules\orders\models\Order;
use app\modules\accounts\models\UserAccount;
use app\components\Model;
use app\modules\systems\models\ErpSystemApi;
use YII;

/**
 * This is the model class for table "{{%gbc_data}}".
 *
 * @property integer $id
 * @property integer $type
 * @property string $ebay_id
 * @property string $payment_email
 * @property string $country
 * @property string $state
 * @property string $city
 * @property string $postal_code
 * @property string $address
 * @property string $recipients
 * @property string $detail
 * @property string $modify_by
 * @property string $modify_time
 * @property integer $is_deleted
 */
class Gbc extends SystemsModel
{

    const STATUS_FORBIDDEN = 0; //false
    const STATUS_START = 1; //true

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%gbc_data}}';
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    /*	public function attributes()
        {
            $attributes = parent::attributes();
            $extraAttributes = ['status_text','rule_tag_name','rule_type','rule_condition_name','rule_template_name'];
            return array_merge($attributes, $extraAttributes);

            return parent::attributes();
        }*/
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type'], 'required'],
            [['id', 'type', 'is_deleted'], 'integer'],
            [['ebay_id', 'payment_email', 'country', 'state', 'city', 'postal_code', 'address', 'recipients', 'detail', 'modify_by', 'modify_time'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'platform_code' => Yii::t('app', '平台'),
            'type' => Yii::t('app', '类型'),
            'account_type' => Yii::t('app', '数据来源'),
            'ebay_id' => Yii::t('app', '买家ID'),
            'payment_email' => Yii::t('app', '付款邮箱'),
            'country' => Yii::t('app', '国家'),
            'state' => Yii::t('app', '州'),
            'city' => Yii::t('app', '城市'),
            'postal_code' => Yii::t('app', '邮编'),
            'address' => Yii::t('app', '地址'),
            'recipients' => Yii::t('app', '收件人'),
            'detail' => Yii::t('app', '详细地址'),
            'modify_by' => Yii::t('app', '修改人'),
            'modify_time' => Yii::t('app', '修改时间'),
            'is_deleted' => Yii::t('app', '操作状态'),
        ];
    }

    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public function searchList($params = [])
    {
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_DESC
        );
        $query = self::find();
        //筛选没有删除is_deleted=1 显示
        $query->where('is_deleted = :is_deleted', [':is_deleted' => '1']);

        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();

        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'data' => self::platformDropdown(),
                'search' => '='
            ],
            [
                'name' => 'type',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => self::getStatusList(),
                'htmlOptions' => [],
                'search' => 'LIKE'
            ],
            [
                'name' => 'account_type',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => self::getAccountTypeList(),
                'htmlOptions' => [],
                'search' => 'LIKE'
            ],
            [
                'name' => 'country',
                'type' => 'text',
                'search' => 'LIKE',
            ],
            [
                'name' => 'state',
                'type' => 'text',
                'search' => 'LIKE',
            ],
            [
                'name' => 'city',
                'type' => 'text',
                'search' => 'LIKE',
            ],
            [
                'name' => 'postal_code',
                'type' => 'text',
                'search' => 'LIKE',
            ],
            [
                'name' => 'address',
                'type' => 'text',
                'search' => 'LIKE',
            ],
            [
                'name' => 'recipients',
                'type' => 'text',
                'search' => 'LIKE',
            ]
        ];
    }

    /**
     * 返回平台下拉框数据
     */
    public static function platformDropdown()
    {
        //获取登录用户绑定的平台账号
        $platformList = UserAccount::getLoginUserPlatformAccounts();
        $platformList = array_merge(['' => '请选择', 'ALL' => '所有平台'], $platformList);
        return $platformList;
    }

    //redio参数
    public static function getStatusList($key = null)
    {

        $typeData = array(
            '1' => '账号',
            '2' => '付款邮箱',
            '3' => '地址'
        );
        return $typeData;
    }

    /**
     * 返回账号类型列表
     */
    public static function getAccountTypeList()
    {
        $accountTypeList = [
            '1' => 'GBC',
            '2' => '公司',
        ];
        return $accountTypeList;
    }

    //更改显示
    public function addition(&$models)
    {
        foreach ($models as $model) {
            isset($model->ebay_id) && $model->ebay_id = json_decode($model->ebay_id, 1);
            isset($model->payment_email) && $model->payment_email = json_decode($model->payment_email, 1);

            //类型
            switch ($model->type) {
                case '1':
                    $model->type = '账号';
                    break;
                case '2':
                    $model->type = '付款邮箱';
                    break;
                case '3':
                    $model->type = '地址';
                    break;
                default:
                    break;
            }

            //平台
            if ($model->platform_code == 'ALL') {
                $model->platform_code = '所有平台';
            }

            //账号类型
            switch ($model->account_type) {
                case '1':
                    $model->account_type = 'GBC';
                    break;
                case '2':
                    $model->account_type = '公司';
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * 检测用户ID是否在GBC列表中
     */
    public static function checkInBlackList($buyerId, $platformCode)
    {
        if (empty($buyerId)) {
            return false;
        }

        //取出全平台GBC账号类型的数据
        $allGbc = self::findOne(['platform_code' => 'ALL', 'type' => 1, 'account_type' => 1]);
       
        //取出当前平台公司的账号类型的数据
        if (!empty($platformCode)) {
            $curGbc = self::findOne(['platform_code' => $platformCode, 'type' => 1, 'account_type' => 2]);
        }
        $blackList = [];
        if (!empty($allGbc)) {
            $blackList = array_merge($blackList, json_decode($allGbc->ebay_id, true));
        }
        if (!empty($curGbc)) {
            $blackList = array_merge($blackList, json_decode($curGbc->ebay_id, true));
        } 
       
        if (in_array($buyerId, $blackList)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 获取类型数据
     * **/
    public static function getgbctype($id){
       $gbc= self::find()->select('platform_code,type,account_type')->where(['id'=>$id])->asArray()->one();
       if(!empty($gbc)){
            return $gbc;
       }else{
           return null;
       }          
    }
    
}



