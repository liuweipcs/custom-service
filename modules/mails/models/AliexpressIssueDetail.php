<?php

namespace app\modules\mails\models;

use Yii;
use app\components\Model;

/**
 * 速卖通纠纷详情模型
 */
class AliexpressIssueDetail extends Model
{
    /**
     * 返回操作的数据库
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
        return '{{%aliexpress_issue_detail}}';
    }
}