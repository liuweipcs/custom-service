<?php
/**
 * @desc accounts module
 * @author Fun
 */
namespace app\modules\mails\models;
use app\components\Model;
class MailsModule extends Model
{
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\Module::init()
     */
    public static function getDb()
    {
        return \Yii::$app->db;
    }
}