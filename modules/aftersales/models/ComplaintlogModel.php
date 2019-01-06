<?php

namespace app\modules\aftersales\models;

use app\components\Model;


class ComplaintlogModel extends Model {

    public static function getDb() {
        return \Yii::$app->db;
    }

    /**
     * @desc 设置表名
     * @return string
     */
    public static function tableName() {
        return '{{%warehouse_customer_complaint_log}}';
    }

  

}
