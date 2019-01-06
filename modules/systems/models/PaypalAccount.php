<?php
namespace app\modules\systems\models;
use app\components\Model;
use app\modules\systems\models\ErpSystemApi;
use Yii;
class PaypalAccount extends Model
{
    public $exceptionMessage = null;

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_system;
    }

    /**
     * 返回当前模型的表名
     */
    public static function tableName()
    {
        return '{{%paypal_account}}';
    }

    /**
     * 获取所以paypal账号
     *
     */
    public static function getPaypleEmail()
    {
        $query = self::find();
        $data  = $query->select(['email'])
            ->from([self::tableName()])
            ->where(['status' => 1])
            ->asArray()
            ->all();
        $info = [];
        foreach ($data as $k => $item){
            $info[$k] = $item['email'];
        }
        return $info;
    }

    /**
     * 获取所以paypal账号
     *
     */
    public static function getPaypleEmails()
    {
        $query = self::find();
        $data  = $query->select(['email'])
            ->from([self::tableName()])
            ->where(['status' => 1])
            ->asArray()
            ->all();
        $info = [];
        foreach ($data as $item){
            $info[$item['email']] = $item['email'];
        }
        return $info;
    }
}
