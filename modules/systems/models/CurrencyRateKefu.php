<?php

namespace app\modules\systems\models;

use Yii;
use app\components\Model;

class CurrencyRateKefu extends Model
{
    const RATE_TYPE_BASE = 'base';
    const CNY = 'CNY';

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_system;
    }

    /**
     * 返回模型的表名
     */
    public static function tableName()
    {
        return '{{%currency_rate}}';
    }

    /**
     * 获取原货币与目标货币对应的汇率
     */
    public static function getRateByCondition($fromCurrencyCode, $toCurrencyCode, $rateType = self::RATE_TYPE_BASE, $date = '')
    {
        if ($fromCurrencyCode == $toCurrencyCode) {
            return 1;
        }

        $query = self::find()
            ->select('rate')
            ->where(['type' => $rateType])
            ->andWhere(['from_currency_code' => $fromCurrencyCode])
            ->andWhere(['to_currency_code' => $toCurrencyCode])
            ->orderBy('id desc');

        if (!empty($date)) {
            $query->andWhere(['rate_month' => $date]);
        }

        $info = $query->asArray()->one();
        if (empty($info)) {
            return false;
        }
        return $info['rate'];
    }
    /**
     * 获取原货币对应人民币汇率集合
     */
    public static function  gerRateConditionAll($toCurrencyCode = 'CNY',$rateMonth = '')
    {
        if(empty($rateMonth)){
            $rateMonth = date('Ym');
        }
        $query = self::find()
            ->select(['from_currency_code','rate'])
            ->where(['type' => self::RATE_TYPE_BASE])
            ->andWhere(['to_currency_code' => $toCurrencyCode])
            ->andWhere(['rate_month' => $rateMonth])
            ->orderBy('id desc');

        $info = $query->asArray()->all();
        if (empty($info)) {
            return false;
        }
        $res = [];
        foreach($info as $k => $v){
            $res[$v['from_currency_code']] = $v['rate'];
        }
        return $res;
    }

    /**
     * 获取 code->currency_name形式的货币列表信息
     * $currencyCode:获取指定货币Code
     */
    public static function getCurrencyList($currencyCode = '')
    {
        $query = self::find()
            ->select(['code','currency_name'])
            ->from('{{%currency}}');

        $info = $query->asArray()->all();
        $currencyArr = [];
        foreach($info as $val){
            $currencyArr[$val['code']] = $val['code'].'-'.$val['currency_name'];
        }

        if(isset($currencyCode) && !empty($currencyCode)){
            return $currencyArr[$currencyCode];
        }
        return $currencyArr;
    }
}
