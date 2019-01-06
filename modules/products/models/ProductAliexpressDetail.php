<?php

namespace app\modules\products\models;

use app\components\Model;

class ProductAliexpressDetail extends ProductsModel
{	
    public static function tableName()
    {
        return '{{%product_aliexpress_list_detail}}'; 
    }



}