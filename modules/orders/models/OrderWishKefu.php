<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderWishKefu extends Model
{
    public $orderdetail = 'order_wish_detail';
    public $orderdetailcopy = 'order_wish_detail_copy';
    public $ordermain = 'order_wish';
    public $ordermaincopy = 'order_wish_copy';
    public $ordernote = 'order_wish_note';
    public $ordertransaction = 'order_wish_transaction';
    public $ordertransactioncopy = 'order_wish_transaction_copy';

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    public static function tableName()
    {
        return '{{%order_wish}}';
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
                        ->join('LEFT JOIN', '{{%order_wish_detail}} t1', 't.order_id = t1.order_id')
                        ->andWhere(['t.platform_order_id' => $platform_order_id])
                        ->asArray()->one();
        if (!empty($site_arr)) {
            return $site_arr['site'];
        } else {
            unset($query);
            $site_arr = self::find()->select(['t1.site'])
                            ->from('{{%order_wish_copy}} t')
                            ->join('LEFT JOIN', '{{%order_wish_detail_copy}} t1', 't.order_id = t1.order_id')
                            ->andWhere(['t.platform_order_id' => $platform_order_id])
                            ->asArray()->one();
            if (!empty($site_arr)) {
                return $site_arr['site'];
            }
        }
    }
    
    
    
}