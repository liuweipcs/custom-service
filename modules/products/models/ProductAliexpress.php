<?php

namespace app\modules\products\models;

use app\components\Model;

class ProductAliexpress extends ProductsModel
{	
    public static function tableName()
    {
        return '{{%product_aliexpress_list}}'; 
    }



}