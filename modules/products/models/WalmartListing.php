<?php
/**
 * Created by PhpStorm.
 * User: alpha
 * Date: 18-8-30
 * Time: 下午4:25
 */

namespace app\modules\products\models;


class WalmartListing extends ProductsModel
{
    public static function tableName()
    {
        return '{{%walmart_listing}}';
    }
    
    /**
     * 根据账号ID  销售sku查询对应的数据
     * @param type $accountId
     * @param type $sellerSku
     * @return type
     * @author allen <2018-10-18>
     */
    public static function getListingInfo($accountId,$sellerSku){
        return self::find()->where(['account_id' => $accountId,'seller_sku' => $sellerSku])->asArray()->one();
    }

}