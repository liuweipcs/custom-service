<?php

namespace app\modules\aftersales\models;

use Yii;
use app\components\Model;
class ComplaintskuModel extends Model {

    public static function getDb() {
        return \Yii::$app->db;
    }

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName() {
        return '{{%complaintware_sku}}';
    }

   

}
