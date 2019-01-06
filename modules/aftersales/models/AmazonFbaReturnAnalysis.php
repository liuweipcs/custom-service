<?php

namespace app\modules\aftersales\models;

use Yii;
use app\modules\accounts\models\Account;
use app\common\VHelper;
/**
 * This is the model class for table "ueb_amazon_fba_return_analysis".
 *
 * @property integer $id
 * @property string $sku
 * @property string $seller_sku
 * @property string $title
 * @property integer $return_7
 * @property integer $return_15
 * @property integer $return_30
 * @property integer $return_60
 * @property integer $return_90
 * @property string $return_rate_7
 * @property string $return_rate_15
 * @property string $return_rate_30
 * @property string $return_rate_60
 * @property string $return_rate_90
 * @property integer $return_trend
 * @property string $sales_7
 * @property string $sales_15
 * @property string $sales_30
 * @property string $sales_60
 * @property string $sales_90
 * @property integer $sales_trend
 * @property integer $customer_7
 * @property integer $customer_15
 * @property integer $customer_30
 * @property integer $customer_60
 * @property integer $customer_90
 * @property integer $description_7
 * @property integer $description_15
 * @property integer $description_30
 * @property integer $description_60
 * @property integer $description_90
 * @property integer $overtime_7
 * @property integer $overtime_15
 * @property integer $overtime_30
 * @property integer $overtime_60
 * @property integer $overtime_90
 * @property integer $quality_7
 * @property integer $quality_15
 * @property integer $quality_30
 * @property integer $quality_60
 * @property integer $quality_90
 * @property string $quality_control
 * @property string $last_quality_control_user
 * @property string $last_quality_control_time
 * @property string $return_date
 * @property string $add_date
 * @property integer $packaging_7
 * @property integer $packaging_15
 * @property integer $packaging_30
 * @property integer $packaging_60
 * @property integer $packaging_90
 * @property integer $shortage_7
 * @property integer $shortage_15
 * @property integer $shortage_30
 * @property integer $shortage_60
 * @property integer $shortage_90
 * @property integer $not_received_7
 * @property integer $not_received_15
 * @property integer $not_received_30
 * @property integer $not_received_60
 * @property integer $not_received_90
 */
