<?php

namespace app\modules\services\modules\paypal\models;

use app\components\Model;

class PayPalAccount extends PaypalModel
{	
    public static function tableName()
    {
        return '{{%paypal_account}}'; 
    }



}