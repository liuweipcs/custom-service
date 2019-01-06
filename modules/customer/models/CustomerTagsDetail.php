<?php

namespace app\modules\customer\models;
use app\components\Model;

class CustomerTagsDetail extends Model{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%customer_tags_details}}';
    }



}