class AmazonFbaReturnAnalysis extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%amazon_fba_return_analysis}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['return_7', 'return_15', 'return_30', 'return_60', 'return_90', 'return_trend', 'sales_7', 'sales_15', 'sales_30', 'sales_60', 'sales_90','sales_trend', 'customer_7', 'customer_15', 'customer_30', 'customer_60', 'customer_90', 'description_7', 'description_15', 'description_30', 'description_60', 'description_90', 'overtime_7', 'overtime_15', 'overtime_30', 'overtime_60', 'overtime_90', 'quality_7', 'quality_15', 'quality_30', 'quality_60', 'quality_90', 'packaging_7', 'packaging_15', 'packaging_30', 'packaging_60', 'packaging_90', 'shortage_7', 'shortage_15', 'shortage_30', 'shortage_60', 'shortage_90', 'not_received_7', 'not_received_15', 'not_received_30', 'not_received_60', 'not_received_90'], 'integer'],
            [['return_rate_7', 'return_rate_15', 'return_rate_30', 'return_rate_60', 'return_rate_90'], 'number'],
            [['quality_control'], 'string'],
            [['last_quality_control_time', 'return_date', 'add_date'], 'safe'],
            [['sku', 'seller_sku'], 'string', 'max' => 100],
            [['title','remark'], 'string', 'max' => 255],
            [['last_quality_control_user'], 'string', 'max' => 50],
            [['sku'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sku' => 'Sku',
            'seller_sku' => 'Seller Sku',
            'title' => 'Title',
            'return_7' => 'Return 7',
            'return_15' => 'Return 15',
            'return_30' => 'Return 30',
            'return_60' => 'Return 60',
            'return_90' => 'Return 90',
            'return_rate_7' => 'Return Rate 7',
            'return_rate_15' => 'Return Rate 15',
            'return_rate_30' => 'Return Rate 30',
            'return_rate_60' => 'Return Rate 60',
            'return_rate_90' => 'Return Rate 90',
            'return_trend' => 'Return Trend',
            'sales_7' => 'Sales 7',
            'sales_15' => 'Sales 15',
            'sales_30' => 'Sales 30',
            'sales_60' => 'Sales 60',
            'sales_90' => 'Sales 90',
            'sales_trend' => 'Sales Trend',
            'customer_7' => 'Customer 7',
            'customer_15' => 'Customer 15',
            'customer_30' => 'Customer 30',
            'customer_60' => 'Customer 60',
            'customer_90' => 'Customer 90',
            'description_7' => 'Description 7',
            'description_15' => 'Description 15',
            'description_30' => 'Description 30',
            'description_60' => 'Description 60',
            'description_90' => 'Description 90',
            'overtime_7' => 'Overtime 7',
            'overtime_15' => 'Overtime 15',
            'overtime_30' => 'Overtime 30',
            'overtime_60' => 'Overtime 60',
            'overtime_90' => 'Overtime 90',
            'quality_7' => 'Quality 7',
            'quality_15' => 'Quality 15',
            'quality_30' => 'Quality 30',
            'quality_60' => 'Quality 60',
            'quality_90' => 'Quality 90',
            'quality_control' => 'Quality Control',
            'last_quality_control_user' => 'Last Quality Control User',
            'last_quality_control_time' => 'Last Quality Control Time',
            'return_date' => 'Return Date',
            'add_date' => 'Add Date',
            'packaging_7' => 'Packaging 7',
            'packaging_15' => 'Packaging 15',
            'packaging_30' => 'Packaging 30',
            'packaging_60' => 'Packaging 60',
            'packaging_90' => 'Packaging 90',
            'shortage_7' => 'Shortage 7',
            'shortage_15' => 'Shortage 15',
            'shortage_30' => 'Shortage 30',
            'shortage_60' => 'Shortage 60',
            'shortage_90' => 'Shortage 90',
            'not_received_7' => 'Not Received 7',
            'not_received_15' => 'Not Received 15',
            'not_received_30' => 'Not Received 30',
            'not_received_60' => 'Not Received 60',
            'not_received_90' => 'Not Received 90',
        ];
    }
    
    
     public static function getDataList($params) {
        $pageCur = $params['pageCur'];//当前页
        $pageSize = $params['pageSize'];//页大小
        
        $sku = $params['sku'];//asin、平台sku ,公司sku
        $lastQualityControlUser = $params['lastQualityControlUser'];//最后记录品控的操作人
        $returnTrend = $params['returnTrend'];//退货趋势
        $salesTrend = $params['salesTrend'];//销售趋势
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
        
        //关键词过滤 asin or 公司sku or 平台sku ['or', 'id=1', 'id=2'] 
        if(!empty($sku)){
            $query->andWhere(['or',['seller_sku' => $sku],['sku'=>$sku]]);
        }
        
        //最后记录品控的操作人
        if(!empty($lastQualityControlUser)){
            $query->andWhere(['last_quality_control_user' => $lastQualityControlUser]);
        }
        
        //退货趋势
        if(!empty($returnTrend)){
            $query->andWhere(['return_trend' => $returnTrend]);
        }
        
        //销量趋势
        if(!empty($salesTrend)){
            $query->andWhere(['sales_trend' => $salesTrend]);
        }
        
        //销售筛选
        if(!empty($sales) && !empty($salesStr) && !empty($salesEnd)){
            $query->andWhere(['between','sales_'.$sales,$salesStr,$salesEnd]);
        }
        
        //退货率
        if(!empty($refundRate) && !empty($refundRateStr) && !empty($refundRateEnd)){
            $query->andWhere(['between','return_rate_'.$refundRate,$refundRateStr,$refundRateEnd]);
        }
        
        
        $count = $query->count();

        $data_list = $query->offset($offset)->limit($pageSize)->orderBy(['id' => SORT_DESC])->asArray()->all();
        //echo $query->createCommand()->getRawSql();die;
        if(is_array($data_list) && !empty($data_list)){
            foreach ($data_list as $key => $value){
                $account = Account::findOne($value['account_id']);
                $data_list[$key]['account_id'] = !empty($account) ? $account->account_name : '未设置';
                $data_list[$key]['order_type'] = 'FBA退货';
                $data_list[$key]['sku'] = '平台sku: '.$value['seller_sku'].'<br/>公司sku: '.$value['sku'].'<br/>中文名:'.$value['title'];
                $data_list[$key]['pro_status'] = '<span style="cursor:pointer;color:green;" data="'.$value['id'].'" class="sku-status cla_'.$value['id'].'" data-toggle="modal" data-target="#skuModal">查看状态</span>';
                $data_list[$key]['is_available_sale'] = !empty($value['is_available_sale']) ? self::$isAvailableSaleArr[$value['is_available_sale']] : '未设置';
                $data_list[$key]['refund_rate'] = '7 天: '.$value['return_rate_7'].'% &nbsp;&nbsp;&nbsp;15天: '.$value['return_rate_15'].'%<br/>30天: '.$value['return_rate_30'].'%&nbsp;&nbsp;&nbsp;&nbsp;60天: '.$value['return_rate_60'].'%<br/>90天: '.$value['return_rate_90'].'%';
                //1下降 2上升 3 持平
                switch ($value['return_trend']) {
                    case 1:
                        $returnTrend = '<b style="color:green">下降</b>';
                        break;
                    case 2:
                        $returnTrend = '<b style="color:red">上升</b>';
                    default:
                        $returnTrend = '<b style="color:#696969">持平</b>';
                        break;
                }
                switch ($value['sales_trend']) {
                    case 1:
                        $salesTrend = '<b style="color:green">下降</b>';
                        break;
                    case 2:
                        $salesTrend = '<b style="color:red">上升</b>';
                    default:
                        $salesTrend = '<b style="color:#696969">持平</b>';
                        break;
                }
                $data_list[$key]['return_trend'] = $returnTrend;
                $data_list[$key]['sales'] = $value['sales_7'].'/'.$value['sales_15'].'/'.$value['sales_30'].'<br/>'.$value['sales_60'].'/'.$value['sales_90'];
                $data_list[$key]['sales_trend'] = $salesTrend;
                $data_list[$key]['customer'] = $value['customer_7'].'/'.$value['customer_15'].'/'.$value['customer_30'].'<br/>'.$value['customer_60'].'/'.$value['customer_90'];
                $data_list[$key]['description'] = $value['description_7'].'/'.$value['description_15'].'/'.$value['description_30'].'<br/>'.$value['description_60'].'/'.$value['description_90'];
                $data_list[$key]['overtime'] = $value['overtime_7'].'/'.$value['overtime_15'].'/'.$value['overtime_30'].'<br/>'.$value['overtime_60'].'/'.$value['overtime_90'];
                $data_list[$key]['quality'] = $value['quality_7'].'/'.$value['quality_15'].'/'.$value['quality_30'].'<br/>'.$value['quality_60'].'/'.$value['quality_90'];
                $data_list[$key]['packaging'] = $value['packaging_7'].'/'.$value['packaging_15'].'/'.$value['packaging_30'].'<br/>'.$value['packaging_60'].'/'.$value['packaging_90'];
                $data_list[$key]['shortage'] = $value['shortage_7'].'/'.$value['shortage_15'].'/'.$value['shortage_30'].'<br/>'.$value['shortage_60'].'/'.$value['shortage_90'];
                $data_list[$key]['not_received'] = $value['not_received_7'].'/'.$value['not_received_15'].'/'.$value['not_received_30'].'<br/>'.$value['not_received_60'].'/'.$value['not_received_90'];
                if(empty($value['quality_control'])){
                    $data_list[$key]['quality_control'] = '<span style="cursor:pointer;" data="'.$value['id'].'" class="not-set" data-toggle="modal" data-target="#myModal">未设置</span>';
                }else{
                    $qualityControl = "";
                    $qualityControl = '<b style="cursor:pointer;color:green;" data="'.$value['id'].'" class="add" data-toggle="modal" data-target="#myModal">新增</b>';
                    $qualityControl .= '&nbsp;&nbsp;&nbsp;&nbsp;<b style="cursor:pointer;color:#008B8B;" data="'.$value['id'].'" class="view" data-toggle="modal" data-target="#historyModal">查看品控历史</b>';
                    $arr = json_decode($value['quality_control'],'true');
                    $arrs = VHelper::array_sort($arr, 'time');//按时间倒序
                    foreach ($arrs as $k => $v) {
                        if($k == 0){
                            if (mb_strlen($v['text']) > 25) {
                                $str = mb_substr($v['text'], 0, 25) . '...';
                                $qualityControl .= '<br/><span style="cursor: pointer;color:#000080;"  class="remark'.$value['id'].'" onclick="demol(this)"> '.addslashes($str).'</span>'
                                        . '<input type="hidden" name="remark"  class="remarkl" value="'.addslashes($v['text']).'"/><br/>'.$v['user'].'&nbsp;&nbsp;'.$v['time'];
                            }else{
                                $qualityControl .= '<br/><span style="cursor: pointer;color:#000080;">'.$v['text'].'</span><br/>'.$v['user'].'&nbsp;&nbsp;'.$v['time'];
                            }
                        }
                        continue;
                    }
                     $data_list[$key]['quality_control'] = $qualityControl;
                }
            }
        }
        
        return [
            'count' => $count,
            'data_list' => $data_list,
        ];
    }
    
    /**
     * 保存品控问题
     * @param type $id
     * @param type $text
     * @return boolean
     * @author allen <2018-11-20>
     */
    public static function saveQualityControl($id,$text){
        $model = self::findOne($id);
            $newData = [
                'user' => Yii::$app->user->identity->login_name,
                'time' => date('Y-m-d H:i:s'),
                'text' => $text
            ];
        //如果是第一次添加 老数据为空
        if(empty($model->quality_control)){
            $oldData = [];
        }else{
            //已有数据  追加
            $oldData = json_decode($model->quality_control,TRUE);
        }
        
        $oldData[] = $newData;
        $jsonData = json_encode($oldData);
        $model->quality_control = $jsonData;
        $model->last_quality_control_user = Yii::$app->user->identity->login_name;
        $model->last_quality_control_time = date('Y-m-d H:i:s');
        if(!$model->save()){
            return FALSE;
        }else{
            return TRUE;
        }
    }
    
    /**
     * 查看历史品控操作
     * @param type $id
     * @return type
     * @author allen <2018-11-21>
     */
    public static function viwHistory($id){
        $arr = [];
        $model = self::findOne($id);
        if($model){
           $res = json_decode($model->quality_control,TRUE);
           $res = VHelper::array_sort($res, 'time');//按时间倒序
           if(is_array($res) && !empty($res)){
               $arr = $res;
           }
        }
        return $res;
    }
    
    /**
     * 获取公司sku
     * @param type $id
     * @return type
     * @author allen <2018-11-21>
     */
    public static function getSku($id){
        $model = self::findOne($id);
        $sku = !empty($model) ? $model->sku : "";
        return $sku;
    }
}
