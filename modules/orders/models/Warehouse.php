<?php

namespace app\modules\orders\models;

use app\modules\systems\models\ErpWarehouseApi;
use Yii;

class Warehouse extends OrderModel
{
    public $exceptionMessage = null;

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_warehouse;
    }

    /**
     * 返回当前模型的表名
     */
    public static function tableName()
    {
        return '{{%warehouse}}';
    }

    /**
     * @desc 获取异常信息
     */
    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }

    /**
     * @desc 获取所有仓库数据
     * @return multitype:|unknown
     */
    public static function getAllWarehouse()
    {
        $warehouses     = [];
        $cacheKey       = md5('cache_erp_warehouse_all');
        $cacheNamespace = 'namespace_erp_warehouse';
        //从缓存获取订单数据
        /*         if (isset(\Yii::$app->memcache) && \Yii::$app->memcache->exists($cacheKey, $cacheNamespace) &&
         !empty(\Yii::$app->memcache->get($cacheKey, $cacheNamespace)))
        {
        return \Yii::$app->memcache->get($cacheKey, $cacheNamespace);
        }   */
        //从接口获取订单数据
        $erpWarehouseApi = new ErpWarehouseApi;
        $result          = $erpWarehouseApi->getWarehouses();
        if (empty($result))
            return $warehouses;
        $warehouses = $result->warehouses;
        if (!empty($warehouses) && isset(\Yii::$app->memcache)) {
            \Yii::$app->memcache->set($cacheKey, $warehouses, $cacheNamespace);
        }
        return $warehouses;
    }

    /**
     * @desc 获取仓库列表
     * @return multitype:Ambigous <>
     */
    public static function getWarehouseList()
    {
        $list = [];
        $res  = self::getAllWarehouse();
        if (!empty($res)) {
            foreach ($res as $row) {
                $list[$row->id] = $row->warehouse_name;
            }
        }
        return $list;
    }

    /**
     * 获取客服仓库列表
     * @param bool $return
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getAllWarehouseList($return = false)
    {
        $query = self::find();
        $data  = $query->select(['id', 'warehouse_name', 'warehouse_type'])
            ->from([self::tableName()])
            ->all();
        if ($return == true) {
            foreach ($data as $val) {
                $datas[$val->id] = $val->warehouse_name;
            }
            return $datas;
        } else {
            return $data;
        }

    }

    /**
     * @author alpha
     * @desc 获取code
     * @return mixed
     */
    public static function getAllWarehouseListCode()
    {
        $query = self::find();
        $data  = $query->select(['id', 'warehouse_code'])
            ->from([self::tableName()])
            ->all();
        foreach ($data as $val) {
            $datas[$val->id] = $val->warehouse_code;
        }
        return $datas;
    }

    /**
     * 根据仓库id 获取仓库类型
     * @param $warehouse_id
     * @return mixed
     */
    public static function getWarehousetype()
    {
        $query = self::find();
        $data  = $query->select(['id', 'warehouse_type'])
            ->from([self::tableName()])
            ->all();
        foreach ($data as $val) {
            $datas[$val->id] = $val->warehouse_type;
        }
        return $datas;

    }

    public static function getWarehouseListAll()
    {
        $list = [];
        $res  = self::getAllWarehouseList();
        if (!empty($res)) {
            foreach ($res as $row) {
                $list[$row->id] = $row->warehouse_name;
            }
        }
        return $list;
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @desc 获取发货仓库
     **/
    public static function getSendWarehouse($id)
    {
        $query     = self::find();
        $warehouse = $query->select(['warehouse_name'])
            ->from([self::tableName()])
            ->where(['id' => $id])
            ->one();

        return $warehouse['warehouse_name'];
    }


}