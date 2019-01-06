<?php

namespace app\modules\orders\models;

use Yii;
use app\components\Model;

class OrderNodeKefu extends Model
{
    //订单生成
    CONST ORDER_GENERATION = 1;
    //付款时间
    CONST ORDER_PAYMENT = 5;
    //订单检查
    CONST ORDER_CHECKING = 10;
    //异常
    CONST ABNORMAL = 15;
    //正常
    CONST NORMAL = 18;
    //推送到仓库
    CONST PUSH_TO_WAREHOUSE = 20;
    //订单已配货
    CONST ORDER_PICKING = 25;
    //订单缺货
    CONST ORDER_SHORTAGE = 30;
    //上传到物流商（申请面单、申请追踪号）
    CONST APPLICATION_SHEET = 35;
    //同步物流单号（发货通知）
    //CONST CONSIGNMENT_NOTE = 40;
    //仓库拉取订单
    CONST WAREHOUSE_PULL_ORDER = 45;
    //仓库拣货
    //CONST WAREHOUSE_PICKING = 50;
    //仓库扫描打包
    CONST WAREHOUSE_SCANNING_PACKAGE = 55;
    //仓库扫描出库
    CONST WAREHOUSE_SCANNING = 60;
    //物流商发货
    CONST FORWARDER_DELIVERY = 65;
    //客户签收
    CONST CUSTOMER_RECEIPT = 70;

    /**
     * 返回当前模型连接的数据库
     */
    public static function getDb()
    {
        return Yii::$app->db_order;
    }

    /**
     * 返回当前模型的表名
     */
    public static function tableName()
    {
        return '{{%order_node}}';
    }

    /**
     * 获取所有节点名称
     */
    public static function getAllNodeName()
    {
        $nodeNameData = array(
            self::ORDER_GENERATION => '订单生成',
            self::ORDER_PAYMENT => '付款时间',
            self::ORDER_CHECKING => '订单检查',
            self::ABNORMAL => '异常',
            self::PUSH_TO_WAREHOUSE => '推送到仓库',
            self::ORDER_PICKING => '订单已配货',
            self::ORDER_SHORTAGE => '订单缺货',
            self::APPLICATION_SHEET => '上传到物流商',
            //self::CONSIGNMENT_NOTE => '同步物流单号',
            self::WAREHOUSE_PULL_ORDER => '仓库拉取订单',
            //self::WAREHOUSE_PICKING => '仓库拣货',
            self::WAREHOUSE_SCANNING_PACKAGE => '仓库扫描打包',
            self::WAREHOUSE_SCANNING => '仓库扫描出库',
            self::FORWARDER_DELIVERY => '物流商发货',
            self::CUSTOMER_RECEIPT => '客户签收',
        );
        return $nodeNameData;
    }

    /**
     * 获取订单节点
     */
    public static function GetOrderNode($orderId = '', $fields = '*')
    {
        $reslut = self::find()
            ->select($fields)
            ->from(self::tableName())
            ->where(['order_id' => $orderId])
            ->orderBy('node_sort ASC')
            ->asArray()
            ->all();

        $data = array();
        if (!empty($reslut)) {
            foreach ($reslut as $value) {
                $data[$value['node_sort']] = $value;
            }
        }
        //如果订单已配货，过滤掉缺货
        if (!empty($data[self::ORDER_PICKING])) {
            unset($data[self::ORDER_SHORTAGE]);
        }
        //如果仓库已拉取，过滤掉异常
        if (!empty($data[self::PUSH_TO_WAREHOUSE])) {
            unset($data[self::ABNORMAL]);
        }
        return $data;
    }

    /**
     * 获取节点名称
     */
    public static function getNnodeName($nodeSort = '')
    {
        $nodeNameData = self::getAllNodeName();
        if ($nodeSort) {
            return $nodeNameData[$nodeSort];
        } else {
            return $nodeNameData;
        }
    }
}