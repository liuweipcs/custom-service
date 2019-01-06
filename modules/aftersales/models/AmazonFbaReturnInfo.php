<?php

namespace app\modules\aftersales\models;
use app\modules\accounts\models\Account;

use Yii;

/**
 * This is the model class for table "ueb_amazon_fba_return_info".
 *
 * @property integer $id
 * @property integer $account_id
 * @property integer $old_account_id
 * @property string $platform_order_id
 * @property string $asin
 * @property string $seller_sku
 * @property string $sku
 * @property integer $company_sku_status
 * @property integer $order_type
 * @property string $return_date
 * @property string $fulfillment_channel
 * @property integer $qty
 * @property integer $status
 * @property string $return_reason
 * @property integer $is_available_sale
 * @property integer $pro_status
 * @property integer $return_3
 * @property integer $return_7
 * @property integer $return_15
 * @property integer $return_30
 * @property integer $return_60
 * @property integer $return_90
 * @property integer $sales_3
 * @property integer $sales_7
 * @property integer $sales_15
 * @property integer $sales_30
 * @property integer $sales_60
 * @property integer $sales_90
 * @property string $return_rate_3
 * @property string $return_rate_7
 * @property string $return_rate_15
 * @property string $return_rate_30
 * @property string $return_rate_60
 * @property string $return_rate_90
 * @property string $add_time
 * @property string $modified_time
 */
class AmazonFbaReturnInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_fba_return_info}}';
    }
    
    //是否可售
    public static $isAvailableSaleArr = [
        1 => '可售',
        2 => '不可售',
    ];
    
    //产品退货状态
    public static $productReturnStatus = [
        'Reimbursed' =>'偿还',
        'Repackaged Successfully' => '重新包装成功',
        'Unit returned to inventory' => '回到仓库'
    ];

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'old_account_id', 'company_sku_status', 'order_type', 'qty', 'is_available_sale', 'status', 'return_3', 'return_7', 'return_15', 'return_30', 'return_60', 'return_90', 'sales_3', 'sales_7', 'sales_15', 'sales_30', 'sales_60', 'sales_90','reason_type'], 'integer'],
            [['return_date', 'add_time', 'modified_time'], 'safe'],
            [['return_rate_3', 'return_rate_7', 'return_rate_15', 'return_rate_30', 'return_rate_60', 'return_rate_90'], 'number'],
            [['platform_order_id', 'seller_sku', 'sku', 'fulfillment_channel'], 'string', 'max' => 50],
            [['asin'], 'string', 'max' => 25],
            [['pro_status'], 'string', 'max' => 100],
            [['return_reason','title'], 'string', 'max' => 225],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'old_account_id' => 'Old Account ID',
            'platform_order_id' => 'Platform Order ID',
            'asin' => 'Asin',
            'seller_sku' => 'Seller Sku',
            'sku' => 'Sku',
            'company_sku_status' => 'Company Sku Status',
            'order_type' => 'Order Type',
            'return_date' => 'Return Date',
            'fulfillment_channel' => 'Fulfillment Channel',
            'qty' => 'Qty',
            'status' => 'Status',
            'return_reason' => 'Return Reason',
            'is_available_sale' => 'Is Available Sale',
            'pro_status' => 'Pro Status',
            'return_3' => 'Return 3',
            'return_7' => 'Return 7',
            'return_15' => 'Return 15',
            'return_30' => 'Return 30',
            'return_60' => 'Return 60',
            'return_90' => 'Return 90',
            'sales_3' => 'Sales 3',
            'sales_7' => 'Sales 7',
            'sales_15' => 'Sales 15',
            'sales_30' => 'Sales 30',
            'sales_60' => 'Sales 60',
            'sales_90' => 'Sales 90',
            'return_rate_3' => 'Return Rate 3',
            'return_rate_7' => 'Return Rate 7',
            'return_rate_15' => 'Return Rate 15',
            'return_rate_30' => 'Return Rate 30',
            'return_rate_60' => 'Return Rate 60',
            'return_rate_90' => 'Return Rate 90',
            'title' => 'Title',
            'add_time' => 'Add Time',
            'modified_time' => 'Modified Time',
            'reason_type' => 'Reason Type',
        ];
    }

    /**
     * 获取列表数据
     * @param type $params
     * @return type
     * @author allen <2018-11-07>
     */
    public static function getDataList($params) {
        $pageCur = $params['pageCur'];//当前页
        $pageSize = $params['pageSize'];//页大小
        $accountId = $params['accountId'];//账号ID
        $platformOrderId = $params['platformOrderId'];//平台订单号
        $asin = $params['asin'];//asin、平台sku ,公司sku
        $returnReason = $params['returnReason'];//退款原因
        $companySkuStatus = $params['companySkuStatus'];//公司sku状态
        $isAvailableSale = $params['isAvailableSale'];//退货是否可售
        $proStatus = $params['proStatus'];//退货产品状态
        $refundStartDate = $params['refundStartDate'];//退款开始时间
        $refundEndDate = $params['refundEndDate'];//退款结束时间
        $sales = $params['sales'];//销售查询
        $salesStr = $params['salesStr'];//销售额查询开始数据
        $salesEnd = $params['salesEnd'];//销售额查询结束数据
        $refundRate = $params['refundRate'];//退款率查询
        $refundRateStr = $params['refundRateStr'];//退款率查询开始数据
        $refundRateEnd = $params['refundRateEnd'];//退款率查询结束数据
        $offset = ($pageCur - 1) * $pageSize;


        $query = self::find();
        $query->select('*');
        $query->where(1);
        //账号过滤
        if(!empty($accountId)){
            $query->andWhere(['account_id' => $accountId]);
        }
        
        //平台订单号过滤
        if(!empty($platformOrderId)){
            $query->andWhere(['platform_order_id' => $platformOrderId]);
        }
        
        //关键词过滤 asin or 公司sku or 平台sku ['or', 'id=1', 'id=2'] 
        if(!empty($asin)){
            $query->andWhere(['or',['asin'=>$asin],['seller_sku' => $asin],['sku'=>$asin]]);
        }
        
        //退款原因过滤
        if(!empty($returnReason)){
            $query->andWhere(['return_reason' => $returnReason]);
        }
        
        //公司sku状态筛选
        if(!empty($companySkuStatus)){
            $query->andWhere(['company_sku_status' => $companySkuStatus]);
        }
        
        //退货是否可售
        if(!empty($isAvailableSale)){
            $query->andWhere(['is_available_sale' => $isAvailableSale]);
        }
        
        //退款产品状态过滤
        if(!empty($proStatus)){
            $query->andWhere(['pro_status' => $proStatus]);
        }
        
        //退货开始时间
        if(!empty($refundStartDate) && !empty($refundEndDate)){
            $query->andWhere(['between','return_date',$refundStartDate,$refundEndDate]);
        }
        
        //销售额筛选
        if(!empty($sales) && !empty($salesStr) && !empty($salesEnd)){
            $query->andWhere(['between','sales_'.$sales,$salesStr,$salesEnd]);
        }
        
        //退款率筛选
        if(!empty($refundRate) && !empty($refundRateStr) && !empty($refundRateEnd)){
            $query->andWhere(['between','return_rate_'.$refundRate,$refundRateStr,$refundRateEnd]);
        }
        
        
        $count = $query->count();

        $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['return_date' => SORT_DESC])->asArray()->all();
        //echo $query->createCommand()->getRawSql();die;
        
        if(is_array($data_list) && !empty($data_list)){
            foreach ($data_list as $key => $value){
                $account = Account::findOne($value['account_id']);
                $data_list[$key]['account_id'] = !empty($account) ? $account->account_name : '未设置';
                $data_list[$key]['order_type'] = 'FBA退货';
                $data_list[$key]['asin'] = 'ASIN: '.$value['asin'].'<Br/>平台sku: '.$value['seller_sku'].'<br/>公司sku: '.$value['sku'];
                $data_list[$key]['is_available_sale'] = !empty($value['is_available_sale']) ? self::$isAvailableSaleArr[$value['is_available_sale']] : '未设置';
                $data_list[$key]['pro_status'] = !empty($value['pro_status']) ? self::$productReturnStatus[$value['pro_status']] : "未设置";
                $data_list[$key]['refund_rate'] = '3 天: '.$value['return_rate_3'].'%&nbsp;&nbsp;&nbsp;&nbsp;7 天: '.$value['return_rate_7'].'%<br/>15天: '.$value['return_rate_15'].'%&nbsp;&nbsp;&nbsp;&nbsp;30天: '.$value['return_rate_30'].''
                        . '%<br/>60天: '.$value['return_rate_60'].'%&nbsp;&nbsp;&nbsp;&nbsp;90天: '.$value['return_rate_90'].'%';
                $data_list[$key]['sales'] = '3 天: '.$value['sales_3'].'&nbsp;&nbsp;&nbsp;&nbsp;7 天: '.$value['sales_7'].'<br/>15天: '.$value['sales_15'].'&nbsp;&nbsp;&nbsp;&nbsp;30天: '.$value['sales_30'].''
                        . '<br/>60天: '.$value['sales_60'].'&nbsp;&nbsp;&nbsp;&nbsp;90天: '.$value['sales_90'];
            }
        }
        
        return [
            'count' => $count,
            'data_list' => $data_list,
        ];
    }
    
    /**
     * 获取FBA退货所有的账号ID
     * @return type
     * @author allen <2018-11-08>
     */
    public static function getOldAccountIdList(){
        return self::find()->select('old_account_id')->groupBy('old_account_id')->column();
    }
    
    /**
     * 返回查询字段的数组
     * @param type $filed
     * @return arr
     * @author allen <2018-11-09>
     */
    public static function getInfos($filed){
        $arr = ['0' => '--请选择--'];
        $res = self::find()->select($filed)->groupBy($filed)->column();
        if(!empty($res)){
            foreach ($res as $val) {
                if($val){
                    $arr[$val] = $val;
                }
            }
        }
        return $arr;
    }
    /**
     * 获取退货产品状态
     * @author allen <2018-11-09>
     */
    public static function getReturnProstatusList(){
        $arr = [];
        $proStatus = self::getInfos('pro_status');
        if($proStatus){
            foreach ($proStatus as $val) {
                if($val){
                    $arr[$val] = self::$productReturnStatus[$val];
                }
            }
        }
        return $arr;
    }
    
    /**
     * 获取退货原因
     * @author allen <2018-11-09>
     */
    public static function getReturnReasonList(){
        return self::getInfos('return_reason');
    }
}
