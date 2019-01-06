<?php
namespace app\modules\mails\models;

use app\components\Model;

class RefundTemplate extends Model
{
    /**
     * 数据库连接
     * @return mixed
     */
    public static function getDb()
    {
        return \Yii::$app->db_warehouse;
    }

    /**
     * 表名称
     * @return string
     */
    public static function tableName()
    {
        return '{{%refund_template}}';
    }

    /**
     * 获取模板数据供下拉选择
     */
    public static function getRefundTemplateDataAsArray()
    {
        $query = self::find();
        $query->select(['`id`,`template_name`']);
        $query->from(self::tableName());
        $data =$query->asArray()->all();
        //组装并且符合下拉selct格式的结果
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['id']] = $value['template_name'];
        }
        return $result;
    }

}