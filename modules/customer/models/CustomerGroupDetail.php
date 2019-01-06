<?php

namespace app\modules\customer\models;
use app\components\Model;

class CustomerGroupDetail extends Model{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%customer_group_detail}}';
    }



}