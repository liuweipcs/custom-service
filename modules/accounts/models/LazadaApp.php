<?php

namespace app\modules\accounts\models;

class LazadaApp  extends ErpAccountModel
{

    public static function tableName() {
        return '{{%lazada_app}}';
    }
}