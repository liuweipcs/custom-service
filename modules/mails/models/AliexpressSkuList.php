<?php
namespace app\modules\mails\models;
use app\components\Model;
class AliexpressSkuList extends Model
{
    public static function getDb()
    {
        return \Yii::$app->db_product;
    }
    
    
    public static function tableName()
    {
        return '{{%aliexpress_sku_list}}';
    }
}    