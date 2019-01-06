<?php
namespace app\modules\systems\models;

use Yii;
use app\components\Model;
use app\modules\accounts\models\Platform;
use yii\data\Sort;

class MailFilterRule extends Model
{
    //发件人包含
    const RULE_TYPE_SEND_CONTAIN = 1;
    //发件人不包含
    const RULE_TYPE_SEND_NOT_CONTAIN = 2;
    //收件人包含
    const RULE_TYPE_RECEIVE_CONTAIN = 3;
    //收件人不包含
    const RULE_TYPE_RECEIVE_NOT_CONTAIN = 4;
    //主题包含
    const RULE_TYPE_SUBJECT_CONTAIN = 5;
    //主题不包含
    const RULE_TYPE_SUBJECT_NOT_CONTAIN = 6;
    //正文包含
    const RULE_TYPE_BODY_CONTAIN = 7;
    //正文不包含
    const RULE_TYPE_BODY_NOT_CONTAIN = 8;

    //发件人等于
    const RULE_TYPE_SEND_EQUAL = 9;

    /**
     * 返回操作数据库
     */
    public static function getDb()
    {
        return Yii::$app->db;
    }

    /**
     * 返回操作的表名
     */
    public static function tableName()
    {
        return '{{%mail_filter_rule}}';
    }

    /**
     * 设置规则
     */
    public function rules()
    {
        return [
            [['manage_id', 'type', 'value'], 'required'],
            [['manage_id', 'type', 'status'], 'integer'],
            [['value'], 'string'],
        ];
    }

    /**
     * 返回规则类型
     */
    public static function getRuleTypeList()
    {
        return [
            '1' => '发件人包含',
            '2' => '发件人不包含',
            '3' => '收件人包含',
            '4' => '收件人不包含',
            '5' => '主题包含',
            '6' => '主题不包含',
            '7' => '正文包含',
            '8' => '正文不包含',
            '9' => '发件人等于',
        ];
    }

    /**
     * 返回邮件过滤器规则列表
     */
    public static function getManageRuleList($manageId)
    {
        return self::find()
            ->select('id, type, value')
            ->where(['manage_id' => $manageId, 'status' => 1])
            ->asArray()
            ->all();
    }
}