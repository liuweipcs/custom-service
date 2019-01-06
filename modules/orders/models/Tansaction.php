<?php
namespace app\modules\orders\models;
use Yii;
use app\modules\accounts\models\Platform;
class Tansaction extends OrderModel
{

    public $exceptionMessage = null;

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    /**
     * 返回当前模型的表名
     */
    public static function tableName()
    {
        return '{{%order_transaction}}';
    }
    /**
     * @desc 获取异常信息
     */
    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }

    /**
     * @desc 获取ebay交易记录
     **/
    public static function getOrderTransactionEbayByOrderId($orderId,$platform_code){
        $list = self::find()
            ->select('*')
            ->from('{{%order_ebay_transaction}}')
            ->where(['order_id' => $orderId,'platform_code' => $platform_code])
            ->asArray()
            ->all();
        if(!$list){
            $list1 = self::find()
                ->select('*')
                ->from('{{%order_ebay_transaction_copy}}')
                ->where(['order_id' => $orderId,'platform_code' => $platform_code])
                ->asArray()
                ->all();
            return $list1;
        }
        return $list;
    }

    /**
     * @desc 获取ebay,cd,wish,lazada,shopee交易记录id
     **/

    public static function getOrderTransactionIdEbayByOrderId($orderId,$platform_code){

        $query = self::find()
            ->select('transaction_id,amt');
        if($platform_code == "EB"){
            $query->from('{{%order_ebay_transaction}}');
        }else{
            $query->from('{{%order_other_transaction}}');
        }
        $list = $query->where(['order_id' => $orderId,'platform_code' => $platform_code])
            ->andWhere(['>','amt',0])
            ->orderBy('modify_time DESC')
            ->asArray()
            ->one();

        if(!$list){
            $query1 = self::find()
                ->select('transaction_id,amt');
            if($platform_code == 'EB'){
                $query1->from('{{%order_ebay_transaction_copy}}');
            }else{
                $query1->from('{{%order_other_transaction_copy}}');
            }
            $list1 = $query1->where(['order_id' => $orderId,'platform_code' => $platform_code])
                ->andWhere(['>','amt',0])
                ->asArray()
                ->one();
            return $list1;
        }
        return $list;
    }

    /**
     * @desc 获取wish交易记录
     **/
    public static function getOrderTransactionWishByOrderId($orderId,$platform_code){
        $list = self::find()
            ->select('*')
            ->from('{{%order_wish_transaction}}')
            ->where(['order_id' => $orderId,'platform_code' => $platform_code])
            ->asArray()
            ->All();
        if(!$list){
            $list1 = self::find()
                ->select('*')
                ->from('{{%order_wish_transaction_copy}}')
                ->where(['order_id' => $orderId,'platform_code' => $platform_code])
                ->asArray()
                ->All();
            return $list1;
        }
        return $list;
    }

    /**
     * @desc 获取Ali交易记录
     **/
    public static function getOrderTransactionAliByOrderId($orderId,$platform_code){
        $list = self::find()
            ->select('*')
            ->from(self::tableName())
            ->where(['order_id' => $orderId,'platform_code' => $platform_code])
            ->asArray()
            ->All();
        if(!$list){
            $list1 = self::find()
                ->select('*')
                ->from('{{%order_aliexpress_transaction_copy}}')
                ->where(['order_id' => $orderId,'platform_code' => $platform_code])
                ->asArray()
                ->All();
            return $list1;
        }
        return $list;
    }
    /**
     * @desc 获取other交易记录
     **/
    public static function getOrderTransactionOtherByOrderId($orderId,$platform_code){
        $list = self::find()
            ->select('*')
            ->from('{{%order_other_transaction}}')
            ->where(['order_id' => $orderId,'platform_code' => $platform_code])
            ->asArray()
            ->All();
        if(!$list){
            $list1 = self::find()
                ->select('*')
                ->from('{{%order_other_transaction_copy}}')
                ->where(['order_id' => $orderId,'platform_code' => $platform_code])
                ->asArray()
                ->All();
            return $list1;
        }
        return $list;
    }
}