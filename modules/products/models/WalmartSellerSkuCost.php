<?php
/**
 * Created by PhpStorm.
 * User: alpha
 * Date: 18-8-30
 * Time: 下午4:25
 */

namespace app\modules\products\models;


class WalmartSellerSkuCost extends ProductsModel
{
    public static function tableName()
    {
        return '{{%walmart_sellersku_cost}}';
    }

}