<?php

namespace app\modules\systems\models;

use Yii;
use app\modules\accounts\models\Platform;
use yii\helpers\Html;

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
class Email extends SystemsModel
{
    const STATUS_FORBIDDEN = 0; //false
    const STATUS_START = 1; //true

    //验证163邮箱，防止获取邮件被阻止
    const VERIFY_163_EMAIL_URL = 'http://config.mail.163.com/settings/imap/index.jsp?uid=';
    //验证126邮箱，防止获取邮件被阻止
    const VERIFY_126_EMAIL_URL = 'http://config.mail.126.com/settings/imap/index.jsp?uid=';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%email_set}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['emailaddress', 'unique'],
            [['platform_code', 'emailaddress', 'imap_server', 'smtp_server', 'imap_port', 'smtp_port'], 'required'],
            [['imap_port', 'smtp_port','is_encrypt', 'ssl', 'is_amazon_send'], 'integer'],
            [['create_time', 'modify_time'], 'safe'],
            [['emailaddress', 'imap_server', 'smtp_server', 'imap_protocol', 'smtp_protocol', 'accesskey', 'user_name', 'password'], 'string', 'max' => 255],
            [['platform_code', 'create_by', 'modify_by'], 'string', 'max' => 50],
            [['encryption'],'string','max' => 10],
            ['filter_option', 'string', 'max' => 500],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'platform_code' => '平台',
            'emailaddress' => '邮箱地址',
            'imap_server' => 'imap_server',
            'smtp_server' => 'smtp_server',
            'imap_protocol' => 'imap_protocol',
            'smtp_protocol' => 'smtp_protocol',
            'imap_port' => 'imap_port端口',
            'smtp_port' => 'smtp_port端口',
            'accesskey' => '授权码',
            'modify_time' => '修改时间',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_time' => '修改时间',
            'modify_by' => '修改人',
            'filter_option' => '过滤邮箱',
            'verify_email' => '验证邮箱',
            'is_encrypt' => '是否加密',
            'encryption' => '加密方式',
            'password' => '密码',
            'is_amazon_send' => '是否亚马逊邮件服务器发送'
        ];
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\db\ActiveRecord::attributes()
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extraAttributes = [
            'verify_email',
        ];
        return array_merge($attributes, $extraAttributes);
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
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 修改模型数据
     */
    public function addition(&$models)
    {
        foreach ($models as $model) {
            if (strpos($model->emailaddress, '163.com') !== false) {
                $model->setAttribute('verify_email', Html::a('验证邮箱', Email::VERIFY_163_EMAIL_URL . $model->emailaddress));
            } else if (strpos($model->emailaddress, '126.com') !== false) {
                $model->setAttribute('verify_email', Html::a('验证邮箱', Email::VERIFY_126_EMAIL_URL . $model->emailaddress));
            } else {
                $model->setAttribute('verify_email', '');
            }
        }
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
                'name' => 'emailaddress',
                'type' => 'text',
                'search' => '=',
            ],
        ];
    }

    /**
     * 返回平台下拉框数据
     */
    public static function platformDropdown()
    {
        return Platform::getPlatformAsArray();
    }

    /** 获取所有邮箱 **/
    public static function getList()
    {
        $list = self::find()
            ->select('*')
            ->asArray()
            ->all();
        return $list;
    }

    /** 获取邮箱的过滤文件夹 **/
    public static function getFilterOption($email)
    {
        $list = self::find()
            ->select('filter_option')
            ->asArray()
            ->where('emailaddress=:email')
            ->addParams([':email' => $email])
            ->one();
        $data = explode(',', $list['filter_option']);
        return $data;
    }

    /**获取ssl状态信息 **/
    public static function getStatusList($key = null)
    {
        $list = [
            self::STATUS_FORBIDDEN => 'false',
            self::STATUS_START => 'true',
        ];
        if (!is_null($key)) {
            if (array_key_exists($key, $list))
                return $list[$key];
            else
                return '';
        }
        return $list;
    }
}
