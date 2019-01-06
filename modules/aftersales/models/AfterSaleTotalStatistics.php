<?php

namespace app\modules\aftersales\models;

use Yii;

/**
 * This is the model class for table "{{%after_sales_statistics}}".
 *
 * @property integer $id
 * @property string $platform_code
 * @property integer $account_id
 * @property string $s_data
 * @property string $s_year
 * @property string $price
 */
class AfterSaleTotalStatistics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%after_sales_statistics}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id'], 'integer'],
            [['price'], 'number'],
            [['platform_code'], 'string', 'max' => 50],
            [['s_data'], 'string', 'max' => 10],
            [['s_year'], 'string', 'max' => 5],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_code' => 'Platform Code',
            'account_id' => 'Account ID',
            's_data' => 'S Data',
            's_year' => 'S Year',
            'price' => 'Price',
        ];
    }
    
    /**
     * 获取每个平台每月总销售额数据
     * @return type
     * @author allen <2018-08-04>
     */
    public static function getData(){
        $arr = [];
        $data = self::find()->asArray()->all();
        if(!empty($data)){
            foreach ($data as $value) {
                $arr[$value['platform_code']][$value['s_data']] = $value['price'];
            }
        }
        return $arr;
    }
}
