<?php

namespace app\modules\products\models;

class Product extends ProductsModel
{
    //刚开发
    const STATUS_NEWLY_DEVELOPED = 1;
    //审核不通过
    const STATUS_CANCEL_PRODUCT = 0;
    //编辑中
    const STATUS_EDITING = 2;
    //预上线
    const STATUS_PRE_ONLINE = 3;
    //在售中
    const STATUS_ON_SALE = 4;
    //已滞销
    const STATUS_HAS_UNSALABLE = 5;
    //待清仓
    const STATUS_WAIT_CLEARANCE = 6;
    //已停售
    const STATUS_STOP_SELLING = 7;
    //待买样
    const STATUS_BUY_SAMPLE = 8;
    //待品检
    const STATUS_SEIZED_GOODS = 9;
    //拍摄中
    const STATUS_SHOOTING = 10;
    //产品信息确认
    const STATUS_CHECK_STATUS = 11;
    //修图中
    const STATUS_RETOUCHING = 12;
    //设计审核中
    const STATUS_SHOOT_CHECK = 14;
    //文案审核中
    const STATUS_EDITING_FINAL = 15;
    //文案主管终审中
    const STATUS_EDITING_LEADER = 16;
    //试卖编辑中
    const STATUS_TRY_EDITING = 17;
    //试卖在售中
    const STATUS_TRY_SELL = 18;
    //试卖文案终审中
    const STATUS_TRY_EDITING_FINAL = 19;
    //预上线拍摄中
    const STATUS_SHOOTING_ONLINE = 20;
    //物流审核中
    const STATUS_LOGISTICS_CHECK = 21;
    //缺货中
    const STATUS_SHORT_SUPPLY = 22;

    public static function tableName()
    {
        return '{{%product}}';
    }

    /**
     * 获取产品状态
     */
    public static function getProductStatus($key)
    {
        $productStatus = [
            self::STATUS_NEWLY_DEVELOPED   => '刚开发',
            self::STATUS_BUY_SAMPLE        => '待买样',
            self::STATUS_SEIZED_GOODS      => '待品检',
            self::STATUS_SHOOTING          => '拍摄中',
            self::STATUS_RETOUCHING        => '修图中',
            self::STATUS_EDITING           => '编辑中',
            self::STATUS_PRE_ONLINE        => '预上线',
            self::STATUS_ON_SALE           => '在售中',
            self::STATUS_HAS_UNSALABLE     => '已滞销',
            self::STATUS_WAIT_CLEARANCE    => '待清仓',
            self::STATUS_STOP_SELLING      => '已停售',
            self::STATUS_CANCEL_PRODUCT    => '审核不通过',
            self::STATUS_CHECK_STATUS      => '产品信息确认',
            self::STATUS_SHOOT_CHECK       => '设计审核中',
            self::STATUS_EDITING_FINAL     => '文案审核中',
            self::STATUS_EDITING_LEADER    => '文案主管终审中',
            self::STATUS_TRY_EDITING       => '试卖编辑中',
            self::STATUS_TRY_SELL          => '试卖在售中',
            self::STATUS_TRY_EDITING_FINAL => '试卖文案终审中',
            self::STATUS_SHOOTING_ONLINE   => '预上线拍摄中',
            self::STATUS_LOGISTICS_CHECK   => '物流审核中',
        ];

        if (is_numeric($key)) {
            return array_key_exists($key, $productStatus) ? $productStatus[$key] : '';
        } else {
            return $productStatus;
        }
    }

    public static function getStatusValueBySku($sku)
    {
        $query   = self::find();
        $product = $query->select('product_status')->where(['sku' => $sku])->asArray()->one();
        return $product['product_status'];
    }

    /**
     * 获取产品线
     * @param $sku
     * @return string
     */
    public static function getLineListName($sku)
    {
        $id = self::find()->alias('p')
            ->select('t.id,t.linelist_cn_name,t.linelist_parent_id')
            ->leftJoin('{{%product_linelist}} t', 'p.product_linelist_id=t.id')
            ->where(['p.sku' => $sku])->asArray()->one();
        $id = $id['id'];
        $linest = ProductLineList::getLineList();
        $one    = isset($linest[$id]) ? $linest[$id] : 0;
        if (!empty($one)) {
            $parent = $one;
            if ($parent['linelist_parent_id'] != 0) {
                $parent = $linest[$one['linelist_parent_id']];
                while ($parent['linelist_parent_id'] > 0) {
                    $parent = $linest[$parent['linelist_parent_id']];
                }
            }
            return !empty($parent) ? $parent['linelist_cn_name'] : '';
        }
        return '';
    }

    /**
     * 根据sku获取产品线
     * @param $sku
     * @return mixed|string
     */
    public static function getLineListNameBySku($sku){

        $linelist_cn_name = self::find()->alias('p')
            ->select('t.linelist_cn_name')
            ->leftJoin('{{%product_linelist}} t', 'p.product_linelist_id=t.id')
            ->where(['p.sku' => $sku])->asArray()->one();
        if(!empty($linelist_cn_name)){
            return !empty($linelist_cn_name) ? $linelist_cn_name['linelist_cn_name'] : '';
        }else{
            return '';
        }
    }

    /**
     * 获取产品类别
     * @param $sku
     * @return string
     */
    public static function getCategory($sku)
    {
        $res = self::find()->alias('p')
            ->select('c.id,c.category_cn_name')
            ->leftJoin('{{%product_category}} c', 'p.product_category_id=c.id')
            ->where(['p.sku' => $sku])->asArray()->one();
        
        if (empty($res)) {
             return '';
        }
        return $res;
    }

    /**
     * 获取产品开发者
     * @param $sku
     * @return string
     */
    public static function getDeveloper($sku)
    {
        $res = self::find()
            ->select('picking_name,create_user_id')
            ->where(['sku' => $sku])->asArray()->one();

       if (!empty($res)) {
            return $res;
       }
       return '';
    }

}
