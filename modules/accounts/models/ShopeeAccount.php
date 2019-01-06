<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/27 0027
 * Time: 17:32
 */

namespace app\modules\accounts\models;



class ShopeeAccount  extends ErpAccountModel
{

    public static function tableName() {
        return '{{%shopee_account}}';
    }

    public function getAllAccounts(){
        return \Yii::app()->db->createCommand()
            ->select('*')
            ->from(self::tableName())
            ->where('status=1 and user_id!=1801')
            ->queryAll();
    }
}