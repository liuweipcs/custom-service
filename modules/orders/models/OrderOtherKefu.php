<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderOtherKefu extends Model
{
    public $orderdetail = "order_other_detail";
    public $orderdetailcopy = "order_other_detail_copy";
    public $ordermain = "order_other";
    public $ordermaincopy = "order_other_copy";
    public $ordernote = "order_other_note";
    public $ordertransaction = "order_other_transaction";
    public $ordertransactioncopy = "order_other_transaction_copy";

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    public static function tableName()
    {
        return '{{%order_other}}';
    }
            /**
     * @author alpha
     * @desc 返回site
     * @param $platform_order_id
     * @return mixed
     */
    public static function getSiteByPlatformId($platform_order_id) {
        $site_arr = self::find()->select(['t1.site'])
                        ->from(self::tableName() . ' t')
                        ->join('LEFT JOIN', '{{%order_other_detail}} t1', 't.order_id = t1.order_id')
                        ->andWhere(['t.platform_order_id' => $platform_order_id])
                        ->asArray()->one();
        if (!empty($site_arr)) {
            return $site_arr['site'];
        } else {
            unset($query);
            $site_arr = self::find()->select(['t1.site'])
                            ->from('{{%order_other_copy}} t')
                            ->join('LEFT JOIN', '{{%order_other_detail_copy}} t1', 't.order_id = t1.order_id')
                            ->andWhere(['t.platform_order_id' => $platform_order_id])
                            ->asArray()->one();
            if (!empty($site_arr)) {
                return $site_arr['site'];
            }
        }
    }
    
    
    
}