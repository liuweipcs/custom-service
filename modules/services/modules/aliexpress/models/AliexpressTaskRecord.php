<?php
namespace app\modules\services\modules\aliexpress\models;

use Yii;
use app\components\Model;

/**
 * 记录速卖通已延长收货时间和催付的订单记录，用于避免下次重复发送
 */
class AliexpressTaskRecord extends Model
{
    public static function tableName()
    {
        return '{{%aliexpress_task_record}}';
    }
}