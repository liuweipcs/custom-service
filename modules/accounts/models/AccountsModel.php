<?php
/**
 * @desc accounts model base class
 * @author Fun
 */
namespace app\modules\accounts\models;
use app\components\Model;
class AccountsModel extends Model
{
    /**
     * @desc set db components
     */
    public static function getDb()
    {
        return \Yii::$app->db;
    }
    public function accountList(){
        return self::find()->select('id,access_token,app_key,secret_key')->where(['status'=>1])->asArray()->all();
    }
}