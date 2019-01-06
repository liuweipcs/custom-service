<?php

namespace app\modules\aftersales\models;

use Yii;
use yii\db\Query;
use app\modules\aftersales\models\RefundCode;

class AfterRefundCode extends AfterSalesModel {

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb() {
        return Yii::$app->db;
    }

    /**
     * 退货编码关联表
     * @return string
     */
    public static function tableName() {

        return '{{%after_refund_code}}';
    }

    /*     * *
     * 生产退货编码
     * * */

    public static function GetRefundcode() {
        $refundcode = RefundCode::find()->select('code')->where(['is_use' => 0])->asArray()->one();
        if($refundcode['code']=="999999"){
            RefundCode::updateAll(['is_use' => 0],['<', 'id', 999998]);
        }elseif($refundcode['code']=="000001"){
             RefundCode::updateAll(['is_use' => 0],['id'=>999999]);
        }
         return $refundcode['code'];
    }

}
