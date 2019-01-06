<?php

namespace app\modules\accounts\models;

class LazadaAccount  extends ErpAccountModel
{

    public static function tableName() {
        return '{{%lazada_account}}';
    }
}