<?php

namespace app\modules\users\models;

use Yii;
use app\components\Model;

class OrderserviceKefu extends Model
{
    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_system;
    }

    /**
     * 返回当前模型的表名
     */
    public static function tableName()
    {
        return '{{%orderservice}}';
    }

    /**
     * 查询数据库中符合要求的用户use_id
     */
    public static function getAllCheckUserId($platform, $accountId)
    {
        $arr = array();
        $result = self::find()
            ->select('user_id')
            ->from(self::tableName())
            ->distinct()
            ->where('platform_code=:platform AND FIND_IN_SET(:accountId, account_ids)', [':platform' => $platform, ':accountId' => $accountId])
            ->asArray()
            ->all();

        if (!empty($result)) {
            foreach ($result as $k => $v) {
                $arr[] = $v['user_id'];
            }
        }
        return $arr;
    }
}