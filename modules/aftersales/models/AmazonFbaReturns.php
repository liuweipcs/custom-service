<?php

namespace app\modules\aftersales\models;

use Yii;

/**
 * This is the model class for table "ueb_amazon_fba_returns".
 *
 * @property string $id
 * @property string $platform_order_id
 * @property integer $account_id
 * @property integer $old_account_id
 * @property string $seller_sku
 * @property string $sku
 * @property string $asin
 * @property string $return_date
 * @property integer $qty
 * @property string $fulfillment_channel
 * @property string $status
 * @property string $product_name
 * @property string $fulfillment_center_id
 * @property string $detailed_disposition
 * @property string $reason
 * @property resource $license_plate_number
 * @property string $customer_comments
 * @property string $created_at
 * @property string $updated_at
 * @property integer $reason_type
 * @property integer $is_available_sale
 * @property string $pro_status
 * @property string $title
 */
class AmazonFbaReturns extends \yii\db\ActiveRecord {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%amazon_fba_returns}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['platform_order_id', 'account_id'], 'required'],
            [['account_id', 'old_account_id', 'qty', 'reason_type', 'is_available_sale'], 'integer'],
            [['return_date', 'created_at', 'updated_at'], 'safe'],
            [['product_name', 'customer_comments'], 'string'],
            [['platform_order_id', 'fulfillment_center_id', 'detailed_disposition', 'license_plate_number'], 'string', 'max' => 50],
            [['seller_sku'], 'string', 'max' => 80],
            [['sku', 'pro_status', 'title'], 'string', 'max' => 255],
            [['asin'], 'string', 'max' => 15],
            [['fulfillment_channel'], 'string', 'max' => 5],
            [['status', 'reason'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'platform_order_id' => 'Platform Order ID',
            'account_id' => 'Account ID',
            'old_account_id' => 'Old Account ID',
            'seller_sku' => 'Seller Sku',
            'sku' => 'Sku',
            'asin' => 'Asin',
            'return_date' => 'Return Date',
            'qty' => 'Qty',
            'fulfillment_channel' => 'Fulfillment Channel',
            'status' => 'Status',
            'product_name' => 'Product Name',
            'fulfillment_center_id' => 'Fulfillment Center ID',
            'detailed_disposition' => 'Detailed Disposition',
            'reason' => 'Reason',
            'license_plate_number' => 'License Plate Number',
            'customer_comments' => 'Customer Comments',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'reason_type' => 'Reason Type',
            'is_available_sale' => 'Is Available Sale',
            'pro_status' => 'Pro Status',
            'title' => 'Title',
        ];
    }

    /**
     * 查询账号都有sku最近3,7,15,30,60,90天退款数量
     * @param type $oldAccountId  erp账号ID
     * @param type $sku 公司sku
     * @author allen <2018-11-17>
     */
    public static function getFbaReturnQtys($sku,$oldAccountId = null) {
        $returnQty3 = $returnQty7 = $returnQty15 = $returnQty30 = $returnQty60 = $returnQty90 = 0; //初始化
        $query = self::find();
        $query ->select('old_account_id,sku,qty,return_date');
        $query ->where(['sku' => $sku]);
        if($oldAccountId){
            $query -> andWhere(['old_account_id' => $oldAccountId]);
        }
        $query->andWhere('DATE_SUB(CURDATE(), INTERVAL 90 DAY) <= date(return_date)');
        $query->orderBy('return_date');
        $res = $query ->asArray() ->all();
        if (!empty($res)) {
            foreach ($res as $value) {
                $returnTime = strtotime(date('Y-m-d', strtotime($value['return_date']))); //退款时间
                $nowTime = strtotime(date('Y-m-d')); //当前时间
                $diff = $nowTime - $returnTime; //相差时间差
                $diffDay = $diff / 86400; //相差天数
                //只统计最近90天销量
                if ($diffDay <= 90) {
                    if ($diffDay > 60) {
                        $returnQty90 += $value['qty'];
                    } elseif ($diffDay > 30) {
                        $returnQty60 += $value['qty'];
                        $returnQty90 += $value['qty'];
                    } elseif ($diffDay > 15) {
                        $returnQty30 += $value['qty'];
                        $returnQty60 += $value['qty'];
                        $returnQty90 += $value['qty'];
                    } elseif ($diffDay > 7) {
                        $returnQty15 += $value['qty'];
                        $salesQty30 += $value['qty'];
                        $returnQty60 += $value['qty'];
                        $returnQty90 += $value['qty'];
                    } elseif ($diffDay > 3) {
                        $returnQty7 += $value['qty'];
                        $returnQty15 += $value['qty'];
                        $salesQty30 += $value['qty'];
                        $returnQty60 += $value['qty'];
                        $returnQty90 += $value['qty'];
                    } else {
                        $returnQty3 += $value['qty'];
                        $returnQty7 += $value['qty'];
                        $returnQty15 += $value['qty'];
                        $returnQty30 += $value['qty'];
                        $returnQty60 += $value['qty'];
                        $returnQty90 += $value['qty'];
                    }
                }

                //echo '退货日期: ' . date('Y-m-d', strtotime($value['return_date'])) . '距离当前' . $diffDay . '天<br/>';
                //echo '3天销量: ' . $returnQty3 . '<br/>7天销量: ' . $returnQty7 . '<br/>15天销量: ' . $returnQty15 . '<br/>30天销量: ' . $returnQty30 . '<br/>60天销量: ' . $returnQty60 . '<br/>90天销量: ' . $returnQty90;
            }
        }
        
        return [
            'returnQty3' => $returnQty3,
            'returnQty7' => $returnQty7,
            'returnQty15' => $returnQty15,
            'returnQty30' => $returnQty30,
            'returnQty60' => $returnQty60,
            'returnQty90' => $returnQty90
        ];
    }
    
    
    public static function getReturnReasonTypeInfo($sku){
        //原因类型:1客户原因;2:描述不符;3.延迟派送 4:产品质量问题; 5:包装问题; 6:数量短缺; 7:未收到
        $customerReason7 = $customerReason15 = $customerReason30 = $customerReason60 = $customerReason90 = 0;
        $descriptionReason7 = $descriptionReason15 = $descriptionReason30 = $descriptionReason60 = $descriptionReason90 = 0;
        $overtimeReason7 = $overtimeReason15 = $overtimeReason30 = $overtimeReason60 = $overtimeReason90 = 0;
        $qualityReason7 = $qualityReason15 = $qualityReason30 = $qualityReason60 = $qualityReason90 = 0;
        $packagingReason7 = $packagingReason15 = $packagingReason30 = $packagingReason60 = $packagingReason90 = 0;
        $shortageReason7 = $shortageReason15 = $shortageReason30 = $shortageReason60 = $shortageReason90 = 0;
        $notReceivedReason7= $notReceivedReason15 = $notReceivedReason30 = $notReceivedReason60 = $notReceivedReason90 = 0;
        $arr = [];
        $query = self::find();
        $query ->select('old_account_id,sku,qty,return_date,reason_type');
        $query ->where(['sku' => $sku]);
        $query->andWhere('DATE_SUB(CURDATE(), INTERVAL 90 DAY) <= date(return_date)');
        $query->orderBy('return_date');
        $res = $query ->asArray() ->all();
        //echo $query->createCommand()->getRawSql();
        if(!empty($res)){
            foreach ($res as $value) {
                if($value['reason_type']){
                    $arr[$value['reason_type']][] = $value;
                }
            }
        }
        
        //循环退款原因类型数据
        if(!empty($arr)){
            $nowTime = strtotime(date('Y-m-d')); //当前时间
            foreach ($arr as $key => $value) {
                switch ($key) {
                    case 1://客户原因
                        foreach ($value as $val) {
                            $returnTime = strtotime(date('Y-m-d', strtotime($val['return_date']))); //退款时间
                            $diff = $nowTime - $returnTime; //相差时间差
                            $diffDay = $diff / 86400; //相差天数
                            //只统计最近90天销量
                            if ($diffDay <= 90) {
                                if ($diffDay > 60) {
                                    $customerReason90 += $val['qty'];
                                } elseif ($diffDay > 30) {
                                    $customerReason60 += $val['qty'];
                                    $customerReason90 += $val['qty'];
                                } elseif ($diffDay > 15) {
                                    $customerReason30 += $val['qty'];
                                    $customerReason60 += $val['qty'];
                                    $customerReason90 += $val['qty'];
                                } elseif ($diffDay > 7) {
                                    $customerReason15 += $val['qty'];
                                    $customerReason30 += $val['qty'];
                                    $customerReason60 += $val['qty'];
                                    $customerReason90 += $val['qty'];
                                } elseif ($diffDay > 3) {
                                    $customerReason7 += $val['qty'];
                                    $customerReason15 += $val['qty'];
                                    $customerReason30 += $val['qty'];
                                    $customerReason60 += $val['qty'];
                                    $customerReason90 += $val['qty'];
                                }
                            }
                        }
                        break;
                    case 2://描述不符
                        foreach ($value as $val) {
                            $returnTime = strtotime(date('Y-m-d', strtotime($val['return_date']))); //退款时间
                            $diff = $nowTime - $returnTime; //相差时间差
                            $diffDay = $diff / 86400; //相差天数
                            //只统计最近90天销量
                            if ($diffDay <= 90) {
                                if ($diffDay > 60) {
                                    $descriptionReason90 += $val['qty'];
                                } elseif ($diffDay > 30) {
                                    $descriptionReason60 += $val['qty'];
                                    $descriptionReason90 += $val['qty'];
                                } elseif ($diffDay > 15) {
                                    $descriptionReason30 += $val['qty'];
                                    $descriptionReason60 += $val['qty'];
                                    $descriptionReason90 += $val['qty'];
                                } elseif ($diffDay > 7) {
                                    $descriptionReason15 += $val['qty'];
                                    $descriptionReason30 += $val['qty'];
                                    $descriptionReason60 += $val['qty'];
                                    $descriptionReason90 += $val['qty'];
                                } elseif ($diffDay > 3) {
                                    $descriptionReason7 += $val['qty'];
                                    $descriptionReason15 += $val['qty'];
                                    $descriptionReason30 += $val['qty'];
                                    $descriptionReason60 += $val['qty'];
                                    $descriptionReason90 += $val['qty'];
                                }
                            }
                        }
                        break;
                    case 3://延迟派送
                        foreach ($value as $val) {
                            $returnTime = strtotime(date('Y-m-d', strtotime($val['return_date']))); //退款时间
                            $diff = $nowTime - $returnTime; //相差时间差
                            $diffDay = $diff / 86400; //相差天数
                            //只统计最近90天销量
                            if ($diffDay <= 90) {
                                if ($diffDay > 60) {
                                    $overtimeReason90 += $val['qty'];
                                } elseif ($diffDay > 30) {
                                    $overtimeReason60 += $val['qty'];
                                    $overtimeReason90 += $val['qty'];
                                } elseif ($diffDay > 15) {
                                    $overtimeReason30 += $val['qty'];
                                    $overtimeReason60 += $val['qty'];
                                    $overtimeReason90 += $val['qty'];
                                } elseif ($diffDay > 7) {
                                    $overtimeReason15 += $val['qty'];
                                    $overtimeReason30 += $val['qty'];
                                    $overtimeReason60 += $val['qty'];
                                    $overtimeReason90 += $val['qty'];
                                } elseif ($diffDay > 3) {
                                    $overtimeReason7 += $val['qty'];
                                    $overtimeReason15 += $val['qty'];
                                    $overtimeReason30 += $val['qty'];
                                    $overtimeReason60 += $val['qty'];
                                    $overtimeReason90 += $val['qty'];
                                }
                            }
                        }
                        break;
                    case 4://产品质量问题
                        foreach ($value as $val) {
                            $returnTime = strtotime(date('Y-m-d', strtotime($val['return_date']))); //退款时间
                            $diff = $nowTime - $returnTime; //相差时间差
                            $diffDay = $diff / 86400; //相差天数
                            //只统计最近90天销量
                            if ($diffDay <= 90) {
                                if ($diffDay > 60) {
                                    $qualityReason90 += $val['qty'];
                                } elseif ($diffDay > 30) {
                                    $qualityReason60 += $val['qty'];
                                    $qualityReason90 += $val['qty'];
                                } elseif ($diffDay > 15) {
                                    $qualityReason30 += $val['qty'];
                                    $qualityReason60 += $val['qty'];
                                    $qualityReason90 += $val['qty'];
                                } elseif ($diffDay > 7) {
                                    $qualityReason15 += $val['qty'];
                                    $qualityReason30 += $val['qty'];
                                    $qualityReason60 += $val['qty'];
                                    $qualityReason90 += $val['qty'];
                                } elseif ($diffDay > 3) {
                                    $qualityReason7 += $val['qty'];
                                    $qualityReason15 += $val['qty'];
                                    $qualityReason30 += $val['qty'];
                                    $qualityReason60 += $val['qty'];
                                    $qualityReason90 += $val['qty'];
                                }
                            }
                        }
                        break;
                    case 5://包装问题
                        foreach ($value as $val) {
                            $returnTime = strtotime(date('Y-m-d', strtotime($val['return_date']))); //退款时间
                            $diff = $nowTime - $returnTime; //相差时间差
                            $diffDay = $diff / 86400; //相差天数
                            //只统计最近90天销量
                            if ($diffDay <= 90) {
                                if ($diffDay > 60) {
                                    $packagingReason90 += $val['qty'];
                                } elseif ($diffDay > 30) {
                                    $packagingReason60 += $val['qty'];
                                    $packagingReason90 += $val['qty'];
                                } elseif ($diffDay > 15) {
                                    $packagingReason30 += $val['qty'];
                                    $packagingReason60 += $val['qty'];
                                    $packagingReason90 += $val['qty'];
                                } elseif ($diffDay > 7) {
                                    $packagingReason15 += $val['qty'];
                                    $packagingReason30 += $val['qty'];
                                    $packagingReason60 += $val['qty'];
                                    $packagingReason90 += $val['qty'];
                                } elseif ($diffDay > 3) {
                                    $packagingReason7 += $val['qty'];
                                    $packagingReason15 += $val['qty'];
                                    $packagingReason30 += $val['qty'];
                                    $packagingReason60 += $val['qty'];
                                    $packagingReason90 += $val['qty'];
                                }
                            }
                        }
                        break;
                    case 6://数量短缺
                        foreach ($value as $val) {
                            $returnTime = strtotime(date('Y-m-d', strtotime($val['return_date']))); //退款时间
                            $diff = $nowTime - $returnTime; //相差时间差
                            $diffDay = $diff / 86400; //相差天数
                            //只统计最近90天销量
                            if ($diffDay <= 90) {
                                if ($diffDay > 60) {
                                    $shortageReason90 += $val['qty'];
                                } elseif ($diffDay > 30) {
                                    $shortageReason60 += $val['qty'];
                                    $shortageReason90 += $val['qty'];
                                } elseif ($diffDay > 15) {
                                    $shortageReason30 += $val['qty'];
                                    $shortageReason60 += $val['qty'];
                                    $shortageReason90 += $val['qty'];
                                } elseif ($diffDay > 7) {
                                    $shortageReason15 += $val['qty'];
                                    $shortageReason30 += $val['qty'];
                                    $shortageReason60 += $val['qty'];
                                    $shortageReason90 += $val['qty'];
                                } elseif ($diffDay > 3) {
                                    $shortageReason7 += $val['qty'];
                                    $shortageReason15 += $val['qty'];
                                    $shortageReason30 += $val['qty'];
                                    $shortageReason60 += $val['qty'];
                                    $shortageReason90 += $val['qty'];
                                }
                            }
                        }
                        break;
                    case 7://未收到
                        foreach ($value as $val) {
                            $returnTime = strtotime(date('Y-m-d', strtotime($val['return_date']))); //退款时间
                            $diff = $nowTime - $returnTime; //相差时间差
                            $diffDay = $diff / 86400; //相差天数
                            //只统计最近90天销量
                            if ($diffDay <= 90) {
                                if ($diffDay > 60) {
                                    $notReceivedReason90 += $val['qty'];
                                } elseif ($diffDay > 30) {
                                    $notReceivedReason60 += $val['qty'];
                                    $notReceivedReason90 += $val['qty'];
                                } elseif ($diffDay > 15) {
                                    $notReceivedReason30 += $val['qty'];
                                    $notReceivedReason60 += $val['qty'];
                                    $notReceivedReason90 += $val['qty'];
                                } elseif ($diffDay > 7) {
                                    $notReceivedReason15 += $val['qty'];
                                    $notReceivedReason30 += $val['qty'];
                                    $notReceivedReason60 += $val['qty'];
                                    $notReceivedReason90 += $val['qty'];
                                } elseif ($diffDay > 3) {
                                    $notReceivedReason7 += $val['qty'];
                                    $notReceivedReason15 += $val['qty'];
                                    $notReceivedReason30 += $val['qty'];
                                    $notReceivedReason60 += $val['qty'];
                                    $notReceivedReason90 += $val['qty'];
                                }
                            }
                        }
                        break;
                }
            }
        }
        
        return [
           'customerReason7' => $customerReason7, 'customerReason15' => $customerReason15,'customerReason30' => $customerReason30,'customerReason60' => $customerReason60,'customerReason90' => $customerReason90,
           'descriptionReason7' => $descriptionReason7,'descriptionReason15' => $descriptionReason15,'descriptionReason30' => $descriptionReason30,'descriptionReason60' => $descriptionReason60, 'descriptionReason90' => $descriptionReason90,
           'overtimeReason7' => $overtimeReason7,'overtimeReason15' => $overtimeReason15,'overtimeReason30' => $overtimeReason30,'overtimeReason60' => $overtimeReason60,'overtimeReason90' => $overtimeReason90,
           'qualityReason7' => $qualityReason7,'qualityReason15' => $qualityReason15,'qualityReason30' => $qualityReason30,'qualityReason60' => $qualityReason60,'qualityReason90' => $qualityReason90,
           'packagingReason7' => $packagingReason7,'packagingReason15' => $packagingReason15,'packagingReason30' => $packagingReason30,'packagingReason60' => $packagingReason60,'packagingReason90' => $packagingReason90,
           'shortageReason7' => $shortageReason7, 'shortageReason15' => $shortageReason15,'shortageReason30' => $shortageReason30,'shortageReason60' => $shortageReason60,'shortageReason90' => $shortageReason90,
           'notReceivedReason7' => $notReceivedReason7,'notReceivedReason15' => $notReceivedReason15,'notReceivedReason30' => $notReceivedReason30,'notReceivedReason60' => $notReceivedReason60,'notReceivedReason90' => $notReceivedReason90
        ];
    }

}
