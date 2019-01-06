<?php

namespace app\modules\mails\models;

use Yii;
use app\components\Model;

/**
 * ebay新接口访问token表
 */
class EbayNewApiToken extends Model
{
    public static function tableName()
    {
        return '{{%ebay_new_api_token}}';
    }

    public static function getDb()
    {
        return Yii::$app->db_system;
    }

    /**
     * 获取token
     */
    public static function getToken($accountId)
    {
        return self::find()->select('token')->where(['account_id' => $accountId])->limit(1)->scalar();
    }
}