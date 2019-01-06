<?php

namespace app\modules\accounts\models;

class MallAccount extends ErpAccountModel
{
    public static function tableName()
    {
        return '{{%mall_account}}';
    }
}