<?php
/**
 * @desc ERP aliexpress account model
 */
namespace app\modules\services\modules\account\models;
class AliexpressAccount extends AccountModel
{
    /**
     * @desc set table name
     * @return string
     */
    public static function tableName()
    {
        return '{{%aliexpress_account_qimen}}';
    }
}