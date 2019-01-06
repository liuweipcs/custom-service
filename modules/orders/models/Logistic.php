<?php
namespace app\modules\orders\models;
use app\modules\systems\models\ErpLogisticApi;
use Yii;
class Logistic extends OrderModel
{
    public $exceptionMessage = null;
    public static $onUse = 1;
    /**
     * @desc 获取异常信息
     */
    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }

    /**

     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_logistics;
    }

    /**

    /**
     * 返回当前模型的表名
     */
    public static function tableName()
    {
        return '{{%logistics}}';
    }
    /**
     * @desc 获取所有仓库数据
     * @return multitype:|unknown
     */
    public static function getWarehouseLogistics($warehouseId)
    {
        $logistics = [];
        $cacheKey = md5('cache_erp_logistics_' . $warehouseId);
        $cacheNamespace = 'namespace_erp_logistic';
        //从缓存获取订单数据
        /*         if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
         !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
        {
        return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
        }   */
        //从接口获取订单数据
        $erpLogisticApi = new ErpLogisticApi;
        $result = $erpLogisticApi->getWarehouseLogistics($warehouseId);
        if (empty($result))
            return $logistics;
        $logistics = $result->logistics;
        if (!empty($warehouses) && isset(\Yii::$app->memcache)){
            \Yii::$app->memcache->set($cacheKey, $logistics, $cacheNamespace);
        }
        return $logistics;
    }

    /**
     * @desc 获取所有邮寄方式数据
     * @return multitype:|unknown
     */
    public static function getAllLogistics()
    {
        $logistics = [];
        $cacheKey = md5('cache_erp_logistics_all');
        $cacheNamespace = 'namespace_erp_logistic';
        //从缓存获取订单数据
        /*         if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
         !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
        {
        return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
        }   */
        //从接口获取订单数据
        $erpLogisticApi = new ErpLogisticApi;
        $result = $erpLogisticApi->getAllLogistics();
        if (empty($result))
            return $logistics;
        $logistics = $result->logistics;
        if (!empty($warehouses) && isset(\Yii::$app->memcache)){
            \Yii::$app->memcache->set($cacheKey, $logistics, $cacheNamespace);
        }
        return $logistics;
    }

    /**
     * 获取所有的物流，包括停止使用的
     */
    public static function getAllStatusLogistics()
    {
        $erpLogisticApi = new ErpLogisticApi;
        $result = $erpLogisticApi->getAllStatusLogistics();
        if (empty($result)) {
            return [];
        }
        $logistics = $result->logistics;
        return $logistics;
    }

    /**
     * @desc 获取所有客户选择的运输方式
     * @return multitype:|unknown
     */
    public static function getBuyerOptionLogistics($platform_code)
    {
        $logistics = [];
        $cacheKey = md5('cache_erp_logistics_buyer_option'.$platform_code);
        $cacheNamespace = 'namespace_erp_logistic_buyer_option';
        //从缓存获取订单数据
        /*         if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
         !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
        {
        return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
        }   */
        //从接口获取订单数据
        $erpLogisticApi = new ErpLogisticApi;
        $result = $erpLogisticApi->getBuyerOptionLogistics($platform_code);
        if (empty($result))
            return $logistics;
        $logistics = $result->logistics;
        if (!empty($warehouses) && isset(\Yii::$app->memcache)){
            \Yii::$app->memcache->set($cacheKey, $logistics, $cacheNamespace);
        }
        return $logistics;
    }


    /**
     *获取所有出货方式
     */
    public static function getLogisArrCodeName(){
        $res = array();
        $query = new \yii\db\Query();
        $logisticsArr = $query->from(self::tableName())
            ->select('ship_name,ship_code')
            ->where("use_status = :use_status",array(':use_status' => SELF::$onUse))
            ->createCommand(Yii::$app->db_logistics)
            ->queryAll();
        foreach($logisticsArr as $logistics){
            $res[$logistics['ship_code']] = $logistics['ship_name'];

        }

        return $res;
    }

    /**
     * @desc 获取发货方式
     **/
    public static function getSendGoodsWay($code)
    {
        $query = self::find();
        $ship = $query->select(['ship_name'])
            ->from([self::tableName()])
            ->where(['ship_code'=>$code])
            ->one();

        return $ship['ship_name'];
    }

    /**
     * @desc 获取发货方式
     * 英文名称
     **/
    public static function getSendWayEng($code)
    {
        $query = self::find();
        $ship = $query->select(['ship_ename'])
            ->from([self::tableName()])
            ->where(['ship_code'=>$code])
            ->asArray()
            ->one();

        return $ship['ship_ename'];
    }

    /**
     * 获取仓库下所有的物流方式
     */
    public static function getLogistics($warehouseId)
    {
        $query = self::find();
        $ship = $query->select(['*'])
            ->from([self::tableName()])
            ->where("ship_warehouse LIKE '%{$warehouseId}%' and use_status = 1")
            ->orderBy(['ship_name' => SORT_ASC])
            ->all();
        $list = array();
        if (!empty($ship)){
            foreach ($ship as $value){
                $ship_warehouse  = explode(',',$value['ship_warehouse']);
                if(in_array($warehouseId,$ship_warehouse)){
                    $list[] = $value;
                }
            }
        }
        return $list;
    }


}