<?php
namespace app\modules\systems\models;
use app\components\Model;
use app\modules\systems\models\ErpSystemApi;
use YII;
class Country extends Model
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
        return '{{%country_new}}';
    }

    /**
     * @desc 获取所有国家
     */
    public static function getAllCountries()
    {
        $countries = [];
        $cacheKey = md5('cache_erp_country');
        $cacheNamespace = 'namespace_erp_country';
        //从缓存获取订单数据
        if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
            !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
        {
            return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
        }
        //从接口获取订单数据
        $erpSystemApi = new ErpSystemApi;
        $result = $erpSystemApi->getCountries();
        if (empty($result))
            return $countries;
        $countries = $result->countries;
        if (!empty($orderInfo) && isset(\Yii::$app->memcache)){
            \Yii::$app->memcache->set($cacheKey, $countries, $cacheNamespace);
        }
        return $countries;
    }

    /**
     * @desc 获取所有国家
     */
    public static function getAllCountrieList()
    {
        $countries = [];
        $cacheKey = md5('cache_erp_country');
        $cacheNamespace = 'namespace_erp_country';
        //从缓存获取订单数据
        if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
            !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
        {
            return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
        }
        //从库获取订单数据
        $query = self::find();
        $result = $query->select(['en_name','en_abbr','cn_name'])
            ->from(self::tableName())
            ->all();
        if (empty($result))
            return $countries;
        $countries = $result;
        if (!empty($orderInfo) && isset(\Yii::$app->memcache)){
            \Yii::$app->memcache->set($cacheKey, $countries, $cacheNamespace);
        }
        return $countries;
    }

    /**
     * @desc 获取国家CODE和en name键值对
     * @return multitype:Ambigous <>
     */
    public static function getCodeNamePairsList($language = 'en_name')
    {
        if($language != 'en_name')
            $language = 'cn_name';
        $list = [];
        $countries = self::getAllCountrieList();
        if (!empty($countries))
        {
            foreach($countries as $row)
                $list[$row->en_abbr] = $row->$language;
        }
        return $list;
    }
    /**
     * @desc 获取国家CODE和en name键值对
     * @return multitype:Ambigous <>
     */
    public static function getCodeNamePairs($language = 'en_name')
    {
        if($language != 'en_name')
            $language = 'cn_name';
        $list = [];
        $countries = self::getAllCountries();
        if (!empty($countries))
        {
            foreach($countries as $row)
                $list[$row->en_abbr] = $row->$language;
        }
        return $list;
    }

    /**
     * @desc 获取异常信息
     */
    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }
}