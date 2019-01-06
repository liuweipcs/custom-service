<?php
namespace app\modules\mails\models;

use Yii;
use app\components\Model;

/**
 * 邮件主题与站点关联模型
 */
class InboxSubjectSite extends Model
{

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
        return '{{%inbox_subject_site}}';
    }
